/**
 * Dynamic Theme Switcher & Color Customization Controller
 * Project: Student Feedback and Course Evaluation System
 */

(function () {
    const THEME_STORAGE_KEY = 'app_theme';
    const CUSTOM_THEME_STORAGE_KEY = 'app_custom_theme';

    // Helper: Determine if a HEX color is dark or light (for adaptive text contrast)
    function isColorDark(hex) {
        if (!hex || hex.length < 6) return false;
        hex = hex.replace('#', '');
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        const brightness = (r * 299 + g * 587 + b * 114) / 1000;
        return brightness < 128;
    }

    // Apply selected theme
    window.setAppTheme = function (themeName, customColors = null) {
        const root = document.documentElement;

        if (themeName === 'custom' && customColors) {
            root.setAttribute('data-theme', 'custom');
            
            // Set dynamic CSS variables
            root.style.setProperty('--theme-primary', customColors.primary);
            root.style.setProperty('--theme-secondary', customColors.secondary);
            root.style.setProperty('--theme-accent', customColors.accent);
            root.style.setProperty('--theme-bg', customColors.bg);
            root.style.setProperty('--theme-navbar-bg', customColors.primary);
            root.style.setProperty('--theme-sidebar-bg', customColors.primary);
            root.style.setProperty('--theme-primary-gradient', `linear-gradient(135deg, ${customColors.primary} 0%, ${customColors.secondary} 100%)`);

            const isDarkBg = isColorDark(customColors.bg);
            root.style.setProperty('--theme-card-bg', isDarkBg ? '#1e293b' : '#ffffff');
            root.style.setProperty('--theme-card-border', isDarkBg ? '#334155' : '#e2e8f0');
            root.style.setProperty('--theme-text-primary', isDarkBg ? '#f8fafc' : '#0f172a');
            root.style.setProperty('--theme-text-secondary', isDarkBg ? '#94a3b8' : '#64748b');
            root.style.setProperty('--theme-input-bg', isDarkBg ? '#0f172a' : '#ffffff');
            root.style.setProperty('--theme-input-border', isDarkBg ? '#475569' : '#cbd5e1');

            localStorage.setItem(THEME_STORAGE_KEY, 'custom');
            localStorage.setItem(CUSTOM_THEME_STORAGE_KEY, JSON.stringify(customColors));
        } else {
            // Clean up custom inline variables when switching to presets
            root.removeAttribute('style');
            root.setAttribute('data-theme', themeName);
            localStorage.setItem(THEME_STORAGE_KEY, themeName);
        }

        // Update active indicators in the dropdown
        updateThemeUI(themeName);
    };

    // Update active checkmarks in Theme Switcher menu
    function updateThemeUI(activeTheme) {
        const themeButtons = document.querySelectorAll('.theme-option-btn');
        themeButtons.forEach(btn => {
            const t = btn.getAttribute('data-theme');
            if (t === activeTheme) {
                btn.classList.add('active');
                if (!btn.querySelector('.theme-check-icon')) {
                    const check = document.createElement('i');
                    check.className = 'bi bi-check-lg ms-auto text-primary theme-check-icon';
                    btn.appendChild(check);
                }
            } else {
                btn.classList.remove('active');
                const check = btn.querySelector('.theme-check-icon');
                if (check) check.remove();
            }
        });
    }

    // Initialize Theme Switcher controls on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function () {
        const currentTheme = localStorage.getItem(THEME_STORAGE_KEY) || 'ocean-blue';
        const savedCustom = JSON.parse(localStorage.getItem(CUSTOM_THEME_STORAGE_KEY) || '{}');

        // Apply saved theme state
        if (currentTheme === 'custom' && savedCustom.primary) {
            window.setAppTheme('custom', savedCustom);
        } else {
            window.setAppTheme(currentTheme);
        }

        // Handle preset theme clicks
        document.querySelectorAll('.theme-option-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const theme = this.getAttribute('data-theme');
                window.setAppTheme(theme);
            });
        });

        // Initialize Custom Color inputs if elements exist
        const primaryPicker = document.getElementById('customPrimaryColor');
        const secondaryPicker = document.getElementById('customSecondaryColor');
        const accentPicker = document.getElementById('customAccentColor');
        const bgPicker = document.getElementById('customBgColor');
        const applyCustomBtn = document.getElementById('applyCustomThemeBtn');
        const resetThemeBtn = document.getElementById('resetDefaultThemeBtn');

        if (primaryPicker && savedCustom.primary) primaryPicker.value = savedCustom.primary;
        if (secondaryPicker && savedCustom.secondary) secondaryPicker.value = savedCustom.secondary;
        if (accentPicker && savedCustom.accent) accentPicker.value = savedCustom.accent;
        if (bgPicker && savedCustom.bg) bgPicker.value = savedCustom.bg;

        if (applyCustomBtn) {
            applyCustomBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const customColors = {
                    primary: primaryPicker.value,
                    secondary: secondaryPicker.value,
                    accent: accentPicker.value,
                    bg: bgPicker.value
                };
                window.setAppTheme('custom', customColors);
            });
        }

        if (resetThemeBtn) {
            resetThemeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                localStorage.removeItem(CUSTOM_THEME_STORAGE_KEY);
                window.setAppTheme('ocean-blue');
                if (primaryPicker) primaryPicker.value = '#0a2240';
                if (secondaryPicker) secondaryPicker.value = '#1a4a82';
                if (accentPicker) accentPicker.value = '#0284c7';
                if (bgPicker) bgPicker.value = '#f4f7fb';
            });
        }
    });
})();

