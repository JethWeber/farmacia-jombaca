<?php
session_start();
require_once 'config/db.php';

// Verifica login
$logado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;
$nome_usuario = $logado ? $_SESSION['nome'] : 'Visitante';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactos - Farmácia Jombaca</title>

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

        /* Cards de Contacto Profissionais */
        .contact-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            border-radius: 20px;
            overflow: hidden;
            background: white;
            height: 100%;
        }

        .contact-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12) !important;
        }

        .img-wrapper {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .contact-card img {
            transition: transform 0.6s ease;
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        .contact-card:hover img {
            transform: scale(1.1);
        }

        .contact-card .card-body {
            padding: 25px;
        }

        .contact-card .card-title {
            font-weight: 800;
            color: #198754;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: #555;
        }

        .info-item i {
            font-size: 1.2rem;
            margin-right: 12px;
            color: #198754;
        }

        .btn-call {
            background: #198754;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-call:hover {
            background: #146c43;
            color: white;
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }

        .badge-sede {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 2;
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
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

    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom border-success border-3 sticky-top shadow-sm">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <i class="bi bi-list fs-3 text-success"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link px-3" href="index.php">Início</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="produtos.php">Produtos</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="servicos.php">Serviços</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="nossos-enderecos.php">Nossos Endereços</a></li>
                    <li class="nav-item"><a class="nav-link px-3 active fw-bold" href="contacto.php">Contactos</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="sobrenos.php">Sobre Nós</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <?php if ($logado): ?>
                        <span class="text-success fw-medium small"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nome_usuario) ?></span>
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
            <h1 class="text-center text-success mb-2 fw-bold">Nossos Contactos</h1>
            <p class="text-center lead mb-5 text-muted small">Estamos prontos para atender a sua solicitação em qualquer unidade.</p>

            <div class="search-container text-center">
                <input type="text" id="contactSearch" class="form-control search-input shadow-sm" placeholder="Pesquise por nome da farmácia ou bairro...">
            </div>

            <div class="row g-4 justify-content-center">
                <?php
                $stmt = $pdo->query("SELECT * FROM filiais ORDER BY principal DESC, nome ASC");
                $filiais = $stmt->fetchAll();

                if (count($filiais) === 0) {
                    echo '<div class="col-12 text-center text-muted py-5">Nenhuma unidade encontrada.</div>';
                }

                foreach ($filiais as $filial):
                    $nome = htmlspecialchars($filial['nome']);
                    $telefone = $filial['telefone'] ?: '(+244) 967 984 094';
                    $imagem = $filial['imagem'] ?: 'assets/img/placeholder-loja.jpg';
                    $isPrincipal = $filial['principal'];
                    $searchTerms = strtolower($nome . ' ' . $filial['bairro'] . ' ' . $filial['endereco']);
                ?>
                    <div class="col-md-6 col-lg-4 contact-item" data-search="<?= $searchTerms ?>">
                        <div class="contact-card shadow-sm">
                            <div class="img-wrapper">
                                <?php if ($isPrincipal): ?>
                                    <span class="badge-sede bg-success shadow">Sede</span>
                                <?php endif; ?>
                                <img src="<?= $imagem ?>" alt="<?= $nome ?>">
                            </div>

                            <div class="card-body">
                                <h4 class="card-title"><?= $nome ?></h4>
                                
                                <div class="info-item">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span><?= htmlspecialchars($filial['endereco']) ?></span>
                                </div>

                                <?php if ($filial['bairro']): ?>
                                    <div class="info-item">
                                        <i class="bi bi-geo-fill"></i>
                                        <span>Bairro: <?= htmlspecialchars($filial['bairro']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="info-item">
                                    <i class="bi bi-telephone-fill"></i>
                                    <span><?= $telefone ?></span>
                                </div>

                                <div class="mt-4">
                                    <a href="tel:<?= str_replace(['(', ')', ' ', '+'], '', $telefone) ?>" class="btn-call w-100">
                                        <i class="bi bi-telephone-outbound"></i> LIGAR AGORA
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white py-5 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 text-center text-md-start">
                    <img src="assets/img/logoJombaca.png" alt="Logo" class="footer-logo mb-3" height="100">
                    <p class="small text-white-50">Rede de farmácias que tem como missão prover saúde e bem-estar para todos os angolanos.</p>
                </div>

                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">MENU RÁPIDO</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none"><i class="bi bi-house-door me-2"></i>Início</a></li>
                        <li class="mb-2"><a href="produtos.php" class="text-white-50 text-decoration-none"><i class="bi bi-capsule me-2"></i>Produtos</a></li>
                        <li class="mb-2"><a href="servicos.php" class="text-white-50 text-decoration-none"><i class="bi bi-heart-pulse me-2"></i>Serviços</a></li>
                        <li class="mb-2"><a href="nossos-enderecos.php" class="text-white-50 text-decoration-none"><i class="bi bi-geo-alt me-2"></i>Onde Estamos</a></li>
                    </ul>
                </div>

                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">CONTACTO GERAL</h5>
                    <p class="small mb-1"><i class="bi bi-telephone-fill me-2 text-success"></i>(+244) 967 984 094</p>
                    <p class="small mb-1"><i class="bi bi-envelope-fill me-2 text-success"></i>geral@farmciajombaca.co.ao</p>
                    <p class="small"><i class="bi bi-geo-alt-fill me-2 text-success"></i>Av. 93 de Ferreira, Luanda</p>
                </div>
            </div>

            <div class="text-center mt-4 pt-4 border-top border-secondary text-white-50 small">
                2026 © Farmácia Jombaca. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('contactSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let items = document.querySelectorAll('.contact-item');
            
            items.forEach(function(item) {
                let searchData = item.getAttribute('data-search');
                if (searchData.includes(filter)) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>