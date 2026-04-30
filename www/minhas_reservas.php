<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login.php?msg=Faça login');
    exit;
}

$nome_usuario = $_SESSION['nome'] ?? '';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$scriptDir = str_replace('\\', '/', $scriptDir);
$basePath = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') ? '' : rtrim($scriptDir, '/');
$siteRoot = $scheme . '://' . $host . $basePath;
$logoAbs = $siteRoot . '/assets/img/logoJombaca.png';

$stmt = $pdo->prepare("
    SELECT r.*,
           p.nome AS produto_nome,
           p.preco AS produto_preco,
           f.nome AS filial_nome,
           f.endereco AS filial_endereco,
           f.bairro AS filial_bairro,
           f.telefone AS filial_telefone
    FROM reservas r
    JOIN produtos p ON p.id = r.produto_id
    LEFT JOIN filiais f ON f.id = r.filial_preferida_id
    WHERE r.usuario_id = ?
    ORDER BY r.id DESC
");
$stmt->execute([$_SESSION['usuario_id']]);
$reservas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas — Farmácia Jombaca</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .receipt-hidden { display: none; }
        .page-header-j { border-left: 4px solid #198754; padding-left: 1rem; }
    </style>
</head>
<body class="bg-light">

    <div class="top-bar bg-white py-3 border-bottom shadow-sm">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="assets/img/logoJombaca.png" alt="Farmácia Jombaca" class="logo-img" height="70">
                <h1 class="fs-4 fw-bold text-success m-0">Farmácia Jombaca</h1>
            </div>
            <div class="status-indicators d-flex gap-2">
                <span class="rounded-circle bg-success" style="width:14px; height:14px;"></span>
                <span class="rounded-circle bg-success" style="width:14px; height:14px;"></span>
                <span class="rounded-circle bg-success" style="width:14px; height:14px;"></span>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light site-main-navbar bg-white border-bottom border-success border-3 shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand py-1 me-2 flex-shrink-0 d-lg-none" href="index.php" title="Farmácia Jombaca">
                <img src="assets/img/logoJombaca.png" alt="Farmácia Jombaca" height="38" style="max-height:38px;width:auto;">
            </a>
            <button class="navbar-toggler ms-auto ms-lg-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Alternar navegação"><i class="bi bi-list fs-3 text-success"></i></button>
            <div class="collapse navbar-collapse flex-grow-1 justify-content-lg-between align-items-lg-center" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 mt-2 mt-lg-0 align-items-lg-center">
                    <li class="nav-item"><a class="nav-link px-3" href="index.php">Início</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="produtos.php">Produtos</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="servicos.php">Serviços</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="nossos-enderecos.php">Nossos Endereços</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="contacto.php">Contactos</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="sobrenos.php">Sobre Nós</a></li>
                </ul>
                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 gap-lg-3 mt-2 mt-lg-0 pb-2 pb-lg-0 navbar-site-authbar">
                    <span class="text-success fw-medium small"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nome_usuario) ?></span>
                    <a href="minhas_reservas.php" class="btn btn-sm btn-success">Minhas Reservas</a>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger">Sair</a>
                </div>
            </div>
        </div>
    </nav>

<div class="container py-4">
    <div class="page-header-j mb-4">
        <h2 class="text-success fw-bold mb-1">Minhas Reservas</h2>
        <p class="text-muted small mb-0">Comprovativos com dados da filial de levantamento.</p>
    </div>

    <?php foreach ($reservas as $r):
        $filialNome = trim((string)($r['filial_nome'] ?? ''));
        $filialEnd = trim((string)($r['filial_endereco'] ?? ''));
        $filialBairro = trim((string)($r['filial_bairro'] ?? ''));
        $filialTel = trim((string)($r['filial_telefone'] ?? ''));
        $temFilial = $filialNome !== '';
    ?>
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-success text-white py-3 d-flex align-items-center gap-3">
                <img src="assets/img/logoJombaca.png" alt="" height="40" class="bg-white rounded p-1">
                <div>
                    <div class="fw-bold">Reserva #<?= (int)$r['id'] ?></div>
                    <small class="opacity-75"><?= htmlspecialchars($r['produto_nome']) ?></small>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Estado:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($r['status'] ?? 'nova') ?></span></p>
                <p class="mb-2 text-muted small">Pedido em <?= date('d/m/Y H:i', strtotime($r['data_solicitacao'])) ?></p>
                <p class="mb-3"><strong>Quantidade:</strong> <?= (int)$r['quantidade_solicitada'] ?> · <strong>Preço unitário:</strong> <?= number_format((float)$r['produto_preco'], 2, ',', '.') ?> Kz</p>
                <div class="border rounded p-3 bg-white mb-3">
                    <h6 class="text-success fw-bold mb-2"><i class="bi bi-shop me-1"></i> Onde levantar</h6>
                    <?php if ($temFilial): ?>
                        <p class="mb-1 fw-semibold"><?= htmlspecialchars($filialNome) ?></p>
                        <?php if ($filialBairro !== ''): ?><p class="mb-1 small"><?= htmlspecialchars($filialBairro) ?></p><?php endif; ?>
                        <?php if ($filialEnd !== ''): ?><p class="mb-1 small text-muted"><?= nl2br(htmlspecialchars($filialEnd)) ?></p><?php endif; ?>
                        <?php if ($filialTel !== ''): ?><p class="mb-0 small"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($filialTel) ?></p><?php endif; ?>
                    <?php else: ?>
                        <p class="mb-0 small text-muted">A filial de levantamento será confirmada pela farmácia quando entrarem em contacto consigo.</p>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-success btn-sm" onclick="imprimirComprovativo(<?= (int)$r['id'] ?>)">
                    <i class="bi bi-printer me-1"></i> Visualizar / imprimir comprovativo
                </button>
            </div>
        </div>

        <div id="comp-<?= (int)$r['id'] ?>" class="receipt-hidden">
            <div class="comprovativo-root" style="font-family: 'Segoe UI', system-ui, sans-serif; max-width: 520px; margin: 0 auto; color: #222;">
                <div style="background: linear-gradient(135deg, #198754 0%, #146c43 100%); color: #fff; padding: 20px 24px; border-radius: 12px 12px 0 0; display: flex; align-items: center; gap: 16px;">
                    <img src="<?= htmlspecialchars($logoAbs) ?>" alt="Farmácia Jombaca" style="height: 52px; background: #fff; border-radius: 8px; padding: 6px;">
                    <div>
                        <div style="font-size: 1.25rem; font-weight: 800; letter-spacing: 0.02em;">Farmácia Jombaca</div>
                        <div style="opacity: 0.9; font-size: 0.85rem;">Comprovativo de reserva</div>
                    </div>
                </div>
                <div style="border: 2px solid #198754; border-top: none; padding: 22px 24px; border-radius: 0 0 12px 12px; background: #fff;">
                    <p style="margin: 0 0 8px; font-size: 0.75rem; text-transform: uppercase; color: #198754; font-weight: 700;">Número da reserva</p>
                    <p style="margin: 0 0 18px; font-size: 1.5rem; font-weight: 800;">#<?= (int)$r['id'] ?></p>
                    <table style="width:100%; font-size: 0.95rem; border-collapse: collapse;">
                        <tr><td style="padding:6px 0; color:#666;">Cliente</td><td style="padding:6px 0; font-weight:600;"><?= htmlspecialchars($r['nome_contato']) ?></td></tr>
                        <tr><td style="padding:6px 0; color:#666;">Telefone</td><td style="padding:6px 0;"><?= htmlspecialchars($r['telefone_contato']) ?></td></tr>
                        <tr><td style="padding:6px 0; color:#666;">Produto</td><td style="padding:6px 0; font-weight:600;"><?= htmlspecialchars($r['produto_nome']) ?></td></tr>
                        <tr><td style="padding:6px 0; color:#666;">Quantidade</td><td style="padding:6px 0;"><?= (int)$r['quantidade_solicitada'] ?></td></tr>
                        <tr><td style="padding:6px 0; color:#666;">Preço unit.</td><td style="padding:6px 0;"><?= number_format((float)$r['produto_preco'], 2, ',', '.') ?> Kz</td></tr>
                        <tr><td style="padding:6px 0; color:#666;">Data do pedido</td><td style="padding:6px 0;"><?= date('d/m/Y H:i', strtotime($r['data_solicitacao'])) ?></td></tr>
                        <tr><td style="padding:6px 0; color:#666;">Estado</td><td style="padding:6px 0; font-weight:600;"><?= htmlspecialchars($r['status'] ?? 'nova') ?></td></tr>
                    </table>
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed #ccc;">
                        <p style="margin:0 0 6px; font-size: 0.75rem; text-transform: uppercase; color: #198754; font-weight: 700;">Filial de levantamento</p>
                        <?php if ($temFilial): ?>
                            <p style="margin:0 0 4px; font-weight:700; font-size:1.05rem;"><?= htmlspecialchars($filialNome) ?></p>
                            <?php if ($filialBairro !== ''): ?><p style="margin:0 0 4px; font-size:0.9rem;"><?= htmlspecialchars($filialBairro) ?></p><?php endif; ?>
                            <?php if ($filialEnd !== ''): ?><p style="margin:0 0 4px; font-size:0.85rem; color:#444;"><?= nl2br(htmlspecialchars($filialEnd)) ?></p><?php endif; ?>
                            <?php if ($filialTel !== ''): ?><p style="margin:0; font-size:0.85rem;"><strong>Tel. filial:</strong> <?= htmlspecialchars($filialTel) ?></p><?php endif; ?>
                        <?php else: ?>
                            <p style="margin:0; font-size:0.9rem; color:#555;">A filial exata será indicada pela equipa da farmácia no contacto de confirmação.</p>
                        <?php endif; ?>
                    </div>
                    <p style="margin: 20px 0 0; font-size: 0.75rem; color: #888; text-align: center;">Documento informativo — Farmácia Jombaca · Angola</p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($reservas)): ?>
        <div class="alert alert-info border-0 shadow-sm">Ainda não tem reservas registadas. <a href="produtos.php">Ver produtos</a></div>
    <?php endif; ?>
</div>
<script>
function imprimirComprovativo(id) {
    var node = document.getElementById('comp-' + id);
    if (!node) return;
    var inner = node.querySelector('.comprovativo-root');
    var html = inner ? inner.outerHTML : node.innerHTML;
    var w = window.open('', '_blank');
    w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Comprovativo — Farmácia Jombaca</title>');
    w.document.write('<style>@media print { body { margin: 0; padding: 12px; } }</style>');
    w.document.write('</head><body style="margin:0;padding:16px;background:#f4f4f4;">');
    w.document.write(html);
    w.document.write('</body></html>');
    w.document.close();
    w.focus();
    w.print();
}
</script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
