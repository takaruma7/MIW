<?php foreach ($packages as $package): ?>
    <div class="package-header">
        <h4><?= htmlspecialchars($package['jenis_paket']) ?> - <?= htmlspecialchars($package['program_pilihan']) ?></h4>
        <p>
            <strong>Tanggal Keberangkatan:</strong> <?= date('d/m/Y', strtotime($package['tanggal_keberangkatan'])) ?><br>
            <strong>Hotel Madinah:</strong> <?= htmlspecialchars($package['hotel_medinah']) ?><br>
            <strong>Hotel Makkah:</strong> <?= htmlspecialchars($package['hotel_makkah']) ?>
        </p>
        <button class="btn btn-sm btn-primary export-kelengkapan" data-pakid="<?= $package['pak_id'] ?>">Export Kelengkapan</button>
    </div>

    <table class="table table-striped package-table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Buku Kuning</th>
                <th>Foto</th>
                <th>Fotocopy KTP</th>
                <th>Fotocopy Ijazah</th>
                <th>Fotocopy KK</th>
                <th>Fotocopy Buku Nikah</th>
                <th>Fotocopy Akta Kelahiran</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Get jamaah for this package
            $stmt = $conn->prepare("SELECT * FROM data_jamaah WHERE pak_id = ?");
            $stmt->execute([$package['pak_id']]);
            $jamaahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $counter = 1;
            foreach ($jamaahs as $jamaah): 
            ?>
            <tr>
                <td><?= $counter++ ?></td>
                <td><?= htmlspecialchars($jamaah['nik']) ?></td>
                <td><?= htmlspecialchars($jamaah['nama']) ?></td>
                <td><?= htmlspecialchars($jamaah['jenis_kelamin']) ?></td>
                <td>
                    <form class="kelengkapan-form" enctype="multipart/form-data">
                        <input type="hidden" name="nik" value="<?= $jamaah['nik'] ?>">
                        <div class="upload-status">
                            <?php if ($jamaah['bk_kuning']): ?>
                                <span class="text-success">Uploaded <?= date('d/m/Y H:i', strtotime($jamaah['bk_kuning'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control" name="bk_kuning" accept=".pdf,.jpg,.jpeg,.png">
                </td>
                <td>
                    <div class="upload-status">
                        <?php if ($jamaah['foto']): ?>
                            <span class="text-success">Uploaded <?= date('d/m/Y H:i', strtotime($jamaah['foto'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control" name="foto" accept=".jpg,.jpeg,.png">
                </td>
                <td>
                    <div class="upload-status">
                        <?php if ($jamaah['fc_ktp_uploaded_at']): ?>
                            <span class="text-success">Uploaded <?= date('d/m/Y H:i', strtotime($jamaah['fc_ktp_uploaded_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control" name="fc_ktp" accept=".pdf,.jpg,.jpeg,.png">
                </td>
                <td>
                    <div class="upload-status">
                        <?php if ($jamaah['fc_ijazah_uploaded_at']): ?>
                            <span class="text-success">Uploaded <?= date('d/m/Y H:i', strtotime($jamaah['fc_ijazah_uploaded_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control" name="fc_ijazah" accept=".pdf,.jpg,.jpeg,.png">
                </td>
                <td>
                    <div class="upload-status">
                        <?php if ($jamaah['fc_kk_uploaded_at']): ?>
                            <span class="text-success">Uploaded <?= date('d/m/Y H:i', strtotime($jamaah['fc_kk_uploaded_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control" name="fc_kk" accept=".pdf,.jpg,.jpeg,.png">
                </td>
                <td>
                    <div class="upload-status">
                        <?php if ($jamaah['fc_bk_nikah_uploaded_at']): ?>
                            <span class="text-success">Uploaded <?= date('d/m/Y H:i', strtotime($jamaah['fc_bk_nikah_uploaded_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control" name="fc_bk_nikah" accept=".pdf,.jpg,.jpeg,.png">
                </td>
                <td>
                    <div class="upload-status">
                        <?php if ($jamaah['fc_akta_lahir_uploaded_at']): ?>
                            <span class="text-success">Uploaded <?= date('d/m/Y H:i', strtotime($jamaah['fc_akta_lahir_uploaded_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control" name="fc_akta_lahir" accept=".pdf,.jpg,.jpeg,.png">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary update-kelengkapan">Upload All</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>