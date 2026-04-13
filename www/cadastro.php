<?php
session_start();
require_once 'config/db.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email    = trim($_POST['email'] ?? ''); // Novo campo
    $senha    = $_POST['senha'] ?? '';
    $lgpd     = isset($_POST['lgpd']) ? 1 : 0;

    if (empty($nome) || empty($telefone) || empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Introduza um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (!$lgpd) {
        $erro = 'Aceite os termos da LGPD.';
    } else {
        // Verifica se telefone ou e-mail já existem
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefone = ? OR email = ?");
        $stmt->execute([$telefone, $email]);
        
        if ($stmt->fetch()) {
            $erro = 'Este telefone ou e-mail já está registado.';
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Inserção na BD incluindo o campo e-mail
            $sql = "INSERT INTO usuarios (nome_completo, telefone, email, senha_hash, lgpd_consent, role) 
                    VALUES (?, ?, ?, ?, ?, 'cliente')";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$nome, $telefone, $email, $senha_hash, $lgpd])) {
                $sucesso = 'Conta criada! Já pode fazer login.';
            } else {
                $erro = 'Erro ao registar. Tente novamente.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Jombaca</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --verde: #198754; }
        body { background-color: #f8f9fa; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: sans-serif; padding: 20px; }
        .auth-box { width: 100%; max-width: 420px; background: white; padding: 35px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .logo-small { height: 50px; display: block; margin: 0 auto 15px; }
        .form-control { border-radius: 8px; padding: 12px; margin-bottom: 12px; border: 1px solid #dee2e6; }
        .form-control:focus { border-color: var(--verde); box-shadow: none; }
        .btn-acao { background: var(--verde); color: white; border: none; width: 100%; padding: 12px; border-radius: 8px; font-weight: bold; margin-top: 10px; }
        .btn-acao:hover { background: #146c43; }
        .lgpd-text { font-size: 0.85rem; color: #6c757d; line-height: 1.4; }
        .links { text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .links a { color: var(--verde); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>

    <div class="auth-box">
        <a href="index.php"><img src="assets/img/logoJombaca.png" alt="Jombaca" class="logo-small"></a>
        <h4 class="text-center fw-bold mb-4">Criar Conta</h4>

        <?php if ($erro): ?>
            <div class="alert alert-danger py-2 small text-center"><?= $erro ?></div>
        <?php elseif ($sucesso): ?>
            <div class="alert alert-success py-2 small text-center"><?= $sucesso ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="nome" class="form-control" placeholder="Nome completo" required>
            <input type="tel" name="telefone" class="form-control" placeholder="Telefone" required pattern="[0-9]{9,12}">
            <input type="email" name="email" class="form-control" placeholder="E-mail" required>
            <input type="password" name="senha" class="form-control" placeholder="Palavra-passe (mín. 6 caracteres)" required minlength="6">

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="lgpd" id="lgpd" required>
                <label class="form-check-label lgpd-text" for="lgpd">
                    Li e aceito o tratamento dos meus dados conforme a <strong>LGPD</strong>.
                </label>
            </div>
            
            <button type="submit" class="btn-acao">Registar Agora</button>
        </form>

        <div class="links">
            <p class="text-muted">Já tem uma conta? <a href="login.php">Fazer login</a></p>
            <a href="index.php" class="text-muted small"><i class="bi bi-arrow-left"></i> Voltar ao início</a>
        </div>
    </div>

</body>
</html>