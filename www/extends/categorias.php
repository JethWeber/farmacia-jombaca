<?php
ob_start();
session_start();
require_once '../config/db.php';

// 1. PROTEÇÃO DE ACESSO
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php?msg=Acesso restrito');
    exit;
}

// 2. FUNÇÃO DE UPLOAD
function executarUpload($file, $subpasta) {
    if (!isset($file) || $file['error'] !== 0) return '';
    $upload_dir = __DIR__ . "/../uploads/$subpasta/"; 
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
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

    if ($acao === 'salvar_categoria') {
        $id      = $_POST['id'] ?? '';
        $nome    = $_POST['nome'];
        $ordem   = (int)$_POST['ordem_exibicao'];
        $desc    = $_POST['descricao'];
        
        // Gerar Slug automaticamente (ex: "Higiene Bucal" -> "higiene-bucal")
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nome)));

        $img_path = $_POST['img_atual'] ?? '';
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
            $img_path = executarUpload($_FILES['imagem'], 'categorias');
        }

        if ($id) { // UPDATE
            $stmt = $pdo->prepare("UPDATE categorias SET nome=?, imagem=?, ordem_exibicao=?, slug=?, descricao=? WHERE id=?");
            $stmt->execute([$nome, $img_path, $ordem, $slug, $desc, $id]);
            $msg = "Categoria atualizada!";
        } else { // INSERT
            $stmt = $pdo->prepare("INSERT INTO categorias (nome, imagem, ordem_exibicao, slug, descricao) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $img_path, $ordem, $slug, $desc]);
            $msg = "Categoria criada!";
        }
        header("Location: categorias.php?msg=" . urlencode($msg));
        exit;
    }

    if ($acao === 'excluir_categoria') {
        // Verificar se existem produtos vinculados a esta categoria antes de excluir
        $id_excluir = (int)$_POST['id'];
        $check = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE categoria_id = ?");
        $check->execute([$id_excluir]);
        
        if ($check->fetchColumn() > 0) {
            header("Location: categorias.php?msg=" . urlencode("Erro: Existem produtos nesta categoria!"));
        } else {
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id_excluir]);
            header("Location: categorias.php?msg=" . urlencode("Categoria removida!"));
        }
        exit;
    }
}

// 4. BUSCA DE DADOS
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY ordem_exibicao ASC, nome ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Categorias - Jombaca</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <script src="../assets/js/sweetalert2.all.min.js"></script>
    <style>
        body { background: #f8f9fa; }
        .table-img { width: 50px; height: 50px; object-fit: contain; background: #eee; border-radius: 4px; }
        .search-box { max-width: 400px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Painel Admin</a>
            <h2 class="fw-bold text-success">Categorias de Produtos</h2>
        </div>
        <button class="btn btn-success" onclick="abrirModalCadastro()"><i class="bi bi-tag-fill"></i> Nova Categoria</button>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <input type="text" id="inputBusca" class="form-control search-box" placeholder="Pesquisar categoria..." onkeyup="filtrarTabela()">
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaCategorias">
                <thead class="table-light">
                    <tr>
                        <th width="80">Ordem</th>
                        <th>Ícone/Img</th>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $c): ?>
                    <tr>
                        <td><span class="badge bg-secondary">#<?= $c['ordem_exibicao'] ?></span></td>
                        <td><img src="../<?= $c['imagem'] ?: 'assets/img/nocategory.png' ?>" class="table-img"></td>
                        <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                        <td><code>/<?= htmlspecialchars($c['slug']) ?></code></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-warning" onclick='editarCategoria(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $c['id'] ?>)"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCategoria" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" enctype="multipart/form-data">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="tituloModal">Nova Categoria</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="acao" value="salvar_categoria">
                <input type="hidden" name="id" id="cat_id">
                <input type="hidden" name="img_atual" id="cat_img_atual">

                <div class="col-md-9">
                    <label class="form-label">Nome da Categoria</label>
                    <input type="text" name="nome" id="cat_nome" class="form-control" required placeholder="Ex: Higiene, Bebés, Medicamentos">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordem</label>
                    <input type="number" name="ordem_exibicao" id="cat_ordem" class="form-control" value="0">
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição (Opcional)</label>
                    <textarea name="descricao" id="cat_desc" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Imagem / Ícone Representativo</label>
                    <input type="file" name="imagem" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Salvar Categoria</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    const modalCategoria = new bootstrap.Modal(document.getElementById('modalCategoria'));

    function abrirModalCadastro() {
        document.getElementById('tituloModal').innerText = "Nova Categoria";
        document.getElementById('cat_id').value = "";
        document.getElementById('cat_img_atual').value = "";
        document.querySelector('form').reset();
        modalCategoria.show();
    }

    function editarCategoria(c) {
        document.getElementById('tituloModal').innerText = "Editar: " + c.nome;
        document.getElementById('cat_id').value = c.id;
        document.getElementById('cat_nome').value = c.nome;
        document.getElementById('cat_ordem').value = c.ordem_exibicao;
        document.getElementById('cat_desc').value = c.descricao;
        document.getElementById('cat_img_atual').value = c.imagem;
        modalCategoria.show();
    }

    function confirmarExclusao(id) {
        Swal.fire({
            title: 'Eliminar Categoria?',
            text: "Certifique-se de que não existem produtos associados a ela.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="acao" value="excluir_categoria"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function filtrarTabela() {
        let input = document.getElementById("inputBusca").value.toUpperCase();
        let trs = document.getElementById("tabelaCategorias").getElementsByTagName("tr");
        for (let i = 1; i < trs.length; i++) {
            let texto = trs[i].innerText.toUpperCase();
            trs[i].style.display = texto.includes(input) ? "" : "none";
        }
    }
</script>

<?php if(isset($_GET['msg'])): ?>
<script>Swal.fire('Mensagem', '<?= htmlspecialchars($_GET['msg']) ?>', 'info');</script>
<?php endif; ?>

</body>
</html>