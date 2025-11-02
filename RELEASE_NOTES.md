# Dark Star Portal - Release Notes

## Version 1.0 "Folsom" - First Major Release

**Release Date**: November 2, 2025
**Codename**: Folsom (honoring the OpenStack project)
**Status**: Production Ready

---

## Overview

Dark Star Portal v1.0 "Folsom" marks the first production-ready release of a secure, browser-accessible Linux desktop environment with innovative password-protected network control.

This release represents the culmination of extensive development, testing, and refinement to deliver a robust, secure, and user-friendly platform for accessing full-featured Linux desktops from anywhere via HTTPS.

---

## What's New in v1.0

### Core Features

#### Browser-Accessible Desktop Environment
- **Fedora KDE Plasma Desktop** - Full-featured modern desktop environment
- **Ubuntu Terminal** - Integrated command-line access via ttyd
- **WebSocket Streaming** - Smooth, responsive desktop interaction
- **No Client Software** - Access from any modern web browser

#### Network Isolation & Control
- **Network Isolation by Default** - Desktop starts with zero internet access
- **Password-Protected Network Control** - Argon2ID-hashed authentication
- **REST API** - Programmatic control over network access
- **Real-Time Status** - Instant feedback on network state

#### Security Features
- **SSL/TLS Encryption** - Let's Encrypt certificates with Cloudflare integration
- **Docker Isolation** - Separate containers for terminal and desktop
- **Resource Limits** - CPU and memory constraints prevent abuse
- **Isolated Networks** - Iptables-enforced network separation

#### Deployment & Operations
- **Automated Deployment** - One-command installation script
- **AI-Friendly Documentation** - Comprehensive guides for AI assistants
- **Session Continuity** - Detailed state tracking for seamless handoffs
- **Production-Tested** - Verified in live production environments

---

## Key Components

### Desktop Environment
- **Image**: `lscr.io/linuxserver/webtop:fedora-kde`
- **RAM**: 3GB allocation
- **CPU**: 2.0 cores
- **Shared Memory**: 2GB
- **Network**: Isolated by default, internet opt-in

### Terminal Container
- **Image**: Custom Ubuntu 24.04 + ttyd build
- **RAM**: 256MB allocation
- **CPU**: 0.5 cores
- **Network**: Isolated

### Web Server Stack
- **Apache 2.4** - HTTP server and WebSocket proxy
- **PHP 8.3** - Network control API backend
- **Cloudflare CDN** - SSL/TLS termination and DDoS protection

### Network Architecture
- **Isolated Network** (`isolated0`) - Default, no internet
- **Internet Network** (`internet0`) - Optional, password-protected
- **Network Control API** - PHP REST endpoint with Argon2ID auth

---

## System Requirements

### Minimum Specifications
- **RAM**: 4GB minimum (8GB recommended)
- **CPU**: 2 cores minimum
- **Storage**: 20GB available disk space
- **OS**: Ubuntu 24.04 LTS
- **Network**: Public IP or domain name

### Software Dependencies
- Docker & Docker Compose (v2+)
- Apache 2.4
- PHP 8.3 with Apache module
- Let's Encrypt certbot
- Git

---

## Installation

### Quick Start
```bash
git clone https://github.com/bufanoc/darkstar-linux-portal.git
cd darkstar-linux-portal
sudo ./scripts/deploy.sh
```

### Manual Installation
See [DEPLOY.md](DEPLOY.md) for detailed step-by-step instructions.

---

## Breaking Changes from Alpha

None - this is the first major release. The alpha branch has been promoted to main with no breaking changes.

---

## Known Issues

### Minor Issues
- **Cache Files in Git**: Webtop runtime cache files occasionally appear in git status (excluded via .gitignore, safe to ignore)
- **Apache Warning**: "Could not reliably determine the server's fully qualified domain name" warning (cosmetic, does not affect functionality)

### Workarounds
All known issues have documented workarounds in the troubleshooting section of README.md.

---

## Documentation

Comprehensive documentation included:

### User Documentation
- **README.md** - Main project documentation
- **DEPLOY.md** - Detailed deployment guide
- **AFTER_RESIZE.md** - Post-resize procedures

### Technical Documentation
- **session_continuity.md** - Current system state and status
- **docs/SECURITY_HARDENING.md** - Advanced security configuration
- **docs/WEBTOP_DEPLOYMENT.md** - Desktop environment details
- **docs/SMS_VERIFICATION_PLAN.md** - Future enhancement plans

---

## Testing & Verification

### Production Validation
- ✅ Deployed on Digital Ocean droplet (7.8GB RAM, 2 CPUs)
- ✅ SSL/TLS certificates validated with Let's Encrypt
- ✅ Cloudflare CDN integration tested
- ✅ Network isolation verified via iptables inspection
- ✅ Password-protected network control functional
- ✅ Resource limits enforced and stable
- ✅ Desktop environment loads and operates smoothly
- ✅ Terminal container operational
- ✅ API endpoints tested and verified

### Performance Metrics
- **Desktop Memory Usage**: 1GB / 3GB (34% utilization)
- **System Memory**: 1.9GB / 7.8GB (excellent headroom)
- **CPU Usage**: Minimal at idle, responsive under load
- **Container Startup**: ~30-60 seconds for full desktop initialization

---

## Security Audit

### Security Features Implemented
- ✅ Network isolation by default
- ✅ Password-protected internet access (Argon2ID hashing)
- ✅ SSL/TLS encryption (Let's Encrypt + Cloudflare)
- ✅ Docker security constraints (capabilities dropped, read-only where possible)
- ✅ Resource limits (memory, CPU, shared memory)
- ✅ Localhost-only container ports
- ✅ Sudo configuration for network control API

### Optional Security Features (Available but Disabled)
- UFW firewall rules
- Fail2Ban brute-force protection
- System monitoring and alerting

See [SECURITY_HARDENING_SUMMARY.md](SECURITY_HARDENING_SUMMARY.md) for full details.

---

## Migration Guide

### From Alpha to v1.0

Since this is the first major release, alpha users can simply update their branches:

```bash
git checkout main
git pull origin main
```

No configuration changes required - all settings are backward compatible.

---

## Credits

### Development
- Primary development and testing
- AI-assisted development with Claude Code
- Community feedback and testing

### Technologies
- [LinuxServer.io](https://www.linuxserver.io/) - Webtop Docker images
- [ttyd](https://github.com/tsl0922/ttyd) - Terminal over WebSocket
- [Let's Encrypt](https://letsencrypt.org/) - SSL/TLS certificates
- [Apache](https://httpd.apache.org/) - Web server
- [Docker](https://www.docker.com/) - Containerization
- [Cloudflare](https://www.cloudflare.com/) - CDN and SSL

### Inspiration
Named after the Folsom release of OpenStack, honoring the open-source infrastructure community and the spirit of building accessible, powerful platforms.

---

## Roadmap to v1.1 "Grizzly"

### Planned Features
- Multi-user support with individual sessions
- SMS/Email verification for network access
- Session time limits and auto-disconnect
- Web-based monitoring dashboard
- Rate limiting for API endpoints
- Enhanced audit logging
- User management interface
- Persistent storage options

### Community Requests
Feature requests and bug reports welcome via GitHub Issues.

---

## Support

### Getting Help
- **Documentation**: Comprehensive docs in repository
- **Issues**: GitHub Issues tracker
- **Security**: Report security issues privately

### Contributing
Contributions welcome! See README.md for contribution guidelines.

---

## Changelog

### v1.0 "Folsom" (2025-11-02)

#### Added
- Fedora KDE Plasma desktop environment
- Ubuntu 24.04 terminal container
- Network isolation with password-protected control
- SSL/TLS encryption via Let's Encrypt
- Cloudflare CDN integration
- Automated deployment script
- Network Control REST API
- Comprehensive documentation
- Session continuity tracking
- Security hardening options
- Resource allocation controls
- Apache + PHP web stack
- Docker network isolation architecture

#### Changed
- Project name: Terminal Portal → Dark Star Portal
- Desktop environment: Ubuntu KDE → Fedora KDE
- Resource allocation: Optimized for 4GB+ servers
- Documentation: Complete rewrite for v1.0

#### Fixed
- Memory exhaustion issues (resolved via proper resource allocation)
- PHP execution in Apache (installed PHP module)
- Docker network creation (manual creation documented)
- SSL/TLS certificate configuration
- Network control API functionality

#### Security
- Argon2ID password hashing for network control
- Network isolation enforced by default
- SSL/TLS required for all connections
- Docker security constraints applied
- Resource limits prevent resource exhaustion attacks

---

## License

MIT License - See LICENSE file for details.

---

## Acknowledgments

Special thanks to:
- The open-source community
- LinuxServer.io team for excellent Docker images
- Let's Encrypt for free SSL/TLS certificates
- OpenStack community for inspiration
- All contributors and testers

---

**Dark Star Portal v1.0 "Folsom"**
*First Major Release - Production Ready*
*Experience Linux, Anywhere*
