<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';

require_admin_any();

$vencidos = $pdo->query("
    SELECT id, nome, estoque_atual, data_validade, DATEDIFF(data_validade, CURDATE()) AS dias
    FROM produtos
    WHERE data_validade IS NOT NULL AND data_validade < CURDATE()
    ORDER BY data_validade ASC
")->fetchAll();

$proximos = $pdo->query("
    SELECT id, nome, estoque_atual, data_validade, DATEDIFF(data_validade, CURDATE()) AS dias
    FROM produtos
    WHERE data_validade IS NOT NULL
      AND data_validade >= CURDATE()
      AND data_validade <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
    ORDER BY data_validade ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Validade de produtos — Jombaca</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="<?= htmlspecialchars(painel_voltar_desde_extends()) ?>" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Voltar ao painel</a>
    <h2 class="text-success fw-bold mb-2">Validação de produtos — validade</h2>
    <p class="text-muted">Lista de <strong>vencidos</strong> e de produtos com validade nos <strong>próximos 3 meses</strong> (alerta antecipado).</p>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-danger text-white fw-bold">Vencidos (ação necessária)</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Produto</th><th>Validade</th><th>Stock</th><th>Dias</th></tr></thead>
                <tbody>
                <?php if (empty($vencidos)): ?>
                    <tr><td colspan="4" class="text-muted p-4">Nenhum produto vencido registado.</td></tr>
                <?php else: ?>
                    <?php foreach ($vencidos as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nome']) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['data_validade'])) ?></td>
                        <td><?= (int) $r['estoque_atual'] ?></td>
                        <td><span class="badge bg-danger"><?= (int) $r['dias'] ?> d</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-warning fw-bold text-dark">Alerta: validade nos próximos 3 meses</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Produto</th><th>Validade</th><th>Stock</th><th>Dias restantes</th></tr></thead>
                <tbody>
                <?php if (empty($proximos)): ?>
                    <tr><td colspan="4" class="text-muted p-4">Nenhum produto nesta janela de 3 meses.</td></tr>
                <?php else: ?>
                    <?php foreach ($proximos as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nome']) ?></td>
                        <td><?= date('d/m/Y', strtotime($r['data_validade'])) ?></td>
                        <td><?= (int) $r['estoque_atual'] ?></td>
                        <td><span class="badge bg-warning text-dark"><?= (int) $r['dias'] ?> dias</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
