<?php
ob_start();
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../config/imagem_helper.php';

require_admin_any();
$isPrincipal = is_admin_principal();

function executarUpload(array $file, string $subpasta): string
{
    if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    $upload_dir = dirname(__DIR__) . '/uploads/' . $subpasta . '/';
    if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0775, true)) {
        error_log('Upload: não foi possível criar ' . $upload_dir);
        return '';
    }
    @chmod($upload_dir, 0775);
    if (!is_writable($upload_dir)) {
        error_log('Upload: diretório sem escrita ' . $upload_dir);
        return '';
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return '';
    }
    $novo_nome = time() . '_' . uniqid('', true) . '.' . $ext;
    $dest = $upload_dir . $novo_nome;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        error_log('Upload: move_uploaded_file falhou para ' . $dest);
        return '';
    }
    @chmod($dest, 0644);

    return 'uploads/' . $subpasta . '/' . $novo_nome;
}

// 3. PROCESSAMENTO DE AÇÕES (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // CADASTRAR OU EDITAR PRODUTO
    if ($acao === 'salvar_produto') {
        if (!$isPrincipal) {
            header("Location: produtos.php?msg=Apenas o Admin Principal pode cadastrar ou editar produtos.");
            exit;
        }
        $id = $_POST['id'] ?? '';
        $nome = $_POST['nome'];
        $cat_id = (int)$_POST['categoria_id'];
        $preco = (float)$_POST['preco'];
        $estoque = (int)$_POST['estoque'];
        $validade = $_POST['data_validade'] ?: null;
        $desc = $_POST['descricao'];
        $comp = $_POST['composicao'];
        $destaque = isset($_POST['em_destaque']) ? 1 : 0;
        $dispo = isset($_POST['disponivel']) ? 1 : 0;

        // Lógica de Imagem
        $img_path = $_POST['img_atual'] ?? '';
        if (isset($_FILES['imagem']) && (int) $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $nova = executarUpload($_FILES['imagem'], 'produtos');
            if ($nova !== '') {
                $img_path = $nova;
            } else {
                header('Location: produtos.php?msg=' . urlencode('Não foi possível gravar a imagem. Confirme permissões em uploads/produtos e tamanho máx. 16MB.'));
                exit;
            }
        } elseif (isset($_FILES['imagem']['error']) && (int) $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
            header('Location: produtos.php?msg=' . urlencode('Erro no envio da imagem (código ' . (int) $_FILES['imagem']['error'] . ').'));
            exit;
        }

        if ($id) { // UPDATE
            $stmt = $pdo->prepare("UPDATE produtos SET nome=?, categoria_id=?, preco=?, imagem=?, descricao=?, composicao=?, estoque_atual=?, data_validade=?, em_destaque=?, disponivel=? WHERE id=?");
            $stmt->execute([$nome, $cat_id, $preco, $img_path, $desc, $comp, $estoque, $validade, $destaque, $dispo, $id]);
            $msg = "Produto atualizado!";
        } else { // INSERT
            $stmt = $pdo->prepare("INSERT INTO produtos (nome, categoria_id, preco, imagem, descricao, composicao, estoque_atual, data_validade, em_destaque, disponivel) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $cat_id, $preco, $img_path, $desc, $comp, $estoque, $validade, $destaque, $dispo]);
            $msg = "Produto cadastrado!";
        }
        header("Location: produtos.php?msg=$msg");
        exit;
    }

    // EXCLUIR PRODUTO
    if ($acao === 'excluir_produto') {
        if (!$isPrincipal) {
            header("Location: produtos.php?msg=Apenas o Admin Principal pode excluir produtos.");
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        header("Location: produtos.php?msg=Produto removido!");
        exit;
    }
}

// 4. BUSCA DE DADOS
$produtos = $pdo->query("SELECT p.*, c.nome as cat_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC")->fetchAll();
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Produtos - Jombaca</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <script src="../assets/js/sweetalert2.all.min.js"></script>
    <style>
        body { background: #f8f9fa; }
        .table-img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .search-box { max-width: 400px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?= htmlspecialchars(painel_voltar_desde_extends()) ?>" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Voltar</a>
            <h2 class="fw-bold text-success">Gestão de Produtos</h2>
        </div>
        <?php if ($isPrincipal): ?>
            <button class="btn btn-success" onclick="abrirModalCadastro()"><i class="bi bi-plus-lg"></i> Novo Produto</button>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <input type="text" id="inputBusca" class="form-control search-box" placeholder="Pesquisar por nome ou categoria..." onkeyup="filtrarTabela()">
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaProdutos">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Imagem</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Estoque</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td><img src="../<?= htmlspecialchars(farmacia_imagem_publica($p['imagem'] ?? '')) ?>" class="table-img" alt=""></td>
                        <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p['cat_nome'] ?: 'Sem Categoria') ?></span></td>
                        <td><?= number_format($p['preco'], 2, ',', '.') ?> Kz</td>
                        <td><span class="badge bg-<?= $p['estoque_atual'] > 5 ? 'success' : 'danger' ?>"><?= $p['estoque_atual'] ?></span></td>
                        <td class="text-end">
                            <?php if ($isPrincipal): ?>
                                <button class="btn btn-sm btn-warning" onclick='editarProduto(<?= json_encode($p) ?>)'><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $p['id'] ?>)"><i class="bi bi-trash"></i></button>
                            <?php else: ?>
                                <span class="badge bg-secondary">Somente consulta</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($isPrincipal): ?>
<div class="modal fade" id="modalProduto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" enctype="multipart/form-data">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="tituloModal">Novo Produto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="acao" value="salvar_produto">
                <input type="hidden" name="id" id="prod_id">
                <input type="hidden" name="img_atual" id="prod_img_atual">

                <div class="col-md-8">
                    <label class="form-label">Nome do Produto</label>
                    <input type="text" name="nome" id="prod_nome" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" id="prod_cat" class="form-select" required>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Preço (Kz)</label>
                    <input type="number" step="0.01" name="preco" id="prod_preco" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estoque Atual</label>
                    <input type="number" name="estoque" id="prod_estoque" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Validade</label>
                    <input type="date" name="data_validade" id="prod_validade" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Imagem (Deixe vazio para manter a atual)</label>
                    <input type="file" name="imagem" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição Curta</label>
                    <textarea name="descricao" id="prod_desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Composição / Detalhes Técnicos</label>
                    <textarea name="composicao" id="prod_comp" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="em_destaque" id="prod_destaque">
                        <label class="form-check-label">Exibir em Destaque</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="disponivel" id="prod_disponivel" checked>
                        <label class="form-check-label">Produto Disponível</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    const modalProdutoEl = document.getElementById('modalProduto');
    const modalProduto = modalProdutoEl ? new bootstrap.Modal(modalProdutoEl) : null;

    function abrirModalCadastro() {
        document.getElementById('tituloModal').innerText = "Novo Produto";
        document.getElementById('prod_id').value = "";
        document.getElementById('prod_img_atual').value = "";
        const f = document.querySelector('#modalProduto form');
        if (f) f.reset();
        if (modalProduto) modalProduto.show();
    }

    function editarProduto(p) {
        document.getElementById('tituloModal').innerText = "Editar Produto: " + p.nome;
        document.getElementById('prod_id').value = p.id;
        document.getElementById('prod_nome').value = p.nome;
        document.getElementById('prod_cat').value = p.categoria_id;
        document.getElementById('prod_preco').value = p.preco;
        document.getElementById('prod_estoque').value = p.estoque_atual;
        document.getElementById('prod_validade').value = p.data_validade;
        document.getElementById('prod_desc').value = p.descricao;
        document.getElementById('prod_comp').value = p.composicao;
        document.getElementById('prod_img_atual').value = p.imagem;
        document.getElementById('prod_destaque').checked = p.em_destaque == 1;
        document.getElementById('prod_disponivel').checked = p.disponivel == 1;
        if (modalProduto) modalProduto.show();
    }

    function confirmarExclusao(id) {
        Swal.fire({
            title: 'Excluir Produto?',
            text: "Esta ação não pode ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="acao" value="excluir_produto"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function filtrarTabela() {
        let input = document.getElementById("inputBusca").value.toUpperCase();
        let trs = document.getElementById("tabelaProdutos").getElementsByTagName("tr");
        for (let i = 1; i < trs.length; i++) {
            let texto = trs[i].innerText.toUpperCase();
            trs[i].style.display = texto.includes(input) ? "" : "none";
        }
    }
</script>

<?php if(isset($_GET['msg'])): ?>
<script>
    Swal.fire('Aviso', '<?= htmlspecialchars($_GET['msg']) ?>', 'info');
</script>
<?php endif; ?>

</body>
</html>