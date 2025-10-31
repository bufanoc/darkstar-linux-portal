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

/* ============================================
   AUTHENTICATION DISABLED - UNCOMMENT TO RE-ENABLE
   ============================================

// Authentication and Modal System
let csrfToken = '';

// Fetch CSRF token on page load
async function fetchCSRFToken() {
    try {
        const response = await fetch('/api/csrf-token.php');
        const data = await response.json();
        csrfToken = data.csrf_token;
    } catch (error) {
        console.error('Failed to fetch CSRF token:', error);
    }
}

// Check authentication status
async function checkAuth() {
    try {
        const response = await fetch('/api/check-auth.php');
        const data = await response.json();

        if (data.authenticated) {
            // Show user menu, hide login/signup buttons
            document.getElementById('navButtons').style.display = 'none';
            document.getElementById('navUser').style.display = 'flex';
            document.getElementById('userGreeting').textContent = `Welcome, ${data.user.username}`;
        } else {
            // Show login/signup buttons, hide user menu
            document.getElementById('navButtons').style.display = 'flex';
            document.getElementById('navUser').style.display = 'none';
        }
    } catch (error) {
        console.error('Auth check failed:', error);
    }
}

// Modal handling
const signupModal = document.getElementById('signupModal');
const loginModal = document.getElementById('loginModal');
const btnSignup = document.getElementById('btnSignup');
const btnLogin = document.getElementById('btnLogin');
const btnLogout = document.getElementById('btnLogout');
const closeSignup = document.getElementById('closeSignup');
const closeLogin = document.getElementById('closeLogin');

// Open modals
btnSignup?.addEventListener('click', () => {
    signupModal.classList.add('show');
});

btnLogin?.addEventListener('click', () => {
    loginModal.classList.add('show');
});

// Close modals
closeSignup?.addEventListener('click', () => {
    signupModal.classList.remove('show');
    document.getElementById('signupForm').reset();
    document.getElementById('signupMessage').className = 'form-message';
});

closeLogin?.addEventListener('click', () => {
    loginModal.classList.remove('show');
    document.getElementById('loginForm').reset();
    document.getElementById('loginMessage').className = 'form-message';
});

// Close modals when clicking outside
window.addEventListener('click', (e) => {
    if (e.target === signupModal) {
        signupModal.classList.remove('show');
    }
    if (e.target === loginModal) {
        loginModal.classList.remove('show');
    }
});

// Signup form submission
document.getElementById('signupForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        name: document.getElementById('signupName').value,
        email: document.getElementById('signupEmail').value,
        phone: document.getElementById('signupPhone').value,
        csrf_token: csrfToken
    };

    const messageEl = document.getElementById('signupMessage');

    try {
        const response = await fetch('/api/signup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            messageEl.textContent = data.message;
            messageEl.className = 'form-message success';
            document.getElementById('signupForm').reset();
        } else {
            messageEl.textContent = data.message;
            messageEl.className = 'form-message error';
        }
    } catch (error) {
        messageEl.textContent = 'An error occurred. Please try again.';
        messageEl.className = 'form-message error';
    }
});

// Login form submission
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        username: document.getElementById('loginUsername').value,
        password: document.getElementById('loginPassword').value,
        csrf_token: csrfToken
    };

    const messageEl = document.getElementById('loginMessage');

    try {
        const response = await fetch('/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            messageEl.textContent = 'Login successful! Redirecting...';
            messageEl.className = 'form-message success';

            // Redirect to dashboard after 1 second
            setTimeout(() => {
                window.location.href = '/dashboard/';
            }, 1000);
        } else {
            messageEl.textContent = data.message;
            messageEl.className = 'form-message error';
        }
    } catch (error) {
        messageEl.textContent = 'An error occurred. Please try again.';
        messageEl.className = 'form-message error';
    }
});

// Logout
btnLogout?.addEventListener('click', async () => {
    try {
        await fetch('/api/logout.php', { method: 'POST' });
        window.location.reload();
    } catch (error) {
        console.error('Logout failed:', error);
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    fetchCSRFToken();
    checkAuth();
});

============================================ */

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
