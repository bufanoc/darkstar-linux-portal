// Scroll Animation Observer
const observerOptions = {
    threshold: 0.15,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

// Observe all fade-in elements
document.addEventListener('DOMContentLoaded', () => {
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach(el => observer.observe(el));

    // Check authentication status
    checkAuthStatus();

    // Interactive feature cards smooth scroll
    const interactiveCards = document.querySelectorAll('.feature-card.interactive');
    interactiveCards.forEach(card => {
        card.addEventListener('click', () => {
            const targetId = card.getAttribute('data-scroll-to');
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                // Add click animation
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.style.transform = '';
                }, 150);

                // Smooth scroll to target
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });

        // Add cursor pointer hint
        card.style.cursor = 'pointer';
    });

    // Smooth scroll for scroll indicator
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', () => {
            document.querySelector('.about').scrollIntoView({
                behavior: 'smooth'
            });
        });
    }

    // Add parallax effect to earth on scroll
    let lastScroll = 0;
    const earth = document.querySelector('.earth-container');

    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallaxSpeed = 0.5;

        if (earth && scrolled < window.innerHeight) {
            earth.style.transform = `translate(-50%, calc(-50% + ${scrolled * parallaxSpeed}px))`;
            earth.style.opacity = 1 - (scrolled / window.innerHeight) * 1.5;
        }

        lastScroll = scrolled;
    });

    // Terminal iframe load handler
    const terminalIframe = document.querySelector('.terminal-embed iframe');
    if (terminalIframe) {
        terminalIframe.addEventListener('load', () => {
            console.log('Terminal connected successfully');
        });

        terminalIframe.addEventListener('error', () => {
            console.error('Failed to load terminal');
        });
    }

    // Add stagger animation to feature cards
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });

    // Enhance glitch effect on hover
    const glitchTitle = document.querySelector('.glitch');
    if (glitchTitle) {
        glitchTitle.addEventListener('mouseenter', () => {
            glitchTitle.style.animation = 'none';
            setTimeout(() => {
                glitchTitle.style.animation = 'glitchAnim 5s infinite';
            }, 10);
        });
    }
});

// Add dynamic star twinkling
function addTwinkle() {
    const stars = document.querySelector('.stars');
    if (!stars) return;

    const twinkle = document.createElement('div');
    twinkle.style.position = 'absolute';
    twinkle.style.width = '2px';
    twinkle.style.height = '2px';
    twinkle.style.background = '#fff';
    twinkle.style.borderRadius = '50%';
    twinkle.style.left = `${Math.random() * 100}%`;
    twinkle.style.top = `${Math.random() * 100}%`;
    twinkle.style.animation = 'twinkle 2s ease-in-out';
    twinkle.style.pointerEvents = 'none';

    stars.appendChild(twinkle);

    setTimeout(() => twinkle.remove(), 2000);
}

// Add CSS for twinkle animation dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes twinkle {
        0%, 100% { opacity: 0; }
        50% { opacity: 1; }
    }
`;
document.head.appendChild(style);

// Periodically add twinkling stars
setInterval(addTwinkle, 500);

// Performance optimization: Reduce animations on low-performance devices
if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) {
    document.body.classList.add('low-performance');
}

// Log version info
console.log('%c Terminal Portal v1.0 ', 'background: #a855f7; color: white; padding: 5px 10px; border-radius: 3px;');
console.log('%c Built with Docker, ttyd, Apache ', 'color: #c084fc;');


// Network Control for Webtop
async function controlNetwork(action) {
    const password = document.getElementById('network-password').value;
    const statusEl = document.getElementById('network-status');

    if (!password) {
        statusEl.textContent = '⚠️ Please enter a password';
        statusEl.style.background = 'rgba(255, 165, 0, 0.2)';
        statusEl.style.border = '1px solid rgba(255, 165, 0, 0.5)';
        statusEl.style.color = '#ffa500';
        statusEl.style.display = 'block';
        return;
    }

    // Show loading state
    statusEl.textContent = `${action === 'enable' ? '🌐' : '🚫'} Processing...`;
    statusEl.style.background = 'rgba(168, 85, 247, 0.2)';
    statusEl.style.border = '1px solid rgba(168, 85, 247, 0.5)';
    statusEl.style.color = '#c084fc';
    statusEl.style.display = 'block';

    try {
        const response = await fetch('/api/network-control.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                password: password,
                action: action
            })
        });

        const data = await response.json();

        if (data.success) {
            statusEl.textContent = `✅ ${data.message}`;
            statusEl.style.background = 'rgba(39, 201, 63, 0.2)';
            statusEl.style.border = '1px solid rgba(39, 201, 63, 0.5)';
            statusEl.style.color = '#27c93f';

            // Clear password field on success
            document.getElementById('network-password').value = '';
        } else {
            statusEl.textContent = `❌ ${data.error}`;
            statusEl.style.background = 'rgba(255, 95, 86, 0.2)';
            statusEl.style.border = '1px solid rgba(255, 95, 86, 0.5)';
            statusEl.style.color = '#ff5f56';
        }
    } catch (error) {
        statusEl.textContent = '❌ Network request failed';
        statusEl.style.background = 'rgba(255, 95, 86, 0.2)';
        statusEl.style.border = '1px solid rgba(255, 95, 86, 0.5)';
        statusEl.style.color = '#ff5f56';
        console.error('Network control error:', error);
    }
}

// Authentication Status Check
async function checkAuthStatus() {
    try {
        const response = await fetch('/api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check' })
        });

        const data = await response.json();

        const authLoggedOut = document.getElementById('authLoggedOut');
        const authLoggedIn = document.getElementById('authLoggedIn');
        const authUsername = document.getElementById('authUsername');
        const adminDashboardLink = document.getElementById('adminDashboardLink');
        const networkControl = document.getElementById('network-control');
        const cronControl = document.getElementById('cron-control');

        if (data.logged_in) {
            // Show logged in state
            authLoggedOut.style.display = 'none';
            authLoggedIn.style.display = 'flex';
            authUsername.textContent = data.user.username;

            // Show admin controls if admin
            if (data.user.role === 'admin') {
                adminDashboardLink.style.display = 'block';
                if (networkControl) networkControl.style.display = 'block';
                if (cronControl) cronControl.style.display = 'block';
            } else {
                if (networkControl) networkControl.style.display = 'none';
                if (cronControl) cronControl.style.display = 'none';
            }

            // Setup logout button
            document.getElementById('logoutBtn').addEventListener('click', async () => {
                await fetch('/api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });
                window.location.reload();
            });
        } else {
            // Show logged out state
            authLoggedOut.style.display = 'flex';
            authLoggedIn.style.display = 'none';

            // Hide admin controls
            if (networkControl) networkControl.style.display = 'none';
            if (cronControl) cronControl.style.display = 'none';
        }
    } catch (error) {
        console.error('Auth check error:', error);
        // Show logged out state on error
        document.getElementById('authLoggedOut').style.display = 'flex';
        document.getElementById('authLoggedIn').style.display = 'none';
    }
}

// Network Control for Webtop
async function controlNetwork(action) {
    const statusEl = document.getElementById('network-status');

    // Show loading state
    statusEl.textContent = `${action === 'enable' ? '🌐' : '🚫'} Processing...`;
    statusEl.style.background = 'rgba(168, 85, 247, 0.2)';
    statusEl.style.border = '1px solid rgba(168, 85, 247, 0.5)';
    statusEl.style.color = '#c084fc';
    statusEl.style.display = 'block';

    try {
        const response = await fetch('/api/network-control.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action
            })
        });

        const data = await response.json();

        if (data.success) {
            statusEl.textContent = `✅ ${data.message}`;
            statusEl.style.background = 'rgba(39, 201, 63, 0.2)';
            statusEl.style.border = '1px solid rgba(39, 201, 63, 0.5)';
            statusEl.style.color = '#27c93f';
        } else {
            statusEl.textContent = `❌ ${data.error}`;
            statusEl.style.background = 'rgba(255, 95, 86, 0.2)';
            statusEl.style.border = '1px solid rgba(255, 95, 86, 0.5)';
            statusEl.style.color = '#ff5f56';
        }
    } catch (error) {
        statusEl.textContent = '❌ Network request failed';
        statusEl.style.background = 'rgba(255, 95, 86, 0.2)';
        statusEl.style.border = '1px solid rgba(255, 95, 86, 0.5)';
        statusEl.style.color = '#ff5f56';
        console.error('Network control error:', error);
    }
}

// Cron Control for Auto-Restart
async function controlCron(action) {
    const statusEl = document.getElementById('cron-status');

    // Show loading state
    const actionText = action === 'pause' ? 'Pausing' : 'Resuming';
    statusEl.textContent = `⏱️ ${actionText}...`;
    statusEl.style.background = 'rgba(168, 85, 247, 0.2)';
    statusEl.style.border = '1px solid rgba(168, 85, 247, 0.5)';
    statusEl.style.color = '#c084fc';
    statusEl.style.display = 'block';

    try {
        const response = await fetch('/api/network-control.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: `cron-${action}`
            })
        });

        const data = await response.json();

        if (data.success) {
            const emoji = action === 'pause' ? '⏸️' : '▶️';
            statusEl.textContent = `${emoji} ${data.message}`;
            statusEl.style.background = 'rgba(39, 201, 63, 0.2)';
            statusEl.style.border = '1px solid rgba(39, 201, 63, 0.5)';
            statusEl.style.color = '#27c93f';
        } else {
            statusEl.textContent = `❌ ${data.error}`;
            statusEl.style.background = 'rgba(255, 95, 86, 0.2)';
            statusEl.style.border = '1px solid rgba(255, 95, 86, 0.5)';
            statusEl.style.color = '#ff5f56';
        }
    } catch (error) {
        statusEl.textContent = '❌ Request failed';
        statusEl.style.background = 'rgba(255, 95, 86, 0.2)';
        statusEl.style.border = '1px solid rgba(255, 95, 86, 0.5)';
        statusEl.style.color = '#ff5f56';
        console.error('Cron control error:', error);
    }
}
