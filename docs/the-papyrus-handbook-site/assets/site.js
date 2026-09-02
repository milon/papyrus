(function () {
    var key = "papyrus-html-theme";
    var stored = null;
    try { stored = localStorage.getItem(key); } catch (e) {}
    var theme = stored === "dark" || stored === "light"
        ? stored
        : (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
    document.documentElement.setAttribute("data-theme", theme);

    document.addEventListener("DOMContentLoaded", function () {
        var button = document.getElementById("theme-toggle");
        if (button) {
            function syncLabel() {
                var dark = document.documentElement.getAttribute("data-theme") === "dark";
                button.setAttribute("aria-pressed", dark ? "true" : "false");
                button.setAttribute("aria-label", dark ? "Switch to light mode" : "Switch to dark mode");
                button.setAttribute("title", dark ? "Light mode" : "Dark mode");
            }
            syncLabel();
            button.addEventListener("click", function () {
                var next = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
                document.documentElement.setAttribute("data-theme", next);
                try { localStorage.setItem(key, next); } catch (e) {}
                syncLabel();
            });
        }

        var sidebar = document.getElementById("sidebar");
        var backdrop = document.getElementById("sidebar-backdrop");
        var navToggle = document.getElementById("nav-toggle");

        function closeSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove("is-open");
            if (backdrop) backdrop.classList.remove("is-visible");
            if (navToggle) navToggle.setAttribute("aria-expanded", "false");
        }

        function openSidebar() {
            if (!sidebar) return;
            sidebar.classList.add("is-open");
            if (backdrop) backdrop.classList.add("is-visible");
            if (navToggle) navToggle.setAttribute("aria-expanded", "true");
        }

        if (navToggle) {
            navToggle.addEventListener("click", function () {
                if (sidebar && sidebar.classList.contains("is-open")) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        if (backdrop) {
            backdrop.addEventListener("click", closeSidebar);
        }

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeSidebar();
            }
        });
    });
})();