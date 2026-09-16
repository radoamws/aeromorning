/* globals jQuery, XNF */
(function ($) {
    'use strict';

    var currentPage   = 1;
    var searchTimer   = null;

    function getFilters() {
        return {
            page:   currentPage,
            search: $('#xnf-search').val(),
            status: $('#xnf-filter-status').val(),
        };
    }

    function loadRows(page) {
        currentPage = page || 1;
        var data = $.extend({ action: 'xnf_get_rows', nonce: XNF.nonce }, getFilters());
        $.post(XNF.ajax_url, data, function (res) {
            if (!res.success) return;
            var d     = res.data;
            var tbody = $('#xnf-tbody');
            tbody.empty();

            if (!d.rows.length) {
                tbody.append('<tr><td colspan="6">Aucun résultat.</td></tr>');
            } else {
                $.each(d.rows, function (i, row) {
                    var toggleClass = row.status == 1 ? 'xnf-toggle-on' : '';
                    var label       = row.status == 1 ? 'Publié' : 'Non publié';
                    tbody.append(
                        '<tr data-id="' + row.id + '">' +
                        '<td><input type="checkbox" class="xnf-row-cb" value="' + row.id + '" /></td>' +
                        '<td>' + row.id + '</td>' +
                        '<td>' + escHtml(row.title) + '</td>' +
                        '<td>' + escHtml(row.date_publish) + '</td>' +
                        '<td>' + escHtml(row.date_string) + '</td>' +
                        '<td><span class="xnf-status-toggle ' + toggleClass + '" data-id="' + row.id + '" data-status="' + row.status + '">' +
                        '<span class="xnf-toggle-slider"></span>' + label +
                        '</span></td>' +
                        '</tr>'
                    );
                });
            }

            // Pagination
            var pages = $('#xnf-pagination');
            pages.empty();
            for (var p = 1; p <= d.last_page; p++) {
                var btn = $('<button>').text(p);
                if (p === d.page) btn.addClass('current');
                (function(pg) {
                    btn.on('click', function () { loadRows(pg); });
                }(p));
                pages.append(btn);
            }
        });
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    $(document).ready(function () {
        if (!$('#xnf-table').length) return;
        loadRows(1);

        // Recherche avec debounce
        $('#xnf-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadRows(1); }, 400);
        });

        // Filtre statut
        $('#xnf-filter-status').on('change', function () { loadRows(1); });

        // Toggle statut individuel
        $(document).on('click', '.xnf-status-toggle', function () {
            var $el    = $(this);
            var id     = parseInt($el.data('id'), 10);
            var status = parseInt($el.data('status'), 10);
            var newStatus = status === 1 ? 0 : 1;

            $.post(XNF.ajax_url, {
                action: 'xnf_toggle_status',
                nonce:  XNF.nonce,
                id:     id,
                status: newStatus,
            }, function (res) {
                if (res.success) {
                    $el.data('status', newStatus);
                    $el.toggleClass('xnf-toggle-on', newStatus === 1);
                    $el.find('.xnf-toggle-slider').next().remove();
                    $el.append(newStatus === 1 ? 'Publié' : 'Non publié');
                }
            });
        });

        // Sélectionner tout
        $('#xnf-select-all').on('change', function () {
            $('.xnf-row-cb').prop('checked', this.checked);
        });

        // Action groupée
        $('#xnf-bulk-apply').on('click', function () {
            var ids    = $('.xnf-row-cb:checked').map(function () { return this.value; }).get();
            var status = $('#xnf-bulk-action').val();
            if (!ids.length || status === '') {
                alert('Sélectionnez des lignes et une action.');
                return;
            }
            $.post(XNF.ajax_url, {
                action: 'xnf_bulk_status',
                nonce:  XNF.nonce,
                ids:    ids,
                status: status,
            }, function (res) {
                if (res.success) loadRows(currentPage);
            });
        });
    });
}(jQuery));
