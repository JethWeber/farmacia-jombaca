<?php
require_once 'config/db.php';

echo "<h1>Conexão ao banco OK!</h1>";
echo "<p>Banco: farmacia_jombaca</p>";
echo "<p>Host: db (Docker)</p>";

// Teste simples: lista tabelas
$stmt = $pdo->query("SHOW TABLES");
echo "<h2>Tabelas existentes:</h2><ul>";
while ($row = $stmt->fetch()) {
    echo "<li>" . $row['Tables_in_farmacia_jombaca'] . "</li>";
}
echo "</ul>";
?>