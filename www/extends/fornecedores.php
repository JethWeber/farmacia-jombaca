<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';

require_admin_principal_only();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'salvar') {
        $id = (int)($_POST['id'] ?? 0);
        $dados = [
            trim($_POST['nome'] ?? ''),
            trim($_POST['telefone'] ?? ''),
            trim($_POST['email'] ?? ''),
            trim($_POST['endereco'] ?? ''),
            trim($_POST['observacoes'] ?? '')
        ];
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome=?, telefone=?, email=?, endereco=?, observacoes=? WHERE id=?");
            $stmt->execute([...$dados, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, telefone, email, endereco, observacoes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($dados);
        }
        header('Location: fornecedores.php');
        exit;
    }
}

$fornecedores = $pdo->query("SELECT * FROM fornecedores ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Fornecedores</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">Voltar ao painel</a>
    <h2 class="text-success fw-bold mb-4">Fornecedores</h2>
    <div class="card card-body border-0 shadow-sm mb-4">
        <form method="POST" class="row g-2">
            <input type="hidden" name="acao" value="salvar">
            <div class="col-md-4"><input class="form-control" name="nome" placeholder="Nome" required></div>
            <div class="col-md-3"><input class="form-control" name="telefone" placeholder="Telefone"></div>
            <div class="col-md-3"><input class="form-control" name="email" placeholder="Email"></div>
            <div class="col-md-2"><button class="btn btn-success w-100">Salvar</button></div>
            <div class="col-12"><input class="form-control" name="endereco" placeholder="Endereço"></div>
            <div class="col-12"><textarea class="form-control" name="observacoes" placeholder="Observações"></textarea></div>
        </form>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Nome</th><th>Telefone</th><th>Email</th><th>Endereço</th></tr></thead>
                <tbody>
                    <?php foreach ($fornecedores as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['nome']) ?></td>
                            <td><?= htmlspecialchars($f['telefone']) ?></td>
                            <td><?= htmlspecialchars($f['email']) ?></td>
                            <td><?= htmlspecialchars($f['endereco']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
