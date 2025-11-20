# Quick Deployment Guide - Dark Star Portal

**Deploy a working Dark Star Portal in under 10 minutes!**

This guide will get you a fully functional browser-based Linux desktop with all features working.

---

## What You'll Get

- ✅ Ubuntu MATE desktop accessible via browser
- ✅ ZORK terminal game on the homepage
- ✅ Password-protected internet control
- ✅ SSL/TLS encryption
- ✅ Network isolation (desktop has no internet by default)
- ✅ Auto-restart every 30 minutes (keeps it clean)
- ✅ Enterprise security (UFW, Fail2ban, Cloudflare-ready)

---

## Requirements

- Ubuntu 24.04 LTS server
- 4GB+ RAM (8GB recommended)
- 2+ CPU cores
- Public IP address or domain name
- Root access

---

## Installation Steps

### 1. Update System & Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com | sh
sudo systemctl enable --now docker

# Install Docker Compose
sudo apt install -y docker-compose-plugin

# Install Apache, PHP, Git
sudo apt install -y apache2 php libapache2-mod-php8.3 git certbot python3-certbot-apache

# Enable Apache modules
sudo a2enmod proxy proxy_http proxy_wstunnel ssl rewrite headers
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

# Edit configuration
nano config/deployment.env
```

**Set these values:**
```bash
DOMAIN=your-domain.com              # Your domain or IP
NETWORK_CONTROL_PASSWORD=YourPassword123   # Choose a strong password
TIMEZONE=America/New_York           # Your timezone
```

Save and exit (Ctrl+X, Y, Enter)

### 4. Deploy Containers

```bash
# Start containers
docker compose up -d

# Wait 30 seconds for desktop to initialize
sleep 30

# Check status
docker ps
```

You should see two containers running:
- `darkstar-webtop` - Ubuntu MATE desktop
- `landing-terminal` - ZORK terminal

### 5. Configure Apache

```bash
# Copy Apache configs
sudo cp config/apache/terminal-portal.conf /etc/apache2/sites-available/
sudo cp config/apache/terminal-portal-ssl.conf /etc/apache2/sites-available/

# Update domain in configs
sudo sed -i "s/your-domain.com/YOUR_ACTUAL_DOMAIN/g" /etc/apache2/sites-available/terminal-portal.conf
sudo sed -i "s/your-domain.com/YOUR_ACTUAL_DOMAIN/g" /etc/apache2/sites-available/terminal-portal-ssl.conf

# Deploy web files
sudo mkdir -p /var/www/darkstar-portal
sudo cp -r www/* /var/www/darkstar-portal/
sudo chown -R www-data:www-data /var/www/darkstar-portal

# Enable site
sudo a2dissite 000-default.conf
sudo a2ensite terminal-portal.conf
sudo systemctl restart apache2
```

### 6. Setup SSL Certificate (Let's Encrypt)

```bash
# Get SSL certificate
sudo certbot --apache -d your-domain.com

# Enable HTTPS site
sudo a2ensite terminal-portal-ssl.conf
sudo systemctl restart apache2
```

### 7. Configure Network Control Password

Generate password hash:
```bash
php -r "echo password_hash('YourPassword123', PASSWORD_ARGON2ID) . PHP_EOL;"
```

Copy the output, then edit the API file:
```bash
sudo nano /var/www/darkstar-portal/api/network-control.php
```

Find line ~139 and replace the hash with your generated hash:
```php
$password_hash = 'YOUR_GENERATED_HASH_HERE';
```

Save and exit.

### 8. Setup Firewall (UFW)

```bash
# Enable UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
```

### 9. Setup Fail2ban

```bash
# Install Fail2ban
sudo apt install -y fail2ban

# Copy custom configs
sudo cp config/fail2ban/darkstar-api.conf /etc/fail2ban/jail.d/
sudo cp config/fail2ban/darkstar-api.conf /etc/fail2ban/filter.d/

# Restart Fail2ban
sudo systemctl restart fail2ban
sudo systemctl enable fail2ban
```

### 10. Setup Auto-Restart (Every 30 Minutes)

```bash
# Create restart script
sudo bash -c 'cat > /usr/local/bin/restart-webtop.sh << "EOF"
#!/bin/bash
cd /root/darkstar-linux-portal
docker compose restart webtop
EOF'

sudo chmod +x /usr/local/bin/restart-webtop.sh

# Add cron job
(crontab -l 2>/dev/null; echo "# Restart Dark Star desktop every 30 minutes"; echo "*/30 * * * * /usr/local/bin/restart-webtop.sh >> /var/log/darkstar-restart.log 2>&1") | crontab -

# Create log file
sudo touch /var/log/darkstar-restart.log
sudo chmod 644 /var/log/darkstar-restart.log
```

### 11. Create Internet Network (Required)

```bash
cd /root/darkstar-linux-portal
docker network create darkstar-linux-portal_internet --driver bridge --opt com.docker.network.bridge.name=internet0
```

---

## Access Your Portal

**Homepage:** `https://your-domain.com`

You should see:
- ZORK terminal embedded on the page
- "Launch Desktop Environment" button
- Network Control panel

**Test Desktop:**
1. Click "Launch Desktop Environment"
2. Ubuntu MATE desktop should load in ~10 seconds
3. Desktop has NO internet by default

**Test Network Control:**
1. Enter your password in the Network Control panel
2. Click "Enable Internet"
3. Desktop now has internet access
4. Click "Disable Internet" to revoke access

---

## Optional: Cloudflare Proxy

To enable Cloudflare's proxy (orange cloud):

1. Add your domain to Cloudflare
2. Set DNS record to your server IP
3. Change cloud icon from gray to orange (Proxied)
4. Set SSL/TLS mode to **"Full (strict)"**

Everything will continue working - Cloudflare handles WebSockets automatically!

---

## Performance Tuning

### If Desktop is Slow:

**In the webtop sidebar (left side of desktop):**
1. Click the settings icon
2. Go to "Video"
3. Change encoder from "x264" to **"JPEG"**
4. Desktop will be much faster!

### Current Resource Allocation:

- Desktop: 4GB RAM, 3.5 CPU cores
- Terminal: 256MB RAM, 0.5 CPU cores

**To adjust:** Edit `docker-compose.yml` and change `mem_limit` and `cpus` values, then:
```bash
docker compose up -d
```

---

## Troubleshooting

### Desktop Won't Load / Black Screen

```bash
# Restart container
docker compose restart webtop

# Check logs
docker logs darkstar-webtop --tail 50
```

### ICEauthority Error

```bash
docker exec darkstar-webtop bash -c "rm -f /config/.XDG/ICEauthority && chown -R abc:abc /config/.XDG"
docker compose restart webtop
```

### Network Control Not Working

```bash
# Check PHP is enabled
sudo apache2ctl -M | grep php

# If not found, install
sudo apt install -y libapache2-mod-php8.3
sudo systemctl restart apache2
```

### Check Auto-Restart

```bash
# View restart log
tail -f /var/log/darkstar-restart.log

# Verify cron job
crontab -l
```

---

## Security Checklist

After deployment, verify:

- ✅ UFW firewall is active: `sudo ufw status`
- ✅ Fail2ban is running: `sudo systemctl status fail2ban`
- ✅ SSL certificate is valid: `sudo certbot certificates`
- ✅ Containers are running: `docker ps`
- ✅ Network control requires password
- ✅ Desktop has no internet by default
- ✅ Auto-restart cron job is active: `crontab -l`

---

## Maintenance

### Monthly Tasks

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Update containers
docker compose pull
docker compose up -d

# Renew SSL (auto-renews, but can force)
sudo certbot renew
```

### View Logs

```bash
# Apache logs
sudo tail -f /var/log/apache2/terminal-portal-error.log

# Container logs
docker logs darkstar-webtop --tail 50
docker logs landing-terminal --tail 50

# Restart logs
tail -f /var/log/darkstar-restart.log
```

---

## What Happens Every 30 Minutes?

The desktop container automatically restarts:
- 🧹 Clears any installed software/malware
- 💾 Frees up memory
- 🔒 Resets desktop to clean state
- ⏱️ Takes ~5 seconds
- 👥 Users briefly disconnected, then reconnect

This is a security feature for public demo environments!

---

## Support

- **Documentation:** See `README.md` for detailed info
- **Issues:** GitHub Issues tracker
- **Security:** Report privately via GitHub

---

## Summary

You now have:
- ✅ Working browser-based Ubuntu MATE desktop
- ✅ ZORK terminal game
- ✅ Password-protected internet control
- ✅ SSL encryption
- ✅ Auto-cleaning every 30 minutes
- ✅ Enterprise security enabled

**Total deployment time:** ~10 minutes

**Your portal is ready!** 🚀

---

**Dark Star Portal v1.0 "Folsom"**
*Experience Linux, Anywhere*
