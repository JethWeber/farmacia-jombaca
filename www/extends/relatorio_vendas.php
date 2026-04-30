<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';

require_admin_any();

$mesRef = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mesRef)) {
    $mesRef = date('Y-m');
}

$diaRef = $_GET['dia'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $diaRef)) {
    $diaRef = date('Y-m-d');
}

$stDia = $pdo->prepare('SELECT COALESCE(SUM(subtotal), 0) FROM vendas WHERE DATE(data_venda) = ?');
$stDia->execute([$diaRef]);
$totalDia = (float) $stDia->fetchColumn();

$stmtMensal = $pdo->prepare("SELECT COALESCE(SUM(subtotal), 0) FROM vendas WHERE DATE_FORMAT(data_venda, '%Y-%m') = ?");
$stmtMensal->execute([$mesRef]);
$totalMes = (float) $stmtMensal->fetchColumn();

$stmtLinhasDia = $pdo->prepare("
    SELECT v.id, v.data_venda, p.nome AS produto_nome, v.quantidade, v.preco_unitario, v.subtotal
    FROM vendas v
    JOIN produtos p ON p.id = v.produto_id
    WHERE DATE(v.data_venda) = ?
    ORDER BY v.data_venda DESC, v.id DESC
");
$stmtLinhasDia->execute([$diaRef]);
$linhasDia = $stmtLinhasDia->fetchAll();

$stmtLinhasMes = $pdo->prepare("
    SELECT v.id, v.data_venda, p.nome AS produto_nome, v.quantidade, v.preco_unitario, v.subtotal
    FROM vendas v
    JOIN produtos p ON p.id = v.produto_id
    WHERE DATE_FORMAT(v.data_venda, '%Y-%m') = ?
    ORDER BY v.data_venda DESC, v.id DESC
    LIMIT 500
");
$stmtLinhasMes->execute([$mesRef]);
$linhasMes = $stmtLinhasMes->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas — Jombaca</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="<?= htmlspecialchars(painel_voltar_desde_extends()) ?>" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Voltar ao painel</a>
    <h2 class="text-success fw-bold mb-4">Relatório de vendas</h2>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total do dia selecionado</h6>
                    <h3 class="fw-bold mb-0"><?= number_format($totalDia, 2, ',', '.') ?> Kz</h3>
                    <small class="text-muted"><?= htmlspecialchars(date('d/m/Y', strtotime($diaRef))) ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total mensal</h6>
                    <h3 class="fw-bold mb-0"><?= number_format($totalMes, 2, ',', '.') ?> Kz</h3>
                    <small class="text-muted">Mês <?= htmlspecialchars($mesRef) ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-3">
            <div class="col-md-6">
                <form method="get" class="border rounded p-3 bg-white">
                    <label class="form-label fw-bold small">Detalhe por dia</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="date" name="dia" class="form-control" value="<?= htmlspecialchars($diaRef) ?>">
                        <input type="hidden" name="mes" value="<?= htmlspecialchars($mesRef) ?>">
                        <button class="btn btn-success">Aplicar</button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <form method="get" class="border rounded p-3 bg-white">
                    <label class="form-label fw-bold small">Total por mês</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="month" name="mes" class="form-control" value="<?= htmlspecialchars($mesRef) ?>">
                        <input type="hidden" name="dia" value="<?= htmlspecialchars($diaRef) ?>">
                        <button class="btn btn-outline-success">Aplicar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">Vendas do dia (linhas)</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Data/Hora</th><th>Produto</th><th>Qtd</th><th>P. unit.</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                <?php foreach ($linhasDia as $v): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
                        <td><?= htmlspecialchars($v['produto_nome']) ?></td>
                        <td><?= (int) $v['quantidade'] ?></td>
                        <td><?= number_format((float) $v['preco_unitario'], 2, ',', '.') ?></td>
                        <td><?= number_format((float) $v['subtotal'], 2, ',', '.') ?> Kz</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($linhasDia)): ?>
                    <tr><td colspan="5" class="text-muted p-3">Sem vendas neste dia.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Vendas do mês (até 500 linhas)</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Data/Hora</th><th>Produto</th><th>Qtd</th><th>P. unit.</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                <?php foreach ($linhasMes as $v): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
                        <td><?= htmlspecialchars($v['produto_nome']) ?></td>
                        <td><?= (int) $v['quantidade'] ?></td>
                        <td><?= number_format((float) $v['preco_unitario'], 2, ',', '.') ?></td>
                        <td><?= number_format((float) $v['subtotal'], 2, ',', '.') ?> Kz</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($linhasMes)): ?>
                    <tr><td colspan="5" class="text-muted p-3">Sem vendas neste mês.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
