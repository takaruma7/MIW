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

    // Update manifest record
    $('.update-manifest').on('click', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        
        // Create object for manifest data only
        const manifestData = {
            nik: form.find('input[name="nik"]').val(),
            pak_id: form.find('input[name="pak_id"]').val(),
            room_prefix: form.find('select[name="room_prefix"]').val(),
            medinah_number: form.find('input[name="medinah_number"]').val(),
            mekkah_number: form.find('input[name="mekkah_number"]').val()
        };

        // Add relation only if the field has a value
        const relation = form.find('input[name="relation"]').val();
        if (relation) {
            manifestData.relation = relation;
        }
        
        $.ajax({
            url: 'update_manifest.php',
            type: 'POST',
            data: manifestData,
            success: function(response) {
                if (response.success) {
                    alert('Manifest record updated successfully');
                    location.reload();
                } else {
                    alert(response.message || 'Error updating manifest record');
                }
            },
            error: function() {
                alert('Error updating manifest record');
            }
        });
    });

    // Export to Excel functions
    $(document).on('click', '.export-manifest', function() {
        const pakId = $(this).data('pakid');
        console.log('Exporting manifest for package:', pakId);
        if (!pakId) {
            alert('No package ID found for export');
            return;
        }
        exportToExcel(pakId, 'manifest');
    });

    $(document).on('click', '.export-kelengkapan', function() {
        const pakId = $(this).data('pakid');
        console.log('Exporting kelengkapan for package:', pakId);
        if (!pakId) {
            alert('No package ID found for export');
            return;
        }
        exportToExcel(pakId, 'kelengkapan');
    });

    function exportToExcel(pakId, type) {
        $.ajax({
            url: 'export_manifest.php',
            type: 'POST',
            data: { pak_id: pakId, export_type: type },
            success: function(response) {
                if (!response.success) {
                    alert(response.message || 'Error during export');
                    return;
                }
                if (!response.data || !response.data.manifest || response.data.manifest.length === 0) {
                    alert('No data found for export');
                    return;
                }

                // Create workbook
                const wb = XLSX.utils.book_new();
                
                // Add package info at the top of manifest sheet
                const packageInfo = [
                    [''],
                    ['MANIFEST UMROH/HAJI'],
                    ['Program:', response.data.package.name],
                    ['Tanggal Keberangkatan:', response.data.package.departure_date],
                    [''],
                    ['Hotel Information:'],
                    ['Madinah:', response.data.package.hotel_medinah, 'HCN:', response.data.package.hotel_medinah_hcn],
                    ['Makkah:', response.data.package.hotel_makkah, 'HCN:', response.data.package.hotel_makkah_hcn],
                    ['HCN Issue Date:', response.data.package.hcn_issue_date, 'Expiry Date:', response.data.package.hcn_expiry_date],
                    ['']
                ];
                
                // Create manifest worksheet
                const wsManifest = XLSX.utils.aoa_to_sheet(packageInfo);
                
                // Add manifest data below package info
                XLSX.utils.sheet_add_json(wsManifest, response.data.manifest, {
                    origin: 'A' + (packageInfo.length + 1),
                    skipHeader: false
                });
                
                // Add the manifest sheet to workbook
                XLSX.utils.book_append_sheet(wb, wsManifest, 'Manifest');
                
                // Set column widths
                const cols = wsManifest['!cols'] = [
                    {wch: 5},  // No
                    {wch: 5},  // Sex
                    {wch: 20}, // Family Name
                    {wch: 20}, // Given Name
                    {wch: 25}, // Name in Passport
                    {wch: 20}, // NIK
                    {wch: 12}, // Birth Date
                    {wch: 15}, // Place of Birth
                    {wch: 12}, // Nationality
                    {wch: 15}, // Passport No
                    {wch: 12}, // Issue Date
                    {wch: 12}, // Expiry Date
                    {wch: 15}, // Issue Place
                    {wch: 20}, // Mahram Name
                    {wch: 15}, // Relation
                    {wch: 10}, // Room Code
                    {wch: 12}, // Room No (MAK)
                    {wch: 12}, // Room No (MAD)
                    {wch: 20}, // Marketing
                    {wch: 15}, // Phone No
                    {wch: 20}, // Father Name
                    {wch: 15}, // Package Type
                    {wch: 5},  // Age
                    {wch: 40}, // Address
                    {wch: 30}  // Special Request
                ];
                
                // Add room lists sheets if available
                if (response.data.roomLists) {
                    // Medinah Rooms
                    if (response.data.roomLists.medinah && response.data.roomLists.medinah.length > 0) {
                        const wsMedinah = XLSX.utils.json_to_sheet(response.data.roomLists.medinah);
                        XLSX.utils.book_append_sheet(wb, wsMedinah, 'Medinah Rooms');
                        wsMedinah['!cols'] = [
                            {wch: 12}, // Room Number
                            {wch: 10}, // Type
                            {wch: 50}  // Occupants
                        ];
                    }
                    
                    // Makkah Rooms
                    if (response.data.roomLists.makkah && response.data.roomLists.makkah.length > 0) {
                        const wsMakkah = XLSX.utils.json_to_sheet(response.data.roomLists.makkah);
                        XLSX.utils.book_append_sheet(wb, wsMakkah, 'Makkah Rooms');
                        wsMakkah['!cols'] = [
                            {wch: 12}, // Room Number
                            {wch: 10}, // Type
                            {wch: 50}  // Occupants
                        ];
                    }
                }
                
                // Create filename with package info
                const packageName = response.data.package.name.replace(/[^a-z0-9]/gi, '_').toLowerCase();
                const filename = `${type}_${packageName}_${pakId}.xlsx`;
                
                // Export the workbook
                XLSX.writeFile(wb, filename);
                console.log(`Export successful: ${type}_paket_${pakId}.xlsx`);
            },
            error: function(xhr, status, error) {
                console.error('Export error:', error);
                alert('Error during export: ' + error);
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
