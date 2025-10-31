<?php
include __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = $_POST['id'] ?? '';
    $norek  = $_POST['norek'] ?? '';

    if ($id && $norek && strpos($norek, '02') === 0) {
        $sql = "UPDATE tabungan u 
                JOIN anggota a ON u.anggotaid = a.id 
                SET u.sms = 1 
                WHERE a.noanggota = '".$conn->real_escape_string($id)."' 
                  AND u.norekening = '".$conn->real_escape_string($norek)."'";

        if ($conn->query($sql)) {
            echo "ok";
        } else {
            echo "fail";
        }
    } else {
        echo "fail";
    }
}
$conn->close();
