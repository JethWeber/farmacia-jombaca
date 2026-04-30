<?php
/**
 * Conexão PDO + migrações automáticas na primeira utilização (e em bases antigas).
 * Credenciais: variáveis de ambiente (Docker) ou valores por omissão para desenvolvimento local.
 */

$host     = getenv('DB_HOST') ?: 'db';
$dbname   = getenv('DB_NAME') ?: 'farmacia_jombaca';
$username = getenv('DB_USER') ?: 'jombaca_user';
$password = getenv('DB_PASSWORD') ?: 'jombaca_mainUser2026';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        $options
    );

    $pdo->exec("SET time_zone = '+01:00'");

    // Migrações idempotentes (sem IF NOT EXISTS em ADD COLUMN — compatível com MySQL 8).
    try {
        $stmtCol = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND COLUMN_NAME = 'perfil_interno'"
        );
        if ($stmtCol && (int) $stmtCol->fetchColumn() === 0) {
            $pdo->exec(
                "ALTER TABLE usuarios ADD COLUMN perfil_interno ENUM('admin_principal','admin_secundario','funcionario') DEFAULT NULL AFTER role"
            );
        }
        $pdo->exec(
            "UPDATE usuarios SET perfil_interno = 'admin_principal' WHERE role = 'admin' AND (perfil_interno IS NULL OR perfil_interno = '')"
        );

        $stmtRes = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'reservas'
               AND COLUMN_NAME = 'filial_preferida_id'"
        );
        if ($stmtRes && (int) $stmtRes->fetchColumn() === 0) {
            $pdo->exec(
                "ALTER TABLE reservas ADD COLUMN filial_preferida_id INT DEFAULT NULL AFTER quantidade_solicitada"
            );
            try {
                $pdo->exec("ALTER TABLE reservas ADD KEY idx_filial_preferida (filial_preferida_id)");
            } catch (PDOException $e) {
                // índice já pode existir
            }
            try {
                $pdo->exec(
                    "ALTER TABLE reservas ADD CONSTRAINT reservas_filial_fk FOREIGN KEY (filial_preferida_id) REFERENCES filiais(id) ON DELETE SET NULL"
                );
            } catch (PDOException $fk) {
                error_log('Migração reservas.filial_preferida_id (FK opcional): ' . $fk->getMessage());
            }
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS fornecedores (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(150) NOT NULL,
                telefone VARCHAR(20) DEFAULT NULL,
                email VARCHAR(255) DEFAULT NULL,
                endereco TEXT,
                observacoes TEXT,
                ativo TINYINT(1) DEFAULT 1,
                data_cadastro TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vendas (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                produto_id INT NOT NULL,
                usuario_id INT DEFAULT NULL,
                quantidade INT NOT NULL,
                preco_unitario DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                categoria_id INT DEFAULT NULL,
                data_venda TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (produto_id),
                INDEX (usuario_id),
                INDEX (categoria_id),
                CONSTRAINT vendas_ibfk_1 FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
                CONSTRAINT vendas_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
                CONSTRAINT vendas_ibfk_3 FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pedidos_recuperacao (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                status ENUM('pendente','atendido','recusado') DEFAULT 'pendente',
                mensagem TEXT,
                resolvido_por INT DEFAULT NULL,
                senha_temporaria VARCHAR(255) DEFAULT NULL,
                data_pedido TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                data_resolucao TIMESTAMP NULL DEFAULT NULL,
                INDEX (usuario_id),
                INDEX (resolvido_por),
                CONSTRAINT pedidos_recuperacao_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                CONSTRAINT pedidos_recuperacao_ibfk_2 FOREIGN KEY (resolvido_por) REFERENCES usuarios(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    } catch (PDOException $e) {
        error_log('Migrações schema (db.php): ' . $e->getMessage());
    }
} catch (PDOException $e) {
    die('Erro na conexão com o banco de dados: ' . htmlspecialchars($e->getMessage()));
}

function debug($var): void
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}
