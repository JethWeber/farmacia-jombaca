<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';

require_admin_principal_only();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'autorizar') {
    $pedidoId = (int)($_POST['pedido_id'] ?? 0);
    $novaSenha = trim($_POST['nova_senha'] ?? '');
    if ($pedidoId > 0 && $novaSenha !== '') {
        $stmtPedido = $pdo->prepare("SELECT usuario_id FROM pedidos_recuperacao WHERE id = ? AND status = 'pendente' LIMIT 1");
        $stmtPedido->execute([$pedidoId]);
        $pedido = $stmtPedido->fetch();
        if ($pedido) {
            $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmtSenha = $pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
            $stmtSenha->execute([$hash, $pedido['usuario_id']]);

            $stmtAtualizaPedido = $pdo->prepare("UPDATE pedidos_recuperacao SET status='atendido', resolvido_por=?, senha_temporaria=?, data_resolucao=NOW() WHERE id=?");
            $stmtAtualizaPedido->execute([$_SESSION['usuario_id'], $novaSenha, $pedidoId]);
        }
    }
    header('Location: pedidos_recuperacao.php');
    exit;
}

$pedidos = $pdo->query("
    SELECT pr.id, pr.data_pedido, pr.status, u.nome_completo, u.telefone, u.perfil_interno
    FROM pedidos_recuperacao pr
    JOIN usuarios u ON u.id = pr.usuario_id
    ORDER BY pr.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Pedidos de Recuperação</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">Voltar ao painel</a>
    <h2 class="text-success fw-bold mb-4">Pedidos de Recuperação de Conta</h2>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Data</th><th>Utilizador</th><th>Perfil</th><th>Status</th><th>Ação</th></tr></thead>
                <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                        <td><?= htmlspecialchars($pedido['nome_completo']) ?> (<?= htmlspecialchars($pedido['telefone']) ?>)</td>
                        <td><?= htmlspecialchars($pedido['perfil_interno'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($pedido['status']) ?></td>
                        <td>
                            <?php if ($pedido['status'] === 'pendente'): ?>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="acao" value="autorizar">
                                    <input type="hidden" name="pedido_id" value="<?= (int)$pedido['id'] ?>">
                                    <input type="text" name="nova_senha" class="form-control form-control-sm" placeholder="Senha temporária" required>
                                    <button class="btn btn-sm btn-success">Autorizar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
