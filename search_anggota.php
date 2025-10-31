<?php
include __DIR__ . '/config/db.php';

$term = $_GET['term'] ?? '';
$data = [];

if ($term != '') {
    $term = $conn->real_escape_string($term);
    $q = $conn->query("SELECT no_rekening, nama 
                       FROM anggota 
                       WHERE no_rekening LIKE '%$term%' 
                          OR nama LIKE '%$term%' 
                       LIMIT 10");
    while ($r = $q->fetch_assoc()) {
        $data[] = [
            'label' => $r['no_rekening'] . " - " . $r['nama'],
            'value' => $r['no_rekening']
        ];
    }
}

echo json_encode($data);
