(function () {

    const THEME_KEY = "crm-theme";

    function getSavedTheme() {
        const saved = localStorage.getItem(THEME_KEY);

        return saved === "dark" ? "dark" : "light";
    }

    function applyTheme(theme) {

        const html = document.documentElement;
        const body = document.body;

        const isDark = theme === "dark";

        /* Main theme classes */
        html.classList.toggle("crm-dark", isDark);
        html.classList.toggle("crm-light", !isDark);

        html.setAttribute(
            "data-theme",
            theme
        );

        /* Body class */
        if (body) {
            body.classList.toggle(
                "crm-dark",
                isDark
            );

            body.classList.toggle(
                "crm-light",
                !isDark
            );
        }

        /* Global CSS variables */
        if (isDark) {

            html.style.setProperty(
                "--crm-bg",
                "#0f172a"
            );

            html.style.setProperty(
                "--crm-surface",
                "#111827"
            );

            html.style.setProperty(
                "--crm-surface-2",
                "#1e293b"
            );

            html.style.setProperty(
                "--crm-border",
                "#334155"
            );

            html.style.setProperty(
                "--crm-border-light",
                "#263447"
            );

            html.style.setProperty(
                "--crm-text",
                "#f8fafc"
            );

            html.style.setProperty(
                "--crm-text-2",
                "#e2e8f0"
            );

            html.style.setProperty(
                "--crm-muted",
                "#cbd5e1"
            );

            html.style.setProperty(
                "--crm-muted-2",
                "#94a3b8"
            );

            html.style.setProperty(
                "--crm-primary",
                "#60a5fa"
            );

        } else {

            html.style.setProperty(
                "--crm-bg",
                "#f8fafc"
            );

            html.style.setProperty(
                "--crm-surface",
                "#ffffff"
            );

            html.style.setProperty(
                "--crm-surface-2",
                "#f8fafc"
            );

            html.style.setProperty(
                "--crm-border",
                "#e2e8f0"
            );

            html.style.setProperty(
                "--crm-border-light",
                "#eef2f7"
            );

            html.style.setProperty(
                "--crm-text",
                "#0f172a"
            );

            html.style.setProperty(
                "--crm-text-2",
                "#334155"
            );

            html.style.setProperty(
                "--crm-muted",
                "#64748b"
            );

            html.style.setProperty(
                "--crm-muted-2",
                "#94a3b8"
            );

            html.style.setProperty(
                "--crm-primary",
                "#2563eb"
            );
        }

        localStorage.setItem(
            THEME_KEY,
            theme
        );

        updateThemeButton();

        /* Notify any module-specific scripts */
        window.dispatchEvent(
            new CustomEvent(
                "crm-theme-change",
                {
                    detail: {
                        theme: theme,
                        dark: isDark
                    }
                }
            )
        );
    }

    function updateThemeButton() {

        const button =
            document.getElementById(
                "crmThemeToggle"
            );

        if (!button) {
            return;
        }

        const isDark =
            document.documentElement.classList.contains(
                "crm-dark"
            );

        button.innerHTML =
            isDark ? "☀️" : "🌙";

        button.title =
            isDark
                ? "Switch to Light Mode"
                : "Switch to Dark Mode";

        button.setAttribute(
            "aria-label",
            isDark
                ? "Switch to Light Mode"
                : "Switch to Dark Mode"
        );

        button.setAttribute(
            "aria-pressed",
            isDark ? "true" : "false"
        );
    }

    function toggleTheme() {

        const isDark =
            document.documentElement.classList.contains(
                "crm-dark"
            );

        applyTheme(
            isDark
                ? "light"
                : "dark"
        );
    }

    /* Apply saved theme immediately */
    applyTheme(
        getSavedTheme()
    );

    /* Direct button listener */
    document.addEventListener(
        "DOMContentLoaded",
        function () {

            const button =
                document.getElementById(
                    "crmThemeToggle"
                );

            if (button) {

                button.addEventListener(
                    "click",
                    function (event) {

                        event.preventDefault();
                        event.stopPropagation();

                        toggleTheme();

                    }
                );

            }

            updateThemeButton();

        }
    );

    /* Fallback event delegation */
    document.addEventListener(
        "click",
        function (event) {

            const button =
                event.target.closest(
                    "#crmThemeToggle"
                );

            if (!button) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            toggleTheme();

        }
    );

})();