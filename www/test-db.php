<?php
$host = 'db';           // Nome do serviço no docker-compose
$dbname = 'farmacia_jombaca';
$user = 'jombaca_user';
$pass = 'jombaca_pass123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão com o banco MySQL OK!";
} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
}
?>