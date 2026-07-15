<?php
    session_start();

    /* -------- Proteção: precisa estar logado E ser administrador -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: login.php');
        exit;
    }
    if (empty($_SESSION['adm']) || (int)$_SESSION['adm'] !== 1) {
        header('Location: dashboard.php');
        exit;
    }

    include_once('config.php');

    /* -------- Biblioteca FPDF (instalada via Composer) --------
       Rode uma vez na pasta do projeto:  composer require setasign/fpdf */
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        header('Location: admin.php?cert=erro');
        exit;
    }
    require_once $autoload;

    /* ==================================================================
       Configuração do certificado (curso de Excel / Informática Básica)
    ================================================================== */
    $CURSO_ID        = 'excel';              // curso alvo
    $CURSO_AULA_FINAL = 3;                    // última aula que libera o certificado
    $CURSO_NOME      = 'Informática Básica';  // nome impresso no certificado
    $CARGA_HORARIA   = '30 horas';            // carga horária impressa
    $DIR_CERT        = __DIR__ . '/certificados'; // pasta onde os PDFs ficam salvos
    $MODELO          = __DIR__ . '/img/certificado_modelo.png'; // fundo do certificado

    $aluno_id = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : 0;
    if ($aluno_id <= 0) {
        header('Location: admin.php?cert=erro');
        exit;
    }

    /* -------- Busca o aluno -------- */
    $stmt = $conexao->prepare("SELECT id, nome FROM alunos WHERE id = ?");
    $stmt->bind_param("i", $aluno_id);
    $stmt->execute();
    $aluno = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$aluno) {
        header('Location: admin.php?cert=erro');
        exit;
    }

    /* -------- Verifica se concluiu a última aula do curso -------- */
    $stmt = $conexao->prepare(
        "SELECT `data` FROM aulas_concluidas
         WHERE aluno_id = ? AND curso_id = ? AND aula_num = ?
         LIMIT 1"
    );
    $stmt->bind_param("isi", $aluno_id, $CURSO_ID, $CURSO_AULA_FINAL);
    $stmt->execute();
    $conclusao = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$conclusao) {
        header('Location: admin.php?cert=incompleto');
        exit;
    }

    // Data de conclusão (fallback para a data atual, se vier vazia)
    $data_conclusao = !empty($conclusao['data']) ? strtotime($conclusao['data']) : time();

    /* ==================================================================
       Reaproveita certificado já emitido para este aluno + curso
    ================================================================== */
    if (!is_dir($DIR_CERT)) {
        @mkdir($DIR_CERT, 0775, true);
    }

    $stmt = $conexao->prepare(
        "SELECT codigo, arquivo FROM certificados
         WHERE aluno_id = ? AND curso_id = ?
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param("is", $aluno_id, $CURSO_ID);
    $stmt->execute();
    $existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existente && !empty($existente['arquivo']) && file_exists(__DIR__ . '/' . $existente['arquivo'])) {
        entregar_pdf(__DIR__ . '/' . $existente['arquivo'], $existente['codigo']);
        exit;
    }

    /* -------- Gera um código único para o certificado -------- */
    $codigo = 'CERTIFICADO-CONCLUSAO' . str_pad((string)$aluno_id, 4, '0', STR_PAD_LEFT) . '-' . date('Ymd', $data_conclusao);
    // Garante unicidade (coluna codigo é UNIQUE)
    $sufixo = 1;
    $codigo_base = $codigo;
    while (true) {
        $chk = $conexao->prepare("SELECT id FROM certificados WHERE codigo = ?");
        $chk->bind_param("s", $codigo);
        $chk->execute();
        $chk->store_result();
        $dup = $chk->num_rows > 0;
        $chk->close();
        if (!$dup) break;
        $codigo = $codigo_base . '-' . (++$sufixo);
    }

    /* ==================================================================
       Monta o PDF sobre o modelo (A4 paisagem: 297 x 210 mm)
    ================================================================== */
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    $dia = date('d', $data_conclusao);
    $mes = $meses[(int)date('n', $data_conclusao)];
    $ano = date('Y', $data_conclusao);

    // Converte UTF-8 -> Windows-1252 (fontes core do FPDF usam Latin-1/cp1252)
    function enc($txt) {
        $out = @iconv('UTF-8', 'windows-1252//TRANSLIT', $txt);
        return $out !== false ? $out : $txt;
    }

    $pdf = new \FPDF('L', 'mm', 'A4'); // Landscape, milímetros, A4
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);

    // Fundo (modelo do certificado) ocupando a página inteira
    if (file_exists($MODELO)) {
        $pdf->Image($MODELO, 0, 0, 297, 210);
    }

    // Cor azul-marinho do modelo
    $pdf->SetTextColor(27, 62, 147);

    // Nome do aluno (centralizado sobre a linha)
    $pdf->SetFont('Arial', 'B', 22);
    $nome = enc($aluno['nome']);
    $largura_nome = $pdf->GetStringWidth($nome);
    $pdf->Text((297 - $largura_nome) / 2, 71.4, $nome);
    
    // Nome do curso
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Text(150, 81.6, enc($CURSO_NOME));

    // Carga horária
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Text(183.4, 92.2, enc($CARGA_HORARIA));

    // Data de conclusão: dia / mês / ano (nos espaços do modelo)
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Text(136.9, 110, enc($dia));
    $pdf->Text(160, 110, enc($mes));
    $pdf->Text(196.5, 110, enc($ano));

    /* -------- Salva o arquivo no servidor -------- */
    $nome_arquivo = 'certificado_' . $codigo . '.pdf';
    $caminho_abs  = $DIR_CERT . '/' . $nome_arquivo;
    $caminho_rel  = 'certificados/' . $nome_arquivo; // caminho relativo salvo no banco
    $pdf->Output('F', $caminho_abs);

    /* -------- Registra no banco de dados -------- */
    $ins = $conexao->prepare(
        "INSERT INTO certificados (codigo, aluno_id, curso_id, data_emissao, arquivo)
         VALUES (?, ?, ?, NOW(), ?)"
    );
    $ins->bind_param("siss", $codigo, $aluno_id, $CURSO_ID, $caminho_rel);
    $ins->execute();
    $ins->close();

    /* -------- Entrega o PDF ao administrador -------- */
    entregar_pdf($caminho_abs, $codigo);
    exit;

    /* ==================================================================
       Envia o PDF ao navegador (inline). Use ?download=1 para baixar.
    ================================================================== */
    function entregar_pdf($caminho_abs, $codigo) {
        if (!file_exists($caminho_abs)) {
            header('Location: admin.php?cert=erro');
            exit;
        }
        $disposicao = (isset($_GET['download']) && $_GET['download'] == '1') ? 'attachment' : 'inline';
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $disposicao . '; filename="certificado_' . $codigo . '.pdf"');
        header('Content-Length: ' . filesize($caminho_abs));
        readfile($caminho_abs);
    }

    