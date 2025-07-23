// Manifest Export Script
// This script handles the export of manifest data to Excel format

// Wait for document ready
jQuery(document).ready(function($) {
    // Export to Excel functions
    $(document).on('click', '.export-manifest', function() {
        const pakId = $(this).data('pakid');
        console.log('Exporting manifest for package:', pakId);
        if (!pakId) {
            alert('No package ID found for export');
            return;
        }
        exportToExcelAjax(pakId, 'manifest');
    });

    function exportToExcel(pakId, type, data) {
        console.log("=== SIMPLIFIED EXPORT START ===");
        console.log("Package ID:", pakId);
        console.log("Type:", type);
        console.log("Data received:", data);
        console.log("Number of jamaah records:", data.manifest ? data.manifest.length : 0);
        
        // Create a new workbook - no template, just build from data
        const wb = XLSX.utils.book_new();
        
        if (type === 'manifest' && data.manifest && data.manifest.length > 0) {
            console.log("Processing manifest data for simplified export");
            
            // Create header information based on manifest_template.xlsx reference
            const headerInfo = [
                [`Manifest Jamaah ${data.package.name || 'Unknown Package'}`],
                [`Tanggal ${data.package.departure_date || 'Unknown Date'}`], 
                [`Program ${data.package.type || 'Unknown Type'}`],
                [''],
                ['Hotel Information:'],
                [`Medinah: ${data.package.hotel_medinah || 'N/A'}`, '', '', `HCN: ${data.package.hotel_medinah_hcn || 'N/A'}`],
                [`Makkah: ${data.package.hotel_makkah || 'N/A'}`, '', '', `HCN: ${data.package.hotel_makkah_hcn || 'N/A'}`],
                [`HCN Issue Date: ${data.package.hcn_issue_date || 'N/A'}`, '', '', `Expiry Date: ${data.package.hcn_expiry_date || 'N/A'}`],
                [''],
                ['No', 'Sex', 'Name of Passport', 'Marketing', 'Nama Ayah', 'Birth: Date', 'Birth: City', 
                 'Passport: No.Passport', 'Passport: Issuing Office', 'Passport: Date of Issue', 
                 'Passport: Date of Expiry', 'Relation', 'Age', 'Cabang', 'Roomlist', 'NIK', 'Alamat', 'Keterangan']
            ];
            
            console.log("Header info created:", headerInfo);
            
            // Convert manifest data to array format - ensure no empty values
            const manifestRows = data.manifest.map((row, index) => {
                console.log(`Processing jamaah ${index + 1}:`, row);
                
                const processedRow = [
                    row['No'] || (index + 1),
                    row['Sex'] || '',
                    row['Name of Passport'] || '',
                    row['Marketing'] || '',
                    row['Nama Ayah'] || '',
                    row['Birth: Date'] || '',
                    row['Birth: City'] || '',
                    row['Passport: No.Passport'] || '',
                    row['Passport: Issuing Office'] || '',
                    row['Passport: Date of Issue'] || '',
                    row['Passport: Date of Expiry'] || '',
                    row['Relation'] || '',
                    row['Age'] || '',
                    row['Cabang'] || '',
                    row['Roomlist'] || '',
                    row['NIK'] || '',
                    row['Alamat'] || '',
                    row['Keterangan'] || ''
                ];
                
                console.log(`Processed row ${index + 1}:`, processedRow);
                return processedRow;
            });
            
            console.log("All manifest rows processed:", manifestRows);
            
            // Combine header and data
            const allData = [...headerInfo, ...manifestRows];
            console.log("Final Excel data structure:", allData);
            
            // Create worksheet from array of arrays
            const wsManifest = XLSX.utils.aoa_to_sheet(allData);
            
            // Set column widths for proper formatting
            wsManifest['!cols'] = [
                {wch: 5},  // No
                {wch: 5},  // Sex  
                {wch: 25}, // Name of Passport
                {wch: 20}, // Marketing
                {wch: 20}, // Nama Ayah
                {wch: 12}, // Birth: Date
                {wch: 15}, // Birth: City
                {wch: 15}, // Passport: No.Passport
                {wch: 15}, // Passport: Issuing Office
                {wch: 12}, // Passport: Date of Issue
                {wch: 12}, // Passport: Date of Expiry
                {wch: 15}, // Relation
                {wch: 5},  // Age
                {wch: 10}, // Cabang
                {wch: 10}, // Roomlist
                {wch: 20}, // NIK
                {wch: 40}, // Alamat
                {wch: 30}  // Keterangan
            ];
            
            // Add the manifest sheet to workbook
            XLSX.utils.book_append_sheet(wb, wsManifest, 'Manifest');
            
            // Create descriptive filename
            let packageName = (data.package.name || 'unknown_package').replace(/[^a-z0-9]/gi, '_').toLowerCase();
            if (packageName.length > 30) {
                packageName = packageName.substring(0, 30);
            }
            
            let departureDate = (data.package.departure_date || '').replace(/\//g, '');
            const filename = `manifest_${packageName}_${departureDate}_pak${pakId}.xlsx`;
            
            console.log("Generating Excel file:", filename);
            
            // Export the workbook to file
            XLSX.writeFile(wb, filename);
            
            console.log(`Export successful: ${filename}`);
            console.log("Records exported:", data.manifest.length);
            console.log("=== SIMPLIFIED EXPORT END ===");
            
            // Show success message with details
            alert(`Manifest export completed successfully!
            
File: ${filename}
Package: ${data.package.name}
Records exported: ${data.manifest.length}
            
The file has been downloaded to your Downloads folder.`);
            
        } else {
            console.error('No manifest data found for export');
            console.log("Data structure received:", data);
            alert('No manifest data found for export. Please check that the package has jamaah assigned to it.');
        }
    }

    function exportToExcelAjax(pakId, type) {
        $.ajax({
            url: 'export_manifest.php',
            type: 'POST',
            data: { pak_id: pakId, export_type: type },
            success: function(response) {
                if (!response.success) {
                    // Check if we need to redirect for table setup
                    if (response.error_code === 'table_not_exists' && response.redirect) {
                        if (confirm('The manifest table does not exist. Would you like to run the setup script now?')) {
                            window.location.href = response.redirect;
                        }
                        return;
                    }
                    alert(response.message || 'Error during export');
                    return;
                }
                if (!response.data || !response.data.manifest || response.data.manifest.length === 0) {
                    alert('No data found for export');
                    return;
                }

                // Use the simplified export function
                exportToExcel(pakId, type, response.data);
            },
            error: function(xhr, status, error) {
                console.error('Export error:', error);
                let message = 'Error generating Excel file';
                try {
                    const response = JSON.parse(xhr.responseText);
                    message = response.message || message;
                } catch (e) {}
                alert('Error during export: ' + message);
            }
        });
    }

    // Make the exportToExcel function available globally
    window.exportToExcel = exportToExcel;
    window.exportToExcelAjax = exportToExcelAjax;
});
