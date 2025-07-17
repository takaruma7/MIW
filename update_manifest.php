<?php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Debug log
    error_log("POST data received: " . print_r($_POST, true));
    
    // Validate all required fields upfront
    $required_fields = ['nik', 'pak_id', 'room_prefix', 'medinah_number', 'mekkah_number', 'relation'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing_fields));
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Check if manifest record exists
    $stmt = $conn->prepare("SELECT * FROM data_manifest WHERE nik = ? AND pak_id = ?");
    $stmt->execute([$_POST['nik'], $_POST['pak_id']]);
    $manifest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare data with trimmed values
    $data = [
        'nik' => trim($_POST['nik']),
        'pak_id' => trim($_POST['pak_id']),
        'room_prefix' => trim($_POST['room_prefix']),
        'medinah_number' => trim($_POST['medinah_number']),
        'mekkah_number' => trim($_POST['mekkah_number']),
        'relation' => trim($_POST['relation'])
    ];
    
    // Double-check that none of the trimmed values are empty
    foreach ($data as $key => $value) {
        if ($value === '') {
            throw new Exception("Field '$key' cannot be empty");
        }
    }
    
    if ($manifest) {
        // Update existing record
        $sql = "UPDATE data_manifest SET 
                room_prefix = :room_prefix,
                medinah_number = :medinah_number,
                mekkah_number = :mekkah_number,
                relation = :relation,
                updated_at = NOW()
                WHERE nik = :nik AND pak_id = :pak_id";
    } else {
        // Insert new record
        $sql = "INSERT INTO data_manifest (
                nik, pak_id, room_prefix, medinah_number, 
                mekkah_number, relation, created_at, updated_at
                ) VALUES (
                :nik, :pak_id, :room_prefix, :medinah_number, 
                :mekkah_number, :relation, NOW(), NOW())";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
    
    $conn->commit();
    $response = [
        'success' => true, 
        'message' => 'Manifest updated successfully'
    ];

} catch (Exception $e) {
    // Only rollback if we're in a transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $response = [
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ];
}

echo json_encode($response);