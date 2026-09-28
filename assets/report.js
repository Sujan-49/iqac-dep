(function() {
    'use strict';

    const ReportUI = {
        /**
         * Toggle the filter panel visibility
         */
        toggleFilter: function() {
            const panel = document.querySelector('.report-filter-panel');
            const btn = document.querySelector('.toggle-filter-btn');
            
            if (panel) {
                const isOpen = panel.classList.contains('is-open');
                if (isOpen) {
                    panel.classList.remove('is-open');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                } else {
                    panel.classList.add('is-open');
                    if (btn) btn.setAttribute('aria-expanded', 'true');
                }
            }
        },

        /**
         * Debounced client-side search
         */
        reportSearch: function(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            if (!input || !table) return;

            let timeout = null;

            input.addEventListener('input', function(e) {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const term = e.target.value.toLowerCase();
                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;

                    const rows = tbody.querySelectorAll('tr:not(.no-search)');
                    let visibleCount = 0;

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(term)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Update page info if exists
                    const pageInfo = document.querySelector('.page-info');
                    if (pageInfo) {
                        pageInfo.textContent = `Showing ${visibleCount} matching records`;
                    }

                    // Reset pagination if exists
                    const p = document.querySelector('.report-pagination');
                    if (p && p.dataset.active === 'true') {
                        ReportUI.reportPaginate(tableId, p.dataset.rows || 20);
                    }
                }, 300);
            });
        },

        /**
         * Click-to-sort on any th in the table
         */
        reportSort: function(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const headers = table.querySelectorAll('th.sortable');
            headers.forEach((th, index) => {
                th.addEventListener('click', () => {
                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;

                    const isAsc = th.classList.contains('sort-asc');
                    const direction = isAsc ? -1 : 1;
                    
                    // Reset all headers
                    headers.forEach(h => {
                        h.classList.remove('sort-asc', 'sort-desc');
                        h.setAttribute('aria-sort', 'none');
                    });

                    // Set current header
                    th.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
                    th.setAttribute('aria-sort', isAsc ? 'descending' : 'ascending');

                    const rows = Array.from(tbody.querySelectorAll('tr:not(.no-sort)'));
                    
                    rows.sort((a, b) => {
                        const aCol = a.cells[index];
                        const bCol = b.cells[index];
                        if (!aCol || !bCol) return 0;
                        
                        const aText = aCol.textContent.trim();
                        const bText = bCol.textContent.trim();
                        
                        // Auto-detect numeric
                        const aNum = parseFloat(aText.replace(/,/g, ''));
                        const bNum = parseFloat(bText.replace(/,/g, ''));
                        
                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            return (aNum - bNum) * direction;
                        }
                        
                        return aText.localeCompare(bText) * direction;
                    });

                    rows.forEach(row => tbody.appendChild(row));
                    
                    // Reset pagination if needed
                    const p = document.querySelector('.report-pagination');
                    if (p && p.dataset.active === 'true') {
                        ReportUI.reportPaginate(tableId, p.dataset.rows || 20);
                    }
                });
            });
        },

        /**
         * Auto-paginates tables
         */
        reportPaginate: function(tableId, rowsPerPage = 20) {
            const table = document.getElementById(tableId);
            const container = document.querySelector('.report-pagination');
            if (!table || !container) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            // Mark pagination as active
            container.dataset.active = 'true';
            container.dataset.rows = rowsPerPage;

            const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none' && !r.classList.contains('no-paginate'));
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            
            if (totalRows === 0) {
                container.innerHTML = '<span class="page-info">No records found</span>';
                return;
            }

            let currentPage = parseInt(container.dataset.currentPage) || 1;
            if (currentPage > totalPages) currentPage = 1;
            container.dataset.currentPage = currentPage;

            const start = (currentPage - 1) * rowsPerPage;
            const end = Math.min(start + rowsPerPage, totalRows);

            // Hide all rows, show only current page
            rows.forEach((row, idx) => {
                row.style.display = (idx >= start && idx < end) ? '' : 'none';
            });

            // Build UI
            let html = `<div class="page-info">Showing ${start + 1}-${end} of ${totalRows}</div>`;
            html += `<div class="pagination-controls">`;
            
            // Prev
            html += `<button type="button" class="btn-prev" ${currentPage === 1 ? 'disabled' : ''} onclick="window.ReportUI._goToPage('${tableId}', ${currentPage - 1}, ${rowsPerPage})">Prev</button>`;
            
            // Pages (simple version for < 7 pages, else truncated)
            for (let i = 1; i <= totalPages; i++) {
                if (totalPages > 7) {
                    if (i > 2 && i < totalPages - 1 && Math.abs(i - currentPage) > 1) {
                        if (i === 3 || i === totalPages - 2) html += '<span class="ellipsis">...</span>';
                        continue;
                    }
                }
                const active = i === currentPage ? 'active' : '';
                html += `<button type="button" class="btn-page ${active}" onclick="window.ReportUI._goToPage('${tableId}', ${i}, ${rowsPerPage})">${i}</button>`;
            }
            
            // Next
            html += `<button type="button" class="btn-next" ${currentPage === totalPages ? 'disabled' : ''} onclick="window.ReportUI._goToPage('${tableId}', ${currentPage + 1}, ${rowsPerPage})">Next</button>`;
            html += `</div>`;

            container.innerHTML = html;
        },

        _goToPage: function(tableId, page, rowsPerPage) {
            const container = document.querySelector('.report-pagination');
            if (container) {
                container.dataset.currentPage = page;
                this.reportPaginate(tableId, rowsPerPage);
            }
        },

        /**
         * Client-side Excel export
         */
        exportExcel: function(tableId, filename = 'export.xls') {
            const table = document.getElementById(tableId);
            if (!table) return;

            const clone = table.cloneNode(true);
            // Remove rows that shouldn't be exported
            clone.querySelectorAll('.no-export').forEach(el => el.remove());

            const html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Sheet1</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>
                <body>${clone.outerHTML}</body>
                </html>
            `;

            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },

        /**
         * Client-side CSV export
         */
        exportCSV: function(tableId, filename = 'export.csv') {
            const table = document.getElementById(tableId);
            if (!table) return;

            let csv = [];
            const rows = table.querySelectorAll('tr:not(.no-export)');
            
            rows.forEach(row => {
                let rowData = [];
                const cols = row.querySelectorAll('th, td');
                
                cols.forEach(col => {
                    if (!col.classList.contains('no-export')) {
                        let text = col.innerText.replace(/"/g, '""');
                        rowData.push('"' + text + '"');
                    }
                });
                csv.push(rowData.join(','));
            });

            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const a = document.createElement('a');
            a.download = filename;
            a.href = URL.createObjectURL(csvFile);
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        },

        /**
         * Client-side Word export
         */
        exportWord: function(tableId, filename = 'export.doc') {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const header = document.querySelector('.report-header');
            const headerHtml = header ? header.outerHTML : '';

            const clone = table.cloneNode(true);
            clone.querySelectorAll('.no-export').forEach(el => el.remove());

            const html = `
                <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                <head><meta charset='utf-8'><title>Export HTML To Doc</title></head>
                <body>
                    ${headerHtml}
                    ${clone.outerHTML}
                </body>
                </html>
            `;

            const blob = new Blob(['\ufeff', html], { type: 'application/msword' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },

        /**
         * Print report
         */
        reportPrint: function() {
            document.body.classList.add('is-printing');
            window.print();
            // Need a slight delay to allow the print dialog to close in some browsers
            setTimeout(() => {
                document.body.classList.remove('is-printing');
            }, 100);
        },

        /**
         * Toggle fullscreen
         */
        toggleFullscreen: function() {
            const elem = document.querySelector('.report-shell') || document.documentElement;
            
            if (!document.fullscreenElement) {
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) { /* Safari */
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) { /* IE11 */
                    elem.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) { /* Safari */
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) { /* IE11 */
                    document.msExitFullscreen();
                }
            }
        }
    };

    // Expose to window
    window.ReportUI = ReportUI;

    // Auto-init on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        // Init sort listeners
        const tables = document.querySelectorAll('.report-table');
        tables.forEach((table, idx) => {
            const id = table.id || `report-table-${idx}`;
            table.id = id;
            ReportUI.reportSort(id);
        });

        // Init search
        const searchInput = document.getElementById('report-search');
        if (searchInput) {
            const targetTableId = searchInput.dataset.tableTarget || document.querySelector('.report-table')?.id;
            if (targetTableId) {
                ReportUI.reportSearch('report-search', targetTableId);
            }
        }

        // Init pagination
        const pagination = document.querySelector('.report-pagination');
        if (pagination) {
            const targetTableId = pagination.dataset.tableTarget || document.querySelector('.report-table')?.id;
            if (targetTableId) {
                ReportUI.reportPaginate(targetTableId, parseInt(pagination.dataset.rows || 20));
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const panel = document.querySelector('.report-filter-panel');
                if (panel && panel.classList.contains('is-open')) {
                    ReportUI.toggleFilter();
                }
            }
        });
        
        // Form enter submisson for search
        if (searchInput) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    // Search is debounced so it will apply automatically
                }
            });
        }
    });

})();
