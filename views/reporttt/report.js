
$(document).ready(function() {
    // Initialize Select2 for dropdowns
    $('.select').select2({
        width: '100%',
        placeholder: "Pilih opsi...",
        allowClear: true
    });
    
    // Safe initialization of DataTable - check if it's already initialized
    var reportTable;
    if (!$.fn.DataTable.isDataTable('#report-table')) {
        reportTable = $('#report-table').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> Copy',
                    className: 'btn btn-sm btn-secondary'
                },
                {
                    extend: 'csv',
                    text: '<i class="fas fa-file-csv"></i> CSV',
                    className: 'btn btn-sm btn-secondary'
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-sm btn-success'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-sm btn-primary'
                }
            ],
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "language": {
                "paginate": {
                    "previous": "<i class='fas fa-chevron-left'></i>",
                    "next": "<i class='fas fa-chevron-right'></i>"
                },
                "search": "Search:",
                "emptyTable": "Tidak ada data laporan yang tersedia",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                "infoFiltered": "(difilter dari _MAX_ total entri)",
                "lengthMenu": "Tampilkan _MENU_ entri",
                "zeroRecords": "Tidak ditemukan data yang sesuai"
            },
            "order": [[1, 'desc']] // Order by date column descending
        });
        
        // Add the DataTable buttons to the custom button container only if not already added
        reportTable.buttons().container().appendTo($('.wordset ul'));
    } else {
        // If already initialized, just get the existing instance
        reportTable = $('#report-table').DataTable();
    }
    
    // Search functionality
    $('#search-input').on('keyup', function() {
        reportTable.search(this.value).draw();
    });
    
    // Filter Type Change Handler
    $('#filter-type').on('change', function() {
        const filterType = $(this).val();
        $('.filter-option').hide();
        
        if (filterType === 'date_range') {
            $('#date-range-filter').show();
        } else if (filterType === 'month') {
            $('#month-filter').show();
            $('#year-filter').show();
        } else if (filterType === 'year') {
            $('#year-filter').show();
        }
    });
    
    // Initialize Date Range Picker
    if ($('#daterange').length && !$('#daterange').data('daterangepicker')) {
        $('#daterange').daterangepicker({
            opens: 'left',
            autoUpdateInput: true,
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: 'Terapkan',
                cancelLabel: 'Batal',
                fromLabel: 'Dari',
                toLabel: 'Sampai',
                customRangeLabel: 'Kustom',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
            },
            ranges: {
               'Hari Ini': [moment(), moment()],
               'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
               '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
               'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
               'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function(start, end, label) {
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));
        });
    }
    
    // Handle receipt image modal
    $('#receipt-modal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const imgSrc = button.data('img');
        const title = button.data('title');
        
        const modal = $(this);
        modal.find('.modal-title').text(title);
        modal.find('#receipt-img').attr('src', imgSrc);
        
        // Set download link
        $('#download-receipt').off('click').on('click', function() {
            const a = document.createElement('a');
            a.href = imgSrc;
            a.download = 'receipt_' + title.replace('Nota #', '') + '.jpg';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    });
    
    // Print functionality
    $('#print-btn, #table-print').off('click').on('click', function() {
        printReport();
    });
    
    // Export to PDF button
    $('#export-pdf').off('click').on('click', function() {
        exportReport('pdf');
    });
    
    // Export to Excel button
    $('#export-excel').off('click').on('click', function() {
        exportReport('excel');
    });
    
    // Initialize Category Distribution Chart
    initCategoryChart();
    
    // Initialize Value Distribution Chart
    initValueChart();
    
    // Refresh charts when filter form is submitted
    $('#report-filter-form').off('submit').on('submit', function() {
        refreshCharts();
        return true; // Continue with form submission
    });
});

// Function to print the report
function printReport() {
    // Set period text
    let periodText = '';
    const filterType = $('#filter-type').val();
    
    if (filterType === 'date_range') {
        periodText = $('#daterange').val();
    } else if (filterType === 'month') {
        const month = $('select[name="filter_month"] option:selected').text();
        const year = $('select[name="filter_year"]').val();
        periodText = month + ' ' + year;
    } else if (filterType === 'year') {
        periodText = 'Tahun ' + $('select[name="filter_year"]').val();
    }
    
    $('#print-period').text(periodText);
    
    // Populate table body
    const tableBody = $('#print-table-body');
    tableBody.empty();
    
    // Get data from the visible table
    $('#report-table tbody tr').each(function() {
        const cells = $(this).find('td');
        
        // Skip if it's the "no data" row
        if (cells.length <= 1) return;
        
        const id = cells.eq(0).text();
        const date = cells.eq(1).text();
        const product = cells.eq(2).find('a').text();
        const category = cells.eq(3).text();
        const qty = cells.eq(4).text();
        const price = cells.eq(5).text();
        const total = cells.eq(6).text();
        
        tableBody.append(`
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;">${id}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${date}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${product}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${category}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${qty}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${price}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${total}</td>
            </tr>
        `);
    });
    
    // Open print dialog
    const printContent = document.getElementById('print-template').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print Report</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        padding: 20px;
                    }
                    h2 {
                        color: #333;
                        margin-bottom: 5px;
                    }
                    p {
                        color: #666;
                        margin-top: 0;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin-top: 20px;
                    }
                    th, td { 
                        padding: 8px; 
                        border: 1px solid #ddd; 
                    }
                    th { 
                        background-color: #f2f2f2; 
                        font-weight: bold;
                        text-align: left;
                    }
                    tfoot td {
                        font-weight: bold;
                        background-color: #f9f9f9;
                    }
                </style>
            </head>
            <body>
                ${printContent}
            </body>
        </html>
    `);
    printWindow.document.close();
    
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}

// Function to export report (PDF or Excel)
function exportReport(type) {
    // Get current filter values
    const filterForm = $('#report-filter-form');
    const filterType = $('#filter-type').val();
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    const filterMonth = $('select[name="filter_month"]').val();
    const filterYear = $('select[name="filter_year"]').val();
    const productId = $('select[name="product_id"]').val();
    const categoryId = $('select[name="category_id"]').val();
    
    // Construct URL with parameters
    let url = 'report_export.php?export=' + type;
    
    if (filterType === 'date_range') {
        url += '&filter_type=date_range&start_date=' + startDate + '&end_date=' + endDate;
    } else if (filterType === 'month') {
        url += '&filter_type=month&filter_month=' + filterMonth + '&filter_year=' + filterYear;
    } else if (filterType === 'year') {
        url += '&filter_type=year&filter_year=' + filterYear;
    }
    
    if (productId) {
        url += '&product_id=' + productId;
    }
    
    if (categoryId) {
        url += '&category_id=' + categoryId;
    }
    
    // Open in new window/tab
    window.open(url, '_blank');
}

// Initialize Category Distribution Chart
function initCategoryChart() {
    const categoryCtx = document.getElementById('categoryChart');
    
    if (!categoryCtx) return;
    
    // Destroy existing chart if it exists
    if (window.categoryChart) {
        window.categoryChart.destroy();
    }
    
    window.categoryChart = new Chart(categoryCtx.getContext('2d'), {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                label: 'Jumlah Items',
                data: [],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(199, 199, 199, 0.7)',
                    'rgba(83, 102, 255, 0.7)',
                    'rgba(40, 159, 64, 0.7)',
                    'rgba(210, 199, 199, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Load initial data
    const labels = [];
    const data = [];
    
    // Get data from PHP-rendered elements with special data attributes
    const dataElements = document.querySelectorAll('[data-category-name]');
    dataElements.forEach(element => {
        labels.push(element.getAttribute('data-category-name'));
        data.push(parseInt(element.getAttribute('data-category-count')));
    });
    
    // If no data elements found, try to parse from the script
    if (labels.length === 0) {
        try {
            // This assumes there's PHP-rendered data in the original script
            const categoryData = JSON.parse(document.getElementById('category-data').textContent);
            categoryData.forEach(item => {
                labels.push(item.name);
                data.push(item.count);
            });
        } catch (e) {
            console.warn('Could not load category chart data', e);
        }
    }
    
    // Update chart with data
    window.categoryChart.data.labels = labels;
    window.categoryChart.data.datasets[0].data = data;
    window.categoryChart.update();
}

// Initialize Value Distribution Chart
function initValueChart() {
    const valueCtx = document.getElementById('valueChart');
    
    if (!valueCtx) return;
    
    // Destroy existing chart if it exists
    if (window.valueChart) {
        window.valueChart.destroy();
    }
    
    window.valueChart = new Chart(valueCtx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Nilai (Rp)',
                data: [],
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'Rp ' + context.parsed.y.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    // Load initial data
    const labels = [];
    const data = [];
    
    // Get data from PHP-rendered elements with special data attributes
    const dataElements = document.querySelectorAll('[data-category-name]');
    dataElements.forEach(element => {
        labels.push(element.getAttribute('data-category-name'));
        data.push(parseInt(element.getAttribute('data-category-amount')));
    });
    
    // If no data elements found, try to parse from the script
    if (labels.length === 0) {
        try {
            // This assumes there's PHP-rendered data in the original script
            const categoryData = JSON.parse(document.getElementById('category-data').textContent);
            categoryData.forEach(item => {
                labels.push(item.name);
                data.push(item.amount);
            });
        } catch (e) {
            console.warn('Could not load value chart data', e);
        }
    }
    
    // Update chart with data
    window.valueChart.data.labels = labels;
    window.valueChart.data.datasets[0].data = data;
    window.valueChart.update();
}

// Refresh chart data from server
function refreshCharts() {
    // Try to update charts with data present in the page first
    try {
        initCategoryChart();
        initValueChart();
    } catch (e) {
        console.warn('Could not refresh charts with page data', e);
    }
}