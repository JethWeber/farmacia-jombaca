<?php
/**
 * Arquivo de configuração da conexão com o banco de dados
 * Farmácia Jombaca - Projeto Acadêmico
 */

// Configurações do banco 
$host     = 'db';                  
$dbname   = 'farmacia_jombaca';
$username = 'jombaca_user';        
$password = 'jombaca_mainUser2026';     

// Opções para PDO (recomendado: mais seguro e moderno que mysqli)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        
    PDO::ATTR_EMULATE_PREPARES   => false,                   
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"      
];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        $options
    );
    
    $pdo->exec("SET time_zone = '+01:00'");  

    // Garante que o sistema nunca fica sem este utilizador admin padrão.
    $adminNome = 'Weber Admin';
    $adminEmail = 'weber@admin.com';
    $adminTelefone = '900000000';
    $adminSenhaHash = password_hash('weber@admin666', PASSWORD_DEFAULT);

    try {
        // Procura primeiro por email; se não existir, reaproveita eventual registo com o telefone padrão.
        $stmtAdmin = $pdo->prepare(
            "SELECT id
             FROM usuarios
             WHERE email = ? OR telefone = ?
             ORDER BY (email = ?) DESC
             LIMIT 1"
        );
        $stmtAdmin->execute([$adminEmail, $adminTelefone, $adminEmail]);
        $adminExistente = $stmtAdmin->fetch();

        if ($adminExistente) {
            $stmtAtualizaAdmin = $pdo->prepare(
                "UPDATE usuarios
                 SET nome_completo = ?, email = ?, telefone = ?, senha_hash = ?, role = 'admin', lgpd_consent = 1
                 WHERE id = ?"
            );
            $stmtAtualizaAdmin->execute([
                $adminNome,
                $adminEmail,
                $adminTelefone,
                $adminSenhaHash,
                $adminExistente['id']
            ]);
        } else {
            $stmtCriaAdmin = $pdo->prepare(
                "INSERT INTO usuarios (nome_completo, email, senha_hash, telefone, role, lgpd_consent)
                 VALUES (?, ?, ?, ?, 'admin', 1)"
            );
            $stmtCriaAdmin->execute([
                $adminNome,
                $adminEmail,
                $adminSenhaHash,
                $adminTelefone
            ]);
        }
    } catch (PDOException $e) {
        // Evita quebrar o arranque quando o schema ainda não foi criado.
        error_log('Falha ao garantir utilizador admin padrão: ' . $e->getMessage());
    }

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