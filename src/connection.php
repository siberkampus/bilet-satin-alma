<?php
try {
    $dbFile = __DIR__ . '/ticket.db';

    $pdo = new PDO("sqlite:" . $dbFile);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "Bağlantı hatası: " . $e->getMessage();
}
?>
