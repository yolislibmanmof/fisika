/**
 * Premium Dark Mode Toggle
 * Features: Smooth transition, persistence, system preference detection
 */

(function() {
    'use strict';
    
    const STORAGE_KEY = 'fisika-theme';
    const THEME_ATTR = 'data-theme';
    
    // Get user's theme preference
    function getTheme() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) return stored;
        
        // Check system preference
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    
    // Apply theme
    function setTheme(theme) {
        document.documentElement.setAttribute(THEME_ATTR, theme);
        localStorage.setItem(STORAGE_KEY, theme);
        
        // Dispatch event for other scripts
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
        
        // Update meta theme-color for mobile browsers
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) {
            meta.content = theme === 'dark' ? '#0B1120' : '#FFFFFF';
        }
    }
    
    // Toggle theme
    function toggleTheme() {
        const current = document.documentElement.getAttribute(THEME_ATTR);
        setTheme(current === 'dark' ? 'light' : 'dark');
    }
    
    // Initialize
    function init() {
        // Apply theme immediately (prevent flash)
        const theme = getTheme();
        document.documentElement.setAttribute(THEME_ATTR, theme);
        
        // Wait for DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupToggle);
        } else {
            setupToggle();
        }
        
        // Listen to system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(STORAGE_KEY)) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }
    
    // Setup toggle button
    function setupToggle() {
        const toggle = document.getElementById('themeToggle');
        if (!toggle) return;
        
        toggle.addEventListener('click', toggleTheme);
        
        // Update icon based on theme
        const updateIcon = () => {
            const theme = document.documentElement.getAttribute(THEME_ATTR);
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
            }
        };
        
        updateIcon();
        window.addEventListener('themechange', updateIcon);
    }
    
    // Auto-init
    init();
    
    // Expose API
    window.DarkMode = {
        get: getTheme,
        set: setTheme,
        toggle: toggleTheme
    };
})();