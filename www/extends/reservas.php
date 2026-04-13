<?php
ob_start();
session_start();
require_once '../config/db.php';

// 1. PROTEÇÃO DE ACESSO
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php?msg=Acesso restrito');
    exit;
}

// 2. PROCESSAMENTO POST (Atualizar Status ou Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar_status') {
        $id = (int)$_POST['id'];
        $novo_status = $_POST['status'];
        $respondida = ($novo_status === 'nova') ? 0 : 1; 

        $stmt = $pdo->prepare("UPDATE reservas SET status = ?, respondida = ? WHERE id = ?");
        $stmt->execute([$novo_status, $respondida, $id]);
        header("Location: reservas.php?msg=Status atualizado!");
        exit;
    }

    if ($acao === 'excluir_reserva') {
        $stmt = $pdo->prepare("DELETE FROM reservas WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        header("Location: reservas.php?msg=Reserva removida!");
        exit;
    }
}

// 3. BUSCA DE DADOS
$query = "
    SELECT r.*, 
           p.nome as produto_nome, 
           f.nome as filial_nome,
           u.nome_completo as usuario_sistema
    FROM reservas r
    LEFT JOIN produtos p ON r.produto_id = p.id
    LEFT JOIN filiais f ON r.filial_preferida_id = f.id
    LEFT JOIN usuarios u ON r.usuario_id = u.id
    ORDER BY r.data_solicitacao DESC
";
$reservas = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Reservas - Jombaca</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <script src="../assets/js/sweetalert2.all.min.js"></script>
    <style>
        body { background: #f8f9fa; }
        .status-nova { background-color: #0dcaf0; color: #000; }
        .status-contactado { background-color: #ffc107; color: #000; }
        /* Alterado de reservado para entregue */
        .status-entregue { background-color: #198754; color: #fff; } 
        .status-indisponivel { background-color: #6c757d; color: #fff; }
        .status-cancelada { background-color: #dc3545; color: #fff; }
        .badge-status { width: 110px; padding: 8px; font-size: 0.75rem; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="container-fluid py-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Painel</a>
            <h2 class="fw-bold text-success">Gestão de Reservas</h2>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelaReservas">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Produto / Qtd</th>
                        <th>Filial</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $r): ?>
                    <tr>
                        <td>
                            <small class="d-block fw-bold"><?= date('d/m/Y', strtotime($r['data_solicitacao'])) ?></small>
                            <small class="text-muted"><?= date('H:i', strtotime($r['data_solicitacao'])) ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($r['nome_contato']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($r['telefone_contato']) ?></small>
                        </td>
                        <td>
                            <span class="text-success fw-bold"><?= htmlspecialchars($r['produto_nome'] ?: 'N/A') ?></span><br>
                            <small>Qtd: <?= $r['quantidade_solicitada'] ?></small>
                        </td>
                        <td><?= htmlspecialchars($r['filial_nome'] ?: 'Geral') ?></td>
                        <td>
                            <span class="badge badge-status status-<?= $r['status'] ?>">
                                <?= ($r['status'] === 'reservado') ? 'entregue' : $r['status'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-dark" onclick='verDetalhes(<?= json_encode($r) ?>)'><i class="bi bi-eye"></i></button>
                            <button class="btn btn-sm btn-primary" onclick='alterarStatus(<?= $r['id'] ?>, "<?= $r['status'] ?>")'><i class="bi bi-arrow-repeat"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?= $r['id'] ?>)"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalStatus" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form class="modal-content" method="POST">
            <input type="hidden" name="acao" value="atualizar_status">
            <input type="hidden" name="id" id="status_id">
            <div class="modal-header">
                <h5 class="modal-title">Status da Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select name="status" id="select_status" class="form-select">
                    <option value="nova">Nova</option>
                    <option value="contactado">Contactado</option>
                    <option value="entregue">Entregue</option>
                    <option value="indisponivel">Indisponível</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100">Salvar Alteração</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Detalhes da Solicitação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>E-mail:</strong> <span id="det_email"></span></p>
                <p><strong>Observações:</strong></p>
                <div class="p-3 bg-light border rounded" id="det_obs"></div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    const modalDetalhes = new bootstrap.Modal(document.getElementById('modalDetalhes'));
    const modalStatus = new bootstrap.Modal(document.getElementById('modalStatus'));

    function verDetalhes(r) {
        document.getElementById('det_email').innerText = r.email_contato || 'N/A';
        document.getElementById('det_obs').innerText = r.observacoes || 'Sem observações.';
        modalDetalhes.show();
    }

    function alterarStatus(id, atual) {
        document.getElementById('status_id').value = id;
        // Se no banco ainda estiver 'reservado', o JS seleciona 'entregue' no select
        document.getElementById('select_status').value = (atual === 'reservado') ? 'entregue' : atual;
        modalStatus.show();
    }

    function confirmarExclusao(id) {
        Swal.fire({
            title: 'Eliminar?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, apagar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="acao" value="excluir_reserva"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

<?php if(isset($_GET['msg'])): ?>
<script>Swal.fire('Sucesso', '<?= htmlspecialchars($_GET['msg']) ?>', 'success');</script>
<?php endif; ?>

</body>
</html>