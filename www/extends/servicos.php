<?php
ob_start();
session_start();
require_once '../config/db.php';

// 1. PROTEÇÃO DE ACESSO
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php?msg=Acesso restrito');
    exit;
}

// 2. FUNÇÃO DE UPLOAD (CORRIGIDA)
function executarUpload($file, $subpasta) {
    if (!isset($file) || $file['error'] !== 0) return '';
    $upload_dir = __DIR__ . "/../uploads/$subpasta/"; 
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    // CORREÇÃO AQUI: PATHINFO_EXTENSION
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $novo_nome = time() . '_' . uniqid() . '.' . $ext;
    
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $novo_nome)) {
        return "uploads/$subpasta/$novo_nome";
    }
    return '';
}

// 3. PROCESSAMENTO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar_servico') {
        $id = $_POST['id'] ?? '';
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        $img_path = $_POST['img_atual'] ?? '';
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
            $img_path = executarUpload($_FILES['imagem'], 'servicos');
        }

        if ($id) { // UPDATE
            $stmt = $pdo->prepare("UPDATE servicos SET nome=?, descricao=?, imagem=?, ativo=? WHERE id=?");
            $stmt->execute([$nome, $descricao, $img_path, $ativo, $id]);
            $msg = "Serviço atualizado com sucesso!";
        } else { // INSERT
            $stmt = $pdo->prepare("INSERT INTO servicos (nome, descricao, imagem, ativo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $descricao, $img_path, $ativo]);
            $msg = "Serviço cadastrado com sucesso!";
        }
        header("Location: servicos.php?msg=" . urlencode($msg));
        exit;
    }

    if ($acao === 'excluir_servico') {
        $stmt = $pdo->prepare("DELETE FROM servicos WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        header("Location: servicos.php?msg=" . urlencode("Serviço removido!"));
        exit;
    }
}

// 4. BUSCA DE DADOS
$servicos = $pdo->query("SELECT * FROM servicos ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Serviços - Jombaca</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <script src="../assets/js/sweetalert2.all.min.js"></script>
    <style>
        body { background: #f8f9fa; }
        .table-img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 2px solid #198754; }
        .search-box { max-width: 400px; }
        .status-badge { width: 80px; text-align: center; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Voltar ao Painel</a>
            <h2 class="fw-bold text-success">Gestão de Serviços</h2>
        </div>
        <button class="btn btn-success" onclick="abrirModalCadastro()"><i class="bi bi-plus-lg"></i> Novo Serviço</button>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <input type="text" id="inputBusca" class="form-control search-box" placeholder="Pesquisar serviço..." onkeyup="filtrarTabela()">
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaServicos">
                <thead class="table-light">
                    <tr>
                        <th>Ícone</th>
                        <th>Nome do Serviço</th>
                        <th>Descrição Resumida</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicos as $s): ?>
                    <tr>
                        <td><img src="../<?= $s['imagem'] ?: 'assets/img/noservice.png' ?>" class="table-img"></td>
                        <td><strong><?= htmlspecialchars($s['nome']) ?></strong></td>
                        <td><small class="text-muted"><?= mb_strimwidth(htmlspecialchars($s['descricao']), 0, 60, "...") ?></small></td>
                        <td>
                            <span class="badge status-badge bg-<?= $s['ativo'] ? 'success' : 'secondary' ?>">
                                <?= $s['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-warning" onclick='editarServico(<?= json_encode($s) ?>)'><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $s['id'] ?>)"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalServico" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" enctype="multipart/form-data">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="tituloModal">Novo Serviço</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="acao" value="salvar_servico">
                <input type="hidden" name="id" id="serv_id">
                <input type="hidden" name="img_atual" id="serv_img_atual">

                <div class="mb-3">
                    <label class="form-label">Nome do Serviço</label>
                    <input type="text" name="nome" id="serv_nome" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descrição Detalhada</label>
                    <textarea name="descricao" id="serv_desc" class="form-control" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ícone / Imagem</label>
                    <input type="file" name="imagem" class="form-control">
                    <small class="text-muted">Recomendado: 512x512px (PNG ou WebP)</small>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="ativo" id="serv_ativo" checked>
                    <label class="form-check-label">Serviço Ativo (Visível no Site)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Salvar Serviço</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    const modalServico = new bootstrap.Modal(document.getElementById('modalServico'));

    function abrirModalCadastro() {
        document.getElementById('tituloModal').innerText = "Novo Serviço";
        document.getElementById('serv_id').value = "";
        document.getElementById('serv_img_atual').value = "";
        document.querySelector('form').reset();
        modalServico.show();
    }

    function editarServico(s) {
        document.getElementById('tituloModal').innerText = "Editar: " + s.nome;
        document.getElementById('serv_id').value = s.id;
        document.getElementById('serv_nome').value = s.nome;
        document.getElementById('serv_desc').value = s.descricao;
        document.getElementById('serv_img_atual').value = s.imagem;
        document.getElementById('serv_ativo').checked = s.ativo == 1;
        modalServico.show();
    }

    function confirmarExclusao(id) {
        Swal.fire({
            title: 'Excluir Serviço?',
            text: "Os clientes não verão mais este serviço.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="acao" value="excluir_servico"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function filtrarTabela() {
        let input = document.getElementById("inputBusca").value.toUpperCase();
        let trs = document.getElementById("tabelaServicos").getElementsByTagName("tr");
        for (let i = 1; i < trs.length; i++) {
            let texto = trs[i].innerText.toUpperCase();
            trs[i].style.display = texto.includes(input) ? "" : "none";
        }
    }
</script>

<?php if(isset($_GET['msg'])): ?>
<script>Swal.fire('Sucesso', '<?= htmlspecialchars($_GET['msg']) ?>', 'success');</script>
<?php endif; ?>

</body>
</html>