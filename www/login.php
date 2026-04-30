<?php
session_start();
require_once 'config/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefone = trim($_POST['telefone'] ?? '');
    $senha    = $_POST['senha'] ?? '';

    if (!empty($telefone) && !empty($senha)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE telefone = ?");
        $stmt->execute([$telefone]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome']       = $usuario['nome_completo'];
            $_SESSION['nome_completo'] = $usuario['nome_completo'];
            $_SESSION['telefone'] = $usuario['telefone'];
            $_SESSION['logado']     = true;
            $_SESSION['role']       = $usuario['role'];
            $_SESSION['perfil_interno'] = $usuario['perfil_interno'] ?? null;
            
            if ($_SESSION['role'] === 'admin') {
                $pi = $_SESSION['perfil_interno'] ?? '';
                if ($pi === 'funcionario') {
                    header('Location: dashboard_funcionario.php');
                } else {
                    header('Location: dashboard.php');
                }
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $erro = 'Dados incorretos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Jombaca</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --verde: #198754; }
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: sans-serif; }
        .login-box { width: 100%; max-width: 380px; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .logo-small { height: 60px; display: block; margin: 0 auto 20px; }
        .form-control { border-radius: 8px; border: 1px solid #dee2e6; padding: 12px; margin-bottom: 15px; }
        .form-control:focus { border-color: var(--verde); box-shadow: none; }
        .btn-entrar { background: var(--verde); color: white; border: none; width: 100%; padding: 12px; border-radius: 8px; font-weight: bold; transition: 0.2s; }
        .btn-entrar:hover { background: #146c43; }
        .links { text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .links a { color: var(--verde); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="login-box">
        <a href="index.php"><img src="assets/img/logoJombaca.png" alt="Jombaca" class="logo-small"></a>
        <h4 class="text-center fw-bold mb-4">Entrar</h4>
        <?php if ($erro): ?>
            <div class="alert alert-danger py-2 small text-center"><?= $erro ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="tel" name="telefone" class="form-control" placeholder="Telefone" required autofocus>
            <input type="password" name="senha" class="form-control" placeholder="Palavra-passe" required>
            <button type="submit" class="btn btn-entrar">Entrar</button>
        </form>
        <div class="links">
            <p class="text-muted mb-1">Não tem conta? <a href="cadastro.php">Criar agora</a></p>
            <p class="text-muted mb-1"><a href="forgot_password.php">Esqueci a minha senha</a></p>
            <a href="index.php" class="text-muted small"><i class="bi bi-arrow-left"></i> Voltar ao início</a>
        </div>
    </div>
</body>
</html>