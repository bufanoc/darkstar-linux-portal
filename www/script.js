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
