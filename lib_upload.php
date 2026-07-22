<?php
/**
 * Upload de mídia das aulas (imagem/vídeo).
 * Usado pelo painel admin_cursos.php ao criar/editar uma aula.
 */

define('UPLOAD_DIR_REL', 'uploads/aulas');
define('UPLOAD_DIR_ABS', __DIR__ . '/uploads/aulas');

const MIDIA_EXTENSOES = [
    'imagem' => ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'],
    'video'  => ['mp4' => 'video/mp4', 'webm' => 'video/webm'],
];

const MIDIA_TAMANHO_MAX = [
    'imagem' => 5 * 1024 * 1024,  // 5 MB
    'video'  => 35 * 1024 * 1024, // 35 MB (o php.ini precisa permitir upload/post desse tamanho)
];

/**
 * Valida e move um arquivo enviado, respeitando a whitelist de extensões/MIME.
 *
 * @throws RuntimeException com mensagem amigável se o arquivo for inválido
 * @return array|null ['tipo' => 'imagem'|'video', 'caminho_rel' => '...'] ou null se nada foi enviado
 */
function _processar_upload(array $arquivo, array $tipos_permitidos, $dir_rel, $dir_abs) {
    if (!isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // campo de upload deixado em branco, não é erro
    }
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio do arquivo (código ' . $arquivo['error'] . '). Verifique o tamanho do arquivo e tente novamente.');
    }
    if (!is_uploaded_file($arquivo['tmp_name'])) {
        throw new RuntimeException('Upload inválido.');
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    $tipo = null;
    foreach ($tipos_permitidos as $categoria => $extensoes) {
        if (isset($extensoes[$extensao])) {
            $tipo = $categoria;
            break;
        }
    }
    if ($tipo === null) {
        throw new RuntimeException('Tipo de arquivo não permitido.');
    }

    if ($arquivo['size'] > MIDIA_TAMANHO_MAX[$tipo]) {
        $limite_mb = round(MIDIA_TAMANHO_MAX[$tipo] / 1024 / 1024);
        throw new RuntimeException("Arquivo muito grande. O limite para {$tipo} é de {$limite_mb}MB.");
    }

    // Confirma o tipo real do arquivo (não confia só na extensão/no que o navegador informou)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_real = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    $mimes_permitidos = array_values($tipos_permitidos[$tipo]);
    if (!in_array($mime_real, $mimes_permitidos, true)) {
        throw new RuntimeException('O conteúdo do arquivo não corresponde a uma ' . ($tipo === 'imagem' ? 'imagem' : 'vídeo') . ' válida.');
    }

    if (!is_dir($dir_abs)) {
        mkdir($dir_abs, 0775, true);
    }

    $nome_arquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
    $destino_abs  = $dir_abs . '/' . $nome_arquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino_abs)) {
        throw new RuntimeException('Não foi possível salvar o arquivo enviado.');
    }

    return ['tipo' => $tipo, 'caminho_rel' => $dir_rel . '/' . $nome_arquivo];
}

/**
 * Processa o upload de mídia de uma aula (imagem OU vídeo).
 *
 * @param array $arquivo Um item de $_FILES (ex.: $_FILES['midia'])
 * @return array|null ['midia_tipo' => 'imagem'|'video', 'midia_arquivo' => 'uploads/aulas/xxx.ext'] ou null se nenhum arquivo foi enviado
 * @throws RuntimeException
 */
function upload_midia_aula(array $arquivo) {
    $r = _processar_upload($arquivo, MIDIA_EXTENSOES, UPLOAD_DIR_REL, UPLOAD_DIR_ABS);
    if ($r === null) return null;
    proteger_pasta_upload(UPLOAD_DIR_ABS);
    return ['midia_tipo' => $r['tipo'], 'midia_arquivo' => $r['caminho_rel']];
}

/**
 * Processa o upload de uma imagem inserida NO MEIO do texto pelo editor
 * (botão de imagem do Quill, em admin_cursos.php). Salva na mesma pasta
 * das mídias de aula, já que passa pela mesma validação/whitelist.
 *
 * @return string|null Caminho relativo do arquivo, ou null se nada foi enviado
 * @throws RuntimeException
 */
function upload_imagem_editor(array $arquivo) {
    $tipos = ['imagem' => MIDIA_EXTENSOES['imagem']];
    $r = _processar_upload($arquivo, $tipos, UPLOAD_DIR_REL, UPLOAD_DIR_ABS);
    if ($r !== null) {
        proteger_pasta_upload(UPLOAD_DIR_ABS);
    }
    return $r === null ? null : $r['caminho_rel'];
}

/**
 * Processa o upload da imagem de capa de um curso.
 *
 * @return string|null Caminho relativo do arquivo, ou null se nada foi enviado
 * @throws RuntimeException
 */
function upload_imagem_curso(array $arquivo) {
    $tipos = ['imagem' => MIDIA_EXTENSOES['imagem']];
    $dir_abs = __DIR__ . '/uploads/cursos';
    $r = _processar_upload($arquivo, $tipos, 'uploads/cursos', $dir_abs);
    if ($r !== null) {
        proteger_pasta_upload($dir_abs);
    }
    return $r === null ? null : $r['caminho_rel'];
}

/** Garante que uma pasta de upload tenha .htaccess (bloqueia PHP) e index.html (bloqueia listagem). */
function proteger_pasta_upload($dir_abs) {
    $htaccess = $dir_abs . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "php_flag engine off\nAddHandler cgi-script .php .phtml .php3 .php4 .php5 .php7 .pl .py .cgi .asp\nOptions -Indexes -ExecCGI\n");
    }
    $indexHtml = $dir_abs . '/index.html';
    if (!file_exists($indexHtml)) {
        file_put_contents($indexHtml, '');
    }
}

/** Remove o arquivo de mídia de uma aula do disco, se existir. */
function remover_midia_aula($caminho_relativo) {
    if (empty($caminho_relativo)) return;
    $abs = __DIR__ . '/' . $caminho_relativo;
    if (is_file($abs)) {
        @unlink($abs);
    }
}
