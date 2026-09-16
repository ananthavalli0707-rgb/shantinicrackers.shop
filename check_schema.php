<?php
require 'config/db.php';
echo "INQUIRIES:\n";
print_r($pdo->query('SHOW COLUMNS FROM inquiries')->fetchAll(PDO::FETCH_ASSOC));
echo "\nSHIPPING_DETAILS:\n";
print_r($pdo->query('SHOW COLUMNS FROM shipping_details')->fetchAll(PDO::FETCH_ASSOC));
