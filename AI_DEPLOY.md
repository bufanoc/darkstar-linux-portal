# AI-Assisted Deployment Guide

This guide is optimized for AI assistants (Claude, GPT, etc.) to deploy Dark Star Portal on a fresh Ubuntu 24.04 LTS server.

---

## Quick Deploy Command for AI Assistants

If you're an AI assistant helping a user deploy Dark Star Portal, follow these steps:

### 1. Prerequisites Check
```bash
# Verify OS version
cat /etc/os-release | grep VERSION_ID
# Should show: VERSION_ID="24.04"

# Check you have root access
whoami
# Should show: root

# Verify internet connectivity
ping -c 2 google.com
```

### 2. Clone Repository
```bash
cd /root
git clone https://github.com/bufanoc/darkstar-linux-portal.git
cd darkstar-linux-portal
```

### 3. Configure Deployment
```bash
# Copy example config
cp config/deployment.env.example config/deployment.env

# Edit configuration - AI should prompt user for:
# - DOMAIN: The domain name (e.g., portal.example.com)
# - NETWORK_CONTROL_PASSWORD: Strong password for network control API
nano config/deployment.env
```

**Required Configuration Variables:**
```bash
# Domain name for the portal
DOMAIN="your-domain.com"

# Password for network control (16+ characters recommended)
NETWORK_CONTROL_PASSWORD="your-secure-password"

# Security features (ENABLED by default for v1.0)
UFW_ENABLED="true"
FAIL2BAN_ENABLED="true"
MONITORING_ENABLED="true"

# SSL setup (manual step after deployment)
SSL_ENABLED="true"
```

### 4. Run Deployment Script
```bash
# Make script executable
chmod +x scripts/deploy.sh

# Run deployment (takes 5-10 minutes)
sudo ./scripts/deploy.sh
```

### 5. DNS Configuration
Point the domain's DNS A record to the server's public IP:
```bash
# Get server's public IP
curl -4 ifconfig.me
```

User must create DNS A record: `your-domain.com` → `server-ip`

### 6. SSL Certificate Setup
Once DNS propagates (5-60 minutes):
```bash
# If using Cloudflare:
# 1. Temporarily disable Cloudflare proxy (orange → grey cloud)
# 2. Install certificate
sudo certbot --apache -d your-domain.com
# 3. Re-enable Cloudflare proxy
# 4. Set Cloudflare SSL/TLS mode to "Full"

# If NOT using Cloudflare:
sudo certbot --apache -d your-domain.com
```

### 7. Verify Deployment
```bash
# Check containers are running
docker ps

# Check Apache status
systemctl status apache2

# Check firewall status
sudo ufw status

# Check fail2ban status
sudo fail2ban-client status

# Test web access (replace with actual domain)
curl -I https://your-domain.com/
```

---

## Architecture Overview for AI Context

### What Gets Deployed:
1. **Docker Engine** - Container runtime
2. **Apache 2.4** - Web server with reverse proxy
3. **PHP 8.3** - For network control API
4. **UFW Firewall** - Ports 22, 80, 443 allowed
5. **Fail2Ban** - Brute force protection for SSH and Apache
6. **Monitoring Script** - System health checks (cron every 6h)
7. **Webtop Container** - Fedora KDE desktop environment
8. **Terminal Container** - Ubuntu 24.04 with ttyd
9. **Network Control API** - PHP endpoint for network management
10. **Docker Networks** - Isolated (default) and internet (opt-in)

### Security Architecture:
- **Network Isolation**: Desktop container starts on `isolated` network with no internet
- **Password-Protected Internet**: API requires Argon2ID-hashed password to connect to `internet` network
- **Firewall Rules**: UFW blocks all except SSH (22), HTTP (80), HTTPS (443)
- **Fail2Ban**: Protects SSH and Apache from brute force attacks
- **SSL/TLS**: Let's Encrypt certificates with Cloudflare CDN optional
- **Container Security**: Read-only filesystems, dropped capabilities, resource limits
- **Monitoring**: Automated health checks with email alerts (optional)

### Access Points:
- **Landing Page**: `https://domain.com/`
- **Desktop**: `https://domain.com/webtop/`
- **Terminal**: `https://domain.com/terminal/`
- **Network API**: `https://domain.com/api/network-control.php`

---

## Common Issues & Solutions

### Issue: "DOMAIN not set in deployment.env"
**Solution**: Edit `config/deployment.env` and set `DOMAIN="your-domain.com"`

### Issue: "Docker command not found after installation"
**Solution**: Logout and login again, or run: `source ~/.bashrc`

### Issue: "certbot: command not found"
**Solution**: Install certbot:
```bash
apt update
apt install -y certbot python3-certbot-apache
```

### Issue: "Webtop container not starting"
**Solution**: Check Docker logs and memory:
```bash
docker logs darkstar-webtop
free -h  # Ensure at least 4GB RAM available
```

### Issue: "Connection refused on 443"
**Solution**: Ensure firewall allows HTTPS:
```bash
sudo ufw allow 443/tcp
sudo ufw reload
```

### Issue: "SSL certificate fails to install"
**Solution**: Verify DNS propagation:
```bash
dig your-domain.com +short
# Should return your server's IP

# Check port 80 is accessible (required for Let's Encrypt)
curl -I http://your-domain.com/
```

---

## Post-Deployment Configuration

### Enable Monitoring Alerts (Optional)
Edit `/usr/local/bin/darkstar-monitor.sh` to configure:
- Email alerts for system issues
- Custom alert thresholds
- Notification methods

### Customize Webtop
Access `https://domain.com/webtop/` and customize:
- Desktop theme and wallpaper
- Installed applications
- User preferences

### Network Control API Usage
```bash
# Enable internet access
curl -X POST https://domain.com/api/network-control.php \
  -H "Content-Type: application/json" \
  -d '{"password":"your-password","action":"enable"}'

# Disable internet access
curl -X POST https://domain.com/api/network-control.php \
  -H "Content-Type: application/json" \
  -d '{"password":"your-password","action":"disable"}'

# Check status
curl -X POST https://domain.com/api/network-control.php \
  -H "Content-Type: application/json" \
  -d '{"password":"your-password","action":"status"}'
```

---

## System Requirements

### Minimum:
- **RAM**: 4GB (8GB recommended)
- **CPU**: 2 cores
- **Storage**: 20GB available
- **OS**: Ubuntu 24.04 LTS
- **Network**: Public IP address or accessible behind NAT

### Recommended:
- **RAM**: 8GB or more
- **CPU**: 4 cores or more
- **Storage**: 50GB SSD
- **Network**: 10+ Mbps connection

---

## Security Checklist

After deployment, AI assistants should verify:

- [ ] UFW firewall is enabled: `sudo ufw status`
- [ ] Fail2Ban is running: `sudo fail2ban-client status`
- [ ] SSL certificate is installed and valid
- [ ] Containers are running: `docker ps`
- [ ] Network control password is strong (16+ characters)
- [ ] SSH password authentication is disabled (key-only)
- [ ] Docker containers have resource limits
- [ ] Apache is serving over HTTPS
- [ ] Monitoring script is scheduled: `crontab -l`
- [ ] No sensitive data in git repository

---

## AI Assistant Best Practices

### When Deploying:
1. **Always verify prerequisites** before starting installation
2. **Prompt user for required information** (domain, password) - never guess
3. **Show progress** at each step
4. **Capture and analyze errors** if deployment fails
5. **Verify each service** after installation
6. **Provide next steps** clearly

### Information to Collect from User:
- Domain name (DNS must point to server)
- Network control password (suggest strong password)
- Email for monitoring alerts (optional)
- Cloudflare usage (yes/no)
- Timezone preference (default: America/New_York)

### Error Handling:
- If a step fails, **show the full error message**
- **Check logs** immediately: `/var/log/apache2/`, `docker logs`
- **Verify prerequisites** weren't skipped
- **Suggest rollback** if needed: `docker compose down && apt remove ...`

### Post-Deployment Verification:
```bash
# Run this comprehensive check
echo "=== Dark Star Portal Health Check ==="
echo ""
echo "1. Docker Containers:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""
echo "2. Firewall Status:"
sudo ufw status numbered
echo ""
echo "3. Fail2Ban Status:"
sudo fail2ban-client status | head -5
echo ""
echo "4. Apache Status:"
systemctl status apache2 --no-pager | head -5
echo ""
echo "5. SSL Certificate:"
certbot certificates 2>/dev/null || echo "No certificates installed yet"
echo ""
echo "6. Disk Space:"
df -h / | tail -1
echo ""
echo "7. Memory Usage:"
free -h | grep Mem
```

---

## Advanced: Manual Installation

If automatic deployment fails, here's the manual process:

1. **Update system**: `apt update && apt upgrade -y`
2. **Install Docker**: Follow official Docker docs for Ubuntu 24.04
3. **Install Apache**: `apt install apache2 libapache2-mod-php8.3 -y`
4. **Enable Apache modules**: `a2enmod proxy proxy_http proxy_wstunnel ssl rewrite headers`
5. **Install UFW**: `apt install ufw -y`
6. **Install Fail2Ban**: `apt install fail2ban -y`
7. **Configure firewall**: `ufw allow 22,80,443/tcp && ufw enable`
8. **Clone repository**: `git clone https://github.com/bufanoc/darkstar-linux-portal.git`
9. **Configure deployment.env**: Copy example and edit
10. **Run deployment script**: `sudo ./scripts/deploy.sh`

---

## Troubleshooting Commands

```bash
# View all service logs
journalctl -xe -u docker -u apache2 --since "5 minutes ago"

# Restart all services
systemctl restart apache2 docker
docker compose restart

# Check port bindings
ss -tlnp | grep -E ':(80|443|3000|3001|7681)'

# Test network connectivity from container
docker exec darkstar-webtop ping -c 2 google.com

# Check Docker network configuration
docker network inspect darkstar-linux-portal_isolated
docker network inspect darkstar-linux-portal_internet

# View Apache configuration
apache2ctl -S

# Test PHP execution
php -r "echo 'PHP Version: ' . phpversion() . PHP_EOL;"

# Check fail2ban jails
sudo fail2ban-client status sshd
```

---

## Repository Structure

```
darkstar-linux-portal/
├── AI_DEPLOY.md                    # This file - AI deployment guide
├── README.md                       # Main documentation
├── DEPLOY.md                       # Detailed deployment instructions
├── RELEASE_NOTES.md                # Version history
├── LICENSE                         # BSD 3-Clause License
├── docker-compose.yml              # Container orchestration
├── config/
│   ├── deployment.env.example      # Configuration template
│   ├── apache/                     # Apache virtual host configs
│   ├── fail2ban/                   # Fail2Ban rules
│   ├── sudoers.d/                  # Sudo rules for API
│   └── ufw/                        # Firewall rules
├── scripts/
│   ├── deploy.sh                   # Main deployment script
│   ├── setup-ssl.sh                # SSL certificate setup
│   └── darkstar-monitor.sh         # System monitoring script
├── www/                            # Web files
│   ├── index.html                  # Landing page
│   ├── script.js                   # Network control UI
│   └── api/
│       └── network-control.php     # Network management API
├── container/                      # Terminal container build
│   └── Dockerfile
└── docs/                           # Additional documentation
```

---

## Success Indicators

Deployment is successful when:
1. ✅ `docker ps` shows 2 containers running (darkstar-webtop, landing-terminal)
2. ✅ `systemctl status apache2` shows active (running)
3. ✅ `sudo ufw status` shows active with ports 22,80,443 allowed
4. ✅ `curl -I https://domain.com/` returns HTTP 200
5. ✅ Web browser can access `https://domain.com/webtop/` and see desktop
6. ✅ Network control API responds to status requests
7. ✅ SSL certificate is valid (browser shows padlock)

---

## Support & Contributing

- **Documentation**: Complete docs in repository
- **Issues**: https://github.com/bufanoc/darkstar-linux-portal/issues
- **Security Issues**: Report privately via GitHub
- **License**: BSD 3-Clause - See LICENSE file

---

**Last Updated**: 2025-11-02
**Version**: 1.0 "Folsom"
