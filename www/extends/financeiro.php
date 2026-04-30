<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';

require_admin_principal_only();

$mesRef = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mesRef)) {
    $mesRef = date('Y-m');
}

$totalDia = (float)$pdo->query("SELECT COALESCE(SUM(subtotal), 0) FROM vendas WHERE DATE(data_venda) = CURDATE()")->fetchColumn();
$stmtMes = $pdo->prepare("SELECT COALESCE(SUM(subtotal), 0) FROM vendas WHERE DATE_FORMAT(data_venda, '%Y-%m') = ?");
$stmtMes->execute([$mesRef]);
$totalMes = (float)$stmtMes->fetchColumn();

$porProduto = $pdo->query("
    SELECT p.nome, SUM(v.subtotal) AS subtotal_total
    FROM vendas v
    JOIN produtos p ON p.id = v.produto_id
    GROUP BY p.id, p.nome
    ORDER BY subtotal_total DESC
    LIMIT 20
")->fetchAll();

$porCategoria = $pdo->query("
    SELECT c.nome, SUM(v.subtotal) AS subtotal_total
    FROM vendas v
    LEFT JOIN categorias c ON c.id = v.categoria_id
    GROUP BY c.id, c.nome
    ORDER BY subtotal_total DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão Financeira</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">Voltar ao painel</a>
    <h2 class="text-success fw-bold mb-4">Gestão Financeira</h2>
    <div class="row g-3 mb-4">
        <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body"><h6>Relatório Diário</h6><h3><?= number_format($totalDia, 2, ',', '.') ?> Kz</h3></div></div></div>
        <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body"><h6>Relatório Mensal</h6><h3><?= number_format($totalMes, 2, ',', '.') ?> Kz</h3></div></div></div>
    </div>

    <form method="GET" class="card card-body border-0 shadow-sm mb-4">
        <label class="form-label">Mês de referência</label>
        <div class="d-flex gap-2">
            <input type="month" class="form-control" name="mes" value="<?= htmlspecialchars($mesRef) ?>">
            <button class="btn btn-success">Navegar</button>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Subtotal por Produto</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($porProduto as $item): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= htmlspecialchars($item['nome']) ?></span>
                        <strong><?= number_format((float)$item['subtotal_total'], 2, ',', '.') ?> Kz</strong>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Subtotal por Categoria</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($porCategoria as $item): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= htmlspecialchars($item['nome'] ?: 'Sem Categoria') ?></span>
                        <strong><?= number_format((float)$item['subtotal_total'], 2, ',', '.') ?> Kz</strong>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
</body>
</html>
