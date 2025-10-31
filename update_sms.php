<?php
// update_sms.php
include __DIR__ . '/config/db.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $q = $conn->query("UPDATE pinjaman SET sms = 1 WHERE id = $id");
    echo $q ? "ok" : "error";
}
$conn->close();
