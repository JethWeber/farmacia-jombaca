<?php
ob_start();
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

require_admin_any();
if (!is_funcionario()) {
    header('Location: dashboard.php');
    exit;
}

$nome = $_SESSION['nome_completo'] ?? $_SESSION['nome'] ?? 'Funcionário';
$mensagem = $_GET['msg'] ?? '';

$alertasStmt = $pdo->query("
    SELECT id, nome, data_validade, estoque_atual,
           CASE
             WHEN data_validade < CURDATE() THEN 'vencido'
             WHEN data_validade <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) THEN 'proximo'
             ELSE 'ok'
           END AS situacao
    FROM produtos
    WHERE data_validade IS NOT NULL
      AND (data_validade < CURDATE() OR data_validade <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH))
    ORDER BY data_validade ASC
    LIMIT 12
");
$alertasValidade = $alertasStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel Funcionário — Jombaca</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --verde: #198754; }
        body { background: #f4f7f6; }
        .sidebar {
            width: 260px; height: 100vh; max-height: 100vh; background: var(--verde); color: #fff;
            position: fixed; left: 0; top: 0; z-index: 1030; display: flex; flex-direction: column; overflow: hidden;
        }
        .sidebar-top { flex-shrink: 0; }
        .sidebar-scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        .content-area { margin-left: 260px; padding: 30px; min-height: 100vh; }
        .sidebar .nav-link { color: rgba(255,255,255,0.88); margin: 4px 12px; border-radius: 6px; line-height: 1.35; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.14); color: #fff; }
        .card-resumo { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); margin-bottom: 1.25rem; }
        @media (max-width: 991.98px) {
            .sidebar { width: 100%; height: auto; max-height: none; position: relative; }
            .sidebar-scroll { max-height: min(50vh, 20rem); overflow-y: auto; }
            .content-area { margin-left: 0; padding: 20px 15px; }
        }
    </style>
</head>
<body>

<nav class="sidebar" aria-label="Menu funcionário">
    <div class="sidebar-top p-4 text-center border-bottom border-white border-opacity-25">
        <img src="assets/img/logoJombaca.png" height="56" alt="">
        <h6 class="mt-2 text-white mb-0 small text-uppercase opacity-75">Área operacional</h6>
        <p class="mb-0 mt-2 text-truncate px-1 small" title="<?= htmlspecialchars($nome) ?>"><?= htmlspecialchars($nome) ?></p>
        <a href="index.php" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light w-100 mt-2"><i class="bi bi-globe"></i> Ver site</a>
    </div>
    <div class="sidebar-scroll py-2">
        <div class="nav flex-column">
            <a class="nav-link active" href="dashboard_funcionario.php"><i class="bi bi-speedometer2 me-2"></i> Início</a>
            <a class="nav-link" href="extends/relatorio_validade.php"><i class="bi bi-exclamation-triangle me-2"></i> Validade de produtos</a>
            <a class="nav-link" href="extends/produtos.php"><i class="bi bi-box-seam me-2"></i> Estoque e produtos</a>
            <a class="nav-link" href="extends/vendas.php"><i class="bi bi-cart-check me-2"></i> Realizar venda</a>
            <a class="nav-link" href="extends/relatorio_vendas.php"><i class="bi bi-graph-up-arrow me-2"></i> Relatório vendas (dia / mês)</a>
            <a class="nav-link" href="extends/reservas.php"><i class="bi bi-calendar-check me-2"></i> Estado das reservas</a>
            <hr class="border-white border-opacity-25 my-2 mx-3">
            <a class="nav-link text-warning" href="logout.php"><i class="bi bi-door-open me-2"></i> Sair</a>
        </div>
    </div>
</nav>

<main class="content-area">
    <h2 class="text-success fw-bold mb-1">Painel do funcionário</h2>
    <p class="text-muted mb-4">Consulta de stock, vendas, reservas e alertas de validade (inclui aviso até 3 meses antes do vencimento).</p>

    <?php if ($mensagem !== ''): ?>
        <div class="alert alert-info alert-dismissible fade show"><?= htmlspecialchars($mensagem) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-resumo p-3 h-100">
                <h6 class="text-muted mb-2">Alertas de validade</h6>
                <h3 class="fw-bold mb-2"><?= count($alertasValidade) ?></h3>
                <a href="extends/relatorio_validade.php" class="btn btn-sm btn-outline-danger">Ver relatório</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-resumo p-3 h-100">
                <h6 class="text-muted mb-2">Vendas hoje</h6>
                <h3 class="fw-bold mb-2"><?= number_format((float) $pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM vendas WHERE DATE(data_venda)=CURDATE()")->fetchColumn(), 2, ',', '.') ?> Kz</h3>
                <a href="extends/relatorio_vendas.php" class="btn btn-sm btn-outline-success">Relatórios</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-resumo p-3 h-100">
                <h6 class="text-muted mb-2">Reservas pendentes</h6>
                <h3 class="fw-bold mb-2"><?= (int) $pdo->query("SELECT COUNT(*) FROM reservas WHERE status IN ('nova','contactado','reservado')")->fetchColumn() ?></h3>
                <a href="extends/reservas.php" class="btn btn-sm btn-outline-primary">Atualizar estado</a>
            </div>
        </div>
    </div>

    <div class="card card-resumo">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-danger">Resumo — validade &amp; vencidos</h6>
            <a href="extends/relatorio_validade.php" class="btn btn-sm btn-success">Abrir relatório completo</a>
        </div>
        <div class="card-body">
            <?php if (empty($alertasValidade)): ?>
                <p class="mb-0 text-muted">Sem produtos vencidos nem com validade nos próximos 3 meses.</p>
            <?php else: ?>
                <ul class="mb-0 small">
                    <?php foreach ($alertasValidade as $a): ?>
                        <li class="mb-1">
                            <strong><?= htmlspecialchars($a['nome']) ?></strong>
                            — validade <?= date('d/m/Y', strtotime($a['data_validade'])) ?>
                            <?php if ($a['situacao'] === 'vencido'): ?>
                                <span class="badge bg-danger">Vencido</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">≤ 3 meses</span>
                            <?php endif; ?>
                            <span class="text-muted">(stock <?= (int) $a['estoque_atual'] ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
