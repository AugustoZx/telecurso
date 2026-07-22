<?php
/**
 * Sanitização do HTML digitado pelo admin no conteúdo das aulas.
 * Antes o texto vinha direto de cursos.php (código-fonte, confiável).
 * Agora vem de um formulário, então: fecha tags quebradas e remove
 * tags/atributos perigosos, mantendo só uma lista curta permitida.
 */

const AULA_TAGS_PERMITIDAS = '<p><br><b><strong><i><em><ul><ol><li><a><h2><h3><h4><blockquote><img>';

// Só aceita <img> apontando para um arquivo já validado e salvo pelo próprio
// upload da aula/editor (nome gerado por bin2hex(random_bytes(16))). Bloqueia
// data: URIs (imagem embutida em base64) e links para fora do site.
const IMG_SRC_PERMITIDO = '#^uploads/aulas/[a-f0-9]{32}\.(jpg|jpeg|png|gif|webp)$#';

function sanitizar_html_aula($html) {
    $html = trim((string) $html);
    if ($html === '') return '';

    // 1) Remove tags fora da allow-list (script, style, iframe, etc.)
    $html = strip_tags($html, AULA_TAGS_PERMITIDAS);

    // 2) Fecha tags quebradas via DOMDocument (ex.: admin esqueceu de fechar <ul>)
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="utf-8"?><div>' . $html . '</div>',
        LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $wrapper = $doc->getElementsByTagName('div')->item(0);
    if ($wrapper === null) return '';

    // 3) Remove atributos perigosos (on*, href/src com esquema javascript:)
    remover_atributos_perigosos($wrapper);

    // 4) <img>: mantém só src/alt, e só se src for um arquivo já enviado pelo upload
    restringir_imagens($wrapper);

    $resultado = '';
    foreach (iterator_to_array($wrapper->childNodes) as $filho) {
        $resultado .= $doc->saveHTML($filho);
    }
    return trim($resultado);
}

function restringir_imagens(DOMNode $wrapper) {
    $imagens = iterator_to_array($wrapper->getElementsByTagName('img'));
    foreach ($imagens as $img) {
        $src = $img->getAttribute('src');
        if (!preg_match(IMG_SRC_PERMITIDO, $src)) {
            $img->parentNode->removeChild($img);
            continue;
        }
        $alt = $img->getAttribute('alt');
        foreach (iterator_to_array($img->attributes) as $attr) {
            $img->removeAttribute($attr->name);
        }
        $img->setAttribute('src', $src);
        if ($alt !== '') $img->setAttribute('alt', $alt);
    }
}

function remover_atributos_perigosos(DOMNode $node) {
    if ($node->hasAttributes()) {
        $remover = [];
        foreach ($node->attributes as $attr) {
            $nome = strtolower($attr->name);
            $valor = trim(strtolower($attr->value));
            if (strpos($nome, 'on') === 0 || strpos($valor, 'javascript:') === 0) {
                $remover[] = $attr->name;
            }
        }
        foreach ($remover as $nome) {
            $node->removeAttribute($nome);
        }
    }
    foreach (iterator_to_array($node->childNodes) as $filho) {
        if ($filho->nodeType === XML_ELEMENT_NODE) {
            remover_atributos_perigosos($filho);
        }
    }
}
