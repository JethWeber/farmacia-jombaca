<?php
/**
 * Arquivo de configuração da conexão com o banco de dados
 * Farmácia Jombaca - Projeto Acadêmico
 */

// Configurações do banco (mesmas do docker-compose.yml)
$host     = 'db';                  // Nome do serviço no Docker Compose (NÃO use 'localhost')
$dbname   = 'farmacia_jombaca';
$username = 'jombaca_user';        // Usuário criado no docker-compose
$password = 'jombaca_mainUser2026';     // Senha criada no docker-compose

// Opções para PDO (recomendado: mais seguro e moderno que mysqli)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lança exceções em erro
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Retorna array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Usa prepared statements reais
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"      // Suporte a emojis e acentos
];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        $options
    );
    
    $pdo->exec("SET time_zone = '+01:00'");  

} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Função auxiliar para depuração (remova em produção)
function debug($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

?>