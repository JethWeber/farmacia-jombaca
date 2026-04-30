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
    <title>Nossos Endereços - Farmácia Jombaca</title>

    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* Busca Dinâmica */
        .search-container { max-width: 500px; margin: 0 auto 40px auto; }
        .search-input { 
            height: 50px; border-radius: 50px; border: 2px solid #198754; 
            padding-left: 20px; transition: 0.3s;
        }

        /* Estilo dos Cards de Filiais */
        .filial-card {
            transition: all 0.4s ease;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            background: white;
            height: 100%;
        }

        .filial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
        }

        .img-wrapper {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .filial-card img {
            transition: transform 0.6s ease;
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        .filial-card:hover img {
            transform: scale(1.1);
        }

        .badge-principal {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 2;
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .info-item i {
            font-size: 1.2rem;
            margin-right: 12px;
            color: #198754;
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
                    <li class="nav-item"><a class="nav-link px-3" href="servicos.php">Serviços</a></li>
                    <li class="nav-item"><a class="nav-link px-3 active fw-bold" href="nossos-enderecos.php">Nossos Endereços</a></li>
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
            <h1 class="text-center text-success mb-2 fw-bold">Onde Estamos</h1>
            <p class="text-center lead mb-5 text-muted small">Encontre a Farmácia Jombaca mais próxima de si.</p>

            <div class="search-container text-center">
                <input type="text" id="addressSearch" class="form-control search-input shadow-sm" placeholder="Pesquise por nome da loja ou bairro...">
            </div>

            <div class="row g-4 justify-content-center">
                <?php
                $stmt = $pdo->query("SELECT * FROM filiais ORDER BY principal DESC, nome ASC");
                $filiais = $stmt->fetchAll();

                if (count($filiais) === 0) {
                    echo '<div class="col-12 text-center text-muted py-5">Nenhuma filial encontrada.</div>';
                }

                foreach ($filiais as $filial):
                    $isPrincipal = $filial['principal'];
                    $searchTerms = strtolower($filial['nome'] . ' ' . $filial['bairro'] . ' ' . $filial['endereco']);
                ?>
                    <div class="col-md-6 col-lg-4 filial-item" data-search="<?= $searchTerms ?>">
                        <div class="filial-card shadow-sm d-flex flex-column">
                            <div class="img-wrapper">
                                <?php if ($isPrincipal): ?>
                                    <span class="badge-principal bg-success shadow">Sede</span>
                                <?php endif; ?>
                                
                                <?php if ($filial['imagem']): ?>
                                    <img src="<?= htmlspecialchars($filial['imagem']) ?>" alt="<?= htmlspecialchars($filial['nome']) ?>">
                                <?php else: ?>
                                    <div class="bg-success text-white d-flex align-items-center justify-content-center h-100">
                                        <i class="bi bi-building fs-1"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body p-4">
                                <h4 class="fw-bold text-success mb-3"><?= htmlspecialchars($filial['nome']) ?></h4>
                                
                                <div class="info-item">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span><?= htmlspecialchars($filial['endereco']) ?><?php if($filial['bairro']) echo ", " . htmlspecialchars($filial['bairro']); ?></span>
                                </div>

                                <?php if ($filial['telefone']): ?>
                                    <div class="info-item">
                                        <i class="bi bi-telephone-fill"></i>
                                        <span><?= htmlspecialchars($filial['telefone']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($filial['coordenadas']): ?>
                                    <div class="mt-3 pt-3 border-top">
                                        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($filial['endereco']) ?>" target="_blank" class="btn btn-outline-success btn-sm w-100 rounded-pill fw-bold">
                                            <i class="bi bi-map me-2"></i>Ver no Mapa
                                        </a>
                                    </div>
                                <?php endif; ?>
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
                    <p class="small text-white-50">Rede de farmácias que tem como missão prover saúde para todos os angolanos.</p>
                </div>

                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">MENU</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-white-50 text-decoration-none"><i class="bi bi-heart-pulse me-2"></i>Vida Saudável</a></li>
                        <li><a href="contacto.php" class="text-white-50 text-decoration-none"><i class="bi bi-telephone me-2"></i>Contactos</a></li>
                    </ul>
                </div>

                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">CONTACTOS</h5>
                    <p class="small mb-1"><i class="bi bi-telephone-fill me-2"></i>(+244) 967 984 094</p>
                    <p class="small mb-1"><i class="bi bi-envelope-fill me-2"></i>geral@farmciajombaca.co.ao</p>
                    <p class="small"><i class="bi bi-geo-alt-fill me-2"></i>AV. 93 DE FERREIRA/LUANDA</p>
                </div>
            </div>
            <div class="text-center mt-4 pt-4 border-top border-secondary text-white-50 small">
                2026 © Farmácia Jombaca. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('addressSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let items = document.querySelectorAll('.filial-item');
            
            items.forEach(function(item) {
                let text = item.getAttribute('data-search');
                if (text.includes(filter)) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>