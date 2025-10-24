<?php
try {
    // /tmp kullan - her zaman yazılabilir
    $dbFile = '/tmp/ticket.db';
    
    if (!file_exists($dbFile)) {
        createDatabaseWithSchema($dbFile);
    }
    
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "Bağlantı hatası: " . $e->getMessage();
    exit;
}

function createDatabaseWithSchema($dbFile) {
    try {
        touch($dbFile);
        chmod($dbFile, 0666);
        
        $pdo = new PDO("sqlite:" . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $schemaFile = __DIR__ . '/schema.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            $pdo->exec($schema);
            
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO User (id, full_name, email, role, password, company_id, balance, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                'usr_68f11e02947d04.15875714',
                'admin',
                'admin@gmail.com',
                'admin',
                '$2y$10$J4.Sh.CHSD9I.u.UQoKxguXt7RTm.tykyfGl/BcfIbGaMIUpKQ4F2',
                NULL,
                800,
                date('Y-m-d H:i:s')
            ]);
        }
        
    } catch (Exception $e) {
        throw $e;
    }
}
?>
