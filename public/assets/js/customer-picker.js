(function () {
    'use strict';

    var MIN_CHARS = 2;
    var DEBOUNCE_MS = 250;
    // Kundenattribut (Schlüssel der Feldzuordnung) => Attribut im JSON
    var ATTR = {
        name: 'name', company: 'company_name', phone: 'phone', email: 'email',
        street: 'street', zip: 'zip', city: 'city'
    };

    function init(root) {
        var form = root.closest('form');
        var search = root.querySelector('[data-picker-search]');
        var list = root.querySelector('[data-picker-results]');
        var idInput = root.querySelector('[data-picker-id]');
        var linked = root.querySelector('[data-picker-linked]');
        var linkedName = root.querySelector('[data-picker-linked-name]');
        var clearBtn = root.querySelector('[data-picker-clear]');
        var url = root.getAttribute('data-search-url') || '/customers/search';
        var autosubmit = root.getAttribute('data-autosubmit') === '1';
        var fieldMap = {};
        try { fieldMap = JSON.parse(root.getAttribute('data-field-map') || '{}') || {}; } catch (e) { fieldMap = {}; }

        var hits = [];
        var active = -1;
        var timer = null;
        var controller = null;
        var requestSeq = 0;

        var pending = false;

        function cancelPending() {
            clearTimeout(timer);
            requestSeq++;
            if (controller) { controller.abort(); controller = null; }
            pending = false;
        }

        function closeList() {
            cancelPending();
            list.classList.add('hidden');
            list.textContent = '';
            hits = [];
            active = -1;
            search.setAttribute('aria-expanded', 'false');
        }

        function setActive(index) {
            var items = list.children;
            for (var i = 0; i < items.length; i++) {
                items[i].classList.toggle('bg-emerald-50', i === index);
            }
            active = index;
            if (items[index] && items[index].scrollIntoView) {
                items[index].scrollIntoView({ block: 'nearest' });
            }
        }

        function render(results) {
            list.textContent = '';
            pending = false;
            hits = results;
            active = -1;
            if (!results.length) {
                var empty = document.createElement('li');
                empty.className = 'px-3 py-2 text-sm text-gray-500';
                empty.textContent = 'Keine Kunden gefunden';
                list.appendChild(empty);
            }
            results.forEach(function (customer, index) {
                var li = document.createElement('li');
                li.className = 'px-3 py-2 cursor-pointer hover:bg-emerald-50 border-b border-gray-100 last:border-b-0';
                li.setAttribute('role', 'option');
                var title = document.createElement('div');
                title.className = 'font-semibold text-gray-900';
                title.textContent = customer.display_name || customer.name || '';
                var sub = document.createElement('div');
                sub.className = 'text-sm text-gray-500';
                sub.textContent = [customer.phone, customer.city].filter(Boolean).join(' · ');
                li.appendChild(title);
                if (sub.textContent) { li.appendChild(sub); }
                // mousedown statt click, damit das Suchfeld vorher nichts schliesst
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    select(hits[index]);
                });
                list.appendChild(li);
            });
            list.classList.remove('hidden');
            search.setAttribute('aria-expanded', 'true');
        }

        function setField(name, value) {
            if (!form || !name) { return; }
            var field = form.elements[name];
            if (field && !field.tagName) { field = field[0]; } // RadioNodeList
            if (!field) { return; }
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function showLinked(customer) {
            linkedName.textContent = customer.display_name || customer.name || '';
            linked.classList.remove('hidden');
        }

        function select(customer) {
            if (!customer) { return; }
            idInput.value = customer.id;
            Object.keys(fieldMap).forEach(function (key) {
                if (!ATTR[key]) { return; }
                var value = customer[ATTR[key]];
                if (value === null || value === undefined || String(value) === '') { return; }
                setField(fieldMap[key], String(value));
            });
            showLinked(customer);
            search.value = '';
            closeList();
            if (autosubmit && form) {
                if (typeof form.requestSubmit === 'function') { form.requestSubmit(); } else { form.submit(); }
            }
        }

        function clearLink() {
            idInput.value = '';
            linkedName.textContent = '';
            linked.classList.add('hidden');
        }

        function runSearch(q) {
            if (controller) { controller.abort(); }
            controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            var seq = ++requestSeq;
            fetch(url + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                signal: controller ? controller.signal : undefined
            }).then(function (r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.json();
            }).then(function (data) {
                if (seq !== requestSeq) { return; } // veraltete Antwort
                render(Array.isArray(data) ? data : []);
            }).catch(function (err) {
                if (err && err.name === 'AbortError') { return; }
                if (seq === requestSeq) { closeList(); }
            });
        }

        search.addEventListener('input', function () {
            clearTimeout(timer);
            var q = search.value.trim();
            if (q.length < MIN_CHARS) {
                closeList();
                return;
            }
            cancelPending();
            pending = true;
            timer = setTimeout(function () { runSearch(q); }, DEBOUNCE_MS);
        });

        search.addEventListener('keydown', function (e) {
            var open = !list.classList.contains('hidden') && hits.length > 0;
            if (e.key === 'ArrowDown' && open) {
                e.preventDefault();
                setActive((active + 1) % hits.length);
            } else if (e.key === 'ArrowUp' && open) {
                e.preventDefault();
                setActive(active <= 0 ? hits.length - 1 : active - 1);
            } else if (e.key === 'Enter') {
                e.preventDefault(); // Formular nie über das Suchfeld absenden
                if (open && !pending) { select(hits[active >= 0 ? active : 0]); }
            } else if (e.key === 'Escape') {
                closeList();
            }
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) { closeList(); }
        });

        clearBtn.addEventListener('click', clearLink);
    }

    function initAll() {
        document.querySelectorAll('[data-customer-picker]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
