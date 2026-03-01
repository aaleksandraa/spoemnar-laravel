import Alpine from 'alpinejs';
import { ConsentManager } from './analytics/consent-manager';
import { CookieBannerUI } from './analytics/cookie-banner';
import { DataLayerManager } from './analytics/data-layer';
import { EventTracker } from './analytics/event-tracker';

window.Alpine = Alpine;

Alpine.start();

// ===== ANALYTICS & COOKIE CONSENT MANAGEMENT =====
// Initialize analytics and cookie consent
function initAnalytics() {
  // Get consent configuration from config
  const consentConfig = {
    storageKey: 'cookie_consent',
    version: 1,
    expirationMonths: 12
  };

  // Initialize consent manager
  const consentManager = new ConsentManager(consentConfig);
  window.consentManager = consentManager;

  // Initialize default consent mode before GTM loads
  consentManager.initializeDefaultConsent();

  // Initialize data layer manager
  const dataLayerManager = new DataLayerManager();
  window.dataLayerManager = dataLayerManager;

  // Initialize event tracker
  const eventTracker = new EventTracker(consentManager, dataLayerManager);
  window.eventTracker = eventTracker;

  // Initialize cookie banner UI
  const cookieBannerUI = new CookieBannerUI(consentManager);
  window.cookieBannerUI = cookieBannerUI;

  // Track initial page view
  eventTracker.trackPageView({
    page_path: window.location.pathname,
    page_title: document.title,
    page_locale: document.documentElement.lang || 'en',
    page_type: window.dataLayer?.[0]?.page_type || 'unknown'
  });

  // Attach event listeners for tracking
  attachAnalyticsEventListeners(eventTracker);

  // Set up global error handler
  setupErrorTracking(eventTracker);
}

/**
 * Attach event listeners for analytics tracking
 * Requirements: 8.1, 16.1, 17.1, 18.1
 */
function attachAnalyticsEventListeners(eventTracker) {
  // Track navigation clicks
  document.querySelectorAll('nav a, [role="navigation"] a').forEach(link => {
    link.addEventListener('click', (e) => {
      eventTracker.trackNavigationClick({
        menu_item: e.target.textContent.trim(),
        destination_url: e.target.href,
        locale: document.documentElement.lang || 'en'
      });
    });
  });

  // Track external link clicks
  document.querySelectorAll('a[href^="http"]').forEach(link => {
    // Check if link is external
    if (!link.href.includes(window.location.hostname)) {
      link.addEventListener('click', (e) => {
        eventTracker.trackOutboundClick({
          link_url: e.target.href,
          link_text: e.target.textContent.trim(),
          page_location: window.location.href
        });
      });
    }
  });

  // Track file downloads
  document.querySelectorAll('a[download], a[href$=".pdf"], a[href$=".zip"], a[href$=".doc"], a[href$=".docx"]').forEach(link => {
    link.addEventListener('click', (e) => {
      const url = new URL(e.target.href);
      const pathname = url.pathname;
      const filename = pathname.substring(pathname.lastIndexOf('/') + 1);
      const extension = filename.substring(filename.lastIndexOf('.') + 1);

      eventTracker.trackFileDownload({
        file_type: extension,
        file_name: filename,
        file_extension: extension
      });
    });
  });
}

/**
 * Set up global error tracking
 * Requirements: 19.1
 */
function setupErrorTracking(eventTracker) {
  window.addEventListener('error', (event) => {
    eventTracker.trackError({
      error_type: 'javascript_error',
      error_message: event.message || 'Unknown error',
      page_url: window.location.href,
      user_agent: navigator.userAgent
    });
  });

  window.addEventListener('unhandledrejection', (event) => {
    eventTracker.trackError({
      error_type: 'unhandled_promise_rejection',
      error_message: event.reason?.message || String(event.reason) || 'Unknown promise rejection',
      page_url: window.location.href,
      user_agent: navigator.userAgent
    });
  });
}

// Initialize analytics when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAnalytics);
} else {
  initAnalytics();
}

// ===== UTILITY FUNCTIONS =====

/**
 * Debounce function to limit how often a function can fire
 * @param {Function} func - The function to debounce
 * @param {number} wait - The delay in milliseconds
 * @returns {Function} - The debounced function
 */
function debounce(func, wait = 300) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Throttle function to limit how often a function can fire
 * @param {Function} func - The function to throttle
 * @param {number} limit - The time limit in milliseconds
 * @returns {Function} - The throttled function
 */
function throttle(func, limit = 300) {
  let inThrottle;
  return function executedFunction(...args) {
    if (!inThrottle) {
      func(...args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}

/**
 * Request idle callback polyfill
 * @param {Function} callback - The callback to execute
 */
const requestIdleCallback = window.requestIdleCallback || function(callback) {
  const start = Date.now();
  return setTimeout(() => {
    callback({
      didTimeout: false,
      timeRemaining: () => Math.max(0, 50 - (Date.now() - start))
    });
  }, 1);
};

// ===== DARK MODE MANAGEMENT =====
function applyTheme(theme) {
  const root = document.documentElement;
  root.classList.remove('dark', 'light');

  if (theme === 'dark') {
    root.classList.add('dark');
    return;
  }

  root.classList.add('light');
}

// Initialize theme based on user preference or system preference
function initDarkMode() {
  const darkModePreference = localStorage.getItem('darkMode');
  const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

  if (darkModePreference === 'dark') {
    applyTheme('dark');
    return;
  }

  if (darkModePreference === 'light') {
    applyTheme('light');
    return;
  }

  applyTheme(systemPrefersDark ? 'dark' : 'light');
}

// Toggle dark mode
function toggleDarkMode() {
  const isDark = document.documentElement.classList.contains('dark');
  const nextTheme = isDark ? 'light' : 'dark';
  applyTheme(nextTheme);
  localStorage.setItem('darkMode', nextTheme);
}

// Listen for system preference changes (debounced)
const handleSystemThemeChange = debounce((e) => {
  if (!localStorage.getItem('darkMode')) {
    applyTheme(e.matches ? 'dark' : 'light');
  }
}, 100);

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', handleSystemThemeChange);

// Initialize on page load
initDarkMode();

// Expose toggle function globally
window.toggleDarkMode = toggleDarkMode;

// ===== SCROLL ANIMATIONS =====
// Intersection Observer for fade-in animations with performance optimizations
function initScrollAnimations() {
  // Check if user prefers reduced motion
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (prefersReducedMotion) {
    // Skip animations if user prefers reduced motion
    document.querySelectorAll('[data-animate], .animate-on-scroll').forEach((el) => {
      el.classList.add('is-visible');
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    return;
  }

  // Use Intersection Observer with optimized settings
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          // Use requestAnimationFrame for smooth animations
          requestAnimationFrame(() => {
            // For elements with animate-on-scroll class, add is-visible
            if (entry.target.classList.contains('animate-on-scroll')) {
              entry.target.classList.add('is-visible');
            } else {
              // For elements with data-animate attribute, add animate-fade-in-up
              entry.target.classList.add('animate-fade-in-up');
            }
          });

          // Unobserve after animation to free up resources
          observer.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px', // Reduced margin for earlier triggering
    }
  );

  // Observe all elements with data-animate attribute
  document.querySelectorAll('[data-animate]').forEach((el) => {
    observer.observe(el);
  });

  // Observe all elements with animate-on-scroll class
  document.querySelectorAll('.animate-on-scroll').forEach((el) => {
    observer.observe(el);
  });
}

// Initialize scroll animations when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initScrollAnimations);
} else {
  initScrollAnimations();
}

// ===== LAZY LOADING IMAGES =====
// Lazy load images using Intersection Observer
function initLazyLoading() {
  // Check if browser supports loading="lazy"
  if ('loading' in HTMLImageElement.prototype) {
    // Native lazy loading is supported
    document.querySelectorAll('img[data-src]').forEach((img) => {
      img.src = img.dataset.src;
      if (img.dataset.srcset) {
        img.srcset = img.dataset.srcset;
      }
      img.removeAttribute('data-src');
      img.removeAttribute('data-srcset');
    });
  } else {
    // Fallback to Intersection Observer
    const imageObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const img = entry.target;

            // Load image
            if (img.dataset.src) {
              img.src = img.dataset.src;
              img.removeAttribute('data-src');
            }

            if (img.dataset.srcset) {
              img.srcset = img.dataset.srcset;
              img.removeAttribute('data-srcset');
            }

            // Add loaded class for fade-in effect
            img.classList.add('loaded');

            // Stop observing this image
            imageObserver.unobserve(img);
          }
        });
      },
      {
        rootMargin: '50px 0px', // Start loading 50px before entering viewport
        threshold: 0.01
      }
    );

    // Observe all images with data-src
    document.querySelectorAll('img[data-src]').forEach((img) => {
      imageObserver.observe(img);
    });
  }
}

// Initialize lazy loading when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initLazyLoading);
} else {
  initLazyLoading();
}

// ===== FORM ENHANCEMENTS =====
// Password visibility toggle
function initPasswordToggles() {
  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = button.previousElementSibling;
      const icon = button.querySelector('svg');

      if (input.type === 'password') {
        input.type = 'text';
        button.setAttribute('aria-label', 'Hide password');
      } else {
        input.type = 'password';
        button.setAttribute('aria-label', 'Show password');
      }
    });
  });
}

// Initialize form enhancements when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPasswordToggles);
} else {
  initPasswordToggles();
}

// ===== PERFORMANCE MONITORING =====
// Monitor performance metrics
function monitorPerformance() {
  if ('PerformanceObserver' in window) {
    // Monitor Long Tasks
    try {
      const longTaskObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (entry.duration > 50) {
            console.warn('Long task detected:', entry.duration, 'ms');
          }
        }
      });
      longTaskObserver.observe({ entryTypes: ['longtask'] });
    } catch (e) {
      // Long task API not supported
    }

    // Monitor Layout Shifts
    try {
      const clsObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (!entry.hadRecentInput && entry.value > 0.1) {
            console.warn('Layout shift detected:', entry.value);
          }
        }
      });
      clsObserver.observe({ entryTypes: ['layout-shift'] });
    } catch (e) {
      // Layout shift API not supported
    }
  }
}

// Initialize performance monitoring in development
if (process.env.NODE_ENV === 'development') {
  monitorPerformance();
}

// ===== SCROLL PERFORMANCE =====
// Optimize scroll handlers with passive event listeners
let ticking = false;
let lastScrollY = window.scrollY;

const handleScroll = () => {
  lastScrollY = window.scrollY;

  if (!ticking) {
    requestAnimationFrame(() => {
      // Add scroll-based classes or effects here
      if (lastScrollY > 100) {
        document.body.classList.add('scrolled');
      } else {
        document.body.classList.remove('scrolled');
      }

      ticking = false;
    });

    ticking = true;
  }
};

// Use passive event listener for better scroll performance
window.addEventListener('scroll', handleScroll, { passive: true });

// ===== RESOURCE HINTS =====
// Add safe preconnect hints for externally hosted fonts.
function preloadCriticalResources() {
  const existing = (href) => document.head.querySelector(`link[rel="preconnect"][href="${href}"]`);
  const addPreconnect = (href, crossOrigin = false) => {
    if (existing(href)) return;
    const link = document.createElement('link');
    link.rel = 'preconnect';
    link.href = href;
    if (crossOrigin) {
      link.crossOrigin = 'anonymous';
    }
    document.head.appendChild(link);
  };

  addPreconnect('https://fonts.googleapis.com');
  addPreconnect('https://fonts.gstatic.com', true);
}

// Add hints when the browser is idle.
requestIdleCallback(preloadCriticalResources);

// ===== CLEANUP =====
// Clean up event listeners and observers on page unload
window.addEventListener('beforeunload', () => {
  // Remove scroll listener
  window.removeEventListener('scroll', handleScroll);

  // Clear any pending timeouts
  // (handled automatically by debounce/throttle functions)
});
