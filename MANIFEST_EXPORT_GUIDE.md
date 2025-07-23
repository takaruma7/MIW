# MIW Manifest Export System - Updated

## Overview
The manifest export system has been updated to export data directly from `data_jamaah` and `data_paket` tables without relying on the `manifest_template.xlsx` file as a template. The template is now used only as a reference for the output format.

## Key Changes Made

### 1. Simplified Database Schema
- **Removed dependency on `data_manifest` table** - All room data is now stored directly in `data_jamaah`
- **Room fields in `data_jamaah`:**
  - `room_prefix` - Room code (e.g., Q1, T2, D3)
  - `medinah_room_number` - Room number in Medinah
  - `mekkah_room_number` - Room number in Mekkah
  - `room_relation` - Relation/mahram information

### 2. Updated Export Process
- **Input:** Package ID (pak_id) and export type
- **Process:** 
  1. Fetch package details from `data_paket`
  2. Fetch jamaah data from `data_jamaah` WHERE pak_id matches
  3. Format data according to manifest template structure
  4. Generate Excel file with actual data values
- **Output:** Excel file with one sheet containing manifest data

### 3. Files Modified

#### Backend (PHP)
- `export_manifest.php` - Updated to use only data_jamaah and data_paket
- `update_manifest.php` - Updates room data directly in data_jamaah
- `manifest_umroh.php` - Updated to read room data from data_jamaah
- `manifest_haji.php` - Updated to read room data from data_jamaah
- `tab_manifest.php` - Updated for new schema
- `admin_dashboard.php` - Removed data_manifest references

#### Frontend (JavaScript)
- `manifest_scripts.js` - Simplified export function, no template dependency
- Creates Excel structure programmatically based on template design
- Improved error handling and user feedback

### 4. New Export Workflow

```
User clicks "Export" → 
AJAX call to export_manifest.php → 
Fetch package data from data_paket → 
Fetch jamaah data from data_jamaah → 
Format data for manifest → 
Return JSON response → 
JavaScript creates Excel file → 
Download to user's browser
```

## How to Use

### 1. Export from Admin Panel
1. Go to `admin_manifest.php`
2. Find the package you want to export
3. Click the "Export" button
4. Excel file will download automatically

### 2. Manual Testing
- Open `final_export_test.html` to test the export functionality
- Test with Package 12 (Haji) or Package 5 (Umroh)
- Verify that Excel files contain actual data values

### 3. Room Management
- Use `admin_roomlist.php` to manage room assignments
- Room data is stored directly in `data_jamaah` table
- No separate manifest table needed

## Expected Output Format

The exported Excel file contains:

### Header Section (Rows 1-9)
```
Manifest Jamaah [Package Name]
Tanggal [Departure Date]
Program [Package Type]

Hotel Information:
Medinah: [Hotel Name]    HCN: [HCN Number]
Makkah: [Hotel Name]     HCN: [HCN Number]
HCN Issue Date: [Date]   Expiry Date: [Date]
```

### Data Section (Row 10+)
Column headers and jamaah data:
- No, Sex, Name of Passport, Marketing, Nama Ayah
- Birth: Date, Birth: City, Passport details
- Relation, Age, Cabang, Roomlist, NIK, Alamat, Keterangan

## Data Mapping

| Excel Column | Data Source |
|--------------|-------------|
| No | Row number |
| Sex | jamaah.jenis_kelamin (L→MR, P→MRS) |
| Name of Passport | jamaah.nama_paspor or jamaah.nama |
| Marketing | jamaah.marketing_nama (defaults to "Eli Rahmalia") |
| Nama Ayah | jamaah.nama_ayah |
| Birth: Date | jamaah.tanggal_lahir |
| Birth: City | jamaah.tempat_lahir |
| Passport: No.Passport | jamaah.no_paspor |
| Passport: Issuing Office | jamaah.tempat_pembuatan_paspor |
| Passport: Date of Issue | jamaah.tanggal_pengeluaran_paspor |
| Passport: Date of Expiry | jamaah.tanggal_habis_berlaku |
| Relation | jamaah.room_relation or jamaah.hubungan_mahram |
| Age | Calculated from jamaah.tanggal_lahir |
| Cabang | Fixed: "Bandung" |
| Roomlist | jamaah.type_room_pilihan |
| NIK | jamaah.nik |
| Alamat | jamaah.alamat |
| Keterangan | jamaah.request_khusus |

## Troubleshooting

### If Export Returns Empty Data:
1. Check that the package has jamaah assigned to it
2. Verify database connection in `config.php`
3. Check browser console for JavaScript errors
4. Test with `final_export_test.html`

### If Excel File Only Shows Template:
- This issue has been resolved - the system now generates data programmatically
- No longer depends on `manifest_template.xlsx` for structure
- Each export creates a fresh Excel file with actual jamaah data

### Room Data Missing:
- Ensure room data is assigned in `admin_roomlist.php`
- Check that `room_prefix`, `medinah_room_number`, `mekkah_room_number` fields are populated in `data_jamaah`

## Files for Testing

1. `final_export_test.html` - Complete export test interface
2. `test_data_structure.php` - Database structure validation
3. `test_export_response.php` - Backend export testing
4. `comprehensive_test.html` - Full workflow testing

## Database Schema

### Key Tables:
- `data_paket` - Package information
- `data_jamaah` - Jamaah information with room data

### Deprecated:
- `data_manifest` table is no longer used
- `create_manifest_table.php` is marked as deprecated

The export system now provides a simplified, reliable way to generate manifest Excel files with actual jamaah data values, using only the core database tables and referencing the template design without depending on the template file itself.
