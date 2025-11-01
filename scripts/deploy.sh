#!/bin/bash
################################################################################
# Dark Star Portal - Automated Deployment Script
# For Ubuntu 24.04 LTS
################################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
CONFIG_DIR="$REPO_ROOT/config"

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════╗"
echo "║   Dark Star Portal - Automated Deployment         ║"
echo "║   Ubuntu 24.04 LTS                                ║"
echo "╚════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Function to print status messages
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Load environment configuration
if [ -f "$CONFIG_DIR/deployment.env" ]; then
    log_info "Loading configuration from deployment.env..."
    source "$CONFIG_DIR/deployment.env"
else
    log_error "Configuration file not found: $CONFIG_DIR/deployment.env"
    log_info "Please copy deployment.env.example to deployment.env and configure it"
    exit 1
fi

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    log_error "This script must be run as root"
    exit 1
fi

# Check Ubuntu version
if ! grep -q "24.04" /etc/os-release; then
    log_warn "This script is designed for Ubuntu 24.04 LTS"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

################################################################################
# Step 1: System Updates
################################################################################
log_info "Step 1: Updating system packages..."
apt update
DEBIAN_FRONTEND=noninteractive apt upgrade -y
log_success "System updated"

################################################################################
# Step 2: Install Docker
################################################################################
if ! command -v docker &> /dev/null; then
    log_info "Step 2: Installing Docker..."

    # Install dependencies
    apt install -y ca-certificates curl gnupg lsb-release

    # Add Docker GPG key
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg

    # Add Docker repository
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
      tee /etc/apt/sources.list.d/docker.list > /dev/null

    # Install Docker
    apt update
    apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

    # Start Docker
    systemctl enable docker
    systemctl start docker

    log_success "Docker installed"
else
    log_success "Docker already installed"
fi

################################################################################
# Step 3: Install Apache
################################################################################
if ! command -v apache2 &> /dev/null; then
    log_info "Step 3: Installing Apache..."
    apt install -y apache2

    # Enable required modules
    a2enmod proxy proxy_http proxy_wstunnel rewrite ssl headers

    systemctl enable apache2
    log_success "Apache installed and modules enabled"
else
    log_success "Apache already installed"
    log_info "Enabling required Apache modules..."
    a2enmod proxy proxy_http proxy_wstunnel rewrite ssl headers
fi

################################################################################
# Step 4: Setup UFW Firewall (Optional)
################################################################################
if [ "$UFW_ENABLED" = "true" ]; then
    log_info "Step 4: Configuring UFW firewall..."
    apt install -y ufw

    # Allow SSH, HTTP, HTTPS
    ufw allow 22/tcp comment "SSH"
    ufw allow 80/tcp comment "HTTP"
    ufw allow 443/tcp comment "HTTPS"

    # Enable UFW
    ufw --force enable
    log_success "UFW firewall configured and enabled"
else
    log_info "Step 4: Skipping UFW firewall (disabled in config)"
fi

################################################################################
# Step 5: Install Fail2Ban (Optional)
################################################################################
if [ "$FAIL2BAN_ENABLED" = "true" ]; then
    if ! command -v fail2ban-client &> /dev/null; then
        log_info "Step 5: Installing Fail2Ban..."
        apt install -y fail2ban

        # Copy configuration
        if [ -f "$CONFIG_DIR/fail2ban/jail.local" ]; then
            cp "$CONFIG_DIR/fail2ban/jail.local" /etc/fail2ban/jail.local
            log_info "Fail2Ban configuration copied"
        fi

        systemctl enable fail2ban
        systemctl restart fail2ban
        log_success "Fail2Ban installed and configured"
    else
        log_success "Fail2Ban already installed"
    fi
else
    log_info "Step 5: Skipping Fail2Ban (disabled in config)"
fi

################################################################################
# Step 6: Deploy Web Files
################################################################################
log_info "Step 6: Deploying web files..."

# Create web directory
mkdir -p /var/www/darkstar-portal

# Copy web files
cp -r "$REPO_ROOT/www/"* /var/www/darkstar-portal/

# Set ownership and permissions
chown -R www-data:www-data /var/www/darkstar-portal
find /var/www/darkstar-portal -type d -exec chmod 755 {} \;
find /var/www/darkstar-portal -type f -exec chmod 644 {} \;

log_success "Web files deployed to /var/www/darkstar-portal"

################################################################################
# Step 7: Configure Apache Virtual Host
################################################################################
log_info "Step 7: Configuring Apache virtual host..."

# Check if domain is configured
if [ -z "$DOMAIN" ]; then
    log_error "DOMAIN not set in deployment.env"
    exit 1
fi

# Copy Apache config and replace domain
sed "s/DOMAIN_PLACEHOLDER/$DOMAIN/g" "$CONFIG_DIR/apache/terminal-portal.conf" > /etc/apache2/sites-available/terminal-portal.conf

# Disable default site
a2dissite 000-default || true

# Enable our site
a2ensite terminal-portal

# Test Apache configuration
if apache2ctl configtest; then
    systemctl restart apache2
    log_success "Apache virtual host configured for $DOMAIN"
else
    log_error "Apache configuration test failed"
    exit 1
fi

################################################################################
# Step 8: Setup Network Control API
################################################################################
log_info "Step 8: Configuring network control API..."

# Generate password hash if password is set
if [ -n "$NETWORK_CONTROL_PASSWORD" ]; then
    PASSWORD_HASH=$(php -r "echo password_hash('$NETWORK_CONTROL_PASSWORD', PASSWORD_ARGON2ID);")

    # Update API with password hash
    sed -i "s|PLACEHOLDER_PASSWORD_HASH|$PASSWORD_HASH|g" /var/www/darkstar-portal/api/network-control.php

    log_success "Network control API password configured"
fi

# Setup sudoers for www-data
if [ -f "$CONFIG_DIR/sudoers.d/www-data-docker" ]; then
    cp "$CONFIG_DIR/sudoers.d/www-data-docker" /etc/sudoers.d/www-data-docker
    chmod 0440 /etc/sudoers.d/www-data-docker

    # Validate sudoers file
    if visudo -c -f /etc/sudoers.d/www-data-docker; then
        log_success "Sudoers configuration installed"
    else
        log_error "Sudoers configuration is invalid"
        rm /etc/sudoers.d/www-data-docker
        exit 1
    fi
fi

################################################################################
# Step 9: Setup Docker Networks
################################################################################
log_info "Step 9: Creating Docker networks..."

# Create isolated network
if ! docker network inspect darkstar-linux-portal_isolated &>/dev/null; then
    docker network create darkstar-linux-portal_isolated \
        --driver bridge \
        --opt com.docker.network.bridge.name=isolated0
    log_success "Created isolated network"
else
    log_info "Isolated network already exists"
fi

# Create internet network
if ! docker network inspect darkstar-linux-portal_internet &>/dev/null; then
    docker network create darkstar-linux-portal_internet \
        --driver bridge \
        --opt com.docker.network.bridge.name=internet0
    log_success "Created internet network"
else
    log_info "Internet network already exists"
fi

################################################################################
# Step 10: Setup Network Isolation Rules
################################################################################
log_info "Step 10: Configuring network isolation..."

# Add iptables rules for network isolation
if [ -f "$CONFIG_DIR/ufw/after.rules" ]; then
    cp "$CONFIG_DIR/ufw/after.rules" /etc/ufw/after.rules
    ufw reload
    log_success "Network isolation rules configured"
fi

################################################################################
# Step 11: Deploy Docker Containers
################################################################################
log_info "Step 11: Deploying Docker containers..."

cd "$REPO_ROOT"

# Build and start containers
docker compose up -d

# Wait for containers to start
sleep 10

# Check container status
if docker ps | grep -q "darkstar-webtop"; then
    log_success "Webtop container is running"
else
    log_warn "Webtop container may not be running properly"
fi

if docker ps | grep -q "landing-terminal"; then
    log_success "Terminal container is running"
else
    log_warn "Terminal container may not be running properly"
fi

################################################################################
# Step 12: Install Monitoring Script (Optional)
################################################################################
if [ "$MONITORING_ENABLED" = "true" ]; then
    log_info "Step 12: Installing monitoring script..."

    if [ -f "$REPO_ROOT/scripts/darkstar-monitor.sh" ]; then
        cp "$REPO_ROOT/scripts/darkstar-monitor.sh" /usr/local/bin/darkstar-monitor.sh
        chmod +x /usr/local/bin/darkstar-monitor.sh

        # Add cron job for monitoring (every 6 hours)
        CRON_JOB="0 */6 * * * /usr/local/bin/darkstar-monitor.sh >> /var/log/darkstar-monitor-cron.log 2>&1"
        (crontab -l 2>/dev/null | grep -v darkstar-monitor; echo "$CRON_JOB") | crontab -

        log_success "Monitoring script installed and scheduled"
    fi
else
    log_info "Step 12: Skipping monitoring script (disabled in config)"
fi

################################################################################
# Final Status
################################################################################
echo ""
echo -e "${GREEN}"
echo "╔════════════════════════════════════════════════════╗"
echo "║   Deployment Complete!                             ║"
echo "╚════════════════════════════════════════════════════╝"
echo -e "${NC}"

log_info "Your Dark Star Portal is deployed!"
echo ""
log_info "Next Steps:"
echo "  1. Point your domain ($DOMAIN) DNS to this server's IP"
if [ "$SSL_ENABLED" = "true" ]; then
    echo "  2. Install SSL certificate:"
    echo "     - Disable Cloudflare proxy (orange to grey cloud)"
    echo "     - Run: certbot --apache -d $DOMAIN"
    echo "     - Re-enable Cloudflare proxy"
    echo "     - Set Cloudflare SSL/TLS to 'Full'"
else
    echo "  2. SSL not configured. Access via HTTP only for now"
fi
echo "  3. Access your portal at: http://$DOMAIN (or https:// after SSL)"
echo ""
log_info "Container Status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""
log_info "Important Credentials:"
echo "  - Network Control Password: $NETWORK_CONTROL_PASSWORD"
echo "  - Webtop Username: darkstar"
echo ""
log_info "Logs:"
echo "  - Apache: /var/log/apache2/terminal-portal-*.log"
echo "  - Monitoring: /var/log/darkstar-monitor.log"
echo "  - Docker: docker logs <container-name>"
echo ""
log_success "Deployment complete! 🚀"
