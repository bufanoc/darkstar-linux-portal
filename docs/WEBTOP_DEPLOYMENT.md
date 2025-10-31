# Dark Star Webtop Deployment Guide

## Overview
Webtop container with password-protected internet access control. Internet is disabled by default for security.

## Architecture

1. **Webtop Container**: Alpine i3 desktop environment
   - Starts on `isolated` network (internal: true - no internet)
   - Can be dynamically connected to `internet` network via API
   - Accessible via Apache proxy at `/webtop/`

2. **Network Control API**: `/api/network-control.php`
   - Password-protected endpoint
   - Controls Docker network connections
   - Enables/disables internet access

3. **Landing Page UI**: Network control panel
   - Password input field
   - Enable/Disable buttons
   - Real-time status feedback

## Deployment Steps

### 1. Set Sudoers Permissions
```bash
chmod 0440 /etc/sudoers.d/www-data-docker
visudo -c
```

### 2. Generate Custom Password Hash
```bash
# Replace 'your-password-here' with your desired password
php -r "echo password_hash('your-password-here', PASSWORD_ARGON2ID) . PHP_EOL;"
```

### 3. Update API Password Hash
Edit `/var/www/darkstar-portal/api/network-control.php`:
```php
// Line 40: Replace the password_hash value
$password_hash = 'YOUR_GENERATED_HASH_HERE';
```

### 4. Deploy Webtop Container
```bash
cd /root/darkstar-linux-portal
docker compose up -d webtop
```

### 5. Verify Networks
```bash
# Check isolated network (internal)
docker network inspect darkstar-linux-portal_isolated

# Check internet network (external)
docker network inspect darkstar-linux-portal_internet

# Verify webtop is only on isolated network
docker inspect darkstar-webtop | grep NetworkMode
```

### 6. Test Access
1. Visit: https://oops.skyfort.group/
2. Scroll to "Enter the Dark Star Desktop"
3. Click "Launch Desktop Environment"
4. Desktop should load (no internet)
5. Try pinging a website - should fail
6. Enter password in Network Control panel
7. Click "Enable Internet"
8. Try pinging again - should work!

## Default Password
**Current password**: `darkstar2025`

**IMPORTANT**: Change this immediately after deployment!

## Container Resource Limits
- Memory: 2GB
- CPUs: 2.0
- Shared Memory: 1GB

## Network Security

### Isolated Network
- `internal: true` - no default gateway
- No outbound internet access
- Containers can communicate with each other
- Apache can proxy to webtop

### Internet Network
- Standard bridge network
- Full internet access
- Only connected when password-authenticated

## API Endpoints

### Enable Internet
```bash
curl -X POST https://oops.skyfort.group/api/network-control.php \
  -H "Content-Type: application/json" \
  -d '{"password":"darkstar2025","action":"enable"}'
```

### Disable Internet
```bash
curl -X POST https://oops.skyfort.group/api/network-control.php \
  -H "Content-Type: application/json" \
  -d '{"password":"darkstar2025","action":"disable"}'
```

## Troubleshooting

### Webtop won't start
```bash
cd /root/darkstar-linux-portal
docker compose logs webtop
```

### Can't access webtop through browser
```bash
# Check if port is accessible from host
curl -k https://127.0.0.1:3001

# Check Apache proxy
sudo tail -f /var/log/apache2/terminal-portal-error.log
```

### API returns permission errors
```bash
# Check sudoers file
sudo cat /etc/sudoers.d/www-data-docker

# Test sudo access as www-data
sudo -u www-data sudo /usr/bin/docker network inspect darkstar-linux-portal_internet --format='{{range .Containers}}{{.Name}}{{end}}'
```

### Internet enable/disable not working
```bash
# Manually test
sudo docker network connect darkstar-linux-portal_internet darkstar-webtop
sudo docker network disconnect darkstar-linux-portal_internet darkstar-webtop

# Check container networks
docker inspect darkstar-webtop | jq '.[0].NetworkSettings.Networks'
```

## File Locations

- Docker Compose: `/root/darkstar-linux-portal/docker-compose.yml`
- API Endpoint: `/var/www/darkstar-portal/api/network-control.php`
- Sudoers: `/etc/sudoers.d/www-data-docker`
- Apache Config: `/etc/apache2/sites-available/terminal-portal-le-ssl.conf`
- Landing Page: `/var/www/darkstar-portal/index.html`
- Network Control JS: `/var/www/darkstar-portal/script.js`

## Security Notes

1. **Change the default password immediately!**
2. The password is hashed with Argon2ID (secure)
3. Sudoers file is restricted to specific docker commands only
4. Container runs with limited resources
5. Internal network has no internet gateway
6. WebSocket connections are encrypted via HTTPS

## Next Steps

1. Change default password
2. Deploy container
3. Test network isolation
4. Test password-protected internet toggle
5. Monitor resource usage
6. Consider adding:
   - Rate limiting on API
   - Logging of network state changes
   - Auto-disconnect after timeout
   - Multi-user support
