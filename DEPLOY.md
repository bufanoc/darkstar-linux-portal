# Dark Star Portal - Automated Deployment Guide

This guide is designed for AI assistants (Claude Code, Gemini CLI, etc.) to automatically deploy the Dark Star Portal on Ubuntu 24.04 LTS.

---

## ⚙️ Minimum Server Requirements

**IMPORTANT**: This application requires adequate resources to run smoothly.

**Minimum Specifications**:
- **RAM**: 4GB (recommended 4GB+)
- **CPU**: 2 cores
- **Disk**: 20GB free space
- **OS**: Ubuntu 24.04 LTS

**Why these specs**:
- Webtop (Ubuntu KDE Desktop): Requires 2GB RAM + 1.5 CPUs
- Terminal container: Requires 256MB RAM + 0.5 CPUs
- System overhead: ~1-1.5GB RAM

**DO NOT** attempt to deploy on servers with less than 3GB RAM - the desktop environment will crash due to memory exhaustion.

---

## 🤖 AI Assistant Instructions

**When a user asks you to deploy this application, follow these exact steps:**

### Prerequisites Check

1. Verify you're running on Ubuntu 24.04 LTS:
   ```bash
   cat /etc/os-release | grep "24.04"
   ```

2. Verify you have root access:
   ```bash
   whoami
   # Should output: root
   ```

3. **Check server resources (CRITICAL)**:
   ```bash
   # Check RAM (need 4GB minimum)
   free -h

   # Check CPU cores (need 2 minimum)
   nproc

   # Check disk space (need 20GB minimum)
   df -h /
   ```

   **STOP IF**: RAM < 3GB or CPUs < 2. Inform the user to resize their server first.

4. Check if git is installed:
   ```bash
   git --version || apt install -y git
   ```

### Step-by-Step Deployment

#### 1. Clone the Repository (if not already cloned)
```bash
cd /root
git clone https://github.com/bufanoc/darkstar-linux-portal.git
cd darkstar-linux-portal
```

#### 2. Configure Deployment Settings
```bash
# Copy the example configuration
cp config/deployment.env.example config/deployment.env

# Edit the configuration
# IMPORTANT: Ask the user for these values:
# - DOMAIN: Their domain name
# - NETWORK_CONTROL_PASSWORD: A strong password for network control
```

**ASK THE USER:**
- "What domain name will you use for this portal?" (e.g., portal.example.com)
- "Please provide a strong password for the network control API:"

**Then update the config file:**
```bash
# Replace DOMAIN_PLACEHOLDER with the user's domain
sed -i 's/portal.example.com/THEIR_DOMAIN/g' config/deployment.env

# Replace password placeholder with their password
sed -i 's/change-this-strong-password/THEIR_PASSWORD/g' config/deployment.env
```

#### 3. Run the Automated Deployment Script
```bash
chmod +x scripts/deploy.sh
./scripts/deploy.sh
```

The script will automatically:
- Update the system
- Install Docker
- Install Apache
- Configure UFW firewall
- Install Fail2Ban
- Deploy web files
- Configure Apache virtual host
- Setup network isolation
- Deploy Docker containers
- Install monitoring

#### 4. SSL Certificate Setup

**INFORM THE USER:**
"The deployment is complete! For HTTPS (SSL), run the automated SSL setup script:"

```bash
./scripts/setup-ssl.sh
```

This script will:
1. Check if certbot is installed (install if needed)
2. Guide you through Cloudflare setup (disable proxy temporarily)
3. Obtain Let's Encrypt certificate automatically
4. Deploy SSL Apache configuration
5. Remind you to re-enable Cloudflare proxy

**Cloudflare-Ready:**
- The application is fully compatible with Cloudflare
- Webtop requires HTTPS (uses WebSockets over SSL)
- Let's Encrypt + Cloudflare "Full" SSL mode works perfectly
- The SSL setup script handles everything automatically

#### 5. Verify Deployment

Check that services are running:
```bash
# Check Docker containers
docker ps

# Check Apache
systemctl status apache2

# Check UFW
ufw status

# Check Fail2Ban
fail2ban-client status
```

**Expected output:**
- 2 Docker containers running (darkstar-webtop, landing-terminal)
- Apache2 active and running
- UFW active with ports 22, 80, 443 allowed
- Fail2Ban active with 6 jails

#### 6. Provide Access Information to User

**Tell the user:**
```
✅ Deployment Complete!

Your Dark Star Portal is now running:
- Portal URL: http://YOUR_DOMAIN (https after SSL setup)
- Terminal: http://YOUR_DOMAIN/terminal/
- Desktop: http://YOUR_DOMAIN/webtop/

Network Control:
- Password: [THE PASSWORD THEY PROVIDED]
- Use the Network Control panel on the main page to enable/disable internet

Next Steps:
1. Complete SSL certificate setup (see instructions above)
2. Test the portal by visiting your domain
3. Use the network control password to enable internet in the desktop

Credentials:
- Webtop Username: darkstar
- Network Control Password: [THEIR PASSWORD]

Logs:
- Apache: /var/log/apache2/terminal-portal-*.log
- Monitoring: /var/log/darkstar-monitor.log
- Docker: docker logs darkstar-webtop
```

---

## Troubleshooting for AI Assistants

### If deployment script fails:

1. **Check system requirements:**
   ```bash
   df -h  # Need at least 10GB free space
   free -h  # Need at least 2GB RAM
   ```

2. **Check Docker installation:**
   ```bash
   docker --version
   docker compose version
   ```

3. **Check Apache configuration:**
   ```bash
   apache2ctl configtest
   ```

4. **Check Docker containers:**
   ```bash
   docker compose logs
   ```

5. **Check network isolation:**
   ```bash
   iptables -L DOCKER-USER -n -v
   ```

### Common Issues:

**Issue: Port already in use**
```bash
# Check what's using port 80/443
ss -tlnp | grep -E ':(80|443)'

# Stop conflicting service if needed
systemctl stop <service-name>
```

**Issue: Docker network already exists**
```bash
# Remove existing networks
docker network rm darkstar-linux-portal_isolated darkstar-linux-portal_internet
# Then re-run deployment script
```

**Issue: Permission denied errors**
```bash
# Ensure running as root
sudo su -
# Then re-run deployment
```

---

## Manual Deployment (if automated script fails)

If the automated script fails, you can deploy manually by following these commands in order:

```bash
# 1. System update
apt update && apt upgrade -y

# 2. Install Docker
curl -fsSL https://get.docker.com | sh

# 3. Install Apache
apt install -y apache2
a2enmod proxy proxy_http proxy_wstunnel rewrite ssl headers

# 4. Install UFW and Fail2Ban
apt install -y ufw fail2ban

# 5. Configure firewall
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# 6. Deploy web files
mkdir -p /var/www/darkstar-portal
cp -r www/* /var/www/darkstar-portal/
chown -R www-data:www-data /var/www/darkstar-portal

# 7. Configure Apache
cp config/apache/terminal-portal.conf /etc/apache2/sites-available/
sed -i 's/DOMAIN_PLACEHOLDER/YOUR_DOMAIN/g' /etc/apache2/sites-available/terminal-portal.conf
a2dissite 000-default
a2ensite terminal-portal
systemctl restart apache2

# 8. Setup sudoers
cp config/sudoers.d/www-data-docker /etc/sudoers.d/
chmod 0440 /etc/sudoers.d/www-data-docker

# 9. Setup network isolation
cp config/ufw/after.rules /etc/ufw/after.rules
ufw reload

# 10. Create Docker networks
docker network create darkstar-linux-portal_isolated --driver bridge --opt com.docker.network.bridge.name=isolated0
docker network create darkstar-linux-portal_internet --driver bridge --opt com.docker.network.bridge.name=internet0

# 11. Deploy containers
docker compose up -d
```

---

## For Users (Human Readable)

If you're a human reading this:

1. **Quick Start:**
   ```bash
   git clone https://github.com/bufanoc/darkstar-linux-portal.git
   cd darkstar-linux-portal
   cp config/deployment.env.example config/deployment.env
   # Edit config/deployment.env with your settings
   sudo ./scripts/deploy.sh
   ```

2. **Then setup SSL:**
   - Point DNS to your server
   - Run: `sudo certbot --apache -d yourdomain.com`

3. **Access your portal:**
   - Main: https://yourdomain.com
   - Terminal: https://yourdomain.com/terminal/
   - Desktop: https://yourdomain.com/webtop/

---

## Security Notes

- **Change default passwords** immediately after deployment
- **Enable Cloudflare** for DDoS protection
- **Monitor logs** regularly: `/var/log/darkstar-monitor.log`
- **Review Fail2Ban** banned IPs: `fail2ban-client status sshd`
- **Keep system updated**: `apt update && apt upgrade`

---

## Support

For issues or questions:
- Check logs: `docker compose logs`
- Review documentation in `/docs/`
- GitHub Issues: https://github.com/bufanoc/darkstar-linux-portal/issues
