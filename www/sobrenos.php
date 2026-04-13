<?php
session_start();
require_once 'config/db.php';

// Conta o total de filiais ativas na base de dados
$stmt_count = $pdo->query("SELECT COUNT(*) FROM filiais");
$total_filiais = $stmt_count->fetchColumn();

// Se quiseres que os anos de experiência também sejam automáticos (desde 2014)
$ano_inicio = 2014;
$ano_atual = date('Y');
$anos_experiencia = $ano_atual - $ano_inicio;

// Verifica login
$logado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;
$nome_usuario = $logado ? $_SESSION['nome'] : 'Visitante';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós - Farmácia Jombaca</title>

    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* Estilização dos blocos de conteúdo */
        .historia-bloco {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-radius: 20px;
            overflow: hidden;
            background: white;
            border: none;
        }

        .historia-bloco:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
        }

        .img-historia {
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.6s ease;
        }
        
        .img-historia:hover {
            transform: scale(1.03);
        }

        /* Seção de Números/Estatísticas */
        .stats-section {
            background: #fff;
            border-radius: 25px;
            padding: 40px;
            border-bottom: 5px solid #198754;
        }

        .estatistica-numero {
            font-size: 3.5rem;
            font-weight: 800;
            color: #198754;
            line-height: 1;
        }

        .valor-card {
            border-radius: 20px;
            border: none;
            padding: 30px;
            transition: 0.3s;
        }

        .icon-box {
            width: 70px;
            height: 70px;
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }

        .cta-banner {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            color: white;
            border-radius: 25px;
            padding: 60px 20px;
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
                    <li class="nav-item"><a class="nav-link px-3" href="contacto.php">Contactos</a></li>
                    <li class="nav-item"><a class="nav-link px-3 active fw-bold" href="sobrenos.php">Sobre Nós</a></li>
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
            
            <section class="mb-5 py-4">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6 order-2 order-lg-1">
                        <span class="badge bg-success mb-2 px-3 py-2 rounded-pill">NOSSA MISSÃO</span>
                        <h2 class="fw-bold text-success mb-4 display-5">A Nossa História</h2>
                        <p class="lead text-muted mb-4">
                            Tudo começou em 2014, quando decidimos que a saúde de qualidade em Luanda não poderia ser um privilégio de poucos.
                        </p>
                        <p class="text-secondary">
                            A Farmácia Jombaca nasceu da união de jovens farmacêuticos angolanos. O nome, inspirado em dialetos locais, representa a nossa essência: <strong>União e Esperança</strong>. Começámos no bairro Rosiane e hoje somos referência em cuidado humanizado.
                        </p>
                        <div class="p-3 bg-white border-start border-success border-4 shadow-sm mt-4 italic">
                            <i class="bi bi-quote fs-2 text-success opacity-25"></i>
                            <p class="mb-0 fw-medium">"Trabalhamos para que nenhum angolano tenha de escolher entre comprar comida ou cuidar da sua saúde."</p>
                        </div>
                    </div>
                    <div class="col-lg-6 order-1 order-lg-2">
                        <img src="assets/img/interior-farmacia.jpg" alt="Interior Jombaca" class="img-fluid img-historia shadow">
                    </div>
                </div>
            </section>

            <section class="stats-section shadow-sm mb-5 text-center">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="estatistica-numero"><?= $anos_experiencia ?>+</div>
                        <p class="text-muted fw-bold text-uppercase small">Anos de História</p>
                    </div>
                    <div class="col-md-4">
                        <div class="estatistica-numero"><?= $total_filiais ?></div>
                        <p class="text-muted fw-bold text-uppercase small">
                            <?= $total_filiais == 1 ? 'Unidade em Luanda' : 'Unidades em Luanda' ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="estatistica-numero">50k+</div>
                        <p class="text-muted fw-bold text-uppercase small">Clientes Atendidos</p>
                    </div>
                </div>
            </section>

            <section class="mb-5">
                <h3 class="text-center fw-bold text-success mb-5">Nossos Pilares</h3>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 valor-card shadow-sm text-center historia-bloco">
                            <div class="icon-box"><i class="bi bi-heart-pulse-fill"></i></div>
                            <h4 class="fw-bold">Humanidade</h4>
                            <p class="text-muted small">Atendimento personalizado onde o paciente vem sempre em primeiro lugar.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 valor-card shadow-sm text-center historia-bloco">
                            <div class="icon-box"><i class="bi bi-shield-check"></i></div>
                            <h4 class="fw-bold">Rigor</h4>
                            <p class="text-muted small">Garantia absoluta da procedência e qualidade de cada medicamento.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 valor-card shadow-sm text-center historia-bloco">
                            <div class="icon-box"><i class="bi bi-wallet2"></i></div>
                            <h4 class="fw-bold">Preço Justo</h4>
                            <p class="text-muted small">Acessibilidade financeira para toda a comunidade angolana.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cta-banner text-center shadow">
                <h2 class="fw-bold mb-3">Faça parte da nossa história</h2>
                <p class="mb-4 opacity-75">Visite uma de nossas unidades ou tire suas dúvidas online agora mesmo.</p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="contacto.php" class="btn btn-light btn-lg rounded-pill fw-bold px-4">Falar com Farmacêutico</a>
                    <a href="nossos-enderecos.php" class="btn btn-outline-light btn-lg rounded-pill fw-bold px-4">Ver Localizações</a>
                </div>
            </section>

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
                    <h5 class="text-success fw-bold">NAVEGAÇÃO</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none">Início</a></li>
                        <li class="mb-2"><a href="produtos.php" class="text-white-50 text-decoration-none">Produtos</a></li>
                        <li class="mb-2"><a href="servicos.php" class="text-white-50 text-decoration-none">Serviços</a></li>
                        <li class="mb-2"><a href="sobrenos.php" class="text-white-50 text-decoration-none fw-bold text-white">Sobre Nós</a></li>
                    </ul>
                </div>

                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">CONTACTO</h5>
                    <p class="small mb-1"><i class="bi bi-telephone-fill me-2 text-success"></i>(+244) 967 984 094</p>
                    <p class="small mb-1"><i class="bi bi-envelope-fill me-2 text-success"></i>geral@farmciajombaca.co.ao</p>
                    <p class="small"><i class="bi bi-geo-alt-fill me-2 text-success"></i>Luanda, Angola</p>
                </div>
            </div>
            <div class="text-center mt-4 pt-4 border-top border-secondary text-white-50 small">
                2026 © Farmácia Jombaca. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>