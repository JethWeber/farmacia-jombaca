<?php
session_start();
require_once 'config/db.php';

// Verifica se usuário está logado
$logado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;
$nome_usuario = $logado ? $_SESSION['nome'] : 'Visitante';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmácia Jombaca - Saúde para Todos</title>

    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .card-img-fix { height: 200px; object-fit: cover; transition: transform 0.5s ease; }
        .text-shadow { text-shadow: 2px 2px 4px rgba(0,0,0,0.6); }
        
        /* HOVER ENDEREÇOS (Overlay Verde) */
        .loja-container { position: relative; overflow: hidden; border-radius: 10px; height: 250px; }
        .loja-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .loja-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(25, 135, 84, 0.85); display: flex; flex-direction: column;
            justify-content: center; align-items: center; color: white;
            opacity: 0; transition: opacity 0.4s ease; padding: 15px; text-align: center;
        }
        .loja-container:hover .loja-overlay { opacity: 1; }
        .loja-container:hover .loja-img { transform: scale(1.1); }

        /* HOVER PRODUTOS (Elevação e Sombra) */
        .card-produto { transition: all 0.3s ease; border: 1px solid transparent !important; }
        .card-produto:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
            border-color: #198754 !important;
        }
        .card-produto .btn { transition: all 0.3s ease; }
        .card-produto:hover .btn { background-color: #198754; color: white; }

        /* HOVER SERVIÇOS (Zoom e Brilho) */
        .card-servico { overflow: hidden; transition: all 0.3s ease; }
        .card-servico:hover .card-img-fix { transform: scale(1.08); }
        .card-servico:hover { border: 1px solid #ffffff; }
    </style>
</head>
<body>

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

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom border-success border-3 shadow-sm sticky-top">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <i class="bi bi-list fs-3 text-success"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link px-3 active fw-bold" href="index.php">Início</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="produtos.php">Produtos</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="servicos.php">Serviços</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="nossos-enderecos.php">Nossos Endereços</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="contacto.php">Contactos</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="sobrenos.php">Sobre Nós</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <?php if ($logado): ?>
                        <span class="text-success fw-medium"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nome_usuario) ?></span>
                        <a href="logout.php" class="btn btn-sm btn-outline-danger">Sair</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-success btn-sm">Login</a>
                        <a href="cadastro.php" class="btn btn-success btn-sm">Criar Conta</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-banner position-relative">
        <img src="assets/img/bannerMain.jpeg" alt="Farmácia Jombaca" class="w-100 object-fit-cover" style="max-height: 550px;">
        <div class="position-absolute top-50 start-50 translate-middle text-center text-white text-shadow">
            <h1 class="display-4 fw-bold">PROVERMOS A SAÚDE</h1>
            <p class="lead fs-3">PARA TODOS</p>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center text-success mb-4 fw-bold">FARMÁCIA JOMBACA</h2>
            <div class="row justify-content-center mb-5">
                <div class="col-lg-10 text-center">
                    <p>Disponibilizamos aos nossos clientes produtos e serviços farmacêuticos de qualidade a preços competitivos em Angola.</p>
                </div>
            </div>
            
            <div class="row g-3">
                <?php
                $stmtFiliais = $pdo->query("SELECT nome, endereco, imagem FROM filiais ORDER BY id ASC LIMIT 4");
                while ($f = $stmtFiliais->fetch()):
                    $img = !empty($f['imagem']) ? $f['imagem'] : 'assets/img/faxada-default.jpg';
                ?>
                <div class="col-6 col-md-3">
                    <div class="loja-container shadow">
                        <img src="<?= htmlspecialchars($img) ?>" class="loja-img" alt="<?= htmlspecialchars($f['nome']) ?>">
                        <div class="loja-overlay">
                            <h6 class="fw-bold mb-1 text-uppercase"><?= htmlspecialchars($f['nome']) ?></h6>
                            <p class="small mb-2"><?= htmlspecialchars($f['endereco'] ?? 'Luanda, Angola') ?></p>
                            <a href="nossos-enderecos.php" class="btn btn-xs btn-light text-success fw-bold rounded-pill" style="font-size: 0.75rem;">Ver mais</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <h2 class="text-center text-success mb-5 fw-bold">PRODUTOS EM DESTAQUE</h2>
            <div class="row g-4">
                <?php
                $stmtProd = $pdo->query("SELECT * FROM produtos ORDER BY id DESC LIMIT 4");
                while ($p = $stmtProd->fetch()):
                ?>
                <div class="col-6 col-md-3">
                    <div class="card card-produto shadow-sm h-100 border-0">
                        <div class="overflow-hidden">
                            <img src="<?= htmlspecialchars($p['imagem'] ?: 'assets/img/nophoto.png') ?>" class="card-img-top card-img-fix" alt="<?= htmlspecialchars($p['nome']) ?>">
                        </div>
                        <div class="card-body text-center p-3">
                            <h6 class="card-title fw-bold text-dark mb-3 small"><?= htmlspecialchars($p['nome']) ?></h6>
                            <a href="produtos.php" class="btn btn-sm btn-outline-success w-100 rounded-pill">Reservar</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div class="text-center mt-5">
                <a href="produtos.php" class="btn btn-success px-5 fw-bold rounded-pill shadow">VER TODOS OS PRODUTOS</a>
            </div>
        </div>
    </section>

    <section class="py-5 bg-success text-white">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold">NOSSOS SERVIÇOS</h2>
            <div class="row g-4 text-dark">
                <?php
                $stmtServ = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY id LIMIT 4");
                while ($s = $stmtServ->fetch()):
                ?>
                <div class="col-6 col-md-3">
                    <div class="card card-servico h-100 border-0 shadow-sm">
                        <div class="overflow-hidden">
                            <img src="<?= htmlspecialchars($s['imagem']) ?>" class="card-img-top card-img-fix" alt="<?= htmlspecialchars($s['nome']) ?>">
                        </div>
                        <div class="card-body text-center p-2">
                            <h6 class="fw-bold text-success mb-0 small"><?= htmlspecialchars($s['nome']) ?></h6>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div class="text-center mt-5">
                <a href="servicos.php" class="btn btn-light text-success px-5 fw-bold rounded-pill shadow">VER TODOS OS SERVIÇOS</a>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container text-center">
            <h2 class="text-success mb-4 fw-bold">VIDA SAUDÁVEL</h2>
            <p class="lead small">Promovemos o seu bem-estar diário.</p>
            <a href="#" class="btn btn-success px-4 rounded-pill">SAIBA MAIS</a>
        </div>
    </section>

    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 text-center text-md-start">
                    <img src="assets/img/logoJombaca.png" alt="Logo" class="footer-logo mb-3" height="100">
                    <p class="small text-white-50">Qualidade e saúde para todos os angolanos.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">MENU</h5>
                    <ul class="list-unstyled small">
                        <li><a href="produtos.php" class="text-white-50 text-decoration-none">Produtos</a></li>
                        <li><a href="nossos-enderecos.php" class="text-white-50 text-decoration-none">Onde Estamos</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">CONTACTOS</h5>
                    <p class="small mb-1"><i class="bi bi-telephone-fill me-2"></i>(+244) 967 984 094</p>
                    <p class="small"><i class="bi bi-envelope-fill me-2"></i>geral@farmciajombaca.co.ao</p>
                </div>
            </div>
            <div class="text-center mt-4 pt-4 border-top border-secondary small text-white-50">
                2026 © Farmácia Jombaca. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>