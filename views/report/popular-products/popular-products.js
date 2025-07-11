/**
 * popular-products.js - Script for handling product popularity report functionality
 * 
 * This script manages:
 * - DataTable initialization
 * - Date filtering and form handling
 * - View toggling (grid vs table)
 * - Export functionality (PDF, Excel)
 * - Chart initialization
 */

// Document ready function
$(document).ready(function () {
    // Initialize datatable with error handling
    initializeDataTable();
    
    // Handle date preset selection
    handleDatePresets();
    
    // Handle limit and sort changes
    handleFilterChanges();
    
    // Form validation
    setupFormValidation();
    
    // View toggle
    setupViewToggle();
    
    // Export functionality
    setupExportFunctionality();
    
    // Initialize Chart.js charts if they exist
    if (typeof initializeCharts === 'function') {
        initializeCharts();
    }
    
    // Hide loading overlay when page is fully loaded
    hideLoadingWithDelay(500);
    
    // Check user role for hiding UI elements
    checkUserPermissions();
});

/**
 * Check user permissions and adjust UI accordingly
 */
function checkUserPermissions() {
    // Check if body has class 'employee' or 'user'
    if ($('body').hasClass('employee') || $('body').hasClass('user')) {
        // Hide any admin-only elements that might not have been hidden by PHP
        $('.admin-only').hide();
    }
}

/**
 * Initialize the DataTable with appropriate settings
 */
function initializeDataTable() {
    try {
        $('.datanew').DataTable({
            responsive: true,
            language: {
                search: '<span>Cari:</span> _INPUT_',
                searchPlaceholder: 'Cari produk...',
                lengthMenu: '<span>Tampilkan:</span> _MENU_',
                paginate: {
                    'first': 'Pertama',
                    'last': 'Terakhir',
                    'next': 'Berikutnya',
                    'previous': 'Sebelumnya'
                },
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                infoFiltered: "(disaring dari _MAX_ entri total)",
                emptyTable: "Tidak ada data tersedia untuk periode yang dipilih",
                zeroRecords: "Tidak ditemukan catatan yang cocok"
            },
            dom: '<"top"fl>rt<"bottom"ip><"clear">',
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            drawCallback: function() {
                hideLoadingOverlay();
            }
        });
    } catch (e) {
        console.error("DataTable initialization error:", e);
        hideLoadingOverlay();
    }
}

/**
 * Handle date preset selection changes
 */
function handleDatePresets() {
    $('#preset').on('change', function() {
        if ($(this).val() === '') {
            // Custom date range selected - show custom date inputs
            $('#custom-date-inputs').show();
        } else {
            // Preset selected - hide custom date inputs and show loading overlay
            $('#custom-date-inputs').hide();
            showLoadingOverlay();
            
            // Add a small delay before submitting to ensure loading overlay is visible
            setTimeout(function() {
                $('#date-filter-form').submit();
            }, 100);
        }
    });
}

/**
 * Handle limit and sort_by changes (auto-submit form)
 */
function handleFilterChanges() {
    $('#limit, #sort_by').on('change', function() {
        showLoadingOverlay();
        
        // Add a small delay before submitting
        setTimeout(function() {
            $('#date-filter-form').submit();
        }, 100);
    });
    
    // Handle reset button - show loading overlay
    $('a[href="?reset=1"]').on('click', function() {
        showLoadingOverlay();
    });
}

/**
 * Setup form validation
 */
function setupFormValidation() {
    $('#date-filter-form').on('submit', function(e) {
        showLoadingOverlay();
        
        // Only validate if custom date range is selected
        if ($('#preset').val() === '') {
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            
            if (!startDate || !endDate) {
                alert('Harap pilih tanggal awal dan akhir');
                hideLoadingOverlay();
                e.preventDefault();
                return false;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Tanggal awal tidak boleh lebih besar dari tanggal akhir');
                hideLoadingOverlay();
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    });
}

/**
 * Setup view toggle between grid and table
 */
function setupViewToggle() {
    $('.view-toggle button').on('click', function() {
        const viewType = $(this).data('view');
        
        // Update active button
        $('.view-toggle button').removeClass('active');
        $(this).addClass('active');
        
        // Show/hide appropriate view
        if (viewType === 'grid') {
            $('#grid-view').show();
            $('#table-view').hide();
        } else {
            $('#grid-view').hide();
            $('#table-view').show();
        }
    });
}

/**
 * Setup export functionality
 */
function setupExportFunctionality() {
    // PDF Export
    $('.pdf-export').on('click', function(e) {
        e.preventDefault();
        
        showLoadingOverlay();
        
        // Check if the user has permission (controlled by CSS class)
        if ($(this).hasClass('disabled-for-employee')) {
            showPermissionModal('Ekspor PDF', 'Laporan ini tidak dapat diekspor ke PDF oleh akun karyawan.');
            hideLoadingOverlay();
            return;
        }
        
        // If we have permission, proceed with export
        exportToPDF();
    });

    // Excel Export
    $('.excel-export').on('click', function(e) {
        e.preventDefault();
        
        showLoadingOverlay();
        
        // Check if the user has permission
        if ($(this).hasClass('disabled-for-employee')) {
            showPermissionModal('Ekspor Excel', 'Laporan ini tidak dapat diekspor ke Excel oleh akun karyawan.');
            hideLoadingOverlay();
            return;
        }
        
        // If we have permission, proceed with export
        exportToExcel();
    });

    // Print Report
    $('.print-report').on('click', function(e) {
        e.preventDefault();
        
        // Check if the user has permission
        if ($(this).hasClass('disabled-for-employee')) {
            showPermissionModal('Cetak Laporan', 'Pencetakan laporan dibatasi hanya untuk akun administrator.');
            return;
        }
        
        // If we have permission, proceed with print
        printReport();
    });
    
    // For employee access - handle restricted action attempts
    $('.employee-print, .employee-export').on('click', function(e) {
        e.preventDefault();
        
        // Get the action type from data attribute or default
        var actionType = $(this).data('action') === 'print_attempt' ? 'Cetak Laporan' : 'Ekspor Laporan';
        var message = $(this).data('action') === 'print_attempt' 
            ? 'Pencetakan laporan dibatasi hanya untuk akun administrator.' 
            : 'Ekspor data dibatasi hanya untuk akun administrator.';
        
        showPermissionModal(actionType, message);
    });
}

/**
 * Show permission denied modal
 */
function showPermissionModal(actionType, message) {
    // Set the action details
    $('#actionDetails').html(
        '<strong>Tindakan yang Dicoba:</strong> ' + actionType + '<br>' +
        '<small>' + message + '</small>'
    );
    
    // Show the modal
    var permissionModal = new bootstrap.Modal(document.getElementById('permissionModal'));
    permissionModal.show();
}

/**
 * Print the current report
 */
function printReport() {
    // Add print-specific styling
    $('<style>')
        .attr('type', 'text/css')
        .html('@media print { ' +
            '.no-print, .dataTables_filter, .dataTables_length, .dataTables_paginate, .sidebar, .header, #sidebar, #mobile_btn { display: none !important; } ' +
            '.page-wrapper { margin-left: 0 !important; padding: 20px !important; } ' +
            '.card { border: none !important; box-shadow: none !important; } ' +
            'thead { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact !important; color-adjust: exact !important; } ' +
            'table { width: 100% !important; } ' +
            '.table th, .table td { padding: 0.25rem !important; } ' +
            '.report-header { text-align: center; margin-bottom: 20px; } ' +
            '.report-header h3 { margin-bottom: 5px; } ' +
            '#table-view { display: block !important; } ' +
            '#grid-view { display: none !important; } ' +
            '}')
        .appendTo('head');

    // Add a report header for printing
    if ($('.report-header').length === 0) {
        // Get date range for report header
        var startDate = $('#start_date').val() || $('.alert-info strong').next().text().split(' sampai ')[0];
        var endDate = $('#end_date').val() || $('.alert-info strong').next().text().split(' sampai ')[1];
        
        $('.content').prepend(
            '<div class="report-header no-screen" style="display:none;">' +
            '<h2>Ayula Store - Laporan Produk Terlaris</h2>' +
            '<p>Periode: ' + startDate + ' sampai ' + endDate + '</p>' +
            '<p>Dibuat pada: ' + new Date().toLocaleDateString() + '</p>' +
            '</div>'
        );
    }

    // Force table view for printing
    $('#grid-view').hide();
    $('#table-view').show();

    window.print();

    // Restore the original view after printing
    if ($('.view-toggle button[data-view="grid"]').hasClass('active')) {
        $('#grid-view').show();
        $('#table-view').hide();
    }
}

/**
 * Export table to Excel (CSV)
 */
function exportToExcel() {
    // Switch to table view temporarily
    $('#grid-view').hide();
    $('#table-view').show();
    
    // Get the table data
    var table = $('.datanew').DataTable();
    var data = [];
    
    try {
        // Get all rows from the table (not just the current page)
        table.rows().every(function() {
            data.push(this.data());
        });
        
        var headers = [];

        // Get headers
        $('.datanew thead th').each(function () {
            headers.push($(this).text());
        });

        // Create CSV content
        var csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\n";

        // Add data rows
        for (var i = 0; i < data.length; i++) {
            var row = [];
            for (var j = 0; j < data[i].length; j++) {
                // Clean the data (remove HTML tags)
                var cellData = data[i][j].toString().replace(/<[^>]*>/g, '').trim();
                // Replace multiple spaces with a single space
                cellData = cellData.replace(/\s+/g, ' ');
                // Wrap in quotes and escape internal quotes
                row.push('"' + cellData.replace(/"/g, '""') + '"');
            }
            csvContent += row.join(",") + "\n";
        }

        // Create download link
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        
        // Use the current date in the filename
        var today = new Date();
        var dateStr = today.getFullYear() + '-' + 
                     ('0' + (today.getMonth()+1)).slice(-2) + '-' + 
                     ('0' + today.getDate()).slice(-2);
        
        link.setAttribute("download", "produk_terlaris_" + dateStr + ".csv");
        document.body.appendChild(link);

        // Download the file
        link.click();

        // Clean up
        document.body.removeChild(link);
    } catch (e) {
        console.error("Error exporting to Excel:", e);
        alert("Terjadi kesalahan saat mengekspor. Silakan coba lagi.");
    }
    
    // Hide loading overlay when export is complete
    hideLoadingOverlay();
    
    // Restore the original view after exporting
    if ($('.view-toggle button[data-view="grid"]').hasClass('active')) {
        $('#grid-view').show();
        $('#table-view').hide();
    }
}

/**
 * Export to PDF via server
 */
function exportToPDF() {
    try {
        // Create a form for server-side generation
        var form = $('<form action="" method="post" target="_blank"></form>');
        form.append('<input type="hidden" name="export_pdf" value="1">');
        form.append('<input type="hidden" name="start_date" value="' + $('#start_date').val() + '">');
        form.append('<input type="hidden" name="end_date" value="' + $('#end_date').val() + '">');
        form.append('<input type="hidden" name="limit" value="' + $('#limit').val() + '">');
        form.append('<input type="hidden" name="sort_by" value="' + $('#sort_by').val() + '">');
        
        // Add the form to the document and submit it
        $('body').append(form);
        form.submit();
        form.remove();
    } catch (e) {
        console.error("Error exporting to PDF:", e);
        alert("Terjadi kesalahan saat mengekspor ke PDF. Silakan coba lagi.");
    }
    
    // Hide loading overlay after 2 seconds (since PDF generation happens in new tab)
    setTimeout(function() {
        hideLoadingOverlay();
    }, 2000);
}

/**
 * Show loading overlay
 */
function showLoadingOverlay() {
    if (document.getElementById('loading-overlay')) {
        document.getElementById('loading-overlay').style.display = 'flex';
    }
}

/**
 * Hide loading overlay
 */
function hideLoadingOverlay() {
    if (document.getElementById('loading-overlay')) {
        document.getElementById('loading-overlay').style.display = 'none';
    }
}

/**
 * Hide loading overlay with delay
 */
function hideLoadingWithDelay(delay) {
    setTimeout(function() {
        hideLoadingOverlay();
    }, delay || 500);
}

// Hide loading overlay when page is loaded
window.addEventListener('load', function() {
    hideLoadingWithDelay(500);
});

// Failsafe timeout to hide loading overlay after 10 seconds
setTimeout(function() {
    hideLoadingOverlay();
}, 10000);

// Disable console logs in production
if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    console.log = function() {}; // Disable console logs
    console.warn = function() {}; // Disable console warnings
    console.error = function() {}; // Disable console errors
}