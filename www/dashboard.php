<?php
ob_start(); 
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

require_admin_any();

if (is_funcionario()) {
    header('Location: dashboard_funcionario.php');
    exit;
}

$admin_nome = $_SESSION['nome_completo'] ?? $_SESSION['nome'] ?? 'Administrador';
$perfil = $_SESSION['perfil_interno'] ?? 'admin_principal';
$isPrincipal = is_admin_principal();
$mensagem = $_GET['msg'] ?? '';

$alertasValidadeStmt = $pdo->query("
    SELECT nome, data_validade
    FROM produtos
    WHERE data_validade IS NOT NULL
      AND data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
    ORDER BY data_validade ASC
    LIMIT 8
");
$alertasValidade = $alertasValidadeStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel - Jombaca</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --verde: #198754; }
        body { background: #f4f7f6; }
        /* Barra lateral: cabeçalho fixo no topo, lista de módulos com rolagem vertical */
        .sidebar {
            width: 260px;
            height: 100vh;
            max-height: 100vh;
            background: var(--verde);
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1030;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .sidebar-top { flex-shrink: 0; }
        .sidebar-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.4) transparent;
        }
        .sidebar-scroll::-webkit-scrollbar { width: 8px; }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.35);
            border-radius: 4px;
        }
        .content-area { margin-left: 260px; padding: 30px; min-height: 100vh; }
        .sidebar .nav-link { color: rgba(255,255,255,0.85); margin: 4px 12px; border-radius: 5px; line-height: 1.35; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.12); color: #fff; }
        .card-resumo { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        @media (max-width: 991.98px) {
            .sidebar { width: 100%; height: auto; max-height: none; position: relative; flex-direction: column; }
            .sidebar-scroll { max-height: min(55vh, 22rem); overflow-y: auto; }
            .content-area { margin-left: 0; padding: 20px 15px; }
        }
    </style>
</head>
<body>

<nav class="sidebar" aria-label="Menu do painel">
    <div class="sidebar-top p-4 text-center border-bottom border-white border-opacity-25">
        <img src="assets/img/logoJombaca.png" height="60" alt="">
        <h6 class="mt-3 text-white mb-0 text-truncate px-1" title="<?= htmlspecialchars($admin_nome) ?>"><?= htmlspecialchars($admin_nome) ?></h6>
        <a href="index.php" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light w-100 mt-2"><i class="bi bi-globe"></i> Ver Site</a>
    </div>
    <div class="sidebar-scroll">
        <div class="nav flex-column py-2 pb-3">
            <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a class="nav-link" href="extends/vendas.php"><i class="bi bi-cart-check me-2"></i> Realizar Venda</a>
            <a class="nav-link" href="extends/reservas.php"><i class="bi bi-calendar-check me-2"></i> Atualizar Status de Reserva</a>
            <a class="nav-link" href="extends/relatorio_vendas.php"><i class="bi bi-graph-up-arrow me-2"></i> Relatório de Vendas</a>
            <a class="nav-link" href="extends/relatorio_validade.php"><i class="bi bi-exclamation-triangle me-2"></i> Relatório de Validade</a>
            <?php if ($isPrincipal): ?>
                <small class="text-white-50 px-4 text-uppercase small fw-bold mt-3 mb-1 d-block">Gestão integral</small>
                <a class="nav-link" href="extends/categorias.php"><i class="bi bi-tags me-2"></i> Gestão de Categorias</a>
                <a class="nav-link" href="extends/filiais.php"><i class="bi bi-geo-alt me-2"></i> Gestão de Filiais</a>
                <a class="nav-link" href="extends/servicos.php"><i class="bi bi-heart-pulse me-2"></i> Gestão de Serviços</a>
            <?php endif; ?>
            <a class="nav-link" href="extends/produtos.php"><i class="bi bi-box-seam me-2"></i> <?= $isPrincipal ? 'Gestão de Produtos' : 'Estoque e Produtos' ?></a>
            <?php if ($isPrincipal): ?>
                <a class="nav-link" href="extends/financeiro.php"><i class="bi bi-cash-coin me-2"></i> Gestão Financeira</a>
                <a class="nav-link" href="extends/fornecedores.php"><i class="bi bi-truck me-2"></i> Fornecedores</a>
                <a class="nav-link" href="extends/usuarios.php"><i class="bi bi-person-plus me-2"></i> Gestão de Utilizadores</a>
                <a class="nav-link" href="extends/pedidos_recuperacao.php"><i class="bi bi-key me-2"></i> Pedidos de Recuperação</a>
            <?php endif; ?>
            <hr class="border-white border-opacity-25 my-2 mx-3">
            <a class="nav-link text-warning" href="logout.php"><i class="bi bi-door-open me-2"></i> Sair</a>
        </div>
    </div>
</nav>

<main class="content-area">
    <h2 class="text-success fw-bold mb-1">Painel de Gestão</h2>
    <p class="text-muted mb-4">Perfil atual: <strong><?= htmlspecialchars(str_replace('_', ' ', $perfil)) ?></strong></p>

    <?php if ($mensagem): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($mensagem) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php
        $cards = [
            ['t' => 'Produtos em Estoque', 'icon' => 'bi-box', 'link' => 'extends/produtos.php', 'table' => 'produtos'],
            ['t' => 'Reservas', 'icon' => 'bi-calendar-check', 'link' => 'extends/reservas.php', 'table' => 'reservas'],
            ['t' => 'Vendas', 'icon' => 'bi-cart-check', 'link' => 'extends/vendas.php', 'table' => 'vendas'],
            ['t' => 'Alertas de Validade', 'icon' => 'bi-exclamation-triangle', 'link' => '#alertas-validade', 'count' => count($alertasValidade)]
        ];
        foreach ($cards as $c): 
            $count = isset($c['count']) ? $c['count'] : $pdo->query("SELECT COUNT(*) FROM {$c['table']}")->fetchColumn();
        ?>
        <div class="col-md-6 col-xl-3">
            <div class="card card-resumo p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1"><?= $c['t'] ?></h6>
                        <h3 class="fw-bold mb-0"><?= $count ?></h3>
                    </div>
                    <div class="icon-shape bg-success bg-opacity-10 text-success p-3 rounded">
                        <i class="bi <?= $c['icon'] ?> fs-3"></i>
                    </div>
                </div>
                <a href="<?= $c['link'] ?>" class="stretched-link"></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card card-resumo mt-4" id="alertas-validade">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-danger">Alertas de Validade (próximos 3 meses)</h6>
        </div>
        <div class="card-body">
            <?php if (empty($alertasValidade)): ?>
                <p class="mb-0 text-muted">Sem produtos com validade próxima nos próximos 3 meses.</p>
            <?php else: ?>
                <ul class="mb-0">
                    <?php foreach ($alertasValidade as $alerta): ?>
                        <li><strong><?= htmlspecialchars($alerta['nome']) ?></strong> - validade em <?= date('d/m/Y', strtotime($alerta['data_validade'])) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-resumo mt-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Últimos Produtos em Estoque</h6>
            <a href="extends/produtos.php" class="btn btn-sm btn-outline-success">Ver Tudo / Gerir</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Produto</th><th>Preço</th><th>Estoque</th></tr></thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM produtos ORDER BY id DESC LIMIT 5");
                    while($row = $stmt->fetch()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['nome']) ?></strong></td>
                        <td><?= number_format($row['preco'], 2) ?> Kz</td>
                        <td><span class="badge bg-success"><?= $row['estoque_atual'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>