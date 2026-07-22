<?php
    session_start();

    /* -------- Proteção: só entra quem está logado -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: index.php');
        exit;
    }

    include_once('config.php');
    include_once('cursos.php');

    $CURSOS = buscar_cursos_ativos($conexao);

    $aluno_id   = $_SESSION['aluno_id'];
    $aluno_nome = $_SESSION['aluno_nome'] ?? $_SESSION['aluno_user'] ?? 'Aluno';

    /* -------- Troca de senha obrigatória --------
       Definida no login (vLogin.php) a partir da coluna `trocar_senha`. */
    $precisa_trocar_senha = !empty($_SESSION['trocar_senha']);

    $senha_trocada_ok = isset($_GET['senha']) && $_GET['senha'] === 'ok';

    $senha_erro_msg = '';
    if (isset($_GET['senha_erro'])) {
        switch ($_GET['senha_erro']) {
            case 'confirmacao':
                $senha_erro_msg = 'As senhas digitadas não coincidem.';
                break;
            case 'tamanho':
                $senha_erro_msg = 'A nova senha deve ter pelo menos 4 caracteres.';
                break;
            case 'padrao':
                $senha_erro_msg = 'Escolha uma senha diferente da senha padrão (123).';
                break;
            default:
                $senha_erro_msg = 'Não foi possível trocar a senha. Tente novamente.';
        }
    }

    /* -------- Frases motivacionais --------
       Adicione novas frases aqui, uma por linha, entre aspas simples e vírgula. */
    $FRASES_MOTIVACIONAIS = [
        'Continue seus estudos de onde parou.',
        'Cada aula concluída é um passo mais perto do seu objetivo.',
        'O conhecimento é a única riqueza que ninguém pode tirar de você.',
        'Grandes conquistas começam com a decisão de tentar.',
        'Aprender hoje é construir o seu amanhã.',
        'A persistência é o caminho do êxito.',
        'Você é capaz de mais do que imagina. Siga em frente!',
        'Pequenos progressos diários levam a grandes resultados.',
    ];

    /* Escolhe uma frase aleatória a cada carregamento da página */
    $frase_do_dia = $FRASES_MOTIVACIONAIS[array_rand($FRASES_MOTIVACIONAIS)];

    /* -------- Busca o progresso do aluno no banco --------
       Retorna, por curso: quantas aulas concluiu e qual foi a última. */
    $progresso = []; // curso_id => ['concluidas' => n, 'ultima_aula' => n, 'ultimo_acesso' => data]

    $sql = "SELECT curso_id,
                   COUNT(*)        AS concluidas,
                   MAX(aula_num)   AS ultima_aula,
                   MAX(data)       AS ultimo_acesso
            FROM aulas_concluidas
            WHERE aluno_id = ?
            GROUP BY curso_id";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $aluno_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($linha = $res->fetch_assoc()) {
        $progresso[$linha['curso_id']] = $linha;
    }

    /* -------- Descobre "continuar de onde parou" --------
       Pega o curso com atividade mais recente que ainda não terminou. */
    $continuar = null; // ['curso_id', 'aula', 'nome']
    $mais_recente = '';
    foreach ($progresso as $curso_id => $p) {
        if (!isset($CURSOS[$curso_id])) continue;
        $total = total_aulas($CURSOS, $curso_id);
        if ($p['concluidas'] >= $total) continue; // já concluiu esse curso

        if ($p['ultimo_acesso'] > $mais_recente) {
            $mais_recente = $p['ultimo_acesso'];
            $proxima = min($p['ultima_aula'] + 1, $total); // próxima aula
            $continuar = [
                'curso_id' => $curso_id,
                'aula'     => $proxima,
                'nome'     => $CURSOS[$curso_id]['nome'],
            ];
        }
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
    <title>Meu Painel</title>
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

        .titulo-secao {
            font-size: 26px;
            margin-bottom: 18px;
        }

        /* ===================== CONTINUAR DE ONDE PAROU ===================== */
        .continuar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background-color: #193e8f;
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .continuar .rotulo {
            display: inline-block;
            font-size: 13px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #cdd8f2;
            margin-bottom: 6px;
        }

        .continuar h2 {
            color: #fff;
            font-size: 28px;
        }

        .continuar p {
            color: #cdd8f2;
            margin-top: 4px;
            font-size: 16px;
        }

        .btn-continuar {
            padding: 14px 40px;
            background-color: #ea3e44;
            color: #fff;
            font-weight: bold;
            font-size: 17px;
            text-decoration: none;
            border-radius: 25px;
            transition: transform 0.2s ease, background-color 0.3s ease;
        }

        .btn-continuar:hover {
            background-color: #fff;
            color: #193e8f;
            transform: translateY(-2px);
        }

        /* ===================== GRADE DE CURSOS ===================== */
        .grade-cursos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .card-curso {
            background-color: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(25, 62, 143, 0.08);
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .card-curso:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(25, 62, 143, 0.15);
        }

        .card-img {
            background-color: #f2f5fc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            height: 150px;
        }

        .card-img img {
            max-height: 100%;
            max-width: 60%;
            object-fit: contain;
        }

        .card-corpo {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-corpo h3 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .card-desc {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
            flex: 1;
        }

        /* ===================== BARRA DE PROGRESSO ===================== */
        .progresso-info {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #555;
            margin-bottom: 6px;
        }

        .barra {
            width: 100%;
            height: 10px;
            background-color: #e2e8f5;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .barra-preenchida {
            height: 100%;
            background-color: #193e8f;
            border-radius: 6px;
            transition: width 0.6s ease;
        }

        /* ===================== BOTÕES DE CURSO ===================== */
        .btn-curso {
            display: inline-block;
            text-align: center;
            padding: 12px 20px;
            background-color: #193e8f;
            color: #eee;
            font-weight: bold;
            font-size: 15px;
            text-decoration: none;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: background-color 0.4s ease;
        }

        .btn-curso:hover {
            background-color: #ea3e44;
        }

        .btn-curso.concluido {
            background-color: #2e8b57;
        }

        .btn-curso.concluido:hover {
            background-color: #ea3e44;
        }

        .btn-curso.secundario {
            background-color: #c0c0c0;
            color: #193e8f;
        }

        .btn-curso.secundario:hover {
            background-color: #a9a9a9;
        }

        /* ===================== PÁGINA DE AULA ===================== */
        .aula-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 28px;
            align-items: start;
        }

        .aula-menu {
            background-color: #fff;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 14px rgba(25, 62, 143, 0.08);
            position: sticky;
            top: 100px;
        }

        .aula-menu h2 {
            font-size: 18px;
            margin-bottom: 14px;
        }

        .aula-menu ol {
            list-style: none;
        }

        .aula-menu li a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 8px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            color: #333;
            transition: background-color 0.2s ease;
        }

        .aula-menu li a:hover {
            background-color: #f2f5fc;
        }

        .aula-menu li.ativa a {
            background-color: #f2f5fc;
            color: #193e8f;
            font-weight: bold;
        }

        .marcador {
            width: 14px;
            height: 14px;
            min-width: 14px;
            border-radius: 50%;
            border: 2px solid #c0c0c0;
            display: inline-block;
        }

        .marcador.ok {
            background-color: #2e8b57;
            border-color: #2e8b57;
        }

        .aula-conteudo {
            background-color: #fff;
            border-radius: 14px;
            padding: 32px;
            box-shadow: 0 4px 14px rgba(25, 62, 143, 0.08);
        }

        .aula-breadcrumb {
            font-size: 14px;
            color: #777;
            margin-bottom: 12px;
        }

        .aula-breadcrumb a {
            color: #193e8f;
            text-decoration: none;
        }

        .aula-breadcrumb a:hover {
            color: #ea3e44;
        }

        .aula-conteudo h1 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        .aula-texto {
            color: #333;
            font-size: 17px;
            line-height: 1.7;
        }

        .aula-texto p {
            color: #333;
            margin-bottom: 14px;
        }

        .aula-acoes {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #eee;
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

            .continuar {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-continuar { width: 100%; text-align: center; }

            .aula-layout {
                grid-template-columns: 1fr;
            }

            .aula-menu {
                position: static;
            }

            .resumo-admin {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .card-num .num { font-size: 30px; }
        }

        /* --- Estilos do bloqueio sequencial de cursos --- */
        .aviso-bloqueio {
            background: #fff4e5;
            border: 1px solid #ffcf99;
            color: #8a4b00;
            padding: 14px 18px;
            border-radius: 8px;
            margin: 0 0 20px;
            font-size: 15px;
        }
        .card-curso.bloqueado { opacity: .7; }
        .card-curso.bloqueado .card-img { position: relative; }
        .card-curso.bloqueado .card-img img { filter: grayscale(100%); }
        .card-curso .cadeado {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-size: 40px;
            text-shadow: 0 2px 6px rgba(0,0,0,.4);
        }
        .msg-bloqueio {
            font-size: 14px;
            color: #8a4b00;
            margin: 8px 0 12px;
        }
        .btn-curso.bloqueado {
            background: #cccccc;
            color: #666666;
            cursor: not-allowed;
            border: none;
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            font: inherit;
        }

        /* ===================== MODAL OBRIGATÓRIO: TROCAR SENHA ===================== */
        /* Mesmo visual do modal de login do index.php, porém sem opção de fechar. */
        .senha-overlay {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-color: rgba(25, 62, 143, 0.35);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .senha-overlay.aberto {
            display: flex;
        }

        .senha-card {
            width: 100%;
            max-width: 420px;
            background-color: #ffffff;
            border-radius: 20px;
            padding: 44px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
        }

        .senha-card .selo {
            display: inline-block;
            background: #eef4ff;
            color: #193e8f;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            padding: 6px 12px;
            border-radius: 9999px;
            margin-bottom: 14px;
        }

        .senha-card h2 {
            font-size: 30px;
            font-weight: 800;
            color: #193e8f;
            margin-bottom: 6px;
        }

        .senha-card .subtitulo {
            color: #666;
            font-size: 15px;
            margin-bottom: 26px;
            line-height: 1.4;
        }

        .senha-card .campo {
            margin-bottom: 16px;
        }

        .senha-card label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #193e8f;
            margin-bottom: 6px;
        }

        .senha-card input {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            color: #193e8f;
            background-color: #f4f6fb;
            border: 1px solid #dfe4f2;
            border-radius: 10px;
            outline: none;
            font-family: 'Roboto', sans-serif;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .senha-card input:focus {
            border-color: #193e8f;
            box-shadow: 0 0 0 3px rgba(25, 62, 143, 0.15);
        }

        .senha-card .erro-msg {
            display: none;
            background-color: #fdeaea;
            color: #ea3e44;
            border: 1px solid #f5c6c8;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .senha-card .erro-msg.visivel {
            display: block;
        }

        .senha-card .btn-trocar {
            width: 100%;
            padding: 15px;
            margin-top: 8px;
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            background-color: #193e8f;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Roboto', sans-serif;
            transition: background-color 0.4s ease;
        }

        .senha-card .btn-trocar:hover {
            background-color: #ea3e44;
        }

        @media (max-width: 480px) {
            .senha-card {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body class="no-select">

    <nav>
        <div class="logo">
            <a href="dashboard.php"><img src="img/logo.png" alt="Logo do Telecentro"></a>
        </div>
        <div class="elem">
            <ul>
                <a href="dashboard.php"><li>MEU PAINEL</li></a>
                <?php if (!empty($_SESSION['adm']) && (int)$_SESSION['adm'] === 1): ?>
                    <a href="admin.php"><li>ADMIN</li></a>
                <?php endif; ?>
                <a href="index.php"><li>INÍCIO</li></a>
            </ul>
        </div>
        <a class="b_login" href="logout.php">Sair</a>
    </nav>

    <main>
        <!-- Saudação -->
        <header class="saudacao">
            <h1>Olá, <?= htmlspecialchars($aluno_nome) ?>!</h1>
            <p><?= htmlspecialchars($frase_do_dia) ?></p>
        </header>

        <?php if ($senha_trocada_ok): ?>
            <div class="aviso-bloqueio" role="status" style="background:#e3f6e9; border-color:#a9dfc0; color:#1f7a44;">
                ✅ Senha alterada com sucesso!
            </div>
        <?php endif; ?>

        <!-- Aviso de curso bloqueado -->
        <?php if (isset($_GET['bloqueado']) && isset($CURSOS[$_GET['bloqueado']])):
            $nome_bloqueado  = $CURSOS[$_GET['bloqueado']]['nome'];
            $nome_requisito  = curso_anterior_nome($CURSOS, $_GET['bloqueado']);
        ?>
            <div class="aviso-bloqueio" role="alert">
                🔒 O curso <strong><?= htmlspecialchars($nome_bloqueado) ?></strong> ainda está bloqueado.
                <?php if ($nome_requisito): ?>
                    Conclua o módulo <strong><?= htmlspecialchars($nome_requisito) ?></strong> para liberá-lo.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Continuar de onde parou -->
        <?php if ($continuar): ?>
            <section class="continuar" aria-label="Continuar de onde parou">
                <div class="continuar-texto">
                    <span class="rotulo">Continuar de onde parou</span>
                    <h2><?= htmlspecialchars($continuar['nome']) ?></h2>
                    <p>Retomar na Aula <?= (int)$continuar['aula'] ?></p>
                </div>
                <a class="btn-continuar"
                   href="curso.php?curso=<?= urlencode($continuar['curso_id']) ?>&amp;aula=<?= (int)$continuar['aula'] ?>">
                    Continuar
                </a>
            </section>
        <?php endif; ?>

        <!-- Lista de cursos -->
        <section class="cursos" aria-label="Seus cursos">
            <h2 class="titulo-secao">Módulos</h2>

            <div class="grade-cursos">
                <?php foreach ($CURSOS as $curso_id => $curso):
                    $total       = count($curso['aulas']);
                    $concluidas  = isset($progresso[$curso_id]) ? (int)$progresso[$curso_id]['concluidas'] : 0;
                    $percentual  = $total > 0 ? round(($concluidas / $total) * 100) : 0;
                    $ultima      = isset($progresso[$curso_id]) ? (int)$progresso[$curso_id]['ultima_aula'] : 0;
                    $proxima     = min($ultima + 1, $total);
                    $terminou    = $concluidas >= $total && $total > 0;
                    $liberado    = curso_liberado($CURSOS, $curso_id, $progresso);
                    $requisito   = curso_anterior_nome($CURSOS, $curso_id);
                ?>
                    <article class="card-curso <?= $liberado ? '' : 'bloqueado' ?>">
                        <div class="card-img">
                            <img src="<?= htmlspecialchars($curso['imagem']) ?>" alt="<?= htmlspecialchars($curso['nome']) ?>">
                            <?php if (!$liberado): ?>
                                <span class="cadeado" aria-hidden="true">🔒</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-corpo">
                            <h3><?= htmlspecialchars($curso['nome']) ?></h3>
                            <p class="card-desc"><?= htmlspecialchars($curso['descricao']) ?></p>

                            <?php if (!$liberado): ?>
                                <!-- Curso bloqueado -->
                                <p class="msg-bloqueio">
                                    🔒 Bloqueado
                                    <?php if ($requisito): ?>
                                        — conclua <strong><?= htmlspecialchars($requisito) ?></strong> para liberar.
                                    <?php endif; ?>
                                </p>
                                <button class="btn-curso bloqueado" type="button" disabled aria-disabled="true">
                                    Módulo bloqueado
                                </button>
                            <?php else: ?>
                                <!-- Barra de progresso -->
                                <div class="progresso-info">
                                    <span><?= $concluidas ?> de <?= $total ?> aulas</span>
                                    <span><?= $percentual ?>%</span>
                                </div>
                                <div class="barra" role="progressbar"
                                     aria-valuenow="<?= $percentual ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="barra-preenchida" style="width: <?= $percentual ?>%"></div>
                                </div>

                                <?php if ($terminou): ?>
                                    <a class="btn-curso concluido"
                                       href="curso.php?curso=<?= urlencode($curso_id) ?>&amp;aula=1">
                                        Concluído — revisar
                                    </a>
                                <?php elseif ($concluidas > 0): ?>
                                    <a class="btn-curso"
                                       href="curso.php?curso=<?= urlencode($curso_id) ?>&amp;aula=<?= $proxima ?>">
                                        Continuar (Aula <?= $proxima ?>)
                                    </a>
                                <?php else: ?>
                                    <a class="btn-curso"
                                       href="curso.php?curso=<?= urlencode($curso_id) ?>&amp;aula=1">
                                        Começar Módulo
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <!-- ===== MODAL OBRIGATÓRIO: TROCAR SENHA (primeiro acesso) ===== -->
    <div class="senha-overlay <?= $precisa_trocar_senha ? 'aberto' : '' ?>" id="senhaOverlay">
        <div class="senha-card" role="dialog" aria-modal="true" aria-labelledby="tituloSenha">
            <span class="selo">Primeiro acesso</span>
            <h2 id="tituloSenha">Troque sua senha</h2>
            <p class="subtitulo">
                Por segurança, você está usando a senha padrão. Defina uma senha nova
                antes de continuar navegando pela plataforma.
            </p>

            <div class="erro-msg <?= $senha_erro_msg !== '' ? 'visivel' : '' ?>">
                <?= htmlspecialchars($senha_erro_msg) ?>
            </div>

            <form action="trocar_senha.php" method="POST">
                <div class="campo">
                    <label for="nova_senha">Nova senha</label>
                    <input type="password" id="nova_senha" name="nova_senha"
                           placeholder="Digite a nova senha" minlength="4" required>
                </div>

                <div class="campo">
                    <label for="confirmar_senha">Confirmar nova senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha"
                           placeholder="Repita a nova senha" minlength="4" required>
                </div>

                <button type="submit" class="btn-trocar">Salvar nova senha</button>
            </form>
        </div>
    </div>

    <script>
        // Bloqueios (menu de contexto e F12)
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });
        document.addEventListener('keydown', function (e) {
            if (e.keyCode === 123) { // F12
                e.preventDefault();
            }
        });

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

        // ===== Modal obrigatório de troca de senha =====
        // Sem botão de fechar, sem clique fora e sem ESC: o aluno precisa trocar a senha.
        if (document.getElementById('senhaOverlay').classList.contains('aberto')) {
            document.body.style.overflow = 'hidden';
        }
    </script>
</body>
</html>
