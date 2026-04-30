<?php

function require_login(): void
{
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        header('Location: /login.php?msg=Faça login para continuar');
        exit;
    }
}

function get_perfil_interno(): string
{
    return $_SESSION['perfil_interno'] ?? '';
}

function is_admin_role(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

function is_admin_principal(): bool
{
    return is_admin_role() && get_perfil_interno() === 'admin_principal';
}

function is_funcionario(): bool
{
    return is_admin_role() && get_perfil_interno() === 'funcionario';
}

function require_admin_any(): void
{
    require_login();
    if (!is_admin_role()) {
        header('Location: /login.php?msg=Acesso restrito');
        exit;
    }
}

function require_admin_principal_only(): void
{
    require_admin_any();
    if (!is_admin_principal()) {
        header('Location: /dashboard.php?msg=Acesso permitido apenas ao Admin Principal');
        exit;
    }
}

/** Link «Voltar ao painel» a partir de páginas em www/extends/ */
function painel_voltar_desde_extends(): string
{
    return is_funcionario() ? '../dashboard_funcionario.php' : '../dashboard.php';
}

