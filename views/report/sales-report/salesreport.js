// salesreport.js - Versi yang ditingkatkan dengan penanganan tidak ada data
$(document).ready(function () {
    // Inisialisasi datatable dengan penanganan kesalahan
    try {
        $('.datanew').DataTable({
            responsive: true,
            language: {
                search: '<span>Cari:</span> _INPUT_',
                searchPlaceholder: 'Cari transaksi...',
                lengthMenu: '<span>Tampilkan:</span> _MENU_',
                paginate: {
                    'first': 'Pertama',
                    'last': 'Terakhir',
                    'next': '>',
                    'previous': '<'
                },
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                infoFiltered: "(disaring dari _MAX_ entri total)",
                emptyTable: "Tidak ada data tersedia untuk periode yang dipilih",
                zeroRecords: "Tidak ditemukan catatan yang cocok",
                processing: "Memproses..."
            },
            dom: '<"top"fl>rt<"bottom"ip><"clear">',
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            // Penanganan kesalahan untuk DataTables
            drawCallback: function() {
                // Sembunyikan loading overlay saat data siap
                if (document.getElementById('loading-overlay')) {
                    document.getElementById('loading-overlay').style.display = 'none';
                }
            }
        });
    } catch (e) {
        console.error("Kesalahan inisialisasi DataTable:", e);
        // Sembunyikan loading overlay jika terjadi kesalahan
        if (document.getElementById('loading-overlay')) {
            document.getElementById('loading-overlay').style.display = 'none';
        }
    }

    // Menangani pemilihan preset tanggal
    $('#preset').on('change', function() {
        if ($(this).val() === '') {
            // Rentang tanggal kustom dipilih - tampilkan input tanggal kustom
            $('#custom-date-inputs').show();
        } else {
            // Preset dipilih - sembunyikan input tanggal kustom dan tampilkan loading overlay
            $('#custom-date-inputs').hide();
            
            // Tampilkan loading overlay sebelum mengirimkan
            if (document.getElementById('loading-overlay')) {
                document.getElementById('loading-overlay').style.display = 'flex';
            }
            
            // Tambahkan penundaan kecil sebelum mengirimkan untuk memastikan loading overlay terlihat
            setTimeout(function() {
                $('#date-filter-form').submit();
            }, 100);
        }
    });

    // Validasi form dasar
    $('#date-filter-form').on('submit', function(e) {
        // Tampilkan loading overlay
        if (document.getElementById('loading-overlay')) {
            document.getElementById('loading-overlay').style.display = 'flex';
        }
        
        // Hanya validasi jika rentang tanggal kustom dipilih
        if ($('#preset').val() === '') {
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            
            if (!startDate || !endDate) {
                alert('Silakan pilih tanggal mulai dan tanggal akhir');
                // Sembunyikan loading overlay jika validasi gagal
                if (document.getElementById('loading-overlay')) {
                    document.getElementById('loading-overlay').style.display = 'none';
                }
                e.preventDefault();
                return false;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir');
                // Sembunyikan loading overlay jika validasi gagal
                if (document.getElementById('loading-overlay')) {
                    document.getElementById('loading-overlay').style.display = 'none';
                }
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    });

    // Fungsi ekspor
    $('.pdf-export').on('click', function(e) {
        e.preventDefault();
        
        // Tampilkan loading overlay
        if (document.getElementById('loading-overlay')) {
            document.getElementById('loading-overlay').style.display = 'flex';
        }
        
        // Periksa apakah pengguna memiliki izin (dikontrol oleh kelas CSS)
        if ($(this).hasClass('disabled-for-employee')) {
            showPermissionModal('Ekspor PDF', 'Laporan ini tidak dapat diekspor ke PDF oleh akun karyawan.');
            // Sembunyikan loading overlay jika pemeriksaan izin gagal
            if (document.getElementById('loading-overlay')) {
                document.getElementById('loading-overlay').style.display = 'none';
            }
            return;
        }
        
        // Jika kita memiliki izin, lanjutkan dengan ekspor
        exportTableToPDF();
    });

    $('.excel-export').on('click', function(e) {
        e.preventDefault();
        
        // Tampilkan loading overlay
        if (document.getElementById('loading-overlay')) {
            document.getElementById('loading-overlay').style.display = 'flex';
        }
        
        // Periksa apakah pengguna memiliki izin (dikontrol oleh kelas CSS)
        if ($(this).hasClass('disabled-for-employee')) {
            showPermissionModal('Ekspor Excel', 'Laporan ini tidak dapat diekspor ke Excel oleh akun karyawan.');
            // Sembunyikan loading overlay jika pemeriksaan izin gagal
            if (document.getElementById('loading-overlay')) {
                document.getElementById('loading-overlay').style.display = 'none';
            }
            return;
        }
        
        // Jika kita memiliki izin, lanjutkan dengan ekspor
        exportTableToExcel();
    });

    $('.print-report').on('click', function(e) {
        e.preventDefault();
        
        // Periksa apakah pengguna memiliki izin (dikontrol oleh kelas CSS)
        if ($(this).hasClass('disabled-for-employee')) {
            showPermissionModal('Cetak Laporan', 'Pencetakan laporan dibatasi hanya untuk akun administrator.');
            return;
        }
        
        // Jika kita memiliki izin, lanjutkan dengan cetak
        printReport();
    });
    
    // Untuk akses karyawan - menangani upaya tindakan terbatas
    $('.employee-print, .employee-export').on('click', function(e) {
        e.preventDefault();
        
        // Dapatkan jenis tindakan dari atribut data atau default
        var actionType = $(this).data('action') === 'print_attempt' ? 'Cetak Laporan' : 'Ekspor Laporan';
        var message = $(this).data('action') === 'print_attempt' 
            ? 'Pencetakan laporan dibatasi hanya untuk akun administrator.' 
            : 'Ekspor data dibatasi hanya untuk akun administrator.';
        
        showPermissionModal(actionType, message);
    });
    
    // Menangani tombol reset - tampilkan loading overlay
    $('a[href="?reset=1"]').on('click', function() {
        if (document.getElementById('loading-overlay')) {
            document.getElementById('loading-overlay').style.display = 'flex';
        }
    });
});

// Fungsi untuk menampilkan modal izin
function showPermissionModal(actionType, message) {
    // Atur detail tindakan
    $('#actionDetails').html(
        '<strong>Tindakan yang Dicoba:</strong> ' + actionType + '<br>' +
        '<small>' + message + '</small>'
    );
    
    // Tampilkan modal
    var permissionModal = new bootstrap.Modal(document.getElementById('permissionModal'));
    permissionModal.show();
}

// Fungsi untuk mencetak tanda terima
function printReceipt(transactionId) {
    // Buka tanda terima di jendela baru untuk pencetakan
    var printWindow = window.open('../../transaction/transaction_success.php?id=' + transactionId + '&print=true', '_blank', 'width=400,height=600');

    // Cetak otomatis setelah konten dimuat
    printWindow.onload = function () {
        setTimeout(function () {
            printWindow.print();
        }, 500);
    };

    return false;
}

// Fungsi untuk mencetak laporan saat ini
function printReport() {
    // Tambahkan gaya khusus untuk pencetakan
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
            '}')
        .appendTo('head');

    // Tambahkan header laporan untuk pencetakan
    if ($('.report-header').length === 0) {
        // Dapatkan rentang tanggal untuk header laporan
        var startDate = $('#start_date').val() || $('.alert-info strong').next().text().split(' sampai ')[0];
        var endDate = $('#end_date').val() || $('.alert-info strong').next().text().split(' sampai ')[1];
        
        $('.content').prepend(
            '<div class="report-header no-screen" style="display:none;">' +
            '<h2>Ayula Store - Laporan Penjualan</h2>' +
            '<p>Periode: ' + startDate + ' sampai ' + endDate + '</p>' +
            '<p>Dibuat pada: ' + new Date().toLocaleDateString() + '</p>' +
            '</div>'
        );
    }

    window.print();
}

// Fungsi untuk mengekspor tabel ke Excel
function exportTableToExcel() {
    // Dapatkan data tabel
    var table = $('.datanew').DataTable();
    var data = [];
    
    try {
        // Dapatkan semua baris dari tabel (tidak hanya halaman saat ini)
        table.rows().every(function() {
            data.push(this.data());
        });
        
        var headers = [];

        // Dapatkan header
        $('.datanew thead th').each(function () {
            // Lewati kolom Aksi
            if ($(this).text() !== 'Aksi') {
                headers.push($(this).text());
            }
        });

        // Buat konten CSV
        var csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\n";

        // Tambahkan baris data
        for (var i = 0; i < data.length; i++) {
            var row = [];
            for (var j = 0; j < data[i].length - 1; j++) { // Lewati kolom terakhir (Aksi)
                // Bersihkan data (hapus tag HTML)
                var cellData = data[i][j].toString().replace(/<[^>]*>/g, '').trim();
                // Ganti beberapa spasi dengan satu spasi
                cellData = cellData.replace(/\s+/g, ' ');
                // Bungkus dalam tanda kutip dan hindari tanda kutip internal
                row.push('"' + cellData.replace(/"/g, '""') + '"');
            }
            csvContent += row.join(",") + "\n";
        }

        // Buat tautan unduhan
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        
        // Gunakan tanggal saat ini dalam nama file
        var today = new Date();
        var dateStr = today.getFullYear() + '-' + 
                     ('0' + (today.getMonth()+1)).slice(-2) + '-' + 
                     ('0' + today.getDate()).slice(-2);
        
        link.setAttribute("download", "laporan_penjualan_" + dateStr + ".csv");
        document.body.appendChild(link);

        // Unduh file
        link.click();

        // Bersihkan
        document.body.removeChild(link);
    } catch (e) {
        console.error("Error mengekspor ke Excel:", e);
        alert("Terjadi kesalahan saat mengekspor. Silakan coba lagi.");
    }
    
    // Sembunyikan loading overlay saat ekspor selesai
    if (document.getElementById('loading-overlay')) {
        document.getElementById('loading-overlay').style.display = 'none';
    }
}

// Fungsi untuk mengekspor tabel ke PDF
function exportTableToPDF() {
    try {
        // Buat form untuk pembuatan di sisi server
        var form = $('<form action="" method="post" target="_blank"></form>');
        form.append('<input type="hidden" name="export_pdf" value="1">');
        form.append('<input type="hidden" name="start_date" value="' + $('#start_date').val() + '">');
        form.append('<input type="hidden" name="end_date" value="' + $('#end_date').val() + '">');
        
        // Tambahkan form ke dokumen dan kirimkan
        $('body').append(form);
        form.submit();
        form.remove();
    } catch (e) {
        console.error("Error mengekspor ke PDF:", e);
        alert("Terjadi kesalahan saat mengekspor ke PDF. Silakan coba lagi.");
    }
    
    // Sembunyikan loading overlay setelah 2 detik (karena pembuatan PDF terjadi di tab baru)
    setTimeout(function() {
        if (document.getElementById('loading-overlay')) {
            document.getElementById('loading-overlay').style.display = 'none';
        }
    }, 2000);
}

// Otomatis sembunyikan loading overlay saat halaman dimuat sepenuhnya
$(window).on('load', function() {
    if (document.getElementById('loading-overlay')) {
        setTimeout(function() {
            document.getElementById('loading-overlay').style.display = 'none';
        }, 500);
    }
});

// Failsafe timeout untuk menyembunyikan loading overlay setelah 10 detik
setTimeout(function() {
    if (document.getElementById('loading-overlay')) {
        document.getElementById('loading-overlay').style.display = 'none';
    }
}, 10000);

// Nonaktifkan console logs dalam produksi
if (window.location.hostname !== 'localhost') {
    console.log = function() {}; // Nonaktifkan console logs
    console.warn = function() {}; // Nonaktifkan console warnings
    console.error = function() {}; // Nonaktifkan console errors
}