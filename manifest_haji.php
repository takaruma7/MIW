<?php
$stmt = $conn->query("SELECT * FROM data_paket WHERE jenis_paket = 'Haji' ORDER BY program_pilihan");
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($packages as $package): ?>
    <div class="package-header">
        <h4>Haji - <?= htmlspecialchars($package['program_pilihan']) ?></h4>
        <p>
            <strong>Tanggal Keberangkatan:</strong> <?= date('d/m/Y', strtotime($package['tanggal_keberangkatan'])) ?><br>
            <strong>Hotel Madinah:</strong> <?= htmlspecialchars($package['hotel_medinah']) ?><br>
            <strong>Hotel Makkah:</strong> <?= htmlspecialchars($package['hotel_makkah']) ?>
        </p>
        <button class="btn btn-sm btn-primary export-manifest" data-pakid="<?= $package['pak_id'] ?>">Export Manifest</button>
    </div>

    <div class="room-data mb-3">
        <h5>Room Data</h5>
        <?php
        $medinahRooms = json_decode($package['hotel_medinah_rooms'], true) ?: [];
        $makkahRooms = json_decode($package['hotel_makkah_rooms'], true) ?: [];
        ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Medinah Rooms</h6>
                        <div class="room-list">
                            <?php if (!empty($medinahRooms['quad'])): ?>
                            <p><strong>Quad Rooms:</strong>
                                <?php foreach ($medinahRooms['quad'] as $index => $room): ?>
                                <span class="badge bg-primary me-1" title="Q<?= $index + 1 ?>"><?= htmlspecialchars($room) ?></span>
                                <?php endforeach; ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($medinahRooms['triple'])): ?>
                            <p><strong>Triple Rooms:</strong>
                                <?php foreach ($medinahRooms['triple'] as $index => $room): ?>
                                <span class="badge bg-success me-1" title="T<?= $index + 1 ?>"><?= htmlspecialchars($room) ?></span>
                                <?php endforeach; ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($medinahRooms['double'])): ?>
                            <p><strong>Double Rooms:</strong>
                                <?php foreach ($medinahRooms['double'] as $index => $room): ?>
                                <span class="badge bg-info me-1" title="D<?= $index + 1 ?>"><?= htmlspecialchars($room) ?></span>
                                <?php endforeach; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Makkah Rooms</h6>
                        <div class="room-list">
                            <?php if (!empty($makkahRooms['quad'])): ?>
                            <p><strong>Quad Rooms:</strong>
                                <?php foreach ($makkahRooms['quad'] as $index => $room): ?>
                                <span class="badge bg-primary me-1" title="Q<?= $index + 1 ?>"><?= htmlspecialchars($room) ?></span>
                                <?php endforeach; ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($makkahRooms['triple'])): ?>
                            <p><strong>Triple Rooms:</strong>
                                <?php foreach ($makkahRooms['triple'] as $index => $room): ?>
                                <span class="badge bg-success me-1" title="T<?= $index + 1 ?>"><?= htmlspecialchars($room) ?></span>
                                <?php endforeach; ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($makkahRooms['double'])): ?>
                            <p><strong>Double Rooms:</strong>
                                <?php foreach ($makkahRooms['double'] as $index => $room): ?>
                                <span class="badge bg-info me-1" title="D<?= $index + 1 ?>"><?= htmlspecialchars($room) ?></span>
                                <?php endforeach; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table class="table table-striped package-table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Relation</th>
                <th>Room Prefix</th>
                <th>Medinah Room</th>
                <th>Mekkah Room</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $stmt = $conn->prepare("SELECT j.*, m.room_prefix as manifest_room_prefix
                                  FROM data_jamaah j 
                                  LEFT JOIN data_manifest m ON j.nik = m.nik AND j.pak_id = m.pak_id 
                                  WHERE j.pak_id = ?");
            $stmt->execute([$package['pak_id']]);
            $jamaahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $counter = 1;
            foreach ($jamaahs as $jamaah): 
                $roomType = $jamaah['type_room_pilihan'];
                $roomPrefixes = [];
                $roomNumbers = explode(',', $package['room_numbers']);
                
                foreach ($roomNumbers as $prefix) {
                    if ($roomType === 'Quad' && strpos($prefix, 'Q') === 0) {
                        $roomPrefixes[] = $prefix;
                    } elseif ($roomType === 'Triple' && strpos($prefix, 'T') === 0) {
                        $roomPrefixes[] = $prefix;
                    } elseif ($roomType === 'Double' && strpos($prefix, 'D') === 0) {
                        $roomPrefixes[] = $prefix;
                    }
                }
                
                $stmt2 = $conn->prepare("SELECT medinah_number, mekkah_number FROM data_manifest WHERE nik = ? AND pak_id = ?");
                $stmt2->execute([$jamaah['nik'], $package['pak_id']]);
                $manifestData = $stmt2->fetch();
                $medinahNumber = $manifestData ? $manifestData['medinah_number'] : '';
                $mekkahNumber = $manifestData ? $manifestData['mekkah_number'] : '';
            ?>
            <tr>
                <td><?= $counter++ ?></td>
                <td><?= htmlspecialchars($jamaah['nik']) ?></td>
                <td><?= htmlspecialchars($jamaah['nama']) ?></td>
                <td><?= htmlspecialchars($jamaah['jenis_kelamin']) ?></td>
                <form class="manifest-form" method="post">
                    <input type="hidden" name="nik" value="<?= htmlspecialchars($jamaah['nik']) ?>">
                    <input type="hidden" name="pak_id" value="<?= htmlspecialchars($package['pak_id']) ?>">
                    <td>
                        <input type="text" class="form-control" name="relation" 
                               value="<?= htmlspecialchars($jamaah['hubungan_mahram'] ?? '') ?>" required>
                    </td>
                    <td>
                        <select class="form-select" name="room_prefix" required>
                            <option value="">Select Room</option>
                            <?php foreach ($roomPrefixes as $prefix): ?>
                                <option value="<?= htmlspecialchars($prefix) ?>" <?= ($jamaah['manifest_room_prefix'] === $prefix) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prefix) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="medinah_number" value="<?= htmlspecialchars($medinahNumber) ?>" required>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="mekkah_number" value="<?= htmlspecialchars($mekkahNumber) ?>" required>
                    </td>
                    <td>
                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>
