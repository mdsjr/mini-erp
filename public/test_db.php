<?php
require_once __DIR__ . '/../config/database.php';
$conn = getDBConnection();
echo "Conexão com o banco de dados bem-sucedida!";
$conn->close();
