// Wait for document ready
jQuery(document).ready(function($) {
    // Initialize DataTables
    $('.package-table').DataTable({
        responsive: true,
        columnDefs: [
            {
                targets: [1], // NIK column index
                width: '120px' // Set width for NIK column
            }
        ],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Tidak ada data yang ditampilkan",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Update manifest record
    $('.update-manifest').on('click', function() {
        const form = $(this).closest('form');
        const formData = form.serialize();
        
        $.ajax({
            url: 'update_manifest.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Record updated successfully');
                    location.reload();
                } else {
                    alert(response.message || 'Error updating record');
                }
            },
            error: function() {
                alert('Error updating record');
            }
        });
    });

    // Export to Excel functions
    $('.export-manifest').on('click', function() {
        const pakId = $(this).data('pakid');
        exportToExcel(pakId, 'manifest');
    });

    $('.export-kelengkapan').on('click', function() {
        const pakId = $(this).data('pakid');
        exportToExcel(pakId, 'kelengkapan');
    });

    function exportToExcel(pakId, type) {
        $.ajax({
            url: 'export_manifest.php',
            type: 'POST',
            data: { pak_id: pakId, export_type: type },
            success: function(response) {
                // Create workbook
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.json_to_sheet(response.data);
                
                // Add worksheet to workbook
                XLSX.utils.book_append_sheet(wb, ws, type === 'manifest' ? 'Manifest' : 'Kelengkapan');
                
                // Export the workbook
                XLSX.writeFile(wb, `${type}_paket_${pakId}.xlsx`);
            },
            error: function(xhr) {
                let message = 'Error generating Excel file';
                try {
                    const response = JSON.parse(xhr.responseText);
                    message = response.message || message;
                } catch (e) {}
                alert(message);
            }
        });
    }

    // Handle manifest form submission
    $('.manifest-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        
        // Disable submit button to prevent double submission
        submitButton.prop('disabled', true);
        
        $.ajax({
            url: 'update_manifest.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Manifest updated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error updating manifest: ' + error);
            },
            complete: function() {
                // Re-enable submit button
                submitButton.prop('disabled', false);
            }
        });
    });
});
