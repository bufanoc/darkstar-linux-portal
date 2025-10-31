# Session Continuity - Dark Star Linux Portal Setup
**Date:** October 31, 2025
**Server:** Ubuntu 24.04 LTS on DigitalOcean
**Public IP:** 159.203.131.190
**Domain:** oops.skyfort.group (via Cloudflare)

## Project Overview
Setting up a visually stunning landing page with embedded Ubuntu 24.04 terminal for network topology documentation and eye candy purposes.

---

## Completed Actions

### 1. System Updates
- Ran `apt update`
- Started `apt upgrade -y` (interrupted by user, then completed via dpkg configure)
- New kernel installed: 6.8.0-87-generic (requires reboot to activate)
- Current running kernel: 6.8.0-71-generic

### 2. GitHub Authentication Setup
- Installed GitHub CLI (gh version 2.82.1)
- Added GitHub CLI repository to apt sources
- Authenticated via device flow with code: 68B3-9D74
- Successfully logged in as: **bufanoc**
- Token scopes: 'gist', 'read:org', 'repo'
- Git operations protocol: HTTPS

### 3. Repository Setup
- Cloned repository: `bufanoc/darkstar-linux-portal`
- Initial location: `/root/darkstar-linux-portal/`
- Repository structure:
  ```
  darkstar-linux-portal/
  ├── container/
  │   └── Dockerfile
  ├── www/
  │   ├── index.html
  │   ├── style.css
  │   ├── script.js
  │   ├── admin/
  │   ├── api/
  │   └── dashboard.disabled/
  ├── includes/
  │   ├── auth.php
  │   ├── config.php
  │   └── db.php
  ├── docker-compose.yml
  └── README.md
  ```

### 4. Docker Installation
- Installed Docker CE version 28.5.1
- Installed Docker Compose plugin version 2.40.3
- Added Docker GPG key and repository
- Docker service: active and running
- Containerd service: active and running

### 5. Apache Installation & Configuration
- Installed Apache 2.4.58
- Enabled required modules:
  - proxy
  - proxy_http
  - proxy_wstunnel
  - rewrite
- Apache service: active and running

### 6. Docker Container Deployment
- Built Ubuntu 24.04 + ttyd container from Dockerfile
- Container name: `landing-terminal`
- Container ID: `af157f591eb3`
- Status: Up and running
- Port mapping: 127.0.0.1:7681->7681/tcp
- Packages installed in container:
  - ttyd (terminal server)
  - neofetch, figlet, lolcat
  - Core utilities and tools
  - Full Ubuntu 24.04 environment

### 7. Web Files Setup
- **Initial issue:** Files in `/root/` were inaccessible to Apache (403 Forbidden)
- **Solution:** Copied www files to `/var/www/darkstar-portal/`
- Set ownership: `www-data:www-data`
- Permissions: 755 for directories, 644 for files

### 8. Apache Virtual Host Configuration
**File:** `/etc/apache2/sites-available/terminal-portal.conf`

Key configuration:
- ServerName: oops.skyfort.group
- ServerAlias: www.oops.skyfort.group
- DocumentRoot: /var/www/darkstar-portal
- WebSocket proxy for terminal: /terminal/ → ws://127.0.0.1:7681/
- Disabled default site (000-default)
- Enabled terminal-portal site

### 9. Cloudflare DNS Setup
- Domain: oops.skyfort.group
- DNS pointing to Cloudflare IPs:
  - 172.67.178.230
  - 104.21.17.248
- Proxy status: Enabled (orange cloud)
- **Issue identified:** SSL/TLS mismatch
  - Cloudflare expects HTTPS
  - Server only has HTTP
  - Causing "web server is down" error

### 10. SSL/TLS Setup ✅ COMPLETED
- Installed Certbot version 2.9.0
- Installed python3-certbot-apache plugin
- User temporarily disabled Cloudflare proxy (orange → grey cloud)
- Successfully obtained Let's Encrypt SSL certificate for oops.skyfort.group
- Certificate location: `/etc/letsencrypt/live/oops.skyfort.group/`
- Certificate expiration: January 29, 2026 (auto-renewal enabled)
- Certbot automatically configured Apache for HTTPS
- Created new config: `/etc/apache2/sites-available/terminal-portal-le-ssl.conf`
- Enabled SSL module in Apache
- HTTP → HTTPS redirect automatically configured
- User re-enabled Cloudflare proxy (grey → orange cloud)
- Cloudflare SSL/TLS mode set to "Full" (end-to-end encryption)
- **Status:** LIVE and fully operational at https://oops.skyfort.group/

### 11. GitHub Repository Update ✅ COMPLETED
- Configured git user identity for the repository
  - Name: Carmine Bufano
  - Email: bufanoc@users.noreply.github.com
- Added session_continuity.md to repository
- Created commit: "Add comprehensive deployment documentation"
- Commit hash: 89ff434
- Successfully pushed to GitHub: bufanoc/darkstar-linux-portal
- Repository URL: https://github.com/bufanoc/darkstar-linux-portal
- Documentation now available on GitHub at `/session_continuity.md`

### 12. WebVM Integration ✅ COMPLETED (LATER REMOVED)
- **Purpose:** Self-hosted Linux desktop environment running in browser via WebAssembly
- **Technology:** WebVM 2.0 with CheerpX virtualization engine
- **Status:** Initially deployed, then removed in favor of Webtop solution
- **Reason for removal:** User preferred Docker-based Webtop with better control over network access

### 13. WebVM Removal ✅ COMPLETED
- Removed WebVM section from landing page HTML
- Removed all WebVM-specific CSS styles
- Removed Apache `/desktop/` configuration
- Deleted `/var/www/webvm/` directory
- Deleted `/root/webvm/` source directory
- Cleaned up all references and dependencies

### 14. Webtop Desktop Implementation with Network Isolation ✅ COMPLETED
- **Technology:** LinuxServer.io Webtop (Alpine i3 variant)
- **Container:** `darkstar-webtop` running Alpine Linux with i3 window manager
- **Network Security Architecture:**
  - Starts on `isolated` network (internal: true - NO internet)
  - Can be dynamically connected to `internet` network via API
  - Password-protected network control system
- **Docker Compose Configuration:**
  - Image: `lscr.io/linuxserver/webtop:alpine-i3`
  - Resources: 2GB RAM, 2 CPUs, 1GB shared memory
  - Ports: 3000 (HTTP), 3001 (HTTPS) - localhost only
  - Networks: isolated (default), internet (on-demand)
- **Apache Configuration:**
  - Endpoint: `/webtop/` proxied to https://127.0.0.1:3001/
  - WebSocket support for desktop streaming
  - SSL proxy enabled
  - Cross-origin headers for WebCodecs API

### 15. Password-Protected Network Control API ✅ COMPLETED
- **API Endpoint:** `/api/network-control.php`
- **Security Features:**
  - Argon2ID password hashing
  - Password verification before any network changes
  - Restricted sudo permissions for www-data user
- **Functionality:**
  - Enable internet: Connects webtop to internet network
  - Disable internet: Disconnects webtop from internet network
  - Status checking: Verifies current network state
- **Sudoers Configuration:** `/etc/sudoers.d/www-data-docker`
  - Limited to specific docker network commands only
  - No password prompt for approved commands
- **Default Password:** `darkstar2025` (should be changed)

### 16. Landing Page Network Control UI ✅ COMPLETED
- **Network Control Panel** integrated into launch section
- **Features:**
  - Password input field
  - Enable/Disable internet buttons
  - Real-time status feedback with color coding
  - Clear instructions about network isolation
- **User Flow:**
  1. User clicks "Launch Desktop Environment"
  2. Desktop opens at `/webtop/` (no internet)
  3. User enters password in control panel
  4. Clicks "Enable Internet" button
  5. JavaScript calls API with password
  6. On success, internet is enabled immediately
  7. User can disable internet anytime

### 17. SMS Verification System - PLANNED
- **Purpose:** Enhanced security for internet access
- **Proposed Flow:**
  1. User clicks "Enable Internet"
  2. Wizard explains members-only policy
  3. User enters: Name, Email, Phone
  4. SMS sent with 6-digit verification code
  5. User enters code for validation
  6. Magic link sent to email
  7. User clicks link → Internet enabled
- **Technology Stack (Proposed):**
  - Twilio for SMS ($15 trial credit, ~$0.0079/SMS)
  - Database table for tracking verifications
  - Token-based magic link system
  - 24-hour access grants (configurable)
- **Cost Estimate:** ~$2-3/month for 10 users/day
- **Status:** Planning phase - awaiting Twilio account setup
- **Documentation:** `/root/WEBTOP_DEPLOYMENT.md`

---

## Current Server State

### Running Services
- **Docker:** Active
  - `landing-terminal` - ttyd terminal container
  - `darkstar-webtop` - Alpine i3 desktop (ready to deploy)
- **Apache:** Active on ports 80 (HTTP redirect) and 443 (HTTPS)
- **Firewall (ufw):** Inactive

### Docker Networks
- `darkstar-linux-portal_isolated` - Internal network (no internet)
- `darkstar-linux-portal_internet` - External network (full internet)

### Ports in Use
- Port 80: Apache (HTTP - redirects to HTTPS)
- Port 443: Apache (HTTPS - SSL/TLS enabled)
- Port 7681: ttyd terminal (localhost only)
- Port 3000: Webtop HTTP (localhost only)
- Port 3001: Webtop HTTPS (localhost only)

### File Locations
- **Web files:** `/var/www/darkstar-portal/`
- **Repository:** `/root/darkstar-linux-portal/`
- **Apache config:** `/etc/apache2/sites-available/terminal-portal-le-ssl.conf`
- **Apache logs:** `/var/log/apache2/terminal-portal-*.log`
- **Network Control API:** `/var/www/darkstar-portal/api/network-control.php`
- **Sudoers config:** `/etc/sudoers.d/www-data-docker`
- **Webtop config:** `/root/darkstar-linux-portal/webtop-config/`

### Network Access
- **Main portal:** ✅ https://oops.skyfort.group/
- **Terminal:** ✅ https://oops.skyfort.group/terminal/ (ZORK game)
- **Desktop:** 🔜 https://oops.skyfort.group/webtop/ (ready to deploy)
- **Network API:** ✅ https://oops.skyfort.group/api/network-control.php

---

## Next Steps

### Potential Future Enhancements

#### Custom WebVM Disk Images
- Build custom Debian/Alpine images with pre-installed tools
- Customize desktop environment themes to match purple space aesthetic
- Add development tools (VS Code, git, compilers, etc.)

#### Tailscale Networking Integration
- Document setup process for WebVM networking
- Create guides for connecting WebVM instances together
- Enable SSH access between WebVM and other services

#### Performance Optimization
- Implement aggressive caching for WebVM assets
- Consider CDN for static WebVM resources
- Monitor resource usage and optimize as needed

#### Additional Interactive Elements
- Network topology visualizations
- Interactive documentation system
- Real-time system monitoring dashboards

---

## Important Configuration Files

### docker-compose.yml
Location: `/root/darkstar-linux-portal/docker-compose.yml`
- Defines terminal container
- Security policies: read-only filesystem, resource limits
- Network isolation

### Apache VirtualHost
Locations:
- `/etc/apache2/sites-available/terminal-portal.conf` - HTTP (port 80) with redirect to HTTPS
- `/etc/apache2/sites-available/terminal-portal-le-ssl.conf` - HTTPS (port 443) with SSL certificates

### Container Dockerfile
Location: `/root/darkstar-linux-portal/container/Dockerfile`
- Base: Ubuntu 24.04
- Main service: ttyd
- User: guestuser (non-root)
- Security: minimal privileges, capability drops

### WebVM Configuration
Locations:
- WebVM source: `/root/webvm/`
- WebVM build output: `/var/www/webvm/`
- Config files:
  - `/root/webvm/config_public_alpine.js` - Alpine Linux desktop configuration
  - `/root/webvm/config_public_terminal.js` - Terminal-only configuration
- **Important:** Requires cross-origin isolation headers (configured in Apache)
- **Build process:** `npm install && npm run build` (SvelteKit + Vite)
- **Disk image:** Served from `wss://disks.webvm.io/alpine_20251007.ext2`

---

## Security Features Implemented

### Container Isolation
- No network access outside host
- Read-only filesystem (except /tmp and /home/guestuser)
- Resource limits: 256MB RAM, 0.5 CPU
- Minimal privileges
- Runs as non-root user (guestuser)

### Web Server
- Terminal only accessible via Apache proxy
- Localhost-only binding (127.0.0.1:7681)
- WebSocket support for secure terminal connection

---

## Troubleshooting Reference

### 403 Forbidden Error
**Cause:** Apache can't access files in /root/
**Solution:** Move to /var/www/ and set www-data ownership

### Cloudflare "Web Server Down"
**Cause:** SSL/TLS mode mismatch (server has no SSL but Cloudflare expects HTTPS)
**Solution Applied:** Installed Let's Encrypt SSL certificate and set Cloudflare to "Full" SSL mode
**Note:** Temporarily disable Cloudflare proxy (orange to grey) when obtaining Let's Encrypt certificates

### Docker Container Not Starting
**Check:**
```bash
docker ps -a
docker logs landing-terminal
docker compose logs
```

### Apache Not Serving Proxy
**Check modules:**
```bash
sudo a2enmod proxy proxy_http proxy_wstunnel rewrite
sudo systemctl restart apache2
```

---

## Useful Commands

### Docker Management
```bash
# View running containers
docker ps

# View all containers
docker ps -a

# View container logs
docker logs landing-terminal

# Restart container
cd /root/darkstar-linux-portal && docker compose restart

# Stop container
docker compose down

# Rebuild and restart
docker compose up -d --build
```

### Apache Management
```bash
# Restart Apache
sudo systemctl restart apache2

# Check Apache status
sudo systemctl status apache2

# Test Apache configuration
sudo apache2ctl configtest

# View Apache logs
sudo tail -f /var/log/apache2/terminal-portal-access.log
sudo tail -f /var/log/apache2/terminal-portal-error.log
```

### SSL Certificate Management
```bash
# Obtain certificate (after DNS only mode)
sudo certbot --apache -d oops.skyfort.group -d www.oops.skyfort.group

# Renew certificates
sudo certbot renew

# Test renewal
sudo certbot renew --dry-run

# List certificates
sudo certbot certificates
```

### System Information
```bash
# Public IP
curl -s ifconfig.me

# Check DNS
dig +short oops.skyfort.group

# Check listening ports
sudo ss -tlnp | grep -E ':(80|443|7681)'

# Check firewall
sudo ufw status
```

### Git Management
```bash
# Check repository status
git status

# View recent commits
git log --oneline -5

# Pull latest changes
git pull origin main

# Add and commit changes
git add .
git commit -m "Your commit message"

# Push to GitHub
git push origin main

# View remote repository
git remote -v
```

### WebVM Management
```bash
# Rebuild WebVM after changes
cd /root/webvm
npm install
npm run build

# Copy build to web directory
sudo cp -r /root/webvm/build/* /var/www/webvm/
sudo chown -R www-data:www-data /var/www/webvm

# Test cross-origin headers
curl -I https://oops.skyfort.group/desktop/alpine.html | grep -i cross-origin

# Check WebVM disk image config
cat /root/webvm/config_public_alpine.js

# Update WebVM from upstream
cd /root/webvm
git pull origin main
npm install
npm run build
```

---

## GitHub Repository Info
- Owner: bufanoc
- Repo: darkstar-linux-portal
- Clone URL: https://github.com/bufanoc/darkstar-linux-portal.git
- Local path: /root/darkstar-linux-portal/

---

## Notes & Observations

### System Kernel
- New kernel installed but not active
- Reboot recommended but not required immediately
- Current: 6.8.0-71-generic
- Available: 6.8.0-87-generic

### Background Process
- Old apt upgrade process (ID: 7e06bd) still technically running
- Can be safely ignored or killed
- dpkg configure already completed the upgrade

### Performance
- Server running smoothly
- Apache memory usage: ~5.4M
- Docker container running efficiently
- No resource constraints observed

---

## Session Context
- **Session 1:** October 31, 2025 ~00:17 UTC - ~01:50 UTC
  - Initial setup, SSL, terminal integration, GitHub setup
- **Session 2:** October 31, 2025 ~06:00 UTC - ~06:20 UTC
  - WebVM integration, desktop environment setup
- **Session 3:** October 31, 2025 ~06:30 UTC - ~08:00 UTC
  - WebVM removal, Webtop implementation
  - Network isolation architecture
  - Password-protected network control API
  - SMS verification system planning
- **Total implementation:** ~4 hours
- **Technologies deployed:** Apache, Docker, ttyd, Webtop, SSL/TLS, PHP API, Argon2ID

## User Preferences Noted
- Wants everything visually stunning
- Planning to document advanced network topologies
- Changed from WebVM to Webtop for better network control
- Security-focused: internet disabled by default
- Prefers proper/secure solutions over quick fixes
- Single-page design with scroll-reveal sections
- Interested in SMS verification for access control
- Responsible access provider model

---

## Resuming Work

**To continue this project from a new computer or session:**

1. SSH into the server: `ssh root@159.203.131.190`
2. Start Claude Code and say: *"Read /root/session_continuity.md to understand the darkstar-linux-portal setup"*
3. This documentation contains everything needed to continue seamlessly

**Alternative:** View documentation on GitHub at:
https://github.com/bufanoc/darkstar-linux-portal/blob/main/session_continuity.md
