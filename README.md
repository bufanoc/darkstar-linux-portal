# Terminal Portal 🌍

A retro-futuristic landing page featuring an interactive, isolated Ubuntu 24.04 terminal accessible directly from your browser.

## Features

- **Dark-themed landing page** with animated purple Earth silhouette
- **Scroll animations** with parallax effects and staggered fade-ins
- **Embedded Ubuntu 24.04 terminal** using ttyd
- **Secure Docker isolation** with strict security policies
- **WebSocket-powered** terminal emulation

## Architecture

```
┌─────────────────┐
│   Cloudflare    │ → Port 80
└────────┬────────┘
         │
┌────────▼────────┐
│   Apache 2.4    │ → Serves landing page
│   (Port 80)     │ → Proxies /terminal/ to ttyd
└────────┬────────┘
         │
┌────────▼────────────────────┐
│   Docker Container          │
│   ┌─────────────────────┐   │
│   │  ttyd (Port 7681)   │   │
│   │  Ubuntu 24.04       │   │
│   │  (guestuser)        │   │
│   └─────────────────────┘   │
│                             │
│  Security:                  │
│  • No network access        │
│  • Read-only filesystem     │
│  • 256MB RAM limit          │
│  • 0.5 CPU limit            │
│  • Minimal privileges       │
└─────────────────────────────┘
```

## Installation

### Prerequisites
- Ubuntu 24.04 LTS
- Docker & Docker Compose
- Apache 2.4
- Sudo access

### Quick Start

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd terminal-landing
   ```

2. **Build and start the Docker container**
   ```bash
   docker compose up -d
   ```

3. **Install and configure Apache**
   ```bash
   sudo apt-get update
   sudo apt-get install -y apache2
   sudo a2enmod proxy proxy_http proxy_wstunnel rewrite
   ```

4. **Configure Apache site**
   ```bash
   sudo cp /etc/apache2/sites-available/terminal-portal.conf /etc/apache2/sites-available/
   sudo a2dissite 000-default
   sudo a2ensite terminal-portal
   sudo systemctl restart apache2
   ```

5. **Access your landing page**
   ```
   http://your-server-ip/
   ```

## File Structure

```
terminal-landing/
├── container/
│   └── Dockerfile           # Ubuntu 24.04 + ttyd container
├── www/
│   ├── index.html          # Landing page
│   ├── style.css           # Dark theme with animations
│   └── script.js           # Scroll effects & interactions
├── docker-compose.yml      # Container configuration
└── README.md
```

## Security Features

- **Container Isolation**: Runs in locked-down Docker container
- **Network Isolation**: Container on isolated bridge network
- **Read-only Filesystem**: Only /tmp and /home/guestuser are writable
- **Resource Limits**: 256MB RAM, 0.5 CPU cores
- **Minimal Privileges**: All capabilities dropped except necessary ones
- **Localhost Only**: Terminal port only accessible via Apache proxy
- **Non-root User**: Terminal runs as unprivileged guestuser

## Available Commands in Terminal

The terminal includes:
- Core utilities: ls, pwd, cd, cat, echo, whoami, etc.
- System tools: uname, uptime, df, du, free, hostname
- Text processing: grep, sed, awk, wc, head, tail, sort
- File operations: touch, mkdir, cp, mv, rm, find
- Fun tools: neofetch, figlet

## Management Commands

```bash
# View container logs
docker logs landing-terminal

# Restart container
docker compose restart

# Restart Apache
sudo systemctl restart apache2

# View Apache logs
sudo tail -f /var/log/apache2/terminal-portal-access.log

# Stop everything
docker compose down
```

## Customization

### Adding more commands to the terminal

Edit `container/Dockerfile` and add packages:
```dockerfile
RUN apt-get install -y \
    your-package-here
```

Then rebuild:
```bash
docker compose down
docker compose build
docker compose up -d
```

### Changing the color theme

Edit `www/style.css` and modify the CSS variables:
```css
:root {
    --bg-dark: #0a0a0f;
    --purple-primary: #a855f7;
    /* ... */
}
```

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Web Server**: Apache 2.4
- **Container**: Docker & Docker Compose
- **Terminal**: ttyd (WebSocket terminal)
- **OS**: Ubuntu 24.04 LTS

## License

MIT License - Feel free to use and modify!

## Credits

Built with Docker, ttyd, and a passion for retro-futuristic design.
