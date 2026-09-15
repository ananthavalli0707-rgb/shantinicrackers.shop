<?php
require 'config/db.php';
print_r($pdo->query('SHOW COLUMNS FROM categories')->fetchAll());
