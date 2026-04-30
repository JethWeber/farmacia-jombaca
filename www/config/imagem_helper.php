<?php
declare(strict_types=1);

/**
 * Caminho web relativo à raiz do site (pasta www) para <img src="...">.
 * Se o ficheiro não existir no disco ou o valor for vazio, devolve placeholder.
 */
function farmacia_imagem_publica(?string $caminhoBd, string $placeholder = 'assets/img/placeholder-produto.svg'): string
{
    $g = trim((string) $caminhoBd);
    if ($g === '') {
        return $placeholder;
    }
    $g = str_replace('\\', '/', $g);
    $g = ltrim($g, '/');
    if (str_starts_with($g, 'http://') || str_starts_with($g, 'https://')) {
        return $g;
    }
    $wwwRoot = dirname(__DIR__);
    $full = $wwwRoot . '/' . $g;
    if (!is_file($full)) {
        return $placeholder;
    }
    return $g;
}
