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

        function escapeRegExp(value) {
            return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        }

        function termScore(haystack, term, wordPts, subPts) {
            if (!haystack || !term) return 0;
            if (haystack === term) return wordPts * 2;
            var re = new RegExp("(^|[^a-z0-9_])" + escapeRegExp(term) + "([^a-z0-9_]|$)", "i");
            if (re.test(haystack)) return wordPts;
            if (haystack.indexOf(term) !== -1) return subPts;
            return 0;
        }

        function scoreEntry(entry, terms) {
            var title = String(entry.title || "").toLowerCase();
            var text = String(entry.text || "").toLowerCase();
            var score = 0;
            for (var t = 0; t < terms.length; t++) {
                var term = terms[t];
                var titleHit = termScore(title, term, 40, 18);
                var textHit = termScore(text, term, 8, 3);
                if (titleHit === 0 && textHit === 0) {
                    return -1;
                }
                score += titleHit + textHit;
            }
            if (entry.kind === "heading") score += 12;
            else if (entry.kind === "home") score += 4;
            if (entry.pretoc) score -= 28;
            return score;
        }

        function excerptFor(entry, terms) {
            var text = String(entry.text || "").replace(/\s+/g, " ").trim();
            if (!text) return "";
            var lower = text.toLowerCase();
            var pos = 0;
            if (terms && terms.length) {
                var best = -1;
                for (var t = 0; t < terms.length; t++) {
                    var p = lower.indexOf(terms[t]);
                    if (p !== -1 && (best === -1 || p < best)) best = p;
                }
                if (best > 0) pos = Math.max(0, best - 36);
            }
            var slice = text.slice(pos, pos + 140);
            if (pos > 0) slice = "…" + slice.replace(/^\S*\s+/, "");
            if (pos + 140 < text.length) slice = slice.replace(/\s+\S*$/, "") + "…";
            return slice;
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
                var ranked = [];
                for (var i = 0; i < entries.length; i++) {
                    var score = scoreEntry(entries[i], terms);
                    if (score >= 0) {
                        ranked.push({ entry: entries[i], score: score, index: i });
                    }
                }
                ranked.sort(function (a, b) {
                    if (b.score !== a.score) return b.score - a.score;
                    return a.index - b.index;
                });
                var matches = ranked.slice(0, 12).map(function (row) { return row.entry; });
                searchResults.hidden = false;
                if (matches.length === 0) {
                    searchResults.innerHTML = "<p class=\"search-no-results\">No matches for “" + escapeHtml(query.trim()) + "”</p>";
                    activeResultIndex = -1;
                    return;
                }
                searchResults.innerHTML = matches.map(function (entry) {
                    var excerpt = excerptFor(entry, terms);
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

        var pageToc = document.querySelector(".page-toc");
        if (pageToc && "IntersectionObserver" in window) {
            var tocLinks = Array.prototype.slice.call(pageToc.querySelectorAll('a[href^="#"]'));
            var tocById = {};
            tocLinks.forEach(function (link) {
                var id = decodeURIComponent((link.getAttribute("href") || "").slice(1));
                if (id) tocById[id] = link;
            });
            var observed = Object.keys(tocById)
                .map(function (id) { return document.getElementById(id); })
                .filter(Boolean);
            var activeId = null;

            function setActiveToc(id) {
                if (id === activeId) return;
                activeId = id;
                tocLinks.forEach(function (link) {
                    var on = link === tocById[id];
                    link.classList.toggle("is-active", on);
                    if (on) link.setAttribute("aria-current", "true");
                    else link.removeAttribute("aria-current");
                });
            }

            if (observed.length > 0) {
                var observer = new IntersectionObserver(function (entries) {
                    var visible = entries
                        .filter(function (entry) { return entry.isIntersecting; })
                        .sort(function (a, b) { return a.boundingClientRect.top - b.boundingClientRect.top; });
                    if (visible.length > 0) {
                        setActiveToc(visible[0].target.id);
                    }
                }, { rootMargin: "-20% 0px -65% 0px", threshold: [0, 1] });
                observed.forEach(function (el) { observer.observe(el); });
            }
        }

        var contentRoot = document.querySelector("main.content");
        if (
            contentRoot
            && !document.body.hasAttribute("data-no-copy-code")
            && navigator.clipboard
            && navigator.clipboard.writeText
        ) {
            var copyIcon = '<svg class="code-copy-clipboard" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">'
                + '<rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="1.75"/>'
                + '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>'
                + '</svg>'
                + '<svg class="code-copy-check" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">'
                + '<path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>'
                + '</svg>';

            Array.prototype.forEach.call(contentRoot.querySelectorAll("pre"), function (pre) {
                if (pre.closest(".code-block") || pre.closest("figure.mermaid")) {
                    return;
                }

                var wrap = document.createElement("div");
                wrap.className = "code-block";
                pre.parentNode.insertBefore(wrap, pre);
                wrap.appendChild(pre);

                var button = document.createElement("button");
                button.type = "button";
                button.className = "code-copy";
                button.setAttribute("aria-label", "Copy code");
                button.setAttribute("title", "Copy");
                button.innerHTML = copyIcon;
                wrap.appendChild(button);

                button.addEventListener("click", function () {
                    var text = pre.innerText || pre.textContent || "";
                    navigator.clipboard.writeText(text).then(function () {
                        button.classList.add("is-copied");
                        button.setAttribute("aria-label", "Copied");
                        button.setAttribute("title", "Copied");
                        window.setTimeout(function () {
                            button.classList.remove("is-copied");
                            button.setAttribute("aria-label", "Copy code");
                            button.setAttribute("title", "Copy");
                        }, 1600);
                    }).catch(function () {});
                });
            });
        }

        var versionSwitcher = document.querySelector("[data-version-switcher]");
        if (versionSwitcher) {
            versionSwitcher.addEventListener("change", function () {
                var base = versionSwitcher.value;
                if (!base) return;

                var file = "index.html";
                try {
                    var path = window.location.pathname || "";
                    var parts = path.split("/").filter(Boolean);
                    if (parts.length > 0) {
                        var last = parts[parts.length - 1];
                        if (/\.html?$/i.test(last)) {
                            file = last;
                        }
                    }
                } catch (e) {}

                var targetBase = base.replace(/\/+$/, "");
                if (targetBase === "") targetBase = "";
                var pageUrl = (targetBase === "" ? "" : targetBase) + "/" + file;
                var homeUrl = (targetBase === "" ? "/" : targetBase + "/");

                function go(url) {
                    window.location.href = url;
                }

                if (!window.fetch) {
                    go(pageUrl);
                    return;
                }

                fetch(pageUrl, { method: "HEAD", redirect: "follow" }).then(function (response) {
                    if (response.status === 404) {
                        go(homeUrl);
                    } else {
                        go(pageUrl);
                    }
                }).catch(function () {
                    go(pageUrl);
                });
            });
        }
    });
})();
