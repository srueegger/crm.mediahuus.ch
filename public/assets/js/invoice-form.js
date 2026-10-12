/**
 * Rechnungsformular: Zeilen verwalten und Live-Vorschau berechnen.
 *
 * Rechnet mit ganzen Rappen (BigInt) und kaufmaennischer Rundung (Half-Up),
 * identisch zur Formel von InvoiceCalculator. Nur Anzeige: Der Server rechnet verbindlich.
 */
(function (root) {
    'use strict';

    // ---------- Berechnung (ohne DOM) ----------

    /** "49.90" / "49,90" / "1'000" -> ganze Hundertstel (BigInt) oder null bei ungueltiger Eingabe. */
    function parseHundredths(value) {
        var s = String(value === undefined || value === null ? '' : value).trim().replace(/'/g, '').replace(',', '.');
        if (!/^\d+(\.\d{0,2})?$/.test(s)) {
            return null;
        }
        var parts = s.split('.');
        var frac = ((parts[1] || '') + '00').slice(0, 2);
        return BigInt(parts[0]) * 100n + BigInt(frac);
    }

    /** Ganzzahldivision mit Half-Up-Rundung (nur nicht-negative Werte). */
    function divRoundHalfUp(numerator, denominator) {
        return (2n * numerator + denominator) / (2n * denominator);
    }

    /** Zeilentotal in Rappen: round_half_up(Menge x Einzelpreis). qtyH und priceCents in Hundertsteln. */
    function lineTotalCents(qtyH, priceCents) {
        return divRoundHalfUp(qtyH * priceCents, 100n);
    }

    /** MWST in Rappen aus Brutto: round_half_up(brutto x satz / (100 + satz)), satz in Hundertsteln. */
    function vatCents(grossCents, rateH) {
        return divRoundHalfUp(grossCents * rateH, 10000n + rateH);
    }

    /**
     * @param {Array<{quantity: string, unitPrice: string, vatRate: string}>} rows
     * @returns {{lines: Array<bigint|null>, groups: Array<{rate: string, gross: bigint, vat: bigint, net: bigint}>, net: bigint, vat: bigint, gross: bigint}}
     */
    function calculate(rows) {
        var lines = [];
        var byRate = {};
        rows.forEach(function (row) {
            var q = parseHundredths(row.quantity);
            var p = parseHundredths(row.unitPrice);
            var rateH = parseHundredths(row.vatRate);
            if (q === null || p === null || rateH === null) {
                lines.push(null);
                return;
            }
            var total = lineTotalCents(q, p);
            lines.push(total);
            var key = rateH.toString();
            byRate[key] = (byRate[key] || 0n) + total;
        });

        var groups = Object.keys(byRate)
            .sort(function (a, b) { return Number(BigInt(b) - BigInt(a)); })
            .map(function (key) {
                var rateH = BigInt(key);
                var gross = byRate[key];
                var vat = vatCents(gross, rateH);
                return { rate: formatRate(rateH), gross: gross, vat: vat, net: gross - vat };
            });

        var net = 0n, vat = 0n, gross = 0n;
        groups.forEach(function (g) { net += g.net; vat += g.vat; gross += g.gross; });
        return { lines: lines, groups: groups, net: net, vat: vat, gross: gross };
    }

    /** Satz in Hundertsteln -> kanonische Schreibweise ("810" -> "8.1", "0" -> "0"). */
    function formatRate(rateH) {
        var whole = rateH / 100n;
        var frac = (rateH % 100n).toString().padStart(2, '0').replace(/0+$/, '');
        return whole.toString() + (frac ? '.' + frac : '');
    }

    /** Rappen -> "1'234.50" */
    function formatCents(cents) {
        var negative = cents < 0n;
        var abs = negative ? -cents : cents;
        var whole = (abs / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, "'");
        var frac = (abs % 100n).toString().padStart(2, '0');
        return (negative ? '-' : '') + whole + '.' + frac;
    }

    /** Fälligkeitsdatum "dd.mm.yyyy" aus "yyyy-mm-dd" + Tagen, sonst "-". */
    function dueDate(dateStr, days) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(dateStr).trim());
        var d = /^\d{1,3}$/.test(String(days).trim()) ? parseInt(days, 10) : NaN;
        if (!m || isNaN(d) || d > 365) {
            return '-';
        }
        var date = new Date(Date.UTC(+m[1], +m[2] - 1, +m[3]));
        if (date.getUTCFullYear() !== +m[1] || date.getUTCMonth() !== +m[2] - 1 || date.getUTCDate() !== +m[3]) {
            return '-';
        }
        date.setUTCDate(date.getUTCDate() + d);
        var pad = function (n) { return String(n).padStart(2, '0'); };
        return pad(date.getUTCDate()) + '.' + pad(date.getUTCMonth() + 1) + '.' + date.getUTCFullYear();
    }

    var api = {
        parseHundredths: parseHundredths,
        lineTotalCents: lineTotalCents,
        vatCents: vatCents,
        calculate: calculate,
        formatCents: formatCents,
        dueDate: dueDate
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = api;
    }
    root.InvoiceForm = api;

    // ---------- DOM ----------

    if (typeof document === 'undefined') {
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('invoice-form');
        if (!form) {
            return;
        }
        var container = document.getElementById('items-container');
        var template = document.getElementById('item-template');
        var addButton = document.getElementById('add-item-btn');

        function rows() {
            return Array.prototype.slice.call(container.querySelectorAll('.item-row'));
        }

        function nextIndex() {
            var max = -1;
            rows().forEach(function (row) {
                var i = parseInt(row.getAttribute('data-index'), 10);
                if (!isNaN(i) && i > max) { max = i; }
            });
            return max + 1;
        }

        function renumber() {
            var all = rows();
            all.forEach(function (row, n) {
                row.querySelector('.item-number').textContent = String(n + 1);
                row.querySelector('.remove-item-btn').style.display = all.length > 1 ? '' : 'none';
            });
        }

        function update() {
            var all = rows();
            var result = calculate(all.map(function (row) {
                return {
                    quantity: row.querySelector('.item-quantity').value,
                    unitPrice: row.querySelector('.item-unit-price').value,
                    vatRate: row.querySelector('.item-vat-rate').value
                };
            }));

            all.forEach(function (row, n) {
                var line = result.lines[n];
                row.querySelector('.item-line-total').textContent = 'CHF ' + (line === null ? '0.00' : formatCents(line));
            });

            document.getElementById('preview-net').textContent = 'CHF ' + formatCents(result.net);
            document.getElementById('preview-gross').textContent = 'CHF ' + formatCents(result.gross);

            var groupsEl = document.getElementById('preview-vat-groups');
            groupsEl.innerHTML = '';
            result.groups.forEach(function (g) {
                var line = document.createElement('div');
                line.className = 'flex justify-between gap-4 text-sm text-gray-700';
                var label = document.createElement('span');
                label.textContent = 'MWST ' + g.rate + ' % auf ' + formatCents(g.net);
                var amount = document.createElement('span');
                amount.className = 'font-medium whitespace-nowrap';
                amount.textContent = formatCents(g.vat);
                line.appendChild(label);
                line.appendChild(amount);
                groupsEl.appendChild(line);
            });

            document.getElementById('due-date').textContent = dueDate(
                document.getElementById('invoice_date').value,
                document.getElementById('payment_term_days').value
            );
        }

        function addRow() {
            var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex()));
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            var row = wrapper.firstElementChild;
            container.appendChild(row);
            renumber();
            update();
            var desc = row.querySelector('.item-description');
            if (desc) { desc.focus(); }
        }

        addButton.addEventListener('click', addRow);

        container.addEventListener('click', function (event) {
            var button = event.target.closest('.remove-item-btn');
            if (!button) { return; }
            var row = button.closest('.item-row');
            if (rows().length > 1) {
                row.remove();
                renumber();
                update();
            }
        });

        form.addEventListener('input', update);
        form.addEventListener('change', update);

        renumber();
        update();
    });
})(typeof window !== 'undefined' ? window : globalThis);
