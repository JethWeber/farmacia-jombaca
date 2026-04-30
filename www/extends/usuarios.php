<?php
ob_start();
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';

require_admin_principal_only();

$meu_id = $_SESSION['usuario_id'] ?? 0;

// 2. PROCESSAMENTO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar_usuario') {
        $id            = $_POST['id'] ?? '';
        $nome_completo = $_POST['nome_completo'];
        $email         = $_POST['email'];
        $telefone      = $_POST['telefone'];
        $role          = $_POST['role'] ?? 'cliente';
        $perfilInterno = $_POST['perfil_interno'] ?? null;
        $senha         = $_POST['senha'];

        $jaPrincipal = false;
        if ($id !== '' && $id !== null) {
            $stEx = $pdo->prepare('SELECT perfil_interno FROM usuarios WHERE id = ?');
            $stEx->execute([(int) $id]);
            $ex = $stEx->fetch();
            $jaPrincipal = (($ex['perfil_interno'] ?? '') === 'admin_principal');
        }
        if ($jaPrincipal) {
            $perfilInterno = 'admin_principal';
            $role = 'admin';
        } elseif ($role !== 'admin') {
            $perfilInterno = null;
        } else {
            $allowedPerfis = ['admin_secundario', 'funcionario'];
            if (!in_array($perfilInterno, $allowedPerfis, true)) {
                $perfilInterno = 'funcionario';
            }
        }

        if ($id) { // UPDATE
            if (!empty($senha)) {
                // Atualiza com nova senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo=?, email=?, telefone=?, role=?, perfil_interno=?, senha_hash=? WHERE id=?");
                $stmt->execute([$nome_completo, $email, $telefone, $role, $perfilInterno, $senha_hash, $id]);
            } else {
                // Atualiza sem mexer na senha
                $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo=?, email=?, telefone=?, role=?, perfil_interno=? WHERE id=?");
                $stmt->execute([$nome_completo, $email, $telefone, $role, $perfilInterno, $id]);
            }
            $msg = "Dados do utilizador atualizados!";
        } else { // INSERT
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome_completo, email, senha_hash, telefone, role, perfil_interno, lgpd_consent) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nome_completo, $email, $senha_hash, $telefone, $role, $perfilInterno]);
            $msg = "Novo utilizador criado com sucesso!";
        }
        header("Location: usuarios.php?msg=" . urlencode($msg));
        exit;
    }

    if ($acao === 'excluir_usuario') {
        $id_excluir = (int)$_POST['id'];
        
        // Impedir que o admin se exclua a si próprio
        if ($id_excluir === $meu_id) {
            header("Location: usuarios.php?msg=" . urlencode("Erro: Não podes eliminar a tua própria conta!"));
        } else {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id_excluir]);
            header("Location: usuarios.php?msg=" . urlencode("Utilizador removido!"));
        }
        exit;
    }
}

// 3. BUSCA DE DADOS
$usuarios = $pdo->query("SELECT id, nome_completo, email, telefone, role, perfil_interno FROM usuarios ORDER BY nome_completo ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Utilizadores - Jombaca</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <script src="../assets/js/sweetalert2.all.min.js"></script>
    <style>
        body { background: #f8f9fa; }
        .badge-role { width: 100px; }
        .search-box { max-width: 400px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Painel Admin</a>
            <h2 class="fw-bold text-success">Gestão de Utilizadores</h2>
        </div>
        <button class="btn btn-success" onclick="abrirModalCadastro()"><i class="bi bi-person-plus-fill"></i> Novo Utilizador</button>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <input type="text" id="inputBusca" class="form-control search-box" placeholder="Pesquisar por nome ou email..." onkeyup="filtrarTabela()">
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaUsuarios">
                <thead class="table-light">
                    <tr>
                        <th>Nome Completo</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Nível</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($u['nome_completo']) ?></strong>
                            <?= ($u['id'] == $meu_id) ? '<span class="badge bg-dark ms-1">Tu</span>' : '' ?>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['telefone'] ?: '---') ?></td>
                        <td>
                            <?php
                            if ($u['role'] === 'admin') {
                                $pl = $u['perfil_interno'] ?? '';
                                $map = [
                                    'admin_principal' => ['Admin principal', 'danger'],
                                    'admin_secundario' => ['Admin secundário', 'warning'],
                                    'funcionario' => ['Funcionário', 'info'],
                                ];
                                [$txt, $cls] = $map[$pl] ?? ['Equipa interna', 'secondary'];
                                echo '<span class="badge badge-role bg-' . htmlspecialchars($cls) . '">' . htmlspecialchars($txt) . '</span>';
                            } else {
                                echo '<span class="badge badge-role bg-primary">Cliente</span>';
                            }
                            ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-warning" onclick='editarUsuario(<?= json_encode($u) ?>)'><i class="bi bi-pencil"></i></button>
                            <?php if($u['id'] != $meu_id): ?>
                                <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $u['id'] ?>)"><i class="bi bi-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="tituloModal">Novo Utilizador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="acao" value="salvar_usuario">
                <input type="hidden" name="id" id="user_id">

                <div class="col-12">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="nome_completo" id="user_nome" class="form-control" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="user_email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" id="user_tel" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nível de Acesso</label>
                    <select name="role" id="user_role" class="form-select">
                        <option value="cliente">Cliente (site)</option>
                        <option value="admin">Equipa interna (painel)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Perfil no painel</label>
                    <select name="perfil_interno" id="user_perfil" class="form-select">
                        <option value="">—</option>
                        <option value="admin_secundario">Administrador secundário</option>
                        <option value="funcionario">Funcionário (vendas, stock consulta, reservas, validade)</option>
                    </select>
                    <small class="text-muted">Só aplica quando o nível é «Equipa interna». Não é possível criar outro admin principal aqui.</small>
                </div>
                <div class="col-12">
                    <label class="form-label" id="labelSenha">Senha de Acesso</label>
                    <input type="password" name="senha" id="user_senha" class="form-control">
                    <small class="text-muted" id="hintSenha">Deixe em branco para manter a senha atual (apenas em edições).</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Salvar Utilizador</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    const modalUsuario = new bootstrap.Modal(document.getElementById('modalUsuario'));

    function syncPerfilInterno() {
        const role = document.getElementById('user_role').value;
        const box = document.getElementById('user_perfil');
        box.disabled = (role !== 'admin');
        if (role !== 'admin') { box.value = ''; }
    }
    document.getElementById('user_role').addEventListener('change', syncPerfilInterno);

    function abrirModalCadastro() {
        document.getElementById('tituloModal').innerText = "Novo Utilizador";
        document.getElementById('user_id').value = "";
        document.getElementById('user_senha').required = true;
        document.getElementById('hintSenha').style.display = "none";
        document.getElementById('modalUsuario').querySelector('form').reset();
        syncPerfilInterno();
        modalUsuario.show();
    }

    function editarUsuario(u) {
        document.getElementById('tituloModal').innerText = "Editar: " + u.nome_completo;
        document.getElementById('user_id').value = u.id;
        document.getElementById('user_nome').value = u.nome_completo;
        document.getElementById('user_email').value = u.email;
        document.getElementById('user_tel').value = u.telefone;
        document.getElementById('user_role').value = u.role;
        document.getElementById('user_perfil').value = u.perfil_interno || '';
        document.getElementById('user_senha').required = false;
        document.getElementById('hintSenha').style.display = "block";
        syncPerfilInterno();
        modalUsuario.show();
    }

    function confirmarExclusao(id) {
        Swal.fire({
            title: 'Remover Utilizador?',
            text: "Esta conta perderá acesso ao sistema imediatamente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, remover'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="acao" value="excluir_usuario"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function filtrarTabela() {
        let input = document.getElementById("inputBusca").value.toUpperCase();
        let trs = document.getElementById("tabelaUsuarios").getElementsByTagName("tr");
        for (let i = 1; i < trs.length; i++) {
            let texto = trs[i].innerText.toUpperCase();
            trs[i].style.display = texto.includes(input) ? "" : "none";
        }
    }
    syncPerfilInterno();
</script>

<?php if(isset($_GET['msg'])): ?>
<script>Swal.fire('Gestão de Contas', '<?= htmlspecialchars($_GET['msg']) ?>', 'info');</script>
<?php endif; ?>

</body>
</html>