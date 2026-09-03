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

        var searchOpen = document.getElementById("search-open");
        var searchModal = document.getElementById("search-modal");
        var searchModalBackdrop = document.getElementById("search-modal-backdrop");
        var searchModalPanel = document.getElementById("search-modal-panel");
        var searchModalClose = document.getElementById("search-modal-close");
        var searchInput = document.getElementById("search-modal-input");
        var searchResults = document.getElementById("search-results");
        var searchHint = document.getElementById("search-modal-hint");
        var searchIndex = null;
        var activeResultIndex = -1;
        var lastActiveElement = null;

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function (character) {
                return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[character];
            });
        }

        function isTypingTarget(target) {
            if (!target || !target.tagName) return false;
            var tag = target.tagName;
            return tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT" || target.isContentEditable;
        }

        function resultLinks() {
            if (!searchResults) return [];
            return Array.prototype.slice.call(searchResults.querySelectorAll("a.search-result-item"));
        }

        function setActiveResult(index) {
            var links = resultLinks();
            if (links.length === 0) {
                activeResultIndex = -1;
                return;
            }

            if (index < 0) {
                index = links.length - 1;
            } else if (index >= links.length) {
                index = 0;
            }

            activeResultIndex = index;
            links.forEach(function (link, i) {
                var active = i === activeResultIndex;
                link.classList.toggle("is-active", active);
                if (active) {
                    link.setAttribute("aria-current", "true");
                    link.scrollIntoView({ block: "nearest" });
                } else {
                    link.removeAttribute("aria-current");
                }
            });
        }

        function openActiveResult() {
            var links = resultLinks();
            if (links.length === 0) return false;
            var target = links[activeResultIndex >= 0 ? activeResultIndex : 0];
            if (!target) return false;
            navigateToResult(target.getAttribute("href"));
            return true;
        }

        function navigateToResult(href) {
            if (!href) return;

            closeSearch();

            var url;
            try {
                url = new URL(href, window.location.href);
            } catch (e) {
                window.location.href = href;
                return;
            }

            var current = new URL(window.location.href);
            var samePage = url.pathname === current.pathname && url.search === current.search;

            if (!samePage) {
                window.location.href = href;
                return;
            }

            if (!url.hash) {
                return;
            }

            var id = decodeURIComponent(url.hash.replace(/^#/, ""));
            var target = id ? document.getElementById(id) : null;

            if (current.hash !== url.hash) {
                history.pushState(null, "", url.pathname + url.search + url.hash);
            }

            if (target) {
                target.scrollIntoView();
            }
        }

        function excerptFor(entry) {
            var text = String(entry.text || "").replace(/\s+/g, " ").trim();
            if (!text) return "";
            if (text.length > 140) {
                return text.slice(0, 140).replace(/\s+\S*$/, "") + "…";
            }
            return text;
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

        function clearResults() {
            if (searchResults) {
                searchResults.hidden = true;
                searchResults.innerHTML = "";
            }
            if (searchHint) {
                searchHint.hidden = false;
            }
            activeResultIndex = -1;
        }

        function renderSearch(query) {
            if (!searchResults) return;
            var terms = query.toLowerCase().trim().split(/\s+/).filter(Boolean);
            if (terms.length === 0) {
                clearResults();
                return;
            }
            if (searchHint) {
                searchHint.hidden = true;
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
                    searchResults.innerHTML = "<p class=\"search-no-results\">No matches for “" + escapeHtml(query.trim()) + "”</p>";
                    activeResultIndex = -1;
                    return;
                }
                searchResults.innerHTML = matches.map(function (entry) {
                    var excerpt = excerptFor(entry);
                    return "<a class=\"search-result-item\" href=\"" + escapeHtml(entry.file) + "\">"
                        + "<span class=\"search-result-title\">" + escapeHtml(entry.title) + "</span>"
                        + (excerpt ? "<span class=\"search-result-excerpt\">" + escapeHtml(excerpt) + "</span>" : "")
                        + "</a>";
                }).join("");
                setActiveResult(0);
            });
        }

        function focusables() {
            if (!searchModalPanel) return [];
            return Array.prototype.slice.call(
                searchModalPanel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])')
            ).filter(function (el) {
                return !el.hasAttribute("disabled") && el.offsetParent !== null;
            });
        }

        function trapFocus(event) {
            if (event.key !== "Tab" || !searchModal || searchModal.hidden) return;
            var items = focusables();
            if (items.length === 0) return;
            var first = items[0];
            var last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        function openSearch() {
            if (!searchModal || !searchInput) return;
            lastActiveElement = document.activeElement;
            closeSidebar();
            searchModal.hidden = false;
            document.body.classList.add("search-modal-open");
            searchInput.value = "";
            clearResults();
            window.setTimeout(function () { searchInput.focus(); }, 0);
        }

        function closeSearch() {
            if (!searchModal) return;
            searchModal.hidden = true;
            document.body.classList.remove("search-modal-open");
            if (searchInput) {
                searchInput.value = "";
            }
            clearResults();
            if (lastActiveElement && typeof lastActiveElement.focus === "function") {
                lastActiveElement.focus();
            }
        }

        if (searchOpen) {
            searchOpen.addEventListener("click", openSearch);
        }
        if (searchModalClose) {
            searchModalClose.addEventListener("click", closeSearch);
        }
        if (searchModalBackdrop) {
            searchModalBackdrop.addEventListener("click", closeSearch);
        }
        if (searchModalPanel) {
            searchModalPanel.addEventListener("keydown", trapFocus);
        }
        if (searchResults) {
            searchResults.addEventListener("mousedown", function (event) {
                var link = event.target.closest("a.search-result-item");
                if (link) {
                    event.preventDefault();
                }
            });
            searchResults.addEventListener("click", function (event) {
                var link = event.target.closest("a.search-result-item");
                if (link) {
                    event.preventDefault();
                    navigateToResult(link.getAttribute("href"));
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                renderSearch(searchInput.value);
            });
            searchInput.addEventListener("keydown", function (event) {
                if (event.key === "Escape") {
                    event.preventDefault();
                    closeSearch();
                    return;
                }
                if (searchResults && !searchResults.hidden && resultLinks().length > 0) {
                    if (event.key === "ArrowDown") {
                        event.preventDefault();
                        setActiveResult(activeResultIndex + 1);
                        return;
                    }
                    if (event.key === "ArrowUp") {
                        event.preventDefault();
                        setActiveResult(activeResultIndex - 1);
                        return;
                    }
                    if (event.key === "Enter") {
                        if (openActiveResult()) {
                            event.preventDefault();
                        }
                    }
                }
            });
        }

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                if (searchModal && !searchModal.hidden) {
                    closeSearch();
                    return;
                }
                closeSidebar();
            }
            if (event.key === "/" && !event.metaKey && !event.ctrlKey && !event.altKey && !isTypingTarget(event.target)) {
                event.preventDefault();
                openSearch();
            }
        });
    });
})();
