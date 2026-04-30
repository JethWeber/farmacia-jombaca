<?php
session_start();
require_once 'config/db.php';
require_once 'config/imagem_helper.php';

// Verifica login
$logado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;
$nome_usuario = $logado ? $_SESSION['nome'] : 'Visitante';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços - Farmácia Jombaca</title>

    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* Campo de Busca Dinâmico */
        .search-container { max-width: 500px; margin: 0 auto 40px auto; }
        .search-input { 
            height: 50px; border-radius: 50px; border: 2px solid #198754; 
            padding-left: 20px; transition: all 0.3s;
        }
        .search-input:focus { box-shadow: 0 0 10px rgba(25, 135, 84, 0.2); outline: none; }

        /* Cards de serviço profissionais */
        .service-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            border-radius: 15px;
            overflow: hidden;
            background: white;
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12) !important;
        }

        .img-wrapper {
            height: 220px;
            overflow: hidden;
        }

        .service-card img {
            transition: transform 0.6s ease;
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        .service-card:hover img {
            transform: scale(1.1);
        }

        .service-card .card-body {
            padding: 25px;
            text-align: center;
        }

        .service-card .card-title {
            font-weight: 800;
            color: #198754;
            margin-bottom: 12px;
            font-size: 1.25rem;
        }

        .btn-more {
            background: #198754;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-more:hover {
            background: #146c43;
            color: white;
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

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

    <nav class="navbar navbar-expand-lg navbar-light site-main-navbar bg-white border-bottom border-success border-3 sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand py-1 me-2 flex-shrink-0 d-lg-none" href="index.php" title="Farmácia Jombaca">
                <img src="assets/img/logoJombaca.png" alt="Farmácia Jombaca" height="38" style="max-height:38px;width:auto;">
            </a>
            <button class="navbar-toggler ms-auto ms-lg-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Alternar navegação">
                <i class="bi bi-list fs-3 text-success"></i>
            </button>
            <div class="collapse navbar-collapse flex-grow-1 justify-content-lg-between align-items-lg-center" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 mt-2 mt-lg-0 align-items-lg-center">
                    <li class="nav-item"><a class="nav-link px-3" href="index.php">Início</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="produtos.php">Produtos</a></li>
                    <li class="nav-item"><a class="nav-link px-3 active fw-bold" href="servicos.php">Serviços</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="nossos-enderecos.php">Nossos Endereços</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="contacto.php">Contactos</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="sobrenos.php">Sobre Nós</a></li>
                </ul>
                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 gap-lg-3 mt-2 mt-lg-0 pb-2 pb-lg-0 navbar-site-authbar">
                    <?php if ($logado): ?>
                        <span class="text-success fw-medium small"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nome_usuario) ?></span>
                        <a href="minhas_reservas.php" class="btn btn-sm btn-outline-success">Minhas Reservas</a>
                        <a href="logout.php" class="btn btn-sm btn-outline-danger">Sair</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-success btn-sm">Login</a>
                        <a href="cadastro.php" class="btn btn-success btn-sm">Criar Conta</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 py-5 bg-light">
        <div class="container">
            <h1 class="text-center text-success mb-2 fw-bold">Nossos Serviços</h1>
            <p class="text-center lead mb-5 text-muted small">Cuidamos da sua saúde com excelência e proximidade.</p>

            <div class="search-container text-center">
                <input type="text" id="serviceSearch" class="form-control search-input shadow-sm" placeholder="O que você precisa? Ex: Pressão, Curativo...">
            </div>

            <div class="row g-4 justify-content-center">
                <?php
                $stmt = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
                $servicos = $stmt->fetchAll();

                if (count($servicos) === 0) {
                    echo '<div class="col-12 text-center text-muted py-5 fs-5">Nenhum serviço disponível no momento.</div>';
                }

                foreach ($servicos as $servico) {
                    $nome = htmlspecialchars($servico['nome']);
                    $descricao = nl2br(htmlspecialchars($servico['descricao'] ?? 'Serviço disponível nas nossas lojas.'));
                    $imagem = htmlspecialchars(farmacia_imagem_publica($servico['imagem'] ?? ''));
                    ?>
                    <div class="col-md-6 col-lg-4 service-item" data-name="<?= strtolower($nome) ?>">
                        <div class="service-card shadow-sm">
                            <div class="img-wrapper">
                                <img src="<?= $imagem ?>" alt="<?= $nome ?>">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h4 class="card-title"><?= $nome ?></h4>
                                <p class="card-text text-muted flex-grow-1 small"><?= $descricao ?></p>
                                <div class="mt-3">
                                    <a href="contacto.php" class="btn-more d-inline-block">
                                        <i class="bi bi-chat-dots me-2"></i>Consultar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white py-5 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 text-center text-md-start">
                    <img src="assets/img/logoJombaca.png" alt="Logo" class="footer-logo mb-3" height="100">
                    <p class="small text-white-50">Rede de farmácias que tem como missão prover saúde para todos.</p>
                </div>

                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">MENU</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-white-50 text-decoration-none">Vida Saudável</a></li>
                        <li><a href="nossos-enderecos.php" class="text-white-50 text-decoration-none">Onde Estamos</a></li>
                    </ul>
                </div>

                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">CONTACTOS</h5>
                    <p class="small mb-1"><i class="bi bi-telephone-fill me-2"></i>(+244) 967 984 094</p>
                    <p class="small mb-1"><i class="bi bi-envelope-fill me-2"></i>geral@farmciajombaca.co.ao</p>
                    <p class="small"><i class="bi bi-geo-alt-fill me-2"></i>Luanda, Angola</p>
                </div>
            </div>
            <div class="text-center mt-4 pt-4 border-top border-secondary text-white-50 small">
                2026 © Farmácia Jombaca. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('serviceSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let items = document.querySelectorAll('.service-item');
            
            items.forEach(function(item) {
                let name = item.getAttribute('data-name');
                if (name.includes(filter)) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>