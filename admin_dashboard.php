<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Data Jamaah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <header class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-people-fill"></i> Data Jamaah</h2>
                <div>
                    <span class="badge bg-primary">Total Jamaah: 1</span>
                </div>
            </div>
        </header>

        <!-- Records Per Page Selector -->
        <div class="records-per-page d-flex justify-content-end mb-3">
            <label for="recordsPerPage">Records per page:</label>
            <select class="form-select form-select-sm" id="recordsPerPage" style="width: auto;">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="dashboardTabsContent">
            <!-- All Jamaah Tab -->
            <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                <div class="table-container">
                    <div class="table-title">
                        <h5>All Registered Jamaah</h5>
                    </div>
                    <div class="table-responsive scrollable-table" style="--records-per-page: 10;">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>NIK</th>
                                    <th>Nama</th>
                                    <th>No. Telp</th>
                                    <th>Program</th>
                                    <th>Room Type</th>
                                    <th>Payment Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>3273272102010002</td>
                                    <td>Yusuf Hendra</td>
                                    <td>081221030301</td>
                                    <td>Haji 2026</td>
                                    <td>Quad</td>
                                    <td><span class="badge bg-success">verified</span></td>
                                    <td class="action-btns">
                                        <button class="btn btn-sm btn-primary view-details" data-nik="3273272102010002">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Verified Payments Tab -->
            <div class="tab-pane fade" id="verified" role="tabpanel" aria-labelledby="verified-tab">
                <div class="table-container">
                    <div class="table-title">
                        <h5>Jamaah with Verified Payments</h5>
                    </div>
                    <div class="table-responsive scrollable-table" style="--records-per-page: 10;">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>NIK</th>
                                    <th>Nama</th>
                                    <th>Payment Type</th>
                                    <th>Amount Paid</th>
                                    <th>Remaining</th>
                                    <th>Verified By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>3273272102010002</td>
                                    <td>Yusuf Hendra</td>
                                    <td>DP</td>
                                    <td>5,000.00</td>
                                    <td>11,000.00</td>
                                    <td>Admin</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Payments Tab -->
            <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                <div class="table-container">
                    <div class="table-title">
                        <h5>Jamaah with Pending Payments</h5>
                    </div>
                    <div class="table-responsive scrollable-table" style="--records-per-page: 10;">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>NIK</th>
                                    <th>Nama</th>
                                    <th>Payment Type</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- No pending payments in the sample data -->
                                <tr>
                                    <td colspan="6" class="text-center">No pending payments found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Jamaah Details Modal -->
    <div class="modal fade" id="jamaahDetailsModal" tabindex="-1" aria-labelledby="jamaahDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jamaahDetailsModalLabel">Jamaah Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="registration-details">
                        <h2>Personal Information</h2>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h3>Basic Information</h3>
                                <table>
                                    <tr>
                                        <td width="40%">NIK</td>
                                        <td id="detail-nik">3273272102010002</td>
                                    </tr>
                                    <tr>
                                        <td>Full Name</td>
                                        <td id="detail-nama">Yusuf Hendra</td>
                                    </tr>
                                    <tr>
                                        <td>Place/Date of Birth</td>
                                        <td id="detail-ttl">Purwakarta, 31 Dec 2025</td>
                                    </tr>
                                    <tr>
                                        <td>Gender</td>
                                        <td id="detail-gender">Laki-laki</td>
                                    </tr>
                                    <tr>
                                        <td>Age</td>
                                        <td id="detail-age">0</td>
                                    </tr>
                                    <tr>
                                        <td>Nationality</td>
                                        <td id="detail-nationality">Indonesia</td>
                                    </tr>
                                    <tr>
                                        <td>Marital Status</td>
                                        <td id="detail-marital">Belum Menikah</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h3>Contact Information</h3>
                                <table>
                                    <tr>
                                        <td width="40%">Address</td>
                                        <td id="detail-alamat">Bandung</td>
                                    </tr>
                                    <tr>
                                        <td>Postal Code</td>
                                        <td id="detail-kodepos">42569</td>
                                    </tr>
                                    <tr>
                                        <td>Email</td>
                                        <td id="detail-email">winstonarma7@gmail.com</td>
                                    </tr>
                                    <tr>
                                        <td>Phone Number</td>
                                        <td id="detail-telp">081221030301</td>
                                    </tr>
                                    <tr>
                                        <td>Emergency Contact</td>
                                        <td id="detail-emergency">-</td>
                                    </tr>
                                    <tr>
                                        <td>Emergency Phone</td>
                                        <td id="detail-emergency-hp">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h3>Family Information</h3>
                                <table>
                                    <tr>
                                        <td width="40%">Father's Name</td>
                                        <td id="detail-ayah">Test</td>
                                    </tr>
                                    <tr>
                                        <td>Mother's Name</td>
                                        <td id="detail-ibu">-</td>
                                    </tr>
                                    <tr>
                                        <td>Mahram Name</td>
                                        <td id="detail-mahram">Drake Andresson</td>
                                    </tr>
                                    <tr>
                                        <td>Mahram Relationship</td>
                                        <td id="detail-mahram-rel">Orang Tua</td>
                                    </tr>
                                    <tr>
                                        <td>Mahram Phone</td>
                                        <td id="detail-mahram-phone">3273272102010001</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h3>Physical Information</h3>
                                <table>
                                    <tr>
                                        <td width="40%">Height</td>
                                        <td id="detail-tinggi">100 cm</td>
                                    </tr>
                                    <tr>
                                        <td>Weight</td>
                                        <td id="detail-berat">30 kg</td>
                                    </tr>
                                    <tr>
                                        <td>Blood Type</td>
                                        <td id="detail-gol-darah">A</td>
                                    </tr>
                                    <tr>
                                        <td>Distinctive Features</td>
                                        <td id="detail-ciri">
                                            Rambut: -<br>
                                            Alis: Test<br>
                                            Hidung: Test<br>
                                            Muka: Test
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <h3 class="mt-4">Education & Work</h3>
                        <table>
                            <tr>
                                <td width="20%">Education</td>
                                <td id="detail-pendidikan">SD</td>
                            </tr>
                            <tr>
                                <td>Occupation</td>
                                <td id="detail-pekerjaan">Pegawai Negeri Sipil</td>
                            </tr>
                        </table>
                        
                        <h3 class="mt-4">Address Details</h3>
                        <table>
                            <tr>
                                <td width="20%">Village</td>
                                <td id="detail-desa">Cisaranten Kidul</td>
                            </tr>
                            <tr>
                                <td>Subdistrict</td>
                                <td id="detail-kecamatan">Gedebage</td>
                            </tr>
                            <tr>
                                <td>City/Regency</td>
                                <td id="detail-kota">Kota Bandung</td>
                            </tr>
                            <tr>
                                <td>Province</td>
                                <td id="detail-provinsi">Jawa Barat</td>
                            </tr>
                        </table>
                        
                        <h2 class="mt-5">Travel Information</h2>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h3>Program Details</h3>
                                <table>
                                    <tr>
                                        <td width="40%">Program Type</td>
                                        <td id="detail-program-type">Haji</td>
                                    </tr>
                                    <tr>
                                        <td>Program Name</td>
                                        <td id="detail-program-name">Haji 2026</td>
                                    </tr>
                                    <tr>
                                        <td>Departure Date</td>
                                        <td id="detail-departure">31 Dec 2025</td>
                                    </tr>
                                    <tr>
                                        <td>Room Type</td>
                                        <td id="detail-room-type">Quad</td>
                                    </tr>
                                    <tr>
                                        <td>Special Requests</td>
                                        <td id="detail-requests">Testing</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h3>Passport Information</h3>
                                <table>
                                    <tr>
                                        <td width="40%">Passport Name</td>
                                        <td id="detail-paspor-nama">-</td>
                                    </tr>
                                    <tr>
                                        <td>Passport Number</td>
                                        <td id="detail-paspor-no">-</td>
                                    </tr>
                                    <tr>
                                        <td>Issuing Authority</td>
                                        <td id="detail-paspor-tempat">-</td>
                                    </tr>
                                    <tr>
                                        <td>Issue Date</td>
                                        <td id="detail-paspor-keluar">-</td>
                                    </tr>
                                    <tr>
                                        <td>Expiry Date</td>
                                        <td id="detail-paspor-expire">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <h3 class="mt-4">Vaccination Information</h3>
                        <table>
                            <tr>
                                <td width="20%">First Vaccine</td>
                                <td id="detail-vaksin1">-</td>
                            </tr>
                            <tr>
                                <td>Second Vaccine</td>
                                <td id="detail-vaksin2">-</td>
                            </tr>
                            <tr>
                                <td>Third Vaccine</td>
                                <td id="detail-vaksin3">-</td>
                            </tr>
                            <tr>
                                <td>Previous Hajj Experience</td>
                                <td id="detail-pengalaman-haji">Belum</td>
                            </tr>
                        </table>
                        
                        <h2 class="mt-5">Payment Information</h2>
                        
                        <div class="payment-section">
                            <h5>Payment Details</h5>
                            <table>
                                <tr>
                                    <td width="30%">Payment Status</td>
                                    <td><span class="badge bg-success">verified</span></td>
                                </tr>
                                <tr>
                                    <td>Payment Type</td>
                                    <td id="detail-payment-type">DP</td>
                                </tr>
                                <tr>
                                    <td>Payment Method</td>
                                    <td id="detail-payment-method">BSI</td>
                                </tr>
                                <tr>
                                    <td>Payment Date</td>
                                    <td id="detail-payment-date">15 Jul 2025</td>
                                </tr>
                                <tr>
                                    <td>Payment Time</td>
                                    <td id="detail-payment-time">13:02:01</td>
                                </tr>
                                <tr>
                                    <td>Account Name</td>
                                    <td id="detail-payment-account">Kevin</td>
                                </tr>
                                <tr>
                                    <td>Amount Paid</td>
                                    <td id="detail-payment-amount">5,000.00</td>
                                </tr>
                                <tr>
                                    <td>Remaining Payment</td>
                                    <td id="detail-payment-remaining">11,000.00</td>
                                </tr>
                                <tr>
                                    <td>Verified By</td>
                                    <td id="detail-payment-verified">Admin</td>
                                </tr>
                                <tr>
                                    <td>Verified At</td>
                                    <td id="detail-payment-verified-at">15 Jul 2025 14:05</td>
                                </tr>
                            </table>
                        </div>
                        
                        <h3 class="mt-4">Document Checklist</h3>
                        <div class="document-checklist">
                            <div class="document-checklist-item">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <span>KK Document</span>
                            </div>
                            <div class="document-checklist-item">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <span>KTP Document</span>
                            </div>
                            <div class="document-checklist-item">
                                <i class="bi bi-x-circle-fill text-danger"></i>
                                <span>Passport</span>
                            </div>
                            <!-- Add more documents as needed -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // View details button click handler
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                // In a real implementation, you would fetch the details for the specific NIK
                var nik = this.getAttribute('data-nik');
                console.log('View details for NIK:', nik);
                
                // Show the modal
                var modal = new bootstrap.Modal(document.getElementById('jamaahDetailsModal'));
                modal.show();
            });
        });

        // Records per page change handler
        document.getElementById('recordsPerPage').addEventListener('change', function() {
            var value = this.value;
            document.querySelectorAll('.scrollable-table').forEach(table => {
                table.style.setProperty('--records-per-page', value);
            });
        });
    </script>
</body>
</html>