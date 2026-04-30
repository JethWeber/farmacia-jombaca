<?php
ob_start();
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../config/imagem_helper.php';

require_admin_principal_only();

// 2. FUNÇÃO DE UPLOAD
function executarUpload($file, $subpasta) {
    if (!isset($file) || $file['error'] !== 0) return '';
    $upload_dir = __DIR__ . "/../uploads/$subpasta/"; 
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
    @chmod($upload_dir, 0775);
    
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

    if ($acao === 'salvar_filial') {
        $id          = $_POST['id'] ?? '';
        $nome        = $_POST['nome'];
        $endereco    = $_POST['endereco'];
        $bairro      = $_POST['bairro'];
        $telefone    = $_POST['telefone'];
        $coordenadas = $_POST['coordenadas'];
        $principal   = isset($_POST['principal']) ? 1 : 0;

        if ($principal === 1) {
            $pdo->query("UPDATE filiais SET principal = 0");
        }
        
        $img_path = $_POST['img_atual'] ?? '';
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
            $img_path = executarUpload($_FILES['imagem'], 'filiais');
        }

        if ($id) { // UPDATE
            $stmt = $pdo->prepare("UPDATE filiais SET nome=?, endereco=?, bairro=?, telefone=?, coordenadas=?, imagem=?, principal=? WHERE id=?");
            $stmt->execute([$nome, $endereco, $bairro, $telefone, $coordenadas, $img_path, $principal, $id]);
            $msg = "Filial atualizada!";
        } else { // INSERT
            $stmt = $pdo->prepare("INSERT INTO filiais (nome, endereco, bairro, telefone, coordenadas, imagem, principal) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $endereco, $bairro, $telefone, $coordenadas, $img_path, $principal]);
            $msg = "Filial cadastrada!";
        }
        header("Location: filiais.php?msg=" . urlencode($msg));
        exit;
    }

    if ($acao === 'excluir_filial') {
        $stmt = $pdo->prepare("DELETE FROM filiais WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        header("Location: filiais.php?msg=" . urlencode("Filial removida!"));
        exit;
    }
}

// 4. BUSCA DE DADOS
$filiais = $pdo->query("SELECT * FROM filiais ORDER BY principal DESC, nome ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Filiais - Jombaca</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <script src="../assets/js/sweetalert2.all.min.js"></script>
    <style>
        body { background: #f8f9fa; }
        .table-img { width: 60px; height: 45px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .search-box { max-width: 400px; }
        .badge-sede { background-color: #0d6efd; font-size: 0.7rem; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Painel Principal</a>
            <h2 class="fw-bold text-success">Gestão de Filiais</h2>
        </div>
        <button class="btn btn-success" onclick="abrirModalCadastro()"><i class="bi bi-geo-alt-fill"></i> Nova Unidade</button>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <input type="text" id="inputBusca" class="form-control search-box" placeholder="Pesquisar por nome ou bairro..." onkeyup="filtrarTabela()">
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaFiliais">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th>
                        <th>Unidade</th>
                        <th>Bairro / Endereço</th>
                        <th>Contacto</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filiais as $f): ?>
                    <tr>
                        <td><img src="../<?= htmlspecialchars(farmacia_imagem_publica($f['imagem'] ?? '', 'assets/img/faxada01.jpeg')) ?>" class="table-img" alt=""></td>
                        <td>
                            <strong><?= htmlspecialchars($f['nome']) ?></strong>
                            <?php if($f['principal']): ?> <span class="badge badge-sede">SEDE</span> <?php endif; ?>
                        </td>
                        <td>
                            <small class="d-block text-dark fw-bold"><?= htmlspecialchars($f['bairro']) ?></small>
                            <small class="text-muted"><?= htmlspecialchars($f['endereco']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($f['telefone']) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-warning" onclick='editarFilial(<?= json_encode($f) ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $f['id'] ?>)" title="Excluir"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFilial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" enctype="multipart/form-data">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="tituloModal">Nova Unidade</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="acao" value="salvar_filial">
                <input type="hidden" name="id" id="filial_id">
                <input type="hidden" name="img_atual" id="filial_img_atual">

                <div class="col-md-7">
                    <label class="form-label">Nome da Unidade</label>
                    <input type="text" name="nome" id="filial_nome" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Bairro</label>
                    <input type="text" name="bairro" id="filial_bairro" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Endereço Completo</label>
                    <input type="text" name="endereco" id="filial_endereco" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" id="filial_telefone" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Coordenadas</label>
                    <input type="text" name="coordenadas" id="filial_coords" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Foto</label>
                    <input type="file" name="imagem" class="form-control">
                </div>
                <div class="col-12">
                    <div class="form-check form-switch p-3 border rounded bg-light">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="principal" id="filial_principal">
                        <label class="form-check-label fw-bold">Sede Principal</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    const modalFilial = new bootstrap.Modal(document.getElementById('modalFilial'));

    function abrirModalCadastro() {
        document.getElementById('tituloModal').innerText = "Nova Unidade";
        document.getElementById('filial_id').value = "";
        document.getElementById('filial_img_atual').value = "";
        document.querySelector('form').reset();
        modalFilial.show();
    }

    // CORREÇÃO APLICADA AQUI NA LINHA ABAIXO
    function editarFilial(f) {
        document.getElementById('tituloModal').innerText = "Editar: " + f.nome;
        document.getElementById('filial_id').value = f.id;
        document.getElementById('filial_nome').value = f.nome;
        document.getElementById('filial_bairro').value = f.bairro;
        document.getElementById('filial_endereco').value = f.endereco;
        document.getElementById('filial_telefone').value = f.telefone;
        document.getElementById('filial_coords').value = f.coordenadas;
        document.getElementById('filial_img_atual').value = f.imagem;
        document.getElementById('filial_principal').checked = f.principal == 1;
        modalFilial.show();
    }

    function confirmarExclusao(id) {
        Swal.fire({
            title: 'Excluir Unidade?',
            text: "Esta unidade deixará de aparecer no site.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="acao" value="excluir_filial"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function filtrarTabela() {
        let input = document.getElementById("inputBusca").value.toUpperCase();
        let trs = document.getElementById("tabelaFiliais").getElementsByTagName("tr");
        for (let i = 1; i < trs.length; i++) {
            let texto = trs[i].innerText.toUpperCase();
            trs[i].style.display = texto.includes(input) ? "" : "none";
        }
    }
</script>

<?php if(isset($_GET['msg'])): ?>
<script>Swal.fire('Informação', '<?= htmlspecialchars($_GET['msg']) ?>', 'success');</script>
<?php endif; ?>

</body>
</html>