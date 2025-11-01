#!/bin/bash
################################################################################
# Dark Star Portal - SSL Setup Script
# Run this after initial deployment to enable HTTPS
################################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
CONFIG_DIR="$REPO_ROOT/config"

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════╗"
echo "║   Dark Star Portal - SSL Setup                    ║"
echo "║   Let's Encrypt + Cloudflare                      ║"
echo "╚════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} This script must be run as root"
    exit 1
fi

# Load configuration
if [ -f "$CONFIG_DIR/deployment.env" ]; then
    source "$CONFIG_DIR/deployment.env"
else
    echo -e "${RED}[ERROR]${NC} Configuration file not found: $CONFIG_DIR/deployment.env"
    exit 1
fi

if [ -z "$DOMAIN" ]; then
    echo -e "${RED}[ERROR]${NC} DOMAIN not set in deployment.env"
    exit 1
fi

echo -e "${BLUE}[INFO]${NC} Domain: $DOMAIN"
echo ""

# Check if certbot is installed
if ! command -v certbot &> /dev/null; then
    echo -e "${BLUE}[INFO]${NC} Installing certbot..."
    apt install -y certbot python3-certbot-apache
fi

# Instructions
echo -e "${YELLOW}╔════════════════════════════════════════════════════╗"
echo "║   IMPORTANT: Cloudflare Setup Required           ║"
echo "╚════════════════════════════════════════════════════╝${NC}"
echo ""
echo "Before running certbot, you must:"
echo "  1. Log in to Cloudflare dashboard"
echo "  2. Go to DNS settings for $DOMAIN"
echo "  3. Click the orange cloud icon to make it GREY (DNS only)"
echo "  4. Wait 2-3 minutes for DNS propagation"
echo ""
read -p "Have you disabled Cloudflare proxy? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy]es$ ]]; then
    echo -e "${YELLOW}[WARN]${NC} Please disable Cloudflare proxy and try again"
    exit 1
fi

echo ""
echo -e "${BLUE}[INFO]${NC} Running certbot to obtain SSL certificate..."
echo -e "${YELLOW}[NOTE]${NC} This will temporarily use the HTTP-only Apache config"
echo ""

# Ensure HTTP-only site is enabled for certbot verification
a2dissite terminal-portal-ssl 2>/dev/null || true
a2ensite terminal-portal
systemctl reload apache2

# Run certbot
if certbot certonly --apache -d "$DOMAIN" --non-interactive --agree-tos --email "${ADMIN_EMAIL:-webmaster@$DOMAIN}"; then
    echo -e "${GREEN}[SUCCESS]${NC} SSL certificate obtained successfully!"
else
    echo -e "${RED}[ERROR]${NC} Failed to obtain SSL certificate"
    echo "Please check:"
    echo "  - DNS is pointing to this server"
    echo "  - Cloudflare proxy is disabled (grey cloud)"
    echo "  - Port 80 is accessible from the internet"
    exit 1
fi

# Deploy SSL Apache configuration
echo -e "${BLUE}[INFO]${NC} Deploying SSL Apache configuration..."

sed "s/DOMAIN_PLACEHOLDER/$DOMAIN/g" "$CONFIG_DIR/apache/terminal-portal-ssl.conf" > /etc/apache2/sites-available/terminal-portal-ssl.conf

# Enable SSL site
a2dissite terminal-portal
a2ensite terminal-portal-ssl

# Test Apache configuration
if apache2ctl configtest; then
    systemctl reload apache2
    echo -e "${GREEN}[SUCCESS]${NC} HTTPS is now enabled!"
else
    echo -e "${RED}[ERROR]${NC} Apache configuration test failed"
    exit 1
fi

# Final instructions
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════╗"
echo "║   SSL Setup Complete!                             ║"
echo "╚════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}IMPORTANT: Re-enable Cloudflare Proxy${NC}"
echo "  1. Go back to Cloudflare DNS settings"
echo "  2. Click the GREY cloud to make it ORANGE (Proxied)"
echo "  3. Go to SSL/TLS settings"
echo "  4. Set mode to 'Full' (not 'Full Strict')"
echo ""
echo "Your portal is now accessible at:"
echo "  https://$DOMAIN"
echo "  https://$DOMAIN/webtop/"
echo "  https://$DOMAIN/terminal/"
echo ""
echo -e "${GREEN}[SUCCESS]${NC} Setup complete! 🚀"
