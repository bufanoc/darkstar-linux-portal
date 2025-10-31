# Dark Star Portal - Security Hardening Summary
**Date:** 2025-10-31
**Server:** eye-candy (159.203.131.190)
**Domain:** https://oops.skyfort.group

---

## Security Audit Results

### ✅ NO BREACH DETECTED
Your server was NOT compromised. All attack attempts were successfully blocked by existing security measures.

### Attack Activity Observed (All Blocked):
- **2,098+ SSH brute force attempts** from various IPs
  - Top attacker: 206.189.109.112 (336 attempts)
  - Usernames attempted: test4, tomcat, tania, rajesh, winona, etc.
  - **Status:** All blocked (password auth disabled, key-only)
- **Multiple web exploit attempts:**
  - Path traversal attempts targeting `/cgi-bin/`
  - Router vulnerability scans
  - Buffer overflow attempts
  - **Status:** All rejected with 404/400 errors

---

## Security Improvements Implemented

### 1. UFW Firewall ✅ ENABLED
**Status:** Active and enabled on system startup

**Allowed Ports:**
- Port 22/tcp - SSH (management access)
- Port 80/tcp - HTTP (Cloudflare → Apache)
- Port 443/tcp - HTTPS (Cloudflare → Apache with SSL)

**Default Policy:** Deny all incoming, allow all outgoing

**Impact:** All other ports are now blocked. Brute force attempts can still see port 22 but cannot bypass key authentication.

---

### 2. Fail2Ban ✅ INSTALLED & CONFIGURED
**Status:** Active with 6 jails monitoring

**Active Jails:**
1. **sshd** - Bans after 3 failed attempts in 10 minutes (2 hour ban)
2. **apache-auth** - Monitors Apache authentication failures
3. **apache-badbots** - Blocks known malicious bots (24 hour ban)
4. **apache-overflows** - Blocks buffer overflow attempts (24 hour ban)
5. **apache-noscript** - Blocks script kiddies (12 hour ban)
6. **apache-shellshock** - Blocks shellshock attacks (1 week ban)

**Features:**
- Incremental ban times for repeat offenders
- Maximum ban time: 1 week
- Logs: `/var/log/fail2ban.log`

**Already Banned:** 5 IPs as of initial scan

**Commands:**
```bash
fail2ban-client status              # View all jails
fail2ban-client status sshd         # View SSH jail details
fail2ban-client unban <IP>          # Unban an IP
```

---

### 3. SSH Configuration ✅ VERIFIED SECURE
**Authentication:**
- ✅ Public key only (password auth disabled)
- ✅ Root login allowed (but only with SSH keys)
- ✅ Only 1 authorized key on file

**Active Sessions:**
- 198.211.111.194 (DigitalOcean web console) - LEGITIMATE
- 108.30.195.138 (Your SSH connection via GitHub key) - LEGITIMATE

---

### 4. Network Isolation ✅ FIXED CRITICAL ISSUE

**ISSUE FOUND:** The "isolated" Docker network was NOT actually isolated - it had full internet access!

**FIXED:**
- Docker network now properly isolated via iptables
- Terminal container CANNOT reach internet (verified)
- Isolation rule made persistent via `/etc/ufw/after.rules`
- Rule automatically loaded on reboot

**Verification:**
```bash
iptables -L DOCKER-USER -n -v
# Shows: isolated0 → eth0 REJECT
```

---

### 5. Webtop Security ✅ HARDENED

**Network Control API Password:** Changed from `darkstar2025`
- New password: `Xm9909ona@+?Xm5909ona@+?`
- Hashed with Argon2ID
- Location: `/var/www/darkstar-portal/api/network-control.php`

**Webtop Login Password:** Changed from `temppassword`
- New password: `Xm9909ona@+?Xm5909ona@+?`
- Username: `darkstar`
- Location: `/root/darkstar-linux-portal/docker-compose.yml`

**Network Architecture:**
- Starts on `isolated` network (NO internet)
- Can be connected to `internet` network via password-protected API
- Internet access is opt-in, not default
- API requires password verification before any network changes

---

### 6. Monitoring System ✅ DEPLOYED

**Script:** `/usr/local/bin/darkstar-monitor.sh`

**Monitors:**
- UFW firewall status
- Fail2Ban service and banned IPs
- Apache service health
- Docker container status
- SSH attack attempts (hourly count)
- System resources (disk, memory, load)
- Network isolation rule integrity

**Logging:**
- Main log: `/var/log/darkstar-monitor.log`
- Alert log: `/var/log/darkstar-alerts.log`
- Cron log: `/var/log/darkstar-monitor-cron.log`

**Schedule:** Runs automatically every 6 hours via cron

**Manual Run:**
```bash
/usr/local/bin/darkstar-monitor.sh
```

---

## System Status Summary

### Services Running
- ✅ Apache 2.4.58 (HTTPS with Let's Encrypt)
- ✅ Docker (terminal container active)
- ✅ UFW Firewall
- ✅ Fail2Ban
- ✅ SSH (key-only authentication)

### Webtop Status
- ⏸️ Currently stopped (safe)
- Ready to deploy when needed
- Network isolation verified
- Passwords updated

### External Access
- ✅ https://oops.skyfort.group/ - Main portal (200 OK)
- ✅ https://oops.skyfort.group/terminal/ - ZORK terminal (200 OK)
- 🔜 https://oops.skyfort.group/webtop/ - Desktop (ready when started)

---

## Next Steps (Future Enhancements)

### SMS Verification System (Planned)
Your concept for SMS + email verification sounds excellent:
1. User clicks "Enable Internet"
2. Wizard explains members-only policy
3. User enters: Name, Email, Phone
4. SMS with 6-digit code sent via Twilio
5. User verifies code
6. Magic link sent to email
7. Link click → Internet enabled

**Benefits:**
- Proper authentication trail
- Rate limiting built-in (SMS cost)
- Revocable access via token expiration
- Audit log of who accessed when

**Cost Estimate:** ~$2-3/month for moderate use

### Additional Recommendations
1. **Email notifications** for security alerts
2. **Rate limiting** on network control API
3. **Session time limits** for webtop internet access
4. **Audit logging** for all API calls
5. **Web dashboard** to view security status

---

## Important Files & Locations

### Configuration Files
- UFW rules: `/etc/ufw/after.rules`
- Fail2Ban config: `/etc/fail2ban/jail.local`
- SSH config: `/etc/ssh/sshd_config`
- Docker compose: `/root/darkstar-linux-portal/docker-compose.yml`
- Network API: `/var/www/darkstar-portal/api/network-control.php`
- Apache SSL: `/etc/apache2/sites-available/terminal-portal-le-ssl.conf`

### Log Files
- Apache: `/var/log/apache2/terminal-portal-*.log`
- Fail2Ban: `/var/log/fail2ban.log`
- SSH: `/var/log/auth.log`
- Monitoring: `/var/log/darkstar-monitor.log`
- Alerts: `/var/log/darkstar-alerts.log`

### Useful Commands
```bash
# Security Status
/usr/local/bin/darkstar-monitor.sh

# Firewall
ufw status verbose

# Fail2Ban
fail2ban-client status
fail2ban-client status sshd

# Docker
docker ps
docker logs landing-terminal
docker network ls

# Monitoring
tail -f /var/log/darkstar-alerts.log
```

---

## Summary

Your Dark Star Portal is now **significantly more secure:**

1. ✅ Firewall blocking all unnecessary ports
2. ✅ Fail2Ban auto-banning attackers
3. ✅ SSH hardened (key-only, already was)
4. ✅ Network isolation properly enforced
5. ✅ Passwords changed from defaults
6. ✅ Monitoring system running every 6 hours
7. ✅ All services verified operational

**The webtop "welcome sign" has been replaced with a locked door.** 🔒

Internet access is now:
- Disabled by default
- Password-protected when enabled
- Network-isolated via iptables
- Monitored and logged

You can safely deploy webtop knowing it starts in a secure, isolated state with no internet access until explicitly granted via your password-protected API.
