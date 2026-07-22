<?php
    session_start();

    /* -------- Proteção dupla: precisa estar logado E ser administrador -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: index.php');
        exit;
    }
    if (empty($_SESSION['adm']) || (int)$_SESSION['adm'] !== 1) {
        header('Location: dashboard.php');
        exit;
    }

    include_once('config.php');
    include_once('cursos.php');
    include_once('lib_upload.php');
    include_once('lib_html.php');

    /* -------- Helpers -------- */

    function flash($tipo, $texto) {
        $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
    }

    function gerar_slug($conexao, $nome) {
        $base = strtolower($nome);
        $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT', $base);
        if ($convertido !== false) $base = $convertido;
        $base = preg_replace('/[^a-z0-9]+/', '-', $base);
        $base = trim($base, '-');
        if ($base === '') $base = 'curso';

        $slug = $base;
        $i = 2;
        while (true) {
            $chk = $conexao->prepare("SELECT id FROM cursos WHERE slug = ?");
            $chk->bind_param("s", $slug);
            $chk->execute();
            $chk->store_result();
            $existe = $chk->num_rows > 0;
            $chk->close();
            if (!$existe) break;
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    // Existe algum registro de conclusão de aula para este curso (slug)?
    function curso_tem_progresso($conexao, $slug) {
        $stmt = $conexao->prepare("SELECT COUNT(*) AS n FROM aulas_concluidas WHERE curso_id = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return (int) $r['n'] > 0;
    }

    function listar_imagens_disponiveis() {
        $padroes = ['*.png', '*.jpg', '*.jpeg', '*.webp', '*.gif'];
        $arquivos = [];
        foreach ($padroes as $p) {
            foreach (glob(__DIR__ . '/img/' . $p) as $caminho) {
                $arquivos[] = 'img/' . basename($caminho);
            }
        }
        sort($arquivos);
        return $arquivos;
    }

    $curso_id_sel = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

    /* ============================================================
       AÇÕES (POST) — padrão PRG (redireciona depois de processar)
       ============================================================ */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
        $acao = $_POST['acao'];

        /* -------- CURSOS -------- */

        if ($acao === 'criar_curso') {
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $nota_minima = max(0, min(100, (int) ($_POST['nota_minima_quiz'] ?? 70)));
            $certifica = isset($_POST['certifica_conclusao']) ? 1 : 0;

            if ($nome === '' || $descricao === '') {
                flash('erro', 'Preencha nome e descrição do curso.');
                header('Location: admin_cursos.php');
                exit;
            }

            try {
                $imagem = null;
                if (!empty($_FILES['imagem_upload']['name'])) {
                    $imagem = upload_imagem_curso($_FILES['imagem_upload']);
                }
                if ($imagem === null) {
                    $imagem = trim($_POST['imagem_existente'] ?? '');
                }
                if ($imagem === '' || $imagem === null) {
                    flash('erro', 'Escolha uma imagem existente ou envie uma nova.');
                    header('Location: admin_cursos.php');
                    exit;
                }

                $slug = gerar_slug($conexao, $nome);
                $res = $conexao->query("SELECT COALESCE(MAX(ordem), 0) + 1 AS prox FROM cursos");
                $ordem = (int) $res->fetch_assoc()['prox'];

                $ins = $conexao->prepare(
                    "INSERT INTO cursos (slug, nome, descricao, imagem, ordem, nota_minima_quiz, certifica_conclusao)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $ins->bind_param("ssssiii", $slug, $nome, $descricao, $imagem, $ordem, $nota_minima, $certifica);
                $ins->execute();

                flash('ok', 'Curso criado com sucesso!');
            } catch (RuntimeException $e) {
                flash('erro', $e->getMessage());
            }
            header('Location: admin_cursos.php');
            exit;
        }

        if ($acao === 'editar_curso') {
            $id = (int) ($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $nota_minima = max(0, min(100, (int) ($_POST['nota_minima_quiz'] ?? 70)));
            $certifica = isset($_POST['certifica_conclusao']) ? 1 : 0;

            if ($id <= 0 || $nome === '' || $descricao === '') {
                flash('erro', 'Preencha nome e descrição do curso.');
                header('Location: admin_cursos.php');
                exit;
            }

            try {
                $imagem = null;
                if (!empty($_FILES['imagem_upload']['name'])) {
                    $imagem = upload_imagem_curso($_FILES['imagem_upload']);
                }
                if ($imagem === null) {
                    $imagem = trim($_POST['imagem_existente'] ?? '');
                }
                if ($imagem === '') {
                    flash('erro', 'Escolha uma imagem existente ou envie uma nova.');
                    header('Location: admin_cursos.php');
                    exit;
                }

                $upd = $conexao->prepare(
                    "UPDATE cursos SET nome=?, descricao=?, imagem=?, nota_minima_quiz=?, certifica_conclusao=? WHERE id=?"
                );
                $upd->bind_param("sssiii", $nome, $descricao, $imagem, $nota_minima, $certifica, $id);
                $upd->execute();

                flash('ok', 'Curso atualizado com sucesso!');
            } catch (RuntimeException $e) {
                flash('erro', $e->getMessage());
            }
            header('Location: admin_cursos.php');
            exit;
        }

        if ($acao === 'excluir_curso') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $conexao->prepare("SELECT slug FROM cursos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $curso = $stmt->get_result()->fetch_assoc();

            if (!$curso) {
                flash('erro', 'Curso não encontrado.');
            } elseif (curso_tem_progresso($conexao, $curso['slug'])) {
                flash('erro', 'Não é possível excluir: alunos já têm progresso registrado neste curso. Desative-o em vez de excluir.');
            } else {
                // Apaga arquivos de mídia das aulas deste curso antes de excluir
                $res = $conexao->prepare("SELECT midia_arquivo FROM aulas WHERE curso_id = ? AND midia_arquivo IS NOT NULL");
                $res->bind_param("i", $id);
                $res->execute();
                $arquivos = $res->get_result();
                while ($a = $arquivos->fetch_assoc()) {
                    remover_midia_aula($a['midia_arquivo']);
                }

                $del = $conexao->prepare("DELETE FROM cursos WHERE id = ?");
                $del->bind_param("i", $id);
                $del->execute();
                flash('ok', 'Curso excluído com sucesso!');
            }
            header('Location: admin_cursos.php');
            exit;
        }

        if ($acao === 'toggle_ativo_curso') {
            $id = (int) ($_POST['id'] ?? 0);
            $conexao->query("UPDATE cursos SET ativo = 1 - ativo WHERE id = " . $id);
            flash('ok', 'Status do curso atualizado.');
            header('Location: admin_cursos.php');
            exit;
        }

        if ($acao === 'mover_curso') {
            $id = (int) ($_POST['id'] ?? 0);
            $direcao = $_POST['direcao'] ?? '';

            $res = $conexao->query("SELECT id, ordem FROM cursos ORDER BY ordem ASC, id ASC");
            $lista = [];
            while ($l = $res->fetch_assoc()) $lista[] = $l;

            $idx = null;
            foreach ($lista as $i => $l) {
                if ((int) $l['id'] === $id) { $idx = $i; break; }
            }

            if ($idx !== null) {
                $vizinho = null;
                if ($direcao === 'cima' && $idx > 0) $vizinho = $lista[$idx - 1];
                if ($direcao === 'baixo' && $idx < count($lista) - 1) $vizinho = $lista[$idx + 1];

                if ($vizinho !== null) {
                    $stmt = $conexao->prepare("UPDATE cursos SET ordem = ? WHERE id = ?");
                    $stmt->bind_param("ii", $vizinho['ordem'], $id);
                    $stmt->execute();
                    $stmt->bind_param("ii", $lista[$idx]['ordem'], $vizinho['id']);
                    $stmt->execute();
                }
            }
            header('Location: admin_cursos.php');
            exit;
        }

        /* -------- AULAS -------- */

        if ($acao === 'criar_aula') {
            $curso_id = (int) ($_POST['curso_id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $conteudo = sanitizar_html_aula($_POST['conteudo'] ?? '');

            if ($curso_id <= 0 || $titulo === '' || trim(strip_tags($conteudo)) === '') {
                flash('erro', 'Preencha o título e o conteúdo da aula.');
                header('Location: admin_cursos.php?curso_id=' . $curso_id);
                exit;
            }

            try {
                $midia_tipo = 'nenhuma';
                $midia_arquivo = null;
                if (!empty($_FILES['midia']['name'])) {
                    $m = upload_midia_aula($_FILES['midia']);
                    if ($m !== null) {
                        $midia_tipo = $m['midia_tipo'];
                        $midia_arquivo = $m['midia_arquivo'];
                    }
                }

                $res = $conexao->prepare("SELECT COALESCE(MAX(ordem), 0) + 1 AS prox FROM aulas WHERE curso_id = ?");
                $res->bind_param("i", $curso_id);
                $res->execute();
                $ordem = (int) $res->get_result()->fetch_assoc()['prox'];

                $ins = $conexao->prepare(
                    "INSERT INTO aulas (curso_id, ordem, titulo, conteudo, midia_tipo, midia_arquivo)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $ins->bind_param("iissss", $curso_id, $ordem, $titulo, $conteudo, $midia_tipo, $midia_arquivo);
                $ins->execute();

                flash('ok', 'Aula criada com sucesso!');
            } catch (RuntimeException $e) {
                flash('erro', $e->getMessage());
            }
            header('Location: admin_cursos.php?curso_id=' . $curso_id);
            exit;
        }

        if ($acao === 'editar_aula') {
            $id = (int) ($_POST['id'] ?? 0);
            $curso_id = (int) ($_POST['curso_id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $conteudo = sanitizar_html_aula($_POST['conteudo'] ?? '');
            $remover_midia = isset($_POST['remover_midia']);

            if ($id <= 0 || $titulo === '' || trim(strip_tags($conteudo)) === '') {
                flash('erro', 'Preencha o título e o conteúdo da aula.');
                header('Location: admin_cursos.php?curso_id=' . $curso_id);
                exit;
            }

            try {
                $stmt = $conexao->prepare("SELECT midia_tipo, midia_arquivo FROM aulas WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $atual = $stmt->get_result()->fetch_assoc();

                $midia_tipo = $atual['midia_tipo'];
                $midia_arquivo = $atual['midia_arquivo'];

                if (!empty($_FILES['midia']['name'])) {
                    $m = upload_midia_aula($_FILES['midia']);
                    if ($m !== null) {
                        remover_midia_aula($midia_arquivo);
                        $midia_tipo = $m['midia_tipo'];
                        $midia_arquivo = $m['midia_arquivo'];
                    }
                } elseif ($remover_midia) {
                    remover_midia_aula($midia_arquivo);
                    $midia_tipo = 'nenhuma';
                    $midia_arquivo = null;
                }

                $upd = $conexao->prepare(
                    "UPDATE aulas SET titulo=?, conteudo=?, midia_tipo=?, midia_arquivo=? WHERE id=?"
                );
                $upd->bind_param("ssssi", $titulo, $conteudo, $midia_tipo, $midia_arquivo, $id);
                $upd->execute();

                flash('ok', 'Aula atualizada com sucesso!');
            } catch (RuntimeException $e) {
                flash('erro', $e->getMessage());
            }
            header('Location: admin_cursos.php?curso_id=' . $curso_id);
            exit;
        }

        if ($acao === 'excluir_aula') {
            $id = (int) ($_POST['id'] ?? 0);
            $curso_id = (int) ($_POST['curso_id'] ?? 0);

            $stmt = $conexao->prepare("SELECT slug FROM cursos WHERE id = ?");
            $stmt->bind_param("i", $curso_id);
            $stmt->execute();
            $curso = $stmt->get_result()->fetch_assoc();

            if ($curso && curso_tem_progresso($conexao, $curso['slug'])) {
                flash('erro', 'Não é possível excluir: alunos já concluíram aulas deste curso.');
            } else {
                $stmt = $conexao->prepare("SELECT midia_arquivo FROM aulas WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $aula = $stmt->get_result()->fetch_assoc();
                if ($aula) remover_midia_aula($aula['midia_arquivo']);

                $del = $conexao->prepare("DELETE FROM aulas WHERE id = ?");
                $del->bind_param("i", $id);
                $del->execute();

                // Renumera a ordem das aulas restantes (1..N), já que é seguro (sem progresso)
                $res = $conexao->prepare("SELECT id FROM aulas WHERE curso_id = ? ORDER BY ordem ASC");
                $res->bind_param("i", $curso_id);
                $res->execute();
                $restantes = $res->get_result();
                $nova_ordem = 1;
                $upd = $conexao->prepare("UPDATE aulas SET ordem = ? WHERE id = ?");
                while ($r = $restantes->fetch_assoc()) {
                    $upd->bind_param("ii", $nova_ordem, $r['id']);
                    $upd->execute();
                    $nova_ordem++;
                }

                flash('ok', 'Aula excluída com sucesso!');
            }
            header('Location: admin_cursos.php?curso_id=' . $curso_id);
            exit;
        }

        if ($acao === 'mover_aula') {
            $id = (int) ($_POST['id'] ?? 0);
            $curso_id = (int) ($_POST['curso_id'] ?? 0);
            $direcao = $_POST['direcao'] ?? '';

            $stmt = $conexao->prepare("SELECT slug FROM cursos WHERE id = ?");
            $stmt->bind_param("i", $curso_id);
            $stmt->execute();
            $curso = $stmt->get_result()->fetch_assoc();

            if ($curso && curso_tem_progresso($conexao, $curso['slug'])) {
                flash('erro', 'Não é possível reordenar: alunos já concluíram aulas deste curso.');
                header('Location: admin_cursos.php?curso_id=' . $curso_id);
                exit;
            }

            $res = $conexao->prepare("SELECT id, ordem FROM aulas WHERE curso_id = ? ORDER BY ordem ASC, id ASC");
            $res->bind_param("i", $curso_id);
            $res->execute();
            $lista = [];
            $r = $res->get_result();
            while ($l = $r->fetch_assoc()) $lista[] = $l;

            $idx = null;
            foreach ($lista as $i => $l) {
                if ((int) $l['id'] === $id) { $idx = $i; break; }
            }

            if ($idx !== null) {
                $vizinho = null;
                if ($direcao === 'cima' && $idx > 0) $vizinho = $lista[$idx - 1];
                if ($direcao === 'baixo' && $idx < count($lista) - 1) $vizinho = $lista[$idx + 1];

                if ($vizinho !== null) {
                    $stmt = $conexao->prepare("UPDATE aulas SET ordem = ? WHERE id = ?");
                    $stmt->bind_param("ii", $vizinho['ordem'], $id);
                    $stmt->execute();
                    $stmt->bind_param("ii", $lista[$idx]['ordem'], $vizinho['id']);
                    $stmt->execute();
                }
            }
            header('Location: admin_cursos.php?curso_id=' . $curso_id);
            exit;
        }

        /* -------- QUIZ -------- */

        if ($acao === 'criar_pergunta' || $acao === 'editar_pergunta') {
            $curso_id = (int) ($_POST['curso_id'] ?? 0);
            $pergunta_id = (int) ($_POST['id'] ?? 0);
            $enunciado = trim($_POST['enunciado'] ?? '');
            $opcoes = array_map('trim', $_POST['opcoes'] ?? []);
            $correta_idx = isset($_POST['correta']) ? (int) $_POST['correta'] : -1;

            $erro = null;
            if ($enunciado === '') {
                $erro = 'Escreva o enunciado da pergunta.';
            } elseif (count($opcoes) < 2) {
                $erro = 'Cadastre pelo menos 2 opções.';
            } elseif (in_array('', $opcoes, true)) {
                $erro = 'Preencha o texto de todas as opções (ou remova as vazias).';
            } elseif ($correta_idx < 0 || $correta_idx >= count($opcoes)) {
                $erro = 'Selecione qual opção é a correta.';
            }

            if ($erro !== null) {
                flash('erro', $erro);
                header('Location: admin_cursos.php?curso_id=' . $curso_id);
                exit;
            }

            $conexao->begin_transaction();
            try {
                if ($acao === 'criar_pergunta') {
                    $res = $conexao->prepare("SELECT COALESCE(MAX(ordem), 0) + 1 AS prox FROM quiz_perguntas WHERE curso_id = ?");
                    $res->bind_param("i", $curso_id);
                    $res->execute();
                    $ordem = (int) $res->get_result()->fetch_assoc()['prox'];

                    $ins = $conexao->prepare("INSERT INTO quiz_perguntas (curso_id, enunciado, ordem) VALUES (?, ?, ?)");
                    $ins->bind_param("isi", $curso_id, $enunciado, $ordem);
                    $ins->execute();
                    $pergunta_id = $conexao->insert_id;
                } else {
                    $upd = $conexao->prepare("UPDATE quiz_perguntas SET enunciado = ? WHERE id = ?");
                    $upd->bind_param("si", $enunciado, $pergunta_id);
                    $upd->execute();

                    $del = $conexao->prepare("DELETE FROM quiz_opcoes WHERE pergunta_id = ?");
                    $del->bind_param("i", $pergunta_id);
                    $del->execute();
                }

                $ins_op = $conexao->prepare("INSERT INTO quiz_opcoes (pergunta_id, texto, correta, ordem) VALUES (?, ?, ?, ?)");
                foreach ($opcoes as $i => $texto) {
                    $correta = ($i === $correta_idx) ? 1 : 0;
                    $ordem_op = $i + 1;
                    $ins_op->bind_param("isii", $pergunta_id, $texto, $correta, $ordem_op);
                    $ins_op->execute();
                }

                $conexao->commit();
                flash('ok', $acao === 'criar_pergunta' ? 'Pergunta criada com sucesso!' : 'Pergunta atualizada com sucesso!');
            } catch (Exception $e) {
                $conexao->rollback();
                flash('erro', 'Não foi possível salvar a pergunta.');
            }
            header('Location: admin_cursos.php?curso_id=' . $curso_id);
            exit;
        }

        if ($acao === 'excluir_pergunta') {
            $id = (int) ($_POST['id'] ?? 0);
            $curso_id = (int) ($_POST['curso_id'] ?? 0);
            $del = $conexao->prepare("DELETE FROM quiz_perguntas WHERE id = ?");
            $del->bind_param("i", $id);
            $del->execute();
            flash('ok', 'Pergunta excluída com sucesso!');
            header('Location: admin_cursos.php?curso_id=' . $curso_id);
            exit;
        }
    }

    /* ============================================================
       LEITURA (GET)
       ============================================================ */
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    $imagens_disponiveis = listar_imagens_disponiveis();

    if ($curso_id_sel > 0) {
        // ---- Modo: gestão de conteúdo de UM curso ----
        $stmt = $conexao->prepare("SELECT * FROM cursos WHERE id = ?");
        $stmt->bind_param("i", $curso_id_sel);
        $stmt->execute();
        $curso_atual = $stmt->get_result()->fetch_assoc();

        if (!$curso_atual) {
            header('Location: admin_cursos.php');
            exit;
        }

        $tem_progresso = curso_tem_progresso($conexao, $curso_atual['slug']);

        $stmt = $conexao->prepare("SELECT * FROM aulas WHERE curso_id = ? ORDER BY ordem ASC");
        $stmt->bind_param("i", $curso_id_sel);
        $stmt->execute();
        $aulas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt = $conexao->prepare("SELECT * FROM quiz_perguntas WHERE curso_id = ? ORDER BY ordem ASC");
        $stmt->bind_param("i", $curso_id_sel);
        $stmt->execute();
        $perguntas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($perguntas as &$p) {
            $stmt = $conexao->prepare("SELECT * FROM quiz_opcoes WHERE pergunta_id = ? ORDER BY ordem ASC");
            $stmt->bind_param("i", $p['id']);
            $stmt->execute();
            $p['opcoes'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        unset($p);
    } else {
        // ---- Modo: lista de cursos ----
        $res = $conexao->query(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM aulas a WHERE a.curso_id = c.id) AS total_aulas,
                    (SELECT COUNT(*) FROM quiz_perguntas q WHERE q.curso_id = c.id) AS total_perguntas
             FROM cursos c ORDER BY c.ordem ASC, c.id ASC"
        );
        $cursos_lista = $res->fetch_all(MYSQLI_ASSOC);
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="libs/quill/quill.snow.css">
    <link rel="shortcut icon" type="imagex/png" href="https://salesianossp.org.br/ositaquera/wp-content/uploads/2024/03/wp-favicon-pvi-os-150x150.webp">
    <title>Gerenciar Conteúdo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', sans-serif; color: #193e8f; }
        body { background-color: #eee; }

        nav {
            position: fixed; top: 0; left: 50%; transform: translateX(-50%);
            width: 100%; max-width: 100%; z-index: 1000;
            display: flex; align-items: center; justify-content: space-around;
            padding: 16px 40px; background-color: transparent;
            transition: max-width 0.4s ease, padding 0.4s ease, top 0.4s ease,
                        background-color 0.4s ease, backdrop-filter 0.4s ease,
                        border-radius 0.4s ease, box-shadow 0.4s ease;
        }
        nav.scrolled {
            top: 14px; max-width: 1200px; padding: 10px 32px;
            background-color: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border-radius: 9999px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        .logo img { width: 150px; display: block; transition: width 0.4s ease; }
        nav.scrolled .logo img { width: 120px; }
        .elem ul { display: flex; gap: 25px; }
        .elem ul a { text-decoration: none; font-weight: bold; }
        .elem ul a li { list-style: none; transition: color 0.3s ease; }
        .elem ul a li:hover { color: #ea3e44; }
        .b_login {
            padding: 10px 30px; background-color: #193e8f; font-size: 17px; border: none;
            border-radius: 25px; color: #eee; font-weight: bold; text-decoration: none;
            transition: background-color 0.5s ease, padding 0.4s ease;
        }
        .b_login:hover { background-color: #ea3e44; }
        nav.scrolled .b_login { padding: 8px 26px; }

        main { max-width: 1100px; margin: 0 auto; padding: 110px 16px 60px; }

        .saudacao { margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .saudacao h1 { font-size: 34px; color: #193e8f; }
        .saudacao p { color: #333; font-size: 16px; margin-top: 6px; }
        .breadcrumb { font-size: 14px; color: #777; margin-bottom: 10px; }
        .breadcrumb a { color: #193e8f; text-decoration: none; }
        .breadcrumb a:hover { color: #ea3e44; }

        .btn-cadastrar, .btn-acao, .btn-salvar, .btn-buscar {
            font-family: 'Roboto', sans-serif; cursor: pointer; border: none;
        }
        .btn-cadastrar {
            background: #193e8f; color: #fff; padding: 12px 22px; border-radius: 8px;
            font-size: 15px; font-weight: 500;
        }
        .btn-cadastrar:hover { background: #142f6d; }

        .msg-feedback { padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; font-weight: bold; }
        .msg-feedback.ok  { background: #e3f6e9; color: #1f7a44; }
        .msg-feedback.erro{ background-color: #fdeaea; color: #ea3e44; border: 1px solid #f5c6c8; }

        .aviso-progresso {
            background: #fff4e5; border: 1px solid #ffcf99; color: #8a4b00;
            padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;
        }

        .tabela-wrap { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow-x: auto; margin-bottom: 32px; }
        .tabela-admin { width: 100%; border-collapse: collapse; min-width: 640px; }
        .tabela-admin th, .tabela-admin td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        .tabela-admin thead th { background: #193e8f; color: #fff; font-weight: 500; font-size: 14px; }
        .tabela-admin tbody tr:hover { background: #f5f7fb; }
        .col-centro { text-align: center; }

        .thumb-curso { width: 44px; height: 44px; object-fit: contain; background: #f2f5fc; border-radius: 8px; padding: 4px; }
        .nome-curso { font-weight: 500; color: #222; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; }
        .badge.ativo { background: #e3f6e9; color: #1f7a44; }
        .badge.inativo { background: #eee; color: #888; }
        .badge.midia-nenhuma { background: #eee; color: #888; }
        .badge.midia-imagem { background: #eaf1ff; color: #193e8f; }
        .badge.midia-video { background: #fdeee0; color: #b4590c; }

        .grupo-botoes { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .btn-acao {
            padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: 500;
            color: #fff; transition: background .2s ease; text-decoration: none; display: inline-block;
        }
        .btn-editar { background: #2d6cdf; } .btn-editar:hover { background: #2457b8; }
        .btn-excluir { background: #c53434; } .btn-excluir:hover { background: #a12626; }
        .btn-mover { background: #8a97b3; padding: 5px 9px; } .btn-mover:hover { background: #6f7ba0; }
        .btn-gerenciar { background: #193e8f; } .btn-gerenciar:hover { background: #142f6d; }
        .btn-toggle { background: #7a8699; } .btn-toggle:hover { background: #626c7d; }
        .form-inline { display: inline; margin: 0; }

        .secao-titulo { display: flex; align-items: center; justify-content: space-between; margin: 30px 0 16px; flex-wrap: wrap; gap: 10px; }
        .secao-titulo h2 { font-size: 22px; }

        .vazio { padding: 30px; text-align: center; color: #888; }

        /* ===== Modais (mesmo padrão do admin.php) ===== */
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 2000; align-items: center; justify-content: center;
            padding: 20px; background-color: rgba(25, 62, 143, 0.25);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            opacity: 0; transition: opacity 0.3s ease;
        }
        .modal-overlay.aberto { display: flex; opacity: 1; }
        .modal-box {
            background-color: #ffffff; width: 100%; max-width: 560px; border-radius: 20px;
            padding: 40px; box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3); max-height: 90vh; overflow-y: auto;
            transform: translateY(20px) scale(0.98); transition: transform 0.3s ease;
        }
        .modal-overlay.aberto .modal-box { transform: translateY(0) scale(1); }
        .modal-topo { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 6px; }
        .modal-box h2 { margin: 0; font-size: 24px; font-weight: 800; color: #193e8f; }
        .modal-box .modal-sub { margin: 0 0 22px; font-size: 14px; color: #666; }
        .btn-fechar-modal {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 13px; font-weight: bold;
            color: #193e8f; background-color: #eef1f8; border: none; border-radius: 9999px; cursor: pointer;
        }
        .btn-fechar-modal:hover { background-color: #dfe4f2; }
        .modal-box label { display: block; font-size: 13px; font-weight: bold; color: #193e8f; margin-bottom: 6px; }
        .modal-box .campo { margin-bottom: 16px; }
        .modal-box input[type="text"], .modal-box input[type="number"], .modal-box input[type="file"],
        .modal-box select, .modal-box textarea {
            width: 100%; padding: 12px 14px; font-size: 15px; color: #193e8f; background-color: #f4f6fb;
            border: 1px solid #dfe4f2; border-radius: 10px; outline: none; font-family: 'Roboto', sans-serif;
        }
        .modal-box textarea { min-height: 140px; resize: vertical; }
        .editor-conteudo { background-color: #f4f6fb; border: 1px solid #dfe4f2; border-radius: 10px; overflow: hidden; }
        .editor-conteudo .ql-toolbar { border: none; border-bottom: 1px solid #dfe4f2; font-family: 'Roboto', sans-serif; }
        .editor-conteudo .ql-container { border: none; font-family: 'Roboto', sans-serif; font-size: 15px; }
        .editor-conteudo .ql-editor { min-height: 140px; color: #193e8f; }
        .editor-conteudo .ql-editor img { max-width: 100%; border-radius: 6px; }
        .modal-box input:focus, .modal-box select:focus, .modal-box textarea:focus {
            border-color: #193e8f; box-shadow: 0 0 0 3px rgba(25, 62, 143, 0.15);
        }
        .modal-box .campo-check { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
        .modal-box .campo-check input[type="checkbox"] { width: 17px; height: 17px; accent-color: #193e8f; cursor: pointer; }
        .modal-box .campo-check label { margin: 0; cursor: pointer; }
        .campo-dica { font-size: 12px; color: #888; margin-top: 4px; }
        .modal-acoes .btn-salvar {
            width: 100%; padding: 14px; font-size: 15px; font-weight: bold; color: #ffffff;
            background-color: #193e8f; border-radius: 10px; cursor: pointer; transition: background-color 0.4s ease;
        }
        .modal-acoes .btn-salvar:hover { background-color: #ea3e44; }
        .img-atual { width: 70px; height: 70px; object-fit: contain; background: #f2f5fc; border-radius: 8px; padding: 6px; margin-bottom: 10px; }

        /* Opções do quiz (linhas dinâmicas) */
        .opcao-linha { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .opcao-linha input[type="radio"] { width: 18px; height: 18px; flex-shrink: 0; }
        .opcao-linha input[type="text"] { flex: 1; }
        .btn-remover-opcao {
            background: #fdeaea; color: #ea3e44; border: none; border-radius: 6px; width: 30px; height: 30px;
            flex-shrink: 0; cursor: pointer; font-weight: bold;
        }
        .btn-add-opcao {
            background: #eef1f8; color: #193e8f; border: none; border-radius: 8px; padding: 8px 14px;
            font-size: 13px; font-weight: bold; cursor: pointer; margin-top: 4px;
        }
        .btn-add-opcao:hover { background: #dfe4f2; }

        @media (max-width: 768px) {
            nav { padding: 12px 20px; }
            nav.scrolled { max-width: calc(100% - 24px); border-radius: 24px; }
            .logo img, nav.scrolled .logo img { width: 100px; }
            .saudacao h1 { font-size: 26px; }
            .modal-box { padding: 28px 22px; }
        }
    </style>
</head>
<body class="no-select">

    <nav>
        <div class="logo">
            <a href="admin.php"><img src="img/logo.png" alt="Logo do Telecentro"></a>
        </div>
        <div class="elem">
            <ul>
                <a href="admin.php"><li>ADMIN</li></a>
                <a href="admin_cursos.php"><li>CONTEÚDO</li></a>
                <a href="dashboard.php"><li>MEU PAINEL</li></a>
                <a href="index.php"><li>INÍCIO</li></a>
            </ul>
        </div>
        <a class="b_login" href="logout.php">Sair</a>
    </nav>

    <main>
        <?php if ($flash): ?>
            <p class="msg-feedback <?= $flash['tipo'] ?>"><?= htmlspecialchars($flash['texto']) ?></p>
        <?php endif; ?>

        <?php if ($curso_id_sel === 0): ?>
        <!-- ======================================================
             MODO: LISTA DE CURSOS
             ====================================================== -->
        <header class="saudacao">
            <div>
                <h1>Gerenciar Conteúdo</h1>
                <p>Crie e organize os cursos, aulas e perguntas de quiz da plataforma.</p>
            </div>
            <button type="button" class="btn-cadastrar" onclick="abrirModal('modalCriarCurso')">Novo curso</button>
        </header>

        <section class="tabela-wrap">
            <?php if (empty($cursos_lista)): ?>
                <p class="vazio">Nenhum curso cadastrado ainda.</p>
            <?php else: ?>
            <table class="tabela-admin">
                <thead>
                    <tr>
                        <th>Ordem</th>
                        <th>Curso</th>
                        <th class="col-centro">Aulas</th>
                        <th class="col-centro">Quiz</th>
                        <th class="col-centro">Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos_lista as $i => $c): ?>
                    <tr>
                        <td>
                            <div class="grupo-botoes">
                                <form method="post" class="form-inline">
                                    <input type="hidden" name="acao" value="mover_curso">
                                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                    <input type="hidden" name="direcao" value="cima">
                                    <button type="submit" class="btn-acao btn-mover" title="Mover para cima" <?= $i === 0 ? 'disabled' : '' ?>>&uarr;</button>
                                </form>
                                <form method="post" class="form-inline">
                                    <input type="hidden" name="acao" value="mover_curso">
                                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                    <input type="hidden" name="direcao" value="baixo">
                                    <button type="submit" class="btn-acao btn-mover" title="Mover para baixo" <?= $i === count($cursos_lista) - 1 ? 'disabled' : '' ?>>&darr;</button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <img class="thumb-curso" src="<?= htmlspecialchars($c['imagem']) ?>" alt="">
                                <div>
                                    <span class="nome-curso"><?= htmlspecialchars($c['nome']) ?></span><br>
                                    <?php if ((int) $c['certifica_conclusao'] === 1): ?>
                                        <span class="badge midia-imagem">gera certificado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="col-centro"><?= (int) $c['total_aulas'] ?></td>
                        <td class="col-centro"><?= (int) $c['total_perguntas'] ?></td>
                        <td class="col-centro">
                            <span class="badge <?= (int) $c['ativo'] === 1 ? 'ativo' : 'inativo' ?>">
                                <?= (int) $c['ativo'] === 1 ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td>
                            <div class="grupo-botoes">
                                <a class="btn-acao btn-gerenciar" href="admin_cursos.php?curso_id=<?= (int) $c['id'] ?>">Gerenciar conteúdo</a>
                                <button type="button" class="btn-acao btn-editar"
                                    data-id="<?= (int) $c['id'] ?>"
                                    data-nome="<?= htmlspecialchars($c['nome'], ENT_QUOTES) ?>"
                                    data-descricao="<?= htmlspecialchars($c['descricao'], ENT_QUOTES) ?>"
                                    data-imagem="<?= htmlspecialchars($c['imagem'], ENT_QUOTES) ?>"
                                    data-nota="<?= (int) $c['nota_minima_quiz'] ?>"
                                    data-certifica="<?= (int) $c['certifica_conclusao'] ?>"
                                    onclick="abrirModalEditarCurso(this)">Editar</button>
                                <form method="post" class="form-inline">
                                    <input type="hidden" name="acao" value="toggle_ativo_curso">
                                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" class="btn-acao btn-toggle"><?= (int) $c['ativo'] === 1 ? 'Desativar' : 'Ativar' ?></button>
                                </form>
                                <form method="post" class="form-inline" onsubmit="return confirm('Excluir o curso \'<?= htmlspecialchars(addslashes($c['nome'])) ?>\'? Essa ação não pode ser desfeita.');">
                                    <input type="hidden" name="acao" value="excluir_curso">
                                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" class="btn-acao btn-excluir">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>

        <!-- Modal: criar curso -->
        <div class="modal-overlay" id="modalCriarCurso">
            <div class="modal-box">
                <div class="modal-topo">
                    <h2>Novo curso</h2>
                    <button type="button" class="btn-fechar-modal" onclick="fecharModal('modalCriarCurso')">Cancelar</button>
                </div>
                <p class="modal-sub">Depois de criar, use "Gerenciar conteúdo" para adicionar aulas e quiz.</p>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="criar_curso">
                    <div class="campo">
                        <label>Nome do curso</label>
                        <input type="text" name="nome" maxlength="191" required>
                    </div>
                    <div class="campo">
                        <label>Descrição</label>
                        <textarea name="descricao" style="min-height:80px;" required></textarea>
                    </div>
                    <div class="campo">
                        <label>Imagem de capa (escolha uma existente)</label>
                        <select name="imagem_existente">
                            <option value="">-- selecione --</option>
                            <?php foreach ($imagens_disponiveis as $img): ?>
                                <option value="<?= htmlspecialchars($img) ?>"><?= htmlspecialchars($img) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label>...ou envie uma nova imagem</label>
                        <input type="file" name="imagem_upload" accept=".jpg,.jpeg,.png,.gif,.webp">
                    </div>
                    <div class="campo">
                        <label>Nota mínima do quiz (%)</label>
                        <input type="number" name="nota_minima_quiz" min="0" max="100" value="70">
                    </div>
                    <div class="campo-check">
                        <input type="checkbox" id="cc_certifica" name="certifica_conclusao" value="1">
                        <label for="cc_certifica">Concluir este curso libera o certificado final</label>
                    </div>
                    <div class="modal-acoes"><button type="submit" class="btn-salvar">Criar curso</button></div>
                </form>
            </div>
        </div>

        <!-- Modal: editar curso -->
        <div class="modal-overlay" id="modalEditarCurso">
            <div class="modal-box">
                <div class="modal-topo">
                    <h2>Editar curso</h2>
                    <button type="button" class="btn-fechar-modal" onclick="fecharModal('modalEditarCurso')">Cancelar</button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="editar_curso">
                    <input type="hidden" name="id" id="ec_id">
                    <div class="campo">
                        <label>Nome do curso</label>
                        <input type="text" name="nome" id="ec_nome" maxlength="191" required>
                    </div>
                    <div class="campo">
                        <label>Descrição</label>
                        <textarea name="descricao" id="ec_descricao" style="min-height:80px;" required></textarea>
                    </div>
                    <div class="campo">
                        <img class="img-atual" id="ec_imagem_atual" src="" alt="">
                        <label>Imagem de capa</label>
                        <select name="imagem_existente" id="ec_imagem_existente">
                            <option value="">-- selecione --</option>
                            <?php foreach ($imagens_disponiveis as $img): ?>
                                <option value="<?= htmlspecialchars($img) ?>"><?= htmlspecialchars($img) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label>...ou envie uma nova imagem</label>
                        <input type="file" name="imagem_upload" accept=".jpg,.jpeg,.png,.gif,.webp">
                    </div>
                    <div class="campo">
                        <label>Nota mínima do quiz (%)</label>
                        <input type="number" name="nota_minima_quiz" id="ec_nota" min="0" max="100">
                    </div>
                    <div class="campo-check">
                        <input type="checkbox" id="ec_certifica" name="certifica_conclusao" value="1">
                        <label for="ec_certifica">Concluir este curso libera o certificado final</label>
                    </div>
                    <div class="modal-acoes"><button type="submit" class="btn-salvar">Salvar alterações</button></div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- ======================================================
             MODO: GESTÃO DE CONTEÚDO DE UM CURSO
             ====================================================== -->
        <p class="breadcrumb"><a href="admin_cursos.php">Conteúdo</a> &rsaquo; <?= htmlspecialchars($curso_atual['nome']) ?></p>
        <header class="saudacao">
            <div>
                <h1><?= htmlspecialchars($curso_atual['nome']) ?></h1>
                <p>Gerencie as aulas e o quiz final deste curso.</p>
            </div>
        </header>

        <?php if ($tem_progresso): ?>
            <p class="aviso-progresso">
                ⚠️ Alunos já concluíram aulas deste curso. Para não desalinhar o progresso já salvo,
                não é possível excluir ou reordenar aulas — apenas editar título/conteúdo/mídia e criar aulas novas.
            </p>
        <?php endif; ?>

        <!-- ---------- AULAS ---------- -->
        <div class="secao-titulo">
            <h2>Aulas</h2>
            <button type="button" class="btn-cadastrar" onclick="abrirModalNovaAula()">Nova aula</button>
        </div>
        <section class="tabela-wrap">
            <?php if (empty($aulas)): ?>
                <p class="vazio">Nenhuma aula cadastrada ainda.</p>
            <?php else: ?>
            <table class="tabela-admin">
                <thead>
                    <tr><th>#</th><th>Título</th><th class="col-centro">Mídia</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($aulas as $i => $a): ?>
                    <tr>
                        <td>
                            <div class="grupo-botoes">
                                <?= $a['ordem'] ?>
                                <?php if (!$tem_progresso): ?>
                                    <form method="post" class="form-inline">
                                        <input type="hidden" name="acao" value="mover_aula">
                                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                        <input type="hidden" name="curso_id" value="<?= $curso_id_sel ?>">
                                        <input type="hidden" name="direcao" value="cima">
                                        <button type="submit" class="btn-acao btn-mover" <?= $i === 0 ? 'disabled' : '' ?>>&uarr;</button>
                                    </form>
                                    <form method="post" class="form-inline">
                                        <input type="hidden" name="acao" value="mover_aula">
                                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                        <input type="hidden" name="curso_id" value="<?= $curso_id_sel ?>">
                                        <input type="hidden" name="direcao" value="baixo">
                                        <button type="submit" class="btn-acao btn-mover" <?= $i === count($aulas) - 1 ? 'disabled' : '' ?>>&darr;</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($a['titulo']) ?></td>
                        <td class="col-centro">
                            <span class="badge midia-<?= $a['midia_tipo'] ?>"><?= ucfirst($a['midia_tipo']) ?></span>
                        </td>
                        <td>
                            <div class="grupo-botoes">
                                <button type="button" class="btn-acao btn-editar"
                                    data-id="<?= (int) $a['id'] ?>"
                                    data-titulo="<?= htmlspecialchars($a['titulo'], ENT_QUOTES) ?>"
                                    data-conteudo="<?= htmlspecialchars($a['conteudo'], ENT_QUOTES) ?>"
                                    data-midia-tipo="<?= $a['midia_tipo'] ?>"
                                    data-midia-arquivo="<?= htmlspecialchars((string) $a['midia_arquivo'], ENT_QUOTES) ?>"
                                    onclick="abrirModalEditarAula(this)">Editar</button>
                                <?php if (!$tem_progresso): ?>
                                <form method="post" class="form-inline" onsubmit="return confirm('Excluir esta aula?');">
                                    <input type="hidden" name="acao" value="excluir_aula">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <input type="hidden" name="curso_id" value="<?= $curso_id_sel ?>">
                                    <button type="submit" class="btn-acao btn-excluir">Excluir</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>

        <!-- ---------- QUIZ ---------- -->
        <div class="secao-titulo">
            <h2>Quiz final</h2>
            <button type="button" class="btn-cadastrar" onclick="abrirModalNovaPergunta()">Nova pergunta</button>
        </div>
        <section class="tabela-wrap">
            <?php if (empty($perguntas)): ?>
                <p class="vazio">Nenhuma pergunta cadastrada — este curso não vai exigir quiz até que você cadastre pelo menos uma.</p>
            <?php else: ?>
            <table class="tabela-admin">
                <thead>
                    <tr><th>#</th><th>Pergunta</th><th class="col-centro">Opções</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($perguntas as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($p['enunciado']) ?></td>
                        <td class="col-centro"><?= count($p['opcoes']) ?></td>
                        <td>
                            <div class="grupo-botoes">
                                <button type="button" class="btn-acao btn-editar"
                                    data-id="<?= (int) $p['id'] ?>"
                                    data-enunciado="<?= htmlspecialchars($p['enunciado'], ENT_QUOTES) ?>"
                                    data-opcoes='<?= htmlspecialchars(json_encode($p['opcoes']), ENT_QUOTES) ?>'
                                    onclick="abrirModalEditarPergunta(this)">Editar</button>
                                <form method="post" class="form-inline" onsubmit="return confirm('Excluir esta pergunta?');">
                                    <input type="hidden" name="acao" value="excluir_pergunta">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <input type="hidden" name="curso_id" value="<?= $curso_id_sel ?>">
                                    <button type="submit" class="btn-acao btn-excluir">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>

        <!-- Modal: criar aula -->
        <div class="modal-overlay" id="modalCriarAula">
            <div class="modal-box">
                <div class="modal-topo">
                    <h2>Nova aula</h2>
                    <button type="button" class="btn-fechar-modal" onclick="fecharModal('modalCriarAula')">Cancelar</button>
                </div>
                <form method="post" enctype="multipart/form-data" id="form-criar-aula">
                    <input type="hidden" name="acao" value="criar_aula">
                    <input type="hidden" name="curso_id" value="<?= $curso_id_sel ?>">
                    <div class="campo">
                        <label>Título da aula</label>
                        <input type="text" name="titulo" maxlength="255" required>
                    </div>
                    <div class="campo">
                        <label>Conteúdo</label>
                        <div class="editor-conteudo"><div id="ca_editor"></div></div>
                        <textarea name="conteudo" id="ca_conteudo" style="display:none"></textarea>
                    </div>
                    <div class="campo">
                        <label>Imagem ou vídeo (opcional)</label>
                        <input type="file" name="midia" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm">
                        <p class="campo-dica">Imagem até 5MB, vídeo até 35MB.</p>
                    </div>
                    <div class="modal-acoes"><button type="submit" class="btn-salvar">Criar aula</button></div>
                </form>
            </div>
        </div>

        <!-- Modal: editar aula -->
        <div class="modal-overlay" id="modalEditarAula">
            <div class="modal-box">
                <div class="modal-topo">
                    <h2>Editar aula</h2>
                    <button type="button" class="btn-fechar-modal" onclick="fecharModal('modalEditarAula')">Cancelar</button>
                </div>
                <form method="post" enctype="multipart/form-data" id="form-editar-aula">
                    <input type="hidden" name="acao" value="editar_aula">
                    <input type="hidden" name="curso_id" value="<?= $curso_id_sel ?>">
                    <input type="hidden" name="id" id="ea_id">
                    <div class="campo">
                        <label>Título da aula</label>
                        <input type="text" name="titulo" id="ea_titulo" maxlength="255" required>
                    </div>
                    <div class="campo">
                        <label>Conteúdo</label>
                        <div class="editor-conteudo"><div id="ea_editor"></div></div>
                        <textarea name="conteudo" id="ea_conteudo" style="display:none"></textarea>
                    </div>
                    <div class="campo" id="ea_midia_atual_wrap">
                        <label>Mídia atual</label>
                        <p class="campo-dica" id="ea_midia_atual_texto">Nenhuma</p>
                        <div class="campo-check">
                            <input type="checkbox" id="ea_remover_midia" name="remover_midia" value="1">
                            <label for="ea_remover_midia">Remover mídia atual</label>
                        </div>
                    </div>
                    <div class="campo">
                        <label>Substituir por imagem ou vídeo (opcional)</label>
                        <input type="file" name="midia" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm">
                        <p class="campo-dica">Imagem até 5MB, vídeo até 35MB.</p>
                    </div>
                    <div class="modal-acoes"><button type="submit" class="btn-salvar">Salvar alterações</button></div>
                </form>
            </div>
        </div>

        <!-- Modal: criar/editar pergunta (compartilhado, JS ajusta título e ação) -->
        <div class="modal-overlay" id="modalPergunta">
            <div class="modal-box">
                <div class="modal-topo">
                    <h2 id="pg_titulo_modal">Nova pergunta</h2>
                    <button type="button" class="btn-fechar-modal" onclick="fecharModal('modalPergunta')">Cancelar</button>
                </div>
                <form method="post" id="form-pergunta">
                    <input type="hidden" name="acao" id="pg_acao" value="criar_pergunta">
                    <input type="hidden" name="curso_id" value="<?= $curso_id_sel ?>">
                    <input type="hidden" name="id" id="pg_id" value="0">
                    <div class="campo">
                        <label>Enunciado</label>
                        <textarea name="enunciado" id="pg_enunciado" style="min-height:70px;" required></textarea>
                    </div>
                    <div class="campo">
                        <label>Opções (marque a correta)</label>
                        <div id="pg_opcoes_container"></div>
                        <button type="button" class="btn-add-opcao" onclick="adicionarOpcao()">+ Adicionar opção</button>
                    </div>
                    <div class="modal-acoes"><button type="submit" class="btn-salvar">Salvar pergunta</button></div>
                </form>
            </div>
        </div>

        <?php endif; ?>
    </main>

    <template id="template-opcao">
        <div class="opcao-linha">
            <input type="radio" name="correta" value="0">
            <input type="text" name="opcoes[]" placeholder="Texto da opção" maxlength="500">
            <button type="button" class="btn-remover-opcao" onclick="this.parentElement.remove()">&times;</button>
        </div>
    </template>

    <script src="libs/quill/quill.js"></script>
    <script>
        const navbar = document.querySelector("nav");
        function handleNavScroll() {
            if (window.scrollY > 40) navbar.classList.add("scrolled");
            else navbar.classList.remove("scrolled");
        }
        window.addEventListener("scroll", handleNavScroll);
        handleNavScroll();

        function abrirModal(id) { document.getElementById(id).classList.add('aberto'); }
        function fecharModal(id) { document.getElementById(id).classList.remove('aberto'); }

        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) overlay.classList.remove('aberto');
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.aberto').forEach(function (m) { m.classList.remove('aberto'); });
            }
        });

        function abrirModalEditarCurso(btn) {
            document.getElementById('ec_id').value = btn.dataset.id;
            document.getElementById('ec_nome').value = btn.dataset.nome;
            document.getElementById('ec_descricao').value = btn.dataset.descricao;
            document.getElementById('ec_imagem_existente').value = btn.dataset.imagem;
            document.getElementById('ec_imagem_atual').src = btn.dataset.imagem;
            document.getElementById('ec_nota').value = btn.dataset.nota;
            document.getElementById('ec_certifica').checked = btn.dataset.certifica === '1';
            abrirModal('modalEditarCurso');
        }

        /* -------- Editor de texto rico (Quill) para o conteúdo das aulas -------- */
        const QUILL_TOOLBAR = [
            [{ header: [2, 3, 4, false] }],
            ['bold', 'italic'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'link', 'image'],
            ['clean'],
        ];

        // O Quill 2 já tem um módulo "uploader" que recebe a imagem tanto do botão
        // da barra quanto de colar (Ctrl+V) ou arrastar um arquivo para o editor.
        // Em vez de deixar no padrão dele (que embutiria a imagem em base64 no
        // texto), a função abaixo envia o arquivo para admin_upload_imagem_editor.php
        // (mesma validação dos outros uploads) e insere só a URL do arquivo salvo.
        function uploaderHandler(range, files) {
            const quill = this.quill;
            let posicao = range.index;

            function enviarProximo(i) {
                if (i >= files.length) return;
                const formData = new FormData();
                formData.append('imagem', files[i]);
                fetch('admin_upload_imagem_editor.php', { method: 'POST', body: formData })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.url) {
                            quill.insertEmbed(posicao, 'image', data.url, 'user');
                            quill.setSelection(posicao + 1);
                            posicao++;
                        } else {
                            alert(data.erro || 'Não foi possível enviar a imagem.');
                        }
                    })
                    .catch(function () {
                        alert('Falha ao enviar a imagem. Tente novamente.');
                    })
                    .finally(function () { enviarProximo(i + 1); });
            }
            enviarProximo(0);
        }

        function criarOpcoesQuill() {
            return {
                theme: 'snow',
                modules: {
                    toolbar: QUILL_TOOLBAR,
                    uploader: {
                        mimetypes: ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
                        handler: uploaderHandler,
                    },
                },
            };
        }

        let quillCriarAula = null;
        let quillEditarAula = null;
        if (document.getElementById('ca_editor')) {
            quillCriarAula = new Quill('#ca_editor', criarOpcoesQuill());
        }
        if (document.getElementById('ea_editor')) {
            quillEditarAula = new Quill('#ea_editor', criarOpcoesQuill());
        }

        // Antes de enviar, copia o HTML montado pelo Quill para o campo de verdade do formulário
        document.getElementById('form-criar-aula')?.addEventListener('submit', function () {
            document.getElementById('ca_conteudo').value = quillCriarAula.root.innerHTML;
        });
        document.getElementById('form-editar-aula')?.addEventListener('submit', function () {
            document.getElementById('ea_conteudo').value = quillEditarAula.root.innerHTML;
        });

        function abrirModalNovaAula() {
            if (quillCriarAula) quillCriarAula.setText('');
            abrirModal('modalCriarAula');
        }

        function abrirModalEditarAula(btn) {
            document.getElementById('ea_id').value = btn.dataset.id;
            document.getElementById('ea_titulo').value = btn.dataset.titulo;
            if (quillEditarAula) quillEditarAula.root.innerHTML = btn.dataset.conteudo;
            document.getElementById('ea_remover_midia').checked = false;
            const tipo = btn.dataset.midiaTipo;
            const arquivo = btn.dataset.midiaArquivo;
            document.getElementById('ea_midia_atual_texto').textContent =
                (tipo === 'nenhuma' || !arquivo) ? 'Nenhuma' : (tipo === 'imagem' ? 'Imagem: ' : 'Vídeo: ') + arquivo;
            abrirModal('modalEditarAula');
        }

        /* -------- Perguntas do quiz: linhas de opção dinâmicas -------- */
        function adicionarOpcao(texto, marcada) {
            const tpl = document.getElementById('template-opcao').content.cloneNode(true);
            if (texto) tpl.querySelector('input[type=text]').value = texto;
            if (marcada) tpl.querySelector('input[type=radio]').checked = true;
            document.getElementById('pg_opcoes_container').appendChild(tpl);
        }

        function abrirModalNovaPergunta() {
            document.getElementById('pg_titulo_modal').textContent = 'Nova pergunta';
            document.getElementById('pg_acao').value = 'criar_pergunta';
            document.getElementById('pg_id').value = '0';
            document.getElementById('pg_enunciado').value = '';
            document.getElementById('pg_opcoes_container').innerHTML = '';
            adicionarOpcao(); adicionarOpcao(); adicionarOpcao(); adicionarOpcao();
            abrirModal('modalPergunta');
        }

        function abrirModalEditarPergunta(btn) {
            document.getElementById('pg_titulo_modal').textContent = 'Editar pergunta';
            document.getElementById('pg_acao').value = 'editar_pergunta';
            document.getElementById('pg_id').value = btn.dataset.id;
            document.getElementById('pg_enunciado').value = btn.dataset.enunciado;
            document.getElementById('pg_opcoes_container').innerHTML = '';
            const opcoes = JSON.parse(btn.dataset.opcoes);
            opcoes.forEach(function (o) {
                adicionarOpcao(o.texto, o.correta === '1' || o.correta === 1);
            });
            abrirModal('modalPergunta');
        }

        // Renumera os valores dos radios (0..N-1) na ordem atual das linhas antes de enviar,
        // já que linhas podem ser adicionadas/removidas dinamicamente.
        document.getElementById('form-pergunta')?.addEventListener('submit', function () {
            const linhas = document.querySelectorAll('#pg_opcoes_container .opcao-linha');
            linhas.forEach(function (linha, idx) {
                linha.querySelector('input[type=radio]').value = idx;
            });
        });
    </script>
</body>
</html>
