<?php
include __DIR__ . '/config/db.php';

$id = $_POST['id'] ?? 0;
$angsuranke = $_POST['rekeningkoran'] ?? 0;

if ($id && is_numeric($angsuranke)) {
    $sql = "UPDATE pinjaman SET rekeningkoran = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $angsuranke, $id);
    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "err";
    }
    $stmt->close();
} else {
    echo "invalid";
}

$conn->close();
?>
