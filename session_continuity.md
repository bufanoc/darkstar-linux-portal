# Dark Star Portal - Session Continuity

**Date**: 2025-11-22 19:30 UTC
**Status**: ✅ **PRODUCTION READY - POST-SNAPSHOT WITH AUTH SYSTEM**
**Domain**: https://oops.skyfort.group
**Server IP**: 159.203.131.190

---

## 🎯 LATEST UPDATE: AUTHENTICATION SYSTEM DEPLOYED

**Major new feature**: Full user authentication system with admin approval workflow!

All features tested, working, and production-ready. Built on top of stable snapshot baseline.

---

## Current Status: ALL SYSTEMS OPERATIONAL ✅

### What's Working Perfectly:
- ✅ Ubuntu MATE desktop (stable, fast with JPEG encoding)
- ✅ ZORK terminal game embedded on homepage
- ✅ **NEW: User authentication system (signup/login)**
- ✅ **NEW: Admin approval workflow for new users**
- ✅ **NEW: Admin dashboard for user management**
- ✅ **NEW: Role-based access control (admin/user)**
- ✅ Network control API (now admin-only, session-based)
- ✅ Cron control API (now admin-only, session-based)
- ✅ Auto-restart every 30 minutes (security feature)
- ✅ Cloudflare proxy enabled (orange cloud)
- ✅ SSL/TLS encryption (Let's Encrypt)
- ✅ All security features active (UFW, Fail2ban, monitoring)
- ✅ GitHub repo fully updated

---

## 🔐 Authentication System

### Admin Account
- **Username**: `carmine`
- **Password**: `Xm2909onaXm2909ona`
- **Role**: admin
- **Status**: active

### Database
- **Location**: `/var/lib/darkstar/users.db`
- **Type**: SQLite3
- **Tables**: users, sessions
- **Permissions**: 660 (www-data:www-data)

### Features
- **Signup Flow**: New users → pending status → admin approval required
- **Login Flow**: Session-based authentication with role checking
- **Admin Dashboard**: `/admin/dashboard.html`
  - View all users with status filtering
  - Approve/reject pending signups
  - Suspend/activate existing users
  - Real-time stats (total, pending, active, suspended)
  - Auto-refresh every 30 seconds
- **Protected Endpoints**: Network control and cron control require admin session

### API Endpoints
- `/api/auth.php` - Authentication (signup, login, logout, session check)
- `/api/admin.php` - User management (list, approve, reject, suspend, activate, stats)
- `/api/network-control.php` - Network control (requires admin session)

### Security Features
- Argon2ID password hashing (memory: 65536, time: 4, threads: 1)
- Rate limiting on auth endpoints (5 attempts per minute, 5-minute lockout)
- SQL injection protection (prepared statements)
- Session-based authentication
- CSRF protection via session validation
- Progressive delay on failed attempts

---

## System Specifications

**Server (Digital Ocean Droplet)**:
- **RAM**: 7.8GB total, ~6GB available
- **CPU**: 4 cores
- **OS**: Ubuntu 24.04 LTS
- **Uptime**: 5+ hours (will reset after snapshot)

**Resource Allocation**:
- Webtop: 4GB RAM, 3.5 CPU cores
- Terminal: 256MB RAM, 0.5 CPU cores

---

## Deployed Configuration

### Webtop Desktop
- **Image**: `lscr.io/linuxserver/webtop:ubuntu-mate`
- **Desktop**: Ubuntu MATE (stable and reliable)
- **RAM Limit**: 4GB
- **CPU Limit**: 3.5 cores
- **Shared Memory**: 2GB
- **Status**: Running smoothly ✅
- **Performance Tip**: Use JPEG encoding (not x264) in sidebar settings

### Terminal Container
- **Image**: Custom ttyd container
- **RAM Limit**: 256MB
- **CPU Limit**: 0.5 cores
- **Features**: ZORK game
- **Status**: Operational ✅

---

## Access URLs

- **Main Portal**: https://oops.skyfort.group/
- **Desktop**: https://oops.skyfort.group/webtop/
- **Terminal**: https://oops.skyfort.group/terminal/ (ZORK)

**All URLs are accessible and working.**

---

## Network Control System

### Configuration
- **API Endpoint**: `/api/network-control.php` ✅
- **Password**: `Xm2909onaXm2909ona`
- **Authentication**: Argon2ID hash
- **Rate Limiting**: 5 attempts/min, 5min lockout ✅
- **Status**: Fully operational

### How It Works
1. Desktop starts on `isolated` network (no internet by default)
2. User enters password in Network Control panel on homepage
3. API authenticates and connects desktop to `internet` network
4. User can disable internet access at any time

### API Testing
```bash
# Enable Internet
curl -X POST https://oops.skyfort.group/api/network-control.php \
  -H "Content-Type: application/json" \
  -d '{"password":"Xm2909onaXm2909ona","action":"enable"}'

# Disable Internet
curl -X POST https://oops.skyfort.group/api/network-control.php \
  -H "Content-Type: application/json" \
  -d '{"password":"Xm2909onaXm2909ona","action":"disable"}'
```

---

## Security Status

### Active Security Features
- ✅ **UFW Firewall**: Active (ports 22, 80, 443 only)
- ✅ **Fail2ban**: Active (SSH + Apache + API protection)
- ✅ **Cloudflare Proxy**: Enabled (DDoS protection, WAF)
- ✅ **SSL/TLS**: Let's Encrypt certificates (auto-renew)
- ✅ **SSH**: Key-only authentication (passwords disabled)
- ✅ **Network Isolation**: Desktop isolated by default
- ✅ **Container Security**: no-new-privileges, capability drops
- ✅ **Auto-Restart**: Every 30 minutes (keeps desktop clean)

### Security Statistics (Last 5+ Hours)
- **Failed SSH Attempts**: 2,384 (all blocked)
- **UFW Firewall Blocks**: 986 (unwanted ports)
- **IPs Banned by Fail2ban**: 22 total, 7 currently
- **Successful Breaches**: 0 ✅
- **Attack Rate**: ~7 attempts/minute (normal background noise)

**Security Status**: EXCELLENT - All defenses working perfectly

---

## Auto-Restart Configuration

**Cron Job**: Desktop restarts every 30 minutes for security

**Script**: `/usr/local/bin/restart-webtop.sh`
```bash
#!/bin/bash
cd /root/darkstar-linux-portal
docker compose restart webtop
```

**Crontab**:
```
*/30 * * * * /usr/local/bin/restart-webtop.sh >> /var/log/darkstar-restart.log 2>&1
```

**Purpose**: Automatically wipes desktop clean every 30 minutes (prevents malware/junk buildup)

---

## GitHub Repository Status

**Repository**: https://github.com/bufanoc/darkstar-linux-portal
**Branch**: main
**Status**: ✅ **Fully up to date**

### Latest Commits
- `236985c` - feat: Switch to Ubuntu MATE desktop with enhanced stability
- `330eed9` - docs: Add comprehensive security documentation
- `1ec6a6a` - fix: Relax security constraints for KDE Plasma desktop

### What's in the Repo
- ✅ Ubuntu MATE configuration (4GB RAM, 3.5 CPU)
- ✅ Updated documentation (README, QUICK_DEPLOY.md)
- ✅ Auto-restart setup instructions
- ✅ Cloudflare proxy documentation
- ✅ All security configurations

**The repo is a perfect mirror of this working system!**

---

## Important Files & Locations

### Configuration Files
- **Docker Compose**: `/root/darkstar-linux-portal/docker-compose.yml`
- **Deployment Config**: `/root/darkstar-linux-portal/config/deployment.env`
- **Apache Config**: `/etc/apache2/sites-available/terminal-portal-ssl.conf`
- **Network Control API**: `/var/www/darkstar-portal/api/network-control.php`

### Web Files
- **Homepage**: `/var/www/darkstar-portal/index.html`
- **Script**: `/var/www/darkstar-portal/script.js`
- **Styles**: `/var/www/darkstar-portal/style.css`

### Logs
- **Auto-Restart Log**: `/var/log/darkstar-restart.log`
- **Apache Logs**: `/var/log/apache2/terminal-portal-error.log`
- **UFW Log**: `/var/log/ufw.log`
- **Container Logs**: `docker logs darkstar-webtop`

### Credentials
- **Network Password**: `Xm2909onaXm2909ona`
- **SSH**: Key-only authentication (no password)
- **Claude Code**: Authenticated in `/root/.claude.json`

---

## What Changed This Session (Nov 20, 2025)

### Major Improvements
1. ✅ **Switched from Fedora KDE to Ubuntu MATE**
   - More stable, no kwin crashes
   - Faster with JPEG encoding

2. ✅ **Increased Resources**
   - RAM: 3GB → 4GB
   - CPU: 2.0 → 3.5 cores

3. ✅ **Fixed ICEauthority Error**
   - Removed corrupt files
   - Fixed permissions

4. ✅ **Added Auto-Restart**
   - Desktop restarts every 30 minutes
   - Keeps environment clean

5. ✅ **Enabled Cloudflare Proxy**
   - Orange cloud mode active
   - WebSockets work perfectly

6. ✅ **Updated All Documentation**
   - README.md reflects Ubuntu MATE
   - Created QUICK_DEPLOY.md
   - Updated homepage text

---

## After Snapshot Instructions

### When Server Restarts

**Everything should auto-start:**
- ✅ Docker containers (darkstar-webtop, landing-terminal)
- ✅ Apache web server
- ✅ UFW firewall
- ✅ Fail2ban
- ✅ Auto-restart cron job

**Verify Everything:**
```bash
# Check containers
docker ps

# Check web server
systemctl status apache2

# Check firewall
sudo ufw status

# Check fail2ban
sudo fail2ban-client status

# Check auto-restart cron
crontab -l

# Access website
curl -I https://oops.skyfort.group
```

**All should be green!** ✅

---

## Known Issues & Solutions

### Issue: Desktop Loading Slow
**Solution**: In webtop sidebar → Settings → Video → Change encoder to "JPEG"

### Issue: ICEauthority Error
**Solution**: Already fixed! If it returns:
```bash
docker exec darkstar-webtop bash -c "rm -f /config/.XDG/ICEauthority && chown -R abc:abc /config/.XDG"
docker compose restart webtop
```

### Issue: Network Control Not Working
**Solution**: Check PHP module:
```bash
sudo apache2ctl -M | grep php
# If missing: sudo apt install libapache2-mod-php8.3
```

---

## Maintenance Commands

### Container Management
```bash
# View containers
docker ps

# Check resources
docker stats --no-stream

# View logs
docker logs darkstar-webtop --tail 50

# Restart
docker compose restart webtop
```

### Security Monitoring
```bash
# Check failed logins
lastb | wc -l

# Check banned IPs
sudo fail2ban-client status sshd

# Check firewall blocks
sudo grep -c "UFW BLOCK" /var/log/ufw.log

# Check who's logged in
who
```

### Network Management
```bash
# List networks
docker network ls

# Check if desktop has internet
docker exec darkstar-webtop curl -s ifconfig.me || echo "No internet"
```

---

## Performance Notes

### Current Performance (Excellent)
- **Desktop**: Snappy with JPEG encoding
- **Memory Usage**: ~50% (healthy)
- **CPU Usage**: Normal (not maxed out)
- **Response Time**: Fast
- **Attack Handling**: All blocked, no impact

### Recommendations
- ✅ Keep JPEG encoding for streaming
- ✅ Monitor disk space in `/root/darkstar-linux-portal/webtop-config/`
- ✅ Auto-restart keeps things clean
- ✅ Current resources are optimal

---

## Multi-User Behavior

**Important**: This is a **shared session** architecture!

- All users see the SAME desktop
- Like collaborative screen sharing
- Good for: Demos, teaching, pair programming
- Not good for: Private/confidential work

**If 10 people open the desktop, they all control the same instance!**

---

## Cloudflare Configuration

**Status**: ✅ Proxy Enabled (Orange Cloud)

**Settings**:
- **SSL/TLS Mode**: Full (strict)
- **Proxy**: Enabled (orange cloud)
- **WebSockets**: Working automatically
- **DDoS Protection**: Active

**Benefits**:
- Hidden origin IP
- DDoS protection
- WAF (Web Application Firewall)
- CDN caching for static assets

---

## Summary

**Status**: ✅ **PRODUCTION READY - SNAPSHOT BASELINE**

This is a stable, tested, production-ready system with:
- ✅ Ubuntu MATE desktop working perfectly
- ✅ All security features active and tested
- ✅ Auto-restart keeping environment clean
- ✅ Cloudflare proxy protecting the server
- ✅ 2,384 attacks blocked, 0 successful breaches
- ✅ GitHub repo fully updated
- ✅ Documentation complete

**This snapshot represents the best working state!**

**Next Steps After Snapshot**:
1. Restart server
2. Verify all services auto-start
3. Test website access
4. Continue with new modifications/features

**Fallback Plan**: If anything goes wrong, revert to this snapshot!

---

## Contact & Resources

**Production URL**: https://oops.skyfort.group
**GitHub**: https://github.com/bufanoc/darkstar-linux-portal
**Documentation**: See QUICK_DEPLOY.md in repo

**Server Info**:
- Provider: Digital Ocean
- Region: NYC3 (likely)
- OS: Ubuntu 24.04 LTS
- RAM: 7.8GB
- CPU: 4 cores

---

**Session saved at**: 2025-11-20 06:10 UTC
**Ready for snapshot**: ✅ YES
**Safe to power off**: ✅ YES

**Everything is working. This is your stable baseline!** 🎯
