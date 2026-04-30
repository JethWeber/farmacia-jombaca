<?php
session_start();
require_once 'config/db.php';
require_once 'config/imagem_helper.php';

// Verifica login e pega dados da sessão
$logado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;
$nome_usuario = $logado ? $_SESSION['nome'] : '';
$telefone_usuario = $logado ? ($_SESSION['telefone'] ?? '') : '';

$filiais_reserva = $pdo->query("SELECT id, nome, bairro, endereco FROM filiais ORDER BY principal DESC, nome ASC")->fetchAll();

// Processa reserva (Apenas se logado)
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservar']) && $logado) {
    $produto_id = (int)$_POST['produto_id'];
    $qtd = (int)$_POST['quantidade'];
    $usuario_id = $_SESSION['usuario_id'];
    $filial_pref = isset($_POST['filial_preferida_id']) ? (int)$_POST['filial_preferida_id'] : 0;
    if ($filial_pref <= 0) {
        $filial_pref = null;
    }

    $stmt = $pdo->prepare("INSERT INTO reservas (usuario_id, nome_contato, telefone_contato, produto_id, quantidade_solicitada, filial_preferida_id) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$usuario_id, $nome_usuario, $telefone_usuario, $produto_id, $qtd, $filial_pref])) {
        $mensagem = "Reserva de " . htmlspecialchars($_POST['prod_nome_hidden']) . " efetuada com sucesso!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Farmácia Jombaca</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .search-container { max-width: 500px; margin: 0 auto 40px auto; }
        .search-input { height: 50px; border-radius: 50px; border: 2px solid #198754; padding-left: 20px; }
        
        .product-card { border: none; border-radius: 15px; transition: 0.3s; background: #fff; height: 100%; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        
        .category-section { margin-bottom: 50px; }
        .category-title { color: #198754; font-weight: 800; border-left: 5px solid #198754; padding-left: 15px; margin-bottom: 25px; }
        
        .img-wrapper {
            height: 180px; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(145deg, #e8f5ef 0%, #d1e7dd 35%, #a3cfbb 100%);
            border-radius: 15px 15px 0 0; padding: 12px;
        }
        .img-wrapper img { max-height: 100%; max-width: 100%; object-fit: contain; }
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

    <nav class="navbar navbar-expand-lg navbar-light site-main-navbar bg-white border-bottom border-success border-3 shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand py-1 me-2 flex-shrink-0 d-lg-none" href="index.php" title="Farmácia Jombaca">
                <img src="assets/img/logoJombaca.png" alt="Farmácia Jombaca" height="38" style="max-height:38px;width:auto;">
            </a>
            <button class="navbar-toggler ms-auto ms-lg-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Alternar navegação"><i class="bi bi-list fs-3 text-success"></i></button>
            <div class="collapse navbar-collapse flex-grow-1 justify-content-lg-between align-items-lg-center" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 mt-2 mt-lg-0 align-items-lg-center">
                    <li class="nav-item"><a class="nav-link px-3" href="index.php">Início</a></li>
                    <li class="nav-item"><a class="nav-link px-3 active fw-bold" href="produtos.php">Produtos</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="servicos.php">Serviços</a></li>
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

    <div class="container my-5">
        
        <div class="search-container text-center">
            <h2 class="fw-bold text-dark mb-3">O que procura?</h2>
            <input type="text" id="liveSearch" class="form-control search-input shadow-sm" placeholder="Digite o nome do produto...">
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 text-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $mensagem ?>
                <div><a href="minhas_reservas.php" class="small">Ver comprovativo da reserva</a></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php
        // Pegar Categorias que possuem produtos disponíveis
        $categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

        foreach ($categorias as $cat):
            $stmt = $pdo->prepare("SELECT * FROM produtos WHERE categoria_id = ? AND disponivel = 1 ORDER BY nome ASC");
            $stmt->execute([$cat['id']]);
            $prods = $stmt->fetchAll();

            if (count($prods) > 0):
        ?>
            <section class="category-section" id="cat-<?= $cat['id'] ?>">
                <h3 class="category-title"><?= htmlspecialchars($cat['nome']) ?></h3>
                <div class="row g-4">
                    <?php foreach ($prods as $p): ?>
                    <div class="col-6 col-md-4 col-lg-3 product-item" data-name="<?= strtolower(htmlspecialchars($p['nome'])) ?>">
                        <div class="card product-card shadow-sm">
                            <div class="img-wrapper">
                                <img src="<?= htmlspecialchars(farmacia_imagem_publica($p['imagem'] ?? '')) ?>" alt="<?= htmlspecialchars($p['nome']) ?>" loading="lazy" width="200" height="200">
                            </div>
                            <div class="card-body d-flex flex-column p-3 text-center">
                                <h6 class="fw-bold text-dark mb-2"><?= htmlspecialchars($p['nome']) ?></h6>
                                <p class="text-success fw-bold mb-3"><?= number_format($p['preco'], 2, ',', '.') ?> Kz</p>
                                
                                <?php if ($logado): ?>
                                    <button class="btn btn-success w-100 rounded-pill btn-sm fw-bold" 
                                            data-bs-toggle="modal" data-bs-target="#reservaModal"
                                            data-id="<?= $p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome']) ?>">
                                        RESERVAR
                                    </button>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-secondary w-100 rounded-pill btn-sm fw-bold">Login para Reservar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php 
            endif;
        endforeach; 
        ?>
    </div>

    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container text-center text-md-start">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <img src="assets/img/logoJombaca.png" height="80" class="mb-3">
                    <p class="small text-white-50">Saúde e bem-estar para todos os angolanos.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">MENU</h5>
                    <ul class="list-unstyled small">
                        <li><a href="produtos.php" class="text-white-50 text-decoration-none">Produtos</a></li>
                        <li><a href="nossos-enderecos.php" class="text-white-50 text-decoration-none">Endereços</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-success fw-bold">CONTACTOS</h5>
                    <p class="small mb-0">(+244) 967 984 094</p>
                </div>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="reservaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 20px;">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold">Confirmar Reserva</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="produto_id" id="modal_id">
                        <input type="hidden" name="prod_nome_hidden" id="modal_nome_hidden">
                        <input type="hidden" name="reservar" value="1">

                        <p class="mb-4">Olá <strong><?= htmlspecialchars($nome_usuario) ?></strong>, deseja reservar este produto utilizando o seu contacto <strong><?= htmlspecialchars($telefone_usuario) ?></strong>?</p>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Produto Selecionado</label>
                            <input type="text" id="modal_nome_display" class="form-control bg-light border-0 fw-bold" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Quantidade</label>
                            <input type="number" name="quantidade" class="form-control border-success" value="1" min="1" required>
                        </div>
                        <?php if (!empty($filiais_reserva)): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Filial de levantamento</label>
                            <select name="filial_preferida_id" class="form-select border-success" required>
                                <?php foreach ($filiais_reserva as $fl): ?>
                                    <option value="<?= (int)$fl['id'] ?>"><?= htmlspecialchars($fl['nome']) ?> — <?= htmlspecialchars($fl['bairro'] ?: '') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Indique onde prefere levantar o produto.</small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold py-2">CONFIRMAR E ENVIAR AGORA</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // BUSCA DINÂMICA (LIVE SEARCH)
        document.getElementById('liveSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let items = document.querySelectorAll('.product-item');
            
            items.forEach(function(item) {
                let name = item.getAttribute('data-name');
                if (name.includes(filter)) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });

            // Esconde seções de categoria vazias durante a busca
            document.querySelectorAll('.category-section').forEach(function(section) {
                let hasVisible = false;
                section.querySelectorAll('.product-item').forEach(function(item) {
                    if (item.style.display !== "none") hasVisible = true;
                });
                section.style.display = hasVisible ? "" : "none";
            });
        });

        // PREENCHER MODAL
        var reservaModal = document.getElementById('reservaModal')
        reservaModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget
            document.getElementById('modal_id').value = button.getAttribute('data-id')
            document.getElementById('modal_nome_hidden').value = button.getAttribute('data-nome')
            document.getElementById('modal_nome_display').value = button.getAttribute('data-nome')
        })
    </script>
</body>
</html>