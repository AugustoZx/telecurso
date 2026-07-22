<?php
    session_start();

    /* -------- Proteção dupla: precisa estar logado E ser admin -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: index.php');
        exit;
    }
    if (empty($_SESSION['adm']) || (int)$_SESSION['adm'] !== 1) {
        // Não é administrador: manda de volta ao painel comum
        header('Location: dashboard.php');
        exit;
    }

    include_once('config.php');
    include_once('cursos.php');

    $CURSOS = buscar_cursos_ativos($conexao);

    /* -------- Validação matemática do CPF (dígitos verificadores) -------- */
    function cpf_valido($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf); // mantém só números
        if (strlen($cpf) !== 11) return false;
        // Rejeita sequências repetidas (00000000000, 11111111111, ...)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int)$cpf[$i] * (($t + 1) - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int)$cpf[$t] !== $digito) return false;
        }
        return true;
    }

    /* -------- Processa o cadastro de um novo aluno (POST) -------- */
    $cadastro_msg  = '';
    $cadastro_erro = false;
    $abrir_modal   = false; // reabre o modal caso haja erro de validação

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
        $abrir_modal = true;

        $novo_user = trim($_POST['user'] ?? '');
        $novo_nome = trim($_POST['nome'] ?? '');
        $novo_cpf  = trim($_POST['cpf'] ?? '');
        $novo_nasc = $_POST['data_nasc'] ?? '';
        $novo_adm  = isset($_POST['adm']) ? 1 : 0;

        /* Senha padrão para todo cadastro feito pelo admin.
           O aluno é obrigado a trocá-la no primeiro login (trocar_senha = 1). */
        $novo_pass = '123';

        /* -------- Validação básica -------- */
        if ($novo_user === '' || $novo_nome === '' || $novo_cpf === '' || $novo_nasc === '') {
            $cadastro_erro = true;
            $cadastro_msg  = 'Preencha todos os campos obrigatórios.';
        } elseif (!cpf_valido($novo_cpf)) {
            $cadastro_erro = true;
            $cadastro_msg  = 'CPF inválido. Verifique os números digitados.';
        } else {
            // Verifica se o usuário já existe (campo user é UNIQUE)
            $chk = $conexao->prepare("SELECT id FROM alunos WHERE user = ?");
            $chk->bind_param("s", $novo_user);
            $chk->execute();
            $chk->store_result();

            if ($chk->num_rows > 0) {
                $cadastro_erro = true;
                $cadastro_msg  = 'Esse nome de usuário já está em uso.';
            } else {
                $ins = $conexao->prepare(
                    "INSERT INTO alunos (user, pass, trocar_senha, nome, cpf, data_nasc, adm, aula)
                     VALUES (?, ?, 1, ?, ?, ?, ?, 1)"
                );
                $ins->bind_param("sssssi", $novo_user, $novo_pass, $novo_nome, $novo_cpf, $novo_nasc, $novo_adm);

                if ($ins->execute()) {
                    // Redireciona (padrão PRG) para evitar reenvio do formulário
                    header('Location: admin.php?cadastro=ok');
                    exit;
                } else {
                    $cadastro_erro = true;
                    $cadastro_msg  = 'Erro ao cadastrar aluno. Tente novamente.';
                }
            }
            $chk->close();
        }
    }

    if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'ok') {
        $cadastro_msg = 'Aluno cadastrado com sucesso!';
    }

    /* -------- Processa a EDIÇÃO de um aluno (POST) -------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
        $ed_id   = (int)($_POST['id'] ?? 0);
        $ed_user = trim($_POST['user'] ?? '');
        $ed_nome = trim($_POST['nome'] ?? '');
        $ed_cpf  = trim($_POST['cpf'] ?? '');
        $ed_nasc = $_POST['data_nasc'] ?? '';
        $ed_adm  = isset($_POST['adm']) ? 1 : 0;
        $ed_reset_senha = isset($_POST['resetar_senha']) ? 1 : 0;

        if ($ed_id > 0 && $ed_user !== '' && $ed_nome !== '' && $ed_cpf !== '' && $ed_nasc !== '' && cpf_valido($ed_cpf)) {
            // Verifica se o usuário já pertence a OUTRO aluno (user é UNIQUE)
            $chk = $conexao->prepare("SELECT id FROM alunos WHERE user = ? AND id <> ?");
            $chk->bind_param("si", $ed_user, $ed_id);
            $chk->execute();
            $chk->store_result();

            if ($chk->num_rows > 0) {
                header('Location: admin.php?edicao=erro');
                exit;
            }
            $chk->close();

            if ($ed_reset_senha) {
                // Volta a senha para o padrão "123" e força troca no próximo login
                $senha_padrao = '123';
                $upd = $conexao->prepare(
                    "UPDATE alunos SET user = ?, pass = ?, trocar_senha = 1, nome = ?, cpf = ?, data_nasc = ?, adm = ? WHERE id = ?"
                );
                $upd->bind_param("sssssii", $ed_user, $senha_padrao, $ed_nome, $ed_cpf, $ed_nasc, $ed_adm, $ed_id);
            } else {
                // Mantém a senha atual
                $upd = $conexao->prepare(
                    "UPDATE alunos SET user = ?, nome = ?, cpf = ?, data_nasc = ?, adm = ? WHERE id = ?"
                );
                $upd->bind_param("ssssii", $ed_user, $ed_nome, $ed_cpf, $ed_nasc, $ed_adm, $ed_id);
            }
            $upd->execute();
            header('Location: admin.php?edicao=ok');
            exit;
        }
        header('Location: admin.php?edicao=erro');
        exit;
    }

    /* -------- Processa a EXCLUSÃO de um aluno (POST) -------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
        $del_id = (int)($_POST['id'] ?? 0);
        // Evita que o admin exclua a própria conta
        if ($del_id > 0 && $del_id !== (int)$_SESSION['aluno_id']) {
            $del = $conexao->prepare("DELETE FROM alunos WHERE id = ?");
            $del->bind_param("i", $del_id);
            $del->execute();
            header('Location: admin.php?exclusao=ok');
            exit;
        }
        header('Location: admin.php?exclusao=erro');
        exit;
    }

    if (isset($_GET['edicao'])) {
        $cadastro_msg  = $_GET['edicao'] === 'ok' ? 'Aluno atualizado com sucesso!' : 'Não foi possível editar (usuário duplicado ou dados inválidos).';
        $cadastro_erro = $_GET['edicao'] !== 'ok';
    }
    if (isset($_GET['exclusao'])) {
        $cadastro_msg  = $_GET['exclusao'] === 'ok' ? 'Aluno excluído com sucesso!' : 'Não foi possível excluir o aluno.';
        $cadastro_erro = $_GET['exclusao'] !== 'ok';
    }
    if (isset($_GET['cert'])) {
        switch ($_GET['cert']) {
            case 'incompleto':
                $cadastro_msg  = 'O aluno ainda não concluiu a última aula do curso. Certificado indisponível.';
                $cadastro_erro = true;
                break;
            case 'erro':
                $cadastro_msg  = 'Não foi possível gerar o certificado. Verifique se a biblioteca FPDF está instalada.';
                $cadastro_erro = true;
                break;
            default:
                $cadastro_msg  = 'Certificado gerado com sucesso!';
                $cadastro_erro = false;
        }
    }

    /* -------- Filtro de busca por nome/usuário (opcional) -------- */
    $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

    /* -------- Total de aulas de todos os cursos somados -------- */
    $total_aulas_plataforma = 0;
    foreach ($CURSOS as $c) {
        $total_aulas_plataforma += count($c['aulas']);
    }

    /* -------- Busca todos os alunos (com filtro opcional) -------- */
    if ($busca !== '') {
        $sql = "SELECT id, nome, user, cpf, data_nasc, adm FROM alunos
                WHERE nome LIKE ? OR user LIKE ?
                ORDER BY nome ASC";
        $stmt = $conexao->prepare($sql);
        $curinga = '%' . $busca . '%';
        $stmt->bind_param("ss", $curinga, $curinga);
    } else {
        $sql = "SELECT id, nome, user, cpf, data_nasc, adm FROM alunos ORDER BY nome ASC";
        $stmt = $conexao->prepare($sql);
    }
    $stmt->execute();
    $alunos = $stmt->get_result();

    /* -------- Progresso de todos os alunos, por curso --------
       Um único SELECT agrupado; guardamos num array em memória. */
    $prog = []; // aluno_id => [ curso_id => concluidas ]
    $res = $conexao->query("SELECT aluno_id, curso_id, COUNT(*) AS concluidas
                            FROM aulas_concluidas
                            GROUP BY aluno_id, curso_id");
    if ($res) {
        while ($l = $res->fetch_assoc()) {
            $prog[$l['aluno_id']][$l['curso_id']] = (int)$l['concluidas'];
        }
    }

    /* -------- Alunos que concluíram o curso marcado como "gera certificado" --------
       Concluir = todas as aulas desse curso + quiz aprovado (quando houver quiz). */
    $concluiu_certificado = []; // aluno_id => true
    $curso_cert_slug = null;
    foreach ($CURSOS as $slug => $c) {
        if ((int) $c['certifica_conclusao'] === 1) { $curso_cert_slug = $slug; break; }
    }
    if ($curso_cert_slug !== null) {
        $total_cert = total_aulas($CURSOS, $curso_cert_slug);
        $tem_quiz_cert = curso_tem_quiz($CURSOS, $curso_cert_slug);
        $nota_min_cert = $CURSOS[$curso_cert_slug]['nota_minima_quiz'];

        $quiz_melhor = []; // aluno_id => melhor percentual no quiz desse curso
        $rq = $conexao->prepare("SELECT aluno_id, MAX(percentual) AS melhor FROM quiz_resultados WHERE curso_id = ? GROUP BY aluno_id");
        $rq->bind_param("s", $curso_cert_slug);
        $rq->execute();
        $resq = $rq->get_result();
        while ($l = $resq->fetch_assoc()) {
            $quiz_melhor[(int) $l['aluno_id']] = (float) $l['melhor'];
        }

        foreach ($prog as $aid => $porCurso) {
            $feitas = $porCurso[$curso_cert_slug] ?? 0;
            if ($total_cert > 0 && $feitas >= $total_cert) {
                $aprovado_quiz = !$tem_quiz_cert || (($quiz_melhor[$aid] ?? 0) >= $nota_min_cert);
                if ($aprovado_quiz) $concluiu_certificado[(int) $aid] = true;
            }
        }
    }

    /* -------- Números gerais para os cartões do topo -------- */
    $total_alunos = $alunos->num_rows;

    // Alunos que já concluíram pelo menos uma aula
    $ativos = 0;
    // Total de aulas concluídas somando todo mundo
    $total_concluidas_geral = 0;
    foreach ($prog as $aluno_id => $cursos_do_aluno) {
        $soma_aluno = array_sum($cursos_do_aluno);
        if ($soma_aluno > 0) $ativos++;
        $total_concluidas_geral += $soma_aluno;
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
    <link rel="shortcut icon" type="imagex/png" href="https://salesianossp.org.br/ositaquera/wp-content/uploads/2024/03/wp-favicon-pvi-os-150x150.webp">
    <title>Administração</title>

    <style>
        /* ===================== BASE ===================== */
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            color: #193e8f;
        }

        body {
            background-color: #eee;
        }

        /* ===================== NAVBAR (padrão, igual ao index) =====================
           Estado inicial: transparente, largura cheia, colada no topo.
           Ao rolar (classe .scrolled via JS): vira uma pílula branca translúcida,
           arredondada, com sombra e recuada nas laterais. */
        nav {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 100%;
            z-index: 1000;

            display: flex;
            align-items: center;
            justify-content: space-around;

            padding: 16px 40px;
            background-color: transparent;
            backdrop-filter: blur(0px);
            -webkit-backdrop-filter: blur(0px);
            border-radius: 0;
            box-shadow: none;

            transition:
                max-width 0.4s ease,
                padding 0.4s ease,
                top 0.4s ease,
                background-color 0.4s ease,
                backdrop-filter 0.4s ease,
                border-radius 0.4s ease,
                box-shadow 0.4s ease;
        }

        nav.scrolled {
            top: 14px;
            max-width: 1200px;
            padding: 10px 32px;
            background-color: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 9999px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .logo img {
            width: 150px;
            display: block;
            transition: width 0.4s ease;
        }

        nav.scrolled .logo img {
            width: 120px;
        }

        .elem ul {
            display: flex;
            gap: 25px;
        }

        .elem ul a {
            text-decoration: none;
            font-weight: bold;
        }

        .elem ul a li {
            list-style: none;
            transition: color 0.3s ease;
        }

        .elem ul a li:hover {
            color: #ea3e44;
        }

        .b_login {
            padding: 10px 30px;
            background-color: #193e8f;
            font-size: 17px;
            border: none;
            border-radius: 25px;
            color: #eee;
            font-weight: bold;
            text-decoration: none;
            transition: background-color 0.5s ease, padding 0.4s ease;
        }

        .b_login:hover {
            background-color: #ea3e44;
        }

        nav.scrolled .b_login {
            padding: 8px 26px;
        }

        /* ===================== MAIN ===================== */
        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 110px 16px 60px;
        }

        .saudacao {
            margin-bottom: 30px;
        }

        .saudacao h1 {
            font-size: 40px;
            color: #193e8f;
        }

        .saudacao p {
            color: #333;
            font-size: 18px;
            margin-top: 6px;
        }

        /* ===================== ADMIN ===================== */
        .resumo-admin {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-num {
            background: #fff;
            border-radius: 12px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-top: 4px solid #193e8f;
        }

        .card-num .num {
            display: block;
            font-size: 38px;
            font-weight: 700;
            color: #193e8f;
            line-height: 1.1;
        }

        .card-num .rot {
            display: block;
            margin-top: 6px;
            font-size: 14px;
            color: #555;
        }

        .barra-busca {
            margin-bottom: 24px;
        }

        .barra-busca form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .barra-busca input {
            flex: 1;
            min-width: 220px;
            padding: 12px 16px;
            border: 2px solid #ccc;
            border-radius: 8px;
            font-family: 'Roboto', sans-serif;
            font-size: 15px;
        }

        .barra-busca input:focus {
            outline: none;
            border-color: #193e8f;
        }

        .btn-buscar {
            background: #193e8f;
            color: #fff;
            border: none;
            padding: 12px 22px;
            border-radius: 8px;
            font-family: 'Roboto', sans-serif;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-buscar:hover { background: #142f6d; }

        .btn-limpar {
            color: #193e8f;
            text-decoration: none;
            font-size: 14px;
            padding: 12px 10px;
        }

        .btn-limpar:hover { text-decoration: underline; }

        .tabela-wrap {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        .tabela-admin {
            width: 100%;
            border-collapse: collapse;
            min-width: 640px;
        }

        .tabela-admin th,
        .tabela-admin td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .tabela-admin thead th {
            background: #193e8f;
            color: #fff;
            font-weight: 500;
            font-size: 14px;
            position: sticky;
            top: 0;
        }

        .tabela-admin .col-curso,
        .tabela-admin .col-total { text-align: center; }

        .tabela-admin tbody tr:hover { background: #f5f7fb; }

        .cel-aluno { min-width: 180px; }

        .nome-aluno {
            display: block;
            font-weight: 500;
            color: #222;
        }

        .user-aluno {
            display: block;
            font-size: 13px;
            color: #888;
        }

        .tag-adm {
            display: inline-block;
            margin-top: 4px;
            background: #ea3e44;
            color: #fff;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 10px;
            text-transform: uppercase;
        }

        .cel-curso { text-align: center; min-width: 110px; }

        .mini-barra {
            height: 8px;
            background: #e6e6e6;
            border-radius: 4px;
            overflow: hidden;
            margin: 0 auto 4px;
            max-width: 90px;
        }

        .mini-preenchida {
            height: 100%;
            background: #193e8f;
            border-radius: 4px;
        }

        .mini-preenchida.ok { background: #2e9e5b; }

        .mini-texto {
            font-size: 12px;
            color: #666;
        }

        .cel-total {
            text-align: center;
            font-weight: 700;
            color: #193e8f;
            font-size: 16px;
        }

        .vazio {
            padding: 30px;
            text-align: center;
            color: #888;
        }

        /* ===================== RESPONSIVO ===================== */
        @media (max-width: 768px) {
            nav {
                padding: 12px 20px;
            }

            nav.scrolled {
                max-width: calc(100% - 24px);
                border-radius: 24px;
            }

            .logo img,
            nav.scrolled .logo img { width: 100px; }

            .saudacao h1 { font-size: 30px; }

            .resumo-admin {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .card-num .num { font-size: 30px; }

            .modal-box {
                padding: 32px 24px;
            }
        }

        /* ===================== BOTÃO CADASTRAR + MODAIS ===================== */
        /* Mesmo visual do modal de login (index.php): overlay azul com blur,
           card branco arredondado, inputs e botão no mesmo padrão de cores. */
        .btn-cadastrar {
            background: #193e8f;
            color: #fff;
            border: none;
            padding: 12px 22px;
            border-radius: 8px;
            font-family: 'Roboto', sans-serif;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
        }
        .btn-cadastrar:hover { background: #142f6d; }

        /* Overlay do modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-color: rgba(25, 62, 143, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.aberto {
            display: flex;
            opacity: 1;
        }

        .modal-box {
            background-color: #ffffff;
            width: 100%;
            max-width: 460px;
            border-radius: 20px;
            padding: 44px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            font-family: 'Roboto', sans-serif;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px) scale(0.98);
            transition: transform 0.3s ease;
        }
        .modal-overlay.aberto .modal-box {
            transform: translateY(0) scale(1);
        }

        .modal-topo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
        }
        .modal-box h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            color: #193e8f;
        }
        .modal-box .modal-sub {
            margin: 0 0 26px;
            font-size: 15px;
            color: #666;
        }

        .btn-fechar-modal {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            color: #193e8f;
            background-color: #eef1f8;
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color 0.3s ease;
        }
        .btn-fechar-modal:hover { background-color: #dfe4f2; }

        .modal-box label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #193e8f;
            margin-bottom: 6px;
        }
        .modal-box .campo { margin-bottom: 16px; }
        .modal-box input[type="text"],
        .modal-box input[type="password"],
        .modal-box input[type="date"] {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            color: #193e8f;
            background-color: #f4f6fb;
            border: 1px solid #dfe4f2;
            border-radius: 10px;
            outline: none;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .modal-box input::placeholder {
            color: #9aa3bd;
        }
        .modal-box input:focus {
            border-color: #193e8f;
            box-shadow: 0 0 0 3px rgba(25, 62, 143, 0.15);
        }
        .modal-box .campo-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
        }
        .modal-box .campo-check input[type="checkbox"] {
            width: 17px;
            height: 17px;
            accent-color: #193e8f;
            cursor: pointer;
        }
        .modal-box .campo-check label { margin: 0; cursor: pointer; }

        .modal-acoes { margin-top: 8px; }
        .modal-acoes .btn-salvar {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            background-color: #193e8f;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.4s ease;
        }
        .modal-acoes .btn-salvar:hover { background-color: #ea3e44; }

        /* Mensagens de feedback */
        .msg-feedback {
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: bold;
        }
        .msg-feedback.ok  { background: #e3f6e9; color: #1f7a44; }
        .msg-feedback.erro{ background-color: #fdeaea; color: #ea3e44; border: 1px solid #f5c6c8; }

        .aviso-senha-padrao {
            background: #eef4ff;
            border: 1px solid #c9dbff;
            color: #193e8f;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        /* Botões de ação (editar / excluir) na coluna Total */
        .total-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .total-pct { font-weight: 700; }
        .total-acoes {
            display: flex;
            gap: 6px;
        }
        .total-acoes .form-excluir { margin: 0; }
        .btn-acao {
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Roboto', sans-serif;
            color: #fff;
            transition: background .2s ease;
        }
        .btn-editar { background: #2d6cdf; }
        .btn-editar:hover { background: #2457b8; }
        .btn-excluir { background: #c53434; }
        .btn-excluir:hover { background: #a12626; }

        /* Botão de certificado (link) */
        .btn-certificado {
            background: #c98a1b;
            text-decoration: none;
            display: inline-block;
            line-height: normal;
        }
        .btn-certificado:hover { background: #a9720f; }
        /* Estado desabilitado (aluno ainda não concluiu o curso) */
        .btn-certificado-off {
            background: #c9c9c9;
            color: #6b6b6b;
            cursor: not-allowed;
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
        <header class="saudacao">
            <h1>Painel do Administrador</h1>
            <p>Acompanhe o progresso dos alunos em cada curso.</p>
        </header>

        <!-- Cartões de resumo -->
        <section class="resumo-admin" aria-label="Resumo geral">
            <div class="card-num">
                <span class="num"><?= (int)$total_alunos ?></span>
                <span class="rot">Alunos cadastrados</span>
            </div>
            <div class="card-num">
                <span class="num"><?= (int)$ativos ?></span>
                <span class="rot">Alunos com progresso</span>
            </div>
            <div class="card-num">
                <span class="num"><?= (int)$total_concluidas_geral ?></span>
                <span class="rot">Aulas concluídas (total)</span>
            </div>
            <div class="card-num">
                <span class="num"><?= count($CURSOS) ?></span>
                <span class="rot">Módulos disponíveis</span>
            </div>
        </section>

        <!-- Mensagem de sucesso (após cadastro) -->
        <?php if ($cadastro_msg !== '' && !$cadastro_erro): ?>
            <p class="msg-feedback ok"><?= htmlspecialchars($cadastro_msg) ?></p>
        <?php endif; ?>

        <!-- Busca -->
        <section class="barra-busca" aria-label="Buscar aluno">
            <form method="get" action="admin.php">
                <input type="text" name="busca" placeholder="Buscar por nome ou usuário..."
                       value="<?= htmlspecialchars($busca) ?>">
                <button type="submit" class="btn-buscar">Buscar</button>
                <?php if ($busca !== ''): ?>
                    <a class="btn-limpar" href="admin.php">Limpar</a>
                <?php endif; ?>
                <!-- Botão que abre o popup de cadastro -->
                <button type="button" class="btn-cadastrar" onclick="abrirModalCadastro()">Cadastrar</button>
            </form>
        </section>

        <!-- Tabela de alunos x cursos -->
        <section class="tabela-wrap" aria-label="Progresso dos alunos">
            <?php if ($total_alunos === 0): ?>
                <p class="vazio">Nenhum aluno encontrado.</p>
            <?php else: ?>
            <table class="tabela-admin">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <?php foreach ($CURSOS as $curso): ?>
                            <th class="col-curso"><?= htmlspecialchars($curso['nome']) ?></th>
                        <?php endforeach; ?>
                        <th class="col-total">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($aluno = $alunos->fetch_assoc()):
                        $aid = $aluno['id'];
                        $soma_aluno = 0;
                    ?>
                        <tr>
                            <td class="cel-aluno">
                                <span class="nome-aluno"><?= htmlspecialchars($aluno['nome']) ?></span>
                                <span class="user-aluno">@<?= htmlspecialchars($aluno['user']) ?></span>
                                <?php if ((int)$aluno['adm'] === 1): ?>
                                    <span class="tag-adm">admin</span>
                                <?php endif; ?>
                            </td>

                            <?php foreach ($CURSOS as $curso_id => $curso):
                                $total_c    = count($curso['aulas']);
                                $feitas     = isset($prog[$aid][$curso_id]) ? $prog[$aid][$curso_id] : 0;
                                $soma_aluno += $feitas;
                                $pct        = $total_c > 0 ? round(($feitas / $total_c) * 100) : 0;
                                $completo   = $feitas >= $total_c && $total_c > 0;
                            ?>
                                <td class="cel-curso">
                                    <div class="mini-barra" role="progressbar"
                                         aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"
                                         title="<?= $feitas ?> de <?= $total_c ?> aulas">
                                        <div class="mini-preenchida <?= $completo ? 'ok' : '' ?>"
                                             style="width: <?= $pct ?>%"></div>
                                    </div>
                                    <span class="mini-texto"><?= $feitas ?>/<?= $total_c ?></span>
                                </td>
                            <?php endforeach; ?>

                            <?php
                                $pct_total = $total_aulas_plataforma > 0
                                    ? round(($soma_aluno / $total_aulas_plataforma) * 100) : 0;
                            ?>
                            <td class="cel-total">
                                <div class="total-wrap">
                                    <span class="total-pct"><?= $pct_total ?>%</span>
                                    <div class="total-acoes">
                                        <button type="button" class="btn-acao btn-editar"
                                            title="Editar aluno"
                                            data-id="<?= (int)$aluno['id'] ?>"
                                            data-nome="<?= htmlspecialchars($aluno['nome'], ENT_QUOTES) ?>"
                                            data-user="<?= htmlspecialchars($aluno['user'], ENT_QUOTES) ?>"
                                            data-cpf="<?= htmlspecialchars($aluno['cpf'], ENT_QUOTES) ?>"
                                            data-nasc="<?= htmlspecialchars($aluno['data_nasc'], ENT_QUOTES) ?>"
                                            data-adm="<?= (int)$aluno['adm'] ?>"
                                            onclick="abrirModalEditar(this)">Editar</button>

                                        <?php if (isset($concluiu_certificado[(int)$aluno['id']])): ?>
                                            <a class="btn-acao btn-certificado"
                                               href="certificado.php?aluno_id=<?= (int)$aluno['id'] ?>"
                                               target="_blank" rel="noopener"
                                               title="Gerar certificado de conclusão">Certificado</a>
                                        <?php else: ?>
                                            <button type="button" class="btn-acao btn-certificado-off"
                                                    title="O aluno ainda não concluiu a última aula do curso"
                                                    disabled>Certificado</button>
                                        <?php endif; ?>

                                        <form method="post" action="admin.php" class="form-excluir"
                                            onsubmit="return confirmarExclusao('<?= htmlspecialchars(addslashes($aluno['nome']), ENT_QUOTES) ?>');">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= (int)$aluno['id'] ?>">
                                            <button type="submit" class="btn-acao btn-excluir" title="Excluir aluno">Excluir</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
    </main>

    <!-- Popup / Modal de cadastro de aluno -->
    <div class="modal-overlay <?= $abrir_modal ? 'aberto' : '' ?>" id="modalCadastro">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="tituloModal">
            <div class="modal-topo">
                <h2 id="tituloModal">Cadastrar novo aluno</h2>
                <button type="button" class="btn-fechar-modal" onclick="fecharModalCadastro()">Cancelar</button>
            </div>
            <p class="modal-sub">Preencha os dados do aluno abaixo.</p>

            <?php if ($cadastro_erro && $cadastro_msg !== ''): ?>
                <p class="msg-feedback erro"><?= htmlspecialchars($cadastro_msg) ?></p>
            <?php endif; ?>

            <form method="post" action="admin.php">
                <input type="hidden" name="acao" value="cadastrar">

                <div class="campo">
                    <label for="c_nome">Nome completo</label>
                    <input type="text" id="c_nome" name="nome" maxlength="164"
                           value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                </div>

                <div class="campo">
                    <label for="c_user">Usuário</label>
                    <input type="text" id="c_user" name="user" maxlength="25"
                           value="<?= htmlspecialchars($_POST['user'] ?? '') ?>" required>
                </div>

                <div class="aviso-senha-padrao">
                    🔑 A senha inicial deste aluno será <strong>123</strong>. Ele será obrigado a
                    trocá-la no primeiro acesso.
                </div>

                <div class="campo">
                    <label for="c_cpf">CPF</label>
                    <input type="text" id="c_cpf" name="cpf" maxlength="15"
                           placeholder="000.000.000-00"
                           value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>" required>
                </div>

                <div class="campo">
                    <label for="c_nasc">Data de nascimento</label>
                    <input type="date" id="c_nasc" name="data_nasc"
                           value="<?= htmlspecialchars($_POST['data_nasc'] ?? '') ?>" required>
                </div>

                <div class="campo-check">
                    <input type="checkbox" id="c_adm" name="adm" value="1"
                           <?= isset($_POST['adm']) ? 'checked' : '' ?>>
                    <label for="c_adm">Tornar administrador</label>
                </div>

                <div class="modal-acoes">
                    <button type="submit" class="btn-salvar">Salvar aluno</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup / Modal de EDIÇÃO de aluno -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="tituloModalEditar">
            <div class="modal-topo">
                <h2 id="tituloModalEditar">Editar aluno</h2>
                <button type="button" class="btn-fechar-modal" onclick="fecharModalEditar()">Cancelar</button>
            </div>
            <p class="modal-sub">Altere os dados do aluno abaixo.</p>

            <form method="post" action="admin.php">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="e_id">

                <div class="campo">
                    <label for="e_nome">Nome completo</label>
                    <input type="text" id="e_nome" name="nome" maxlength="164" required>
                </div>

                <div class="campo">
                    <label for="e_user">Usuário</label>
                    <input type="text" id="e_user" name="user" maxlength="25" required>
                </div>

                <div class="campo-check">
                    <input type="checkbox" id="e_resetar_senha" name="resetar_senha" value="1">
                    <label for="e_resetar_senha">Resetar senha para o padrão (123) — aluno terá que trocá-la no próximo login</label>
                </div>

                <div class="campo">
                    <label for="e_cpf">CPF</label>
                    <input type="text" id="e_cpf" name="cpf" maxlength="15"
                           placeholder="000.000.000-00" required>
                </div>

                <div class="campo">
                    <label for="e_nasc">Data de nascimento</label>
                    <input type="date" id="e_nasc" name="data_nasc" required>
                </div>

                <div class="campo-check">
                    <input type="checkbox" id="e_adm" name="adm" value="1">
                    <label for="e_adm">Tornar administrador</label>
                </div>

                <div class="modal-acoes">
                    <button type="submit" class="btn-salvar">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ===== Efeito de encolher/opacar a navbar ao rolar =====
        const navbar = document.querySelector("nav");
        function handleNavScroll() {
            if (window.scrollY > 40) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }
        }
        window.addEventListener("scroll", handleNavScroll);
        handleNavScroll();

        /* -------- Modal de cadastro -------- */
        const modalCadastro = document.getElementById('modalCadastro');

        function abrirModalCadastro() {
            modalCadastro.classList.add('aberto');
        }
        function fecharModalCadastro() {
            modalCadastro.classList.remove('aberto');
        }

        // Fecha ao clicar fora da caixa
        modalCadastro.addEventListener('click', function (e) {
            if (e.target === modalCadastro) fecharModalCadastro();
        });

        /* -------- Modal de edição -------- */
        const modalEditar = document.getElementById('modalEditar');

        function abrirModalEditar(btn) {
            document.getElementById('e_id').value   = btn.dataset.id;
            document.getElementById('e_nome').value = btn.dataset.nome;
            document.getElementById('e_user').value = btn.dataset.user;
            document.getElementById('e_cpf').value  = btn.dataset.cpf;
            document.getElementById('e_nasc').value = btn.dataset.nasc;
            document.getElementById('e_resetar_senha').checked = false;
            document.getElementById('e_adm').checked = btn.dataset.adm === '1';
            modalEditar.classList.add('aberto');
        }
        function fecharModalEditar() {
            modalEditar.classList.remove('aberto');
        }
        modalEditar.addEventListener('click', function (e) {
            if (e.target === modalEditar) fecharModalEditar();
        });

        /* -------- Confirmação de exclusão -------- */
        function confirmarExclusao(nome) {
            return confirm('Tem certeza que deseja excluir o aluno "' + nome + '"?\nEssa ação não pode ser desfeita.');
        }

        // Fecha com a tecla ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { fecharModalCadastro(); fecharModalEditar(); }
        });

        /* -------- Máscara automática do CPF (000.000.000-00) -------- */
        function mascararCPF(valor) {
            valor = valor.replace(/\D/g, '').slice(0, 11); // só números, máx. 11
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
            valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            return valor;
        }

        /* -------- Validação matemática do CPF (dígitos verificadores) -------- */
        function cpfValido(cpf) {
            cpf = cpf.replace(/\D/g, '');
            if (cpf.length !== 11) return false;
            // Rejeita sequências repetidas (000..., 111..., etc.)
            if (/^(\d)\1{10}$/.test(cpf)) return false;

            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.charAt(9))) return false;

            soma = 0;
            for (let i = 0; i < 10; i++) {
                soma += parseInt(cpf.charAt(i)) * (11 - i);
            }
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.charAt(10))) return false;

            return true;
        }

        // Aplica a máscara enquanto o usuário digita, nos dois modais
        ['c_cpf', 'e_cpf'].forEach(function (id) {
            const campo = document.getElementById(id);
            if (!campo) return;
            campo.addEventListener('input', function () {
                this.value = mascararCPF(this.value);
            });
            // Valida ao enviar o formulário
            campo.closest('form').addEventListener('submit', function (e) {
                if (!cpfValido(campo.value)) {
                    e.preventDefault();
                    campo.focus();
                    alert('CPF inválido. Verifique os números digitados.');
                }
            });
        });
    </script>

</body>
</html>
