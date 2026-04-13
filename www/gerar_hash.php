<?php
$senha_plana = 'AdminRoot2026!';  // Mude para a senha que quiseres usar
$hash = password_hash($senha_plana, PASSWORD_DEFAULT);
echo "<h2>Hash gerado:</h2>";
echo "<pre>" . htmlspecialchars($hash) . "</pre>";
echo "<p>Use este hash no INSERT ou UPDATE abaixo.</p>";
?>