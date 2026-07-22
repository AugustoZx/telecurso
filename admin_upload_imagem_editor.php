<?php
/**
 * Endpoint AJAX chamado pelo botão de imagem do Quill (admin_cursos.php)
 * ao inserir uma imagem NO MEIO do texto da aula. Retorna JSON com a URL
 * do arquivo salvo, para o editor inserir um <img> apontando para ele
 * (em vez de gravar a imagem em base64 dentro do HTML).
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['aluno_id']) || empty($_SESSION['adm']) || (int) $_SESSION['adm'] !== 1) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

include_once('lib_upload.php');

if (empty($_FILES['imagem']['name'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Nenhuma imagem enviada.']);
    exit;
}

try {
    $caminho = upload_imagem_editor($_FILES['imagem']);
    echo json_encode(['url' => $caminho]);
} catch (RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['erro' => $e->getMessage()]);
}
