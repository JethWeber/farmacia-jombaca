<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';

require_admin_any();

$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'registrar_venda') {
    $produtoId = (int)($_POST['produto_id'] ?? 0);
    $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));

    $stmtProduto = $pdo->prepare("SELECT id, nome, categoria_id, preco, estoque_atual FROM produtos WHERE id = ? LIMIT 1");
    $stmtProduto->execute([$produtoId]);
    $produto = $stmtProduto->fetch();

    if (!$produto) {
        header('Location: vendas.php?msg=Produto não encontrado.');
        exit;
    }
    if ((int)$produto['estoque_atual'] < $quantidade) {
        header('Location: vendas.php?msg=Estoque insuficiente para esta venda.');
        exit;
    }

    $subtotal = (float)$produto['preco'] * $quantidade;
    $stmtVenda = $pdo->prepare("INSERT INTO vendas (produto_id, usuario_id, quantidade, preco_unitario, subtotal, categoria_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtVenda->execute([$produtoId, $_SESSION['usuario_id'], $quantidade, $produto['preco'], $subtotal, $produto['categoria_id']]);

    $stmtEstoque = $pdo->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?");
    $stmtEstoque->execute([$quantidade, $produtoId]);

    header('Location: vendas.php?msg=Venda registada com sucesso.');
    exit;
}

$produtos = $pdo->query("SELECT id, nome, preco, estoque_atual FROM produtos WHERE disponivel = 1 ORDER BY nome ASC")->fetchAll();
$ultimasVendas = $pdo->query("
    SELECT v.*, p.nome AS produto_nome
    FROM vendas v
    JOIN produtos p ON p.id = v.produto_id
    ORDER BY v.id DESC
    LIMIT 15
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Realizar Venda - Jombaca</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="<?= htmlspecialchars(painel_voltar_desde_extends()) ?>" class="btn btn-outline-secondary btn-sm mb-3">Voltar ao painel</a>
    <h2 class="text-success fw-bold mb-4">Realizar Venda</h2>
    <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="acao" value="registrar_venda">
                <div class="col-md-6">
                    <label class="form-label">Produto</label>
                    <select class="form-select" name="produto_id" required>
                        <option value="">Selecionar...</option>
                        <?php foreach ($produtos as $produto): ?>
                            <option value="<?= (int)$produto['id'] ?>">
                                <?= htmlspecialchars($produto['nome']) ?> - <?= number_format((float)$produto['preco'], 2, ',', '.') ?> Kz (Estoque: <?= (int)$produto['estoque_atual'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantidade</label>
                    <input type="number" class="form-control" name="quantidade" min="1" value="1" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-success w-100">Registar venda</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">Últimas vendas</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Data</th><th>Produto</th><th>Qtd</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($ultimasVendas as $venda): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                        <td><?= htmlspecialchars($venda['produto_nome']) ?></td>
                        <td><?= (int)$venda['quantidade'] ?></td>
                        <td><?= number_format((float)$venda['subtotal'], 2, ',', '.') ?> Kz</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
