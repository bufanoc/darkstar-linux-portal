# Dark Star Portal

**Version 1.0 "Folsom"** - First Major Release

A secure, browser-accessible Linux desktop environment with password-protected network control. Experience a full-featured Fedora KDE Plasma desktop and Ubuntu terminal directly in your browser, with enterprise-grade security and network isolation.

---

## Overview

Dark Star Portal delivers a complete Linux desktop environment through your web browser, combining powerful features with thoughtful security. Launch applications, browse files, and work in a familiar desktop environment - all accessible from anywhere via HTTPS.

**Key Innovation**: Network isolation by default with password-protected internet access control, ensuring your desktop environment is secure until you explicitly grant it network access.

---

## Features

### Desktop Environment
- **Fedora KDE Plasma** - Full-featured, modern desktop experience
- **Ubuntu Terminal** - Integrated ttyd-powered terminal
- **Browser-Based Access** - No client software required
- **WebSocket Streaming** - Smooth, responsive desktop interaction

### Security
- **Network Isolation** - Desktop starts with zero internet access
- **Password-Protected Network Control** - Argon2ID-hashed authentication
- **SSL/TLS Encryption** - Let's Encrypt certificates with Cloudflare integration
- **Resource Limits** - CPU and memory constraints prevent abuse
- **Read-Only Filesystems** - Where possible, for enhanced security
- **Isolated Docker Networks** - Separate networks for isolated and internet-connected modes

### Deployment
- **Automated Setup** - One-command deployment script
- **AI-Friendly Documentation** - Comprehensive guides for AI assistants
- **Session Continuity** - Detailed state tracking for seamless handoffs
- **Production-Ready** - Tested and verified in live environments

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Cloudflare CDN                        │
│                    (SSL/TLS Termination)                     │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTPS (443)
┌──────────────────────────▼──────────────────────────────────┐
│                     Apache 2.4 + PHP 8.3                     │
│  ┌────────────────┐  ┌────────────────┐  ┌───────────────┐ │
│  │  Landing Page  │  │ Network Control │  │  Proxy Layer  │ │
│  │    (Static)    │  │   API (PHP)     │  │  (WebSocket)  │ │
│  └────────────────┘  └────────────────┘  └───────────────┘ │
└──────────────────────────┬──────────────────────────────────┘
                           │
        ┌──────────────────┴──────────────────┐
        │                                     │
┌───────▼────────┐                  ┌─────────▼──────────┐
│   Docker:      │                  │   Docker:          │
│   Terminal     │                  │   Webtop Desktop   │
│                │                  │                    │
│ • Ubuntu 24.04 │                  │ • Fedora KDE       │
│ • ttyd         │                  │ • 3GB RAM          │
│ • 256MB RAM    │                  │ • 2.0 CPUs         │
│ • 0.5 CPU      │                  │ • 2GB Shared Mem   │
│ • Isolated Net │                  │ • Isolated Net     │
└────────────────┘                  └─────────┬──────────┘
                                              │
                                   Network Control API
                                              │
                                    ┌─────────▼──────────┐
                                    │ Internet Network   │
                                    │ (Optional, Auth)   │
                                    └────────────────────┘
```

### Network Isolation Architecture

1. **Isolated Network** (`isolated0`)
   - Default network for desktop and terminal
   - No external internet access
   - Internal communication only
   - Iptables rules enforce isolation

2. **Internet Network** (`internet0`)
   - Optional secondary network
   - Connected only via password-protected API
   - Provides external internet access
   - User-controlled, opt-in only

3. **Network Control API**
   - PHP-based REST endpoint
   - Argon2ID password hashing
   - Docker network connect/disconnect via sudo
   - Real-time status feedback

---

## System Requirements

### Minimum Requirements
- **RAM**: 4GB (8GB recommended)
- **CPU**: 2 cores minimum
- **Storage**: 20GB available
- **OS**: Ubuntu 24.04 LTS
- **Network**: Public IP or domain name

### Software Dependencies
- Docker & Docker Compose
- Apache 2.4 with PHP 8.3
- Let's Encrypt certbot (for SSL)
- Git

---

## Quick Start

### Automated Deployment

The fastest way to get started:

```bash
# Clone the repository
git clone https://github.com/bufanoc/darkstar-linux-portal.git
cd darkstar-linux-portal

# Run automated deployment
sudo ./scripts/deploy.sh
```

The deployment script will:
- Install all dependencies (Docker, Apache, PHP)
- Configure SSL/TLS certificates
- Set up network isolation
- Deploy containers
- Configure security settings

### Manual Deployment

For detailed control, see [DEPLOY.md](DEPLOY.md) for comprehensive step-by-step instructions.

---

## Configuration

### Environment Variables

Edit `config/deployment.env`:

```bash
DOMAIN=your-domain.com
NETWORK_CONTROL_PASSWORD=your-secure-password
TIMEZONE=America/New_York
```

### Network Control Password

Generate a new password hash:

```bash
php -r "echo password_hash('your-password', PASSWORD_ARGON2ID) . PHP_EOL;"
```

Update in `/var/www/darkstar-portal/api/network-control.php`:

```php
$password_hash = 'your-generated-hash';
```

### Resource Allocation

Adjust container resources in `docker-compose.yml`:

```yaml
webtop:
  mem_limit: 3g      # RAM allocation
  cpus: 2.0          # CPU cores
  shm_size: "2gb"    # Shared memory
```

---

## Usage

### Accessing the Portal

1. Navigate to your domain: `https://your-domain.com`
2. Click "Launch Desktop Environment" for the full KDE desktop
3. Click "Access Terminal" for command-line access

### Enabling Internet Access

1. Open the Network Control panel on the landing page
2. Enter your network control password
3. Click "Enable Internet"
4. Desktop now has internet access
5. Click "Disable Internet" to revoke access

### API Usage

Enable internet programmatically:

```bash
curl -X POST https://your-domain.com/api/network-control.php \
  -H "Content-Type: application/json" \
  -d '{"password":"your-password","action":"enable"}'
```

Disable internet:

```bash
curl -X POST https://your-domain.com/api/network-control.php \
  -H "Content-Type: application/json" \
  -d '{"password":"your-password","action":"disable"}'
```

---

## Security

### Default Security Posture

- **Network Isolated**: Desktop has zero internet access by default
- **SSL/TLS Required**: All traffic encrypted via Let's Encrypt
- **Password Protected**: Network control requires authentication
- **Resource Limited**: Containers cannot consume excessive resources
- **Capability Dropped**: Minimal Linux capabilities granted

### Optional Security Hardening

Additional security features available but disabled by default:

- **UFW Firewall**: System-level firewall rules
- **Fail2Ban**: Automatic IP banning for brute force attempts
- **System Monitoring**: Automated health checks and alerts

See [SECURITY_HARDENING_SUMMARY.md](SECURITY_HARDENING_SUMMARY.md) for details.

### Security Considerations

- **Change Default Passwords**: Update network control password immediately
- **Monitor Resource Usage**: Check `docker stats` regularly
- **Review Access Logs**: Monitor who's accessing your portal
- **Keep System Updated**: Regularly update packages and containers

---

## Documentation

Comprehensive documentation available:

- **[DEPLOY.md](DEPLOY.md)** - Detailed deployment guide with AI-friendly instructions
- **[AFTER_RESIZE.md](AFTER_RESIZE.md)** - Post-resize checklist and procedures
- **[session_continuity.md](session_continuity.md)** - Current system status and session state
- **[docs/SECURITY_HARDENING.md](docs/SECURITY_HARDENING.md)** - Advanced security configuration
- **[docs/WEBTOP_DEPLOYMENT.md](docs/WEBTOP_DEPLOYMENT.md)** - Desktop environment details

---

## Management

### Container Management

```bash
# View running containers
docker ps

# Check resource usage
docker stats darkstar-webtop --no-stream

# View logs
docker logs darkstar-webtop --tail 50
docker logs landing-terminal --tail 50

# Restart containers
docker compose restart
```

### Apache Management

```bash
# Restart Apache
systemctl restart apache2

# Check status
systemctl status apache2

# View logs
tail -f /var/log/apache2/terminal-portal-error.log
```

### Network Management

```bash
# List Docker networks
docker network ls

# Inspect internet network
docker network inspect darkstar-linux-portal_internet

# Manually connect/disconnect
docker network connect darkstar-linux-portal_internet darkstar-webtop
docker network disconnect darkstar-linux-portal_internet darkstar-webtop
```

---

## Troubleshooting

### Desktop Won't Load

```bash
# Check container status
docker ps

# Check logs for errors
docker logs darkstar-webtop --tail 100

# Verify resource allocation
docker stats darkstar-webtop --no-stream

# Restart container
docker compose restart webtop
```

### Network Control Not Working

```bash
# Verify PHP module enabled
apache2ctl -M | grep php

# Check API accessibility
curl https://your-domain.com/api/network-control.php

# Review Apache error logs
tail -f /var/log/apache2/terminal-portal-error.log

# Test password hash
php -r "var_dump(password_verify('your-password', 'hash-from-api'));"
```

### SSL Certificate Issues

```bash
# Check certificate status
certbot certificates

# Renew certificates
certbot renew --dry-run

# Force renewal
certbot renew --force-renewal
```

---

## Project Structure

```
darkstar-linux-portal/
├── config/                          # Configuration files
│   ├── apache/                      # Apache virtual host configs
│   ├── deployment.env.example       # Environment template
│   ├── fail2ban/                    # Fail2Ban jail configs
│   ├── sudoers.d/                   # Sudo permissions for API
│   └── ufw/                         # Firewall rules
├── container/                       # Docker container definitions
│   └── Dockerfile                   # Terminal container build
├── docs/                            # Additional documentation
│   ├── SECURITY_HARDENING.md
│   ├── SESSION_CONTINUITY.md
│   ├── SMS_VERIFICATION_PLAN.md
│   └── WEBTOP_DEPLOYMENT.md
├── scripts/                         # Automation scripts
│   ├── deploy.sh                    # Automated deployment
│   ├── setup-ssl.sh                 # SSL certificate setup
│   └── darkstar-monitor.sh          # System monitoring
├── www/                             # Web files
│   ├── index.html                   # Landing page
│   ├── style.css                    # Styling
│   └── script.js                    # Frontend logic
├── docker-compose.yml               # Container orchestration
├── README.md                        # This file
├── DEPLOY.md                        # Deployment guide
├── AFTER_RESIZE.md                  # Post-resize procedures
└── session_continuity.md            # Current system state
```

---

## Technology Stack

### Frontend
- HTML5, CSS3, JavaScript
- WebSocket for terminal/desktop streaming
- Responsive design

### Backend
- Apache 2.4 + PHP 8.3
- Docker & Docker Compose
- Let's Encrypt SSL/TLS

### Desktop Environment
- Fedora KDE Plasma (via LinuxServer.io webtop)
- Ubuntu 24.04 terminal (via ttyd)

### Security
- Argon2ID password hashing
- Iptables network isolation
- Docker security constraints
- SSL/TLS encryption

---

## Roadmap

### v1.1 "Grizzly" (Future)
- Multi-user support with individual sessions
- SMS/Email verification for network access
- Session time limits and auto-disconnect
- Web-based monitoring dashboard
- Rate limiting for API endpoints
- Enhanced audit logging

### Community Requests
- User-submitted feature requests welcome
- Contribute via GitHub Issues
- Pull requests encouraged

---

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

See issues for feature requests and bugs.

---

## Credits

### Built With
- [LinuxServer.io](https://www.linuxserver.io/) - Webtop Docker images
- [ttyd](https://github.com/tsl0922/ttyd) - Terminal over WebSocket
- [Let's Encrypt](https://letsencrypt.org/) - Free SSL/TLS certificates
- [Apache](https://httpd.apache.org/) - Web server
- [Docker](https://www.docker.com/) - Containerization

### Inspiration
Named after the Folsom release of OpenStack, honoring the open-source infrastructure community.

---

## License

MIT License - See LICENSE file for details

---

## Support

- **Documentation**: See docs/ directory
- **Issues**: GitHub Issues tracker
- **Security**: Report security issues privately via GitHub

---

## Acknowledgments

Special thanks to the open-source community and all contributors who made this project possible.

Built with passion for secure, accessible Linux desktop environments.

---

**Dark Star Portal v1.0 "Folsom"**
*Experience Linux, Anywhere*
