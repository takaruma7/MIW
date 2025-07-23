// Wait for document ready
jQuery(document).ready(function($) {
    // Initialize DataTables with destroy option
    const tables = $('.package-table').each(function() {
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable(this)) {
            $(this).DataTable().destroy();
        }
        
        // Initialize new DataTable
        $(this).DataTable({
            destroy: true, // Enable table destruction
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
    });

    // Update roomlist record
    $('.update-manifest').on('click', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        
        // Create object for roomlist data only
        const roomlistData = {
            nik: form.find('input[name="nik"]').val(),
            pak_id: form.find('input[name="pak_id"]').val(),
            room_prefix: form.find('select[name="room_prefix"]').val(),
            medinah_number: form.find('input[name="medinah_number"]').val(),
            mekkah_number: form.find('input[name="mekkah_number"]').val()
        };

        // Add relation only if the field has a value
        const relation = form.find('input[name="relation"]').val();
        if (relation) {
            roomlistData.relation = relation;
        }
        
        $.ajax({
            url: 'update_manifest.php',
            type: 'POST',
            data: roomlistData,
            success: function(response) {
                if (response.success) {
                    alert('Roomlist record updated successfully');
                    location.reload();
                } else {
                    // Check if we need to redirect for table setup
                    if (response.error_code === 'table_not_exists' && response.redirect) {
                        if (confirm('The manifest table does not exist. Would you like to run the setup script now?')) {
                            window.location.href = response.redirect;
                        }
                        return;
                    }
                    alert(response.message || 'Error updating roomlist record');
                }
            },
            error: function() {
                alert('Error updating roomlist record');
            }
        });
    });

    // Handle roomlist form submission
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
                    alert('Roomlist updated successfully');
                    location.reload();
                } else {
                    // Check if we need to redirect for table setup
                    if (response.error_code === 'table_not_exists' && response.redirect) {
                        if (confirm('The manifest table does not exist. Would you like to run the setup script now?')) {
                            window.location.href = response.redirect;
                        }
                        return;
                    }
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error updating roomlist: ' + error);
            },
            complete: function() {
                // Re-enable submit button
                submitButton.prop('disabled', false);
            }
        });
    });
});
