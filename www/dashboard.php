<?php
ob_start(); 
session_start();
require_once 'config/db.php';

// var_dump($_SESSION); 
// die();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['role'] !== 'admin') {
    // Se não for admin, manda para o login com mensagem de erro
    header('Location: login.php?erro=restrito');
    exit;
}

$admin_nome = $_SESSION['nome_completo'] ?? 'Administrador';
$mensagem = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Jombaca</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --verde: #198754; }
        body { background: #f4f7f6; }
        .sidebar { width: 260px; height: 100vh; background: var(--verde); color: #fff; position: fixed; }
        .content-area { margin-left: 260px; padding: 30px; }
        .nav-link { color: rgba(255,255,255,0.8); margin: 5px 15px; border-radius: 5px; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: #fff; }
        .card-resumo { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem; }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="p-4 text-center">
        <img src="assets/img/logoJombaca.png" height="60">
        <h6 class="mt-3 text-white"><?= $admin_nome ?></h6>
        <a href="index.php" target="_blank" class="btn btn-sm btn-outline-light w-100 mt-2"><i class="bi bi-globe"></i> Ver Site</a>
    </div>
    <div class="nav flex-column mt-3">
        <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
        
        <a class="nav-link" href="extends/reservas.php"><i class="bi bi-calendar-check me-2"></i> Gestão de Reservas</a>
        
        <a class="nav-link" href="extends/produtos.php"><i class="bi bi-box-seam me-2"></i> Gestão de Produtos</a>
        <a class="nav-link" href="extends/servicos.php"><i class="bi bi-heart-pulse me-2"></i> Gestão de Serviços</a>
        <a class="nav-link" href="extends/filiais.php"><i class="bi bi-geo-alt me-2"></i> Gestão de Filiais</a>
        <a class="nav-link" href="extends/categorias.php"><i class="bi bi-tags me-2"></i> Gestão de Categorias</a>
        <a class="nav-link" href="extends/usuarios.php"><i class="bi bi-person-plus me-2"></i> Gestão de Usuários</a>
        <hr>
        <a class="nav-link text-warning" href="logout.php"><i class="bi bi-door-open me-2"></i> Sair</a>
    </div>
</nav>

<main class="content-area">
    <h2 class="text-success fw-bold mb-4">Painel de Gestão</h2>

    <?php if ($mensagem): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($mensagem) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php
        $cards = [
            ['t' => 'Produtos', 'icon' => 'bi-box', 'link' => 'extends/produtos.php', 'table' => 'produtos'],
            ['t' => 'Serviços', 'icon' => 'bi-activity', 'link' => 'extends/servicos.php', 'table' => 'servicos'],
            ['t' => 'Filiais', 'icon' => 'bi-shop', 'link' => 'extends/filiais.php', 'table' => 'filiais'],
            ['t' => 'Usuários', 'icon' => 'bi-people', 'link' => 'extends/usuarios.php', 'table' => 'usuarios']
        ];
        foreach ($cards as $c): 
            $count = $pdo->query("SELECT COUNT(*) FROM {$c['table']}")->fetchColumn();
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

    <div class="card card-resumo mt-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Últimos Produtos Cadastrados</h6>
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