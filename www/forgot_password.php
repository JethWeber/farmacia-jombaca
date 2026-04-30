<?php
session_start();
require_once 'config/db.php';

$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefone = trim($_POST['telefone'] ?? '');

    if ($telefone === '') {
        $erro = 'Informe o telefone da conta.';
    } else {
        $stmtUser = $pdo->prepare("SELECT id, role, perfil_interno FROM usuarios WHERE telefone = ? LIMIT 1");
        $stmtUser->execute([$telefone]);
        $usuario = $stmtUser->fetch();

        if (!$usuario) {
            $erro = 'Utilizador não encontrado.';
        } elseif ($usuario['role'] !== 'admin' || ($usuario['perfil_interno'] ?? '') === 'admin_principal') {
            $erro = 'Este tipo de conta não usa este fluxo de recuperação manual.';
        } else {
            $stmtExiste = $pdo->prepare("SELECT id FROM pedidos_recuperacao WHERE usuario_id = ? AND status = 'pendente' LIMIT 1");
            $stmtExiste->execute([$usuario['id']]);
            if (!$stmtExiste->fetch()) {
                $stmtPedido = $pdo->prepare("INSERT INTO pedidos_recuperacao (usuario_id, mensagem, status) VALUES (?, ?, 'pendente')");
                $stmtPedido->execute([$usuario['id'], 'Pedido aberto pela tela "Esqueci a minha senha".']);
            }
            $msg = 'Pedido enviado. Contacte o Admin Principal para autorizar a redefinição da senha.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Conta - Jombaca</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="text-success fw-bold mb-3">Esqueci a minha senha</h4>
                    <p class="text-muted small">Este sistema não usa e-mail, SMS ou OTP. O processo é manual e mediado pelo Admin Principal.</p>
                    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                    <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
                    <form method="POST">
                        <label class="form-label">Telefone da conta</label>
                        <input type="text" name="telefone" class="form-control mb-3" required>
                        <button class="btn btn-success w-100">Solicitar recuperação</button>
                    </form>
                    <a href="login.php" class="btn btn-link mt-3 p-0">Voltar ao login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
