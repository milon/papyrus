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
                hideSearch();
            }
            if (event.key === "/" && event.target && event.target.tagName !== "INPUT" && event.target.tagName !== "TEXTAREA") {
                var searchInput = document.getElementById("site-search-input");
                if (searchInput) {
                    event.preventDefault();
                    openSidebar();
                    searchInput.focus();
                }
            }
        });

        var searchInput = document.getElementById("site-search-input");
        var searchResults = document.getElementById("site-search-results");
        var searchIndex = null;

        function hideSearch() {
            if (searchResults) {
                searchResults.hidden = true;
                searchResults.innerHTML = "";
            }
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function (character) {
                return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[character];
            });
        }

        function loadSearchIndex(done) {
            if (searchIndex) {
                done(searchIndex);
                return;
            }
            fetch("assets/search.json")
                .then(function (response) { return response.ok ? response.json() : []; })
                .then(function (data) {
                    searchIndex = Array.isArray(data) ? data : [];
                    done(searchIndex);
                })
                .catch(function () { done([]); });
        }

        function renderSearch(query) {
            if (!searchResults) return;
            var terms = query.toLowerCase().trim().split(/\s+/).filter(Boolean);
            if (terms.length === 0) {
                hideSearch();
                return;
            }
            loadSearchIndex(function (entries) {
                var matches = [];
                for (var i = 0; i < entries.length && matches.length < 12; i++) {
                    var haystack = ((entries[i].title || "") + " " + (entries[i].text || "")).toLowerCase();
                    var ok = true;
                    for (var t = 0; t < terms.length; t++) {
                        if (haystack.indexOf(terms[t]) === -1) {
                            ok = false;
                            break;
                        }
                    }
                    if (ok) matches.push(entries[i]);
                }
                searchResults.hidden = false;
                if (matches.length === 0) {
                    searchResults.innerHTML = "<li><p class=\"site-search-empty\">No matches</p></li>";
                    return;
                }
                searchResults.innerHTML = matches.map(function (entry) {
                    return "<li><a href=\"" + entry.file + "\">" + escapeHtml(entry.title) + "</a></li>";
                }).join("");
            });
        }

        if (searchInput && searchResults) {
            searchInput.addEventListener("input", function () {
                renderSearch(searchInput.value);
            });
            searchInput.addEventListener("keydown", function (event) {
                if (event.key === "Escape") {
                    hideSearch();
                    searchInput.blur();
                }
            });
        }
    });
})();
