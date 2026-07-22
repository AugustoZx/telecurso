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

    $aluno_id = $_SESSION['aluno_id'];

    // Progresso do aluno em todos os cursos (usado para o bloqueio sequencial)
    $progresso = buscar_progresso($conexao, $aluno_id);

    /* -------- Ação: marcar aula como concluída (POST) -------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['concluir'], $_POST['curso'], $_POST['aula'])) {

        $curso_id = $_POST['curso'];
        $aula_num = (int) $_POST['aula'];

        // Bloqueio: não deixa concluir aula de curso que ainda não foi liberado
        if (!curso_liberado($CURSOS, $curso_id, $progresso)) {
            header('Location: dashboard.php?bloqueado=' . urlencode($curso_id));
            exit;
        }

        // Só grava se o curso e a aula realmente existirem
        if (isset($CURSOS[$curso_id]) && $aula_num >= 1
            && $aula_num <= total_aulas($CURSOS, $curso_id)) {

            // INSERT IGNORE evita erro se a aula já estava concluída
            $sql = "INSERT IGNORE INTO aulas_concluidas (aluno_id, curso_id, aula_num)
                    VALUES (?, ?, ?)";
            $stmt = $conexao->prepare($sql);
            $stmt->bind_param("isi", $aluno_id, $curso_id, $aula_num);
            $stmt->execute();
        }

        // Redireciona para a próxima aula, para o quiz (se houver) ou de volta ao painel
        $total = total_aulas($CURSOS, $curso_id);
        if ($aula_num < $total) {
            header('Location: curso.php?curso=' . urlencode($curso_id) . '&aula=' . ($aula_num + 1));
        } elseif (curso_tem_quiz($CURSOS, $curso_id)) {
            header('Location: quiz.php?curso=' . urlencode($curso_id));
        } else {
            header('Location: dashboard.php?concluido=' . urlencode($curso_id));
        }
        exit;
    }

    /* -------- Exibição da aula (GET) -------- */
    $curso_id = $_GET['curso'] ?? '';
    $aula_num = isset($_GET['aula']) ? (int) $_GET['aula'] : 1;

    // Validações
    if (!isset($CURSOS[$curso_id])) {
        header('Location: dashboard.php');
        exit;
    }

    // Bloqueio: curso trancado volta para o painel com aviso
    if (!curso_liberado($CURSOS, $curso_id, $progresso)) {
        header('Location: dashboard.php?bloqueado=' . urlencode($curso_id));
        exit;
    }

    $curso = $CURSOS[$curso_id];
    $total = count($curso['aulas']);
    if ($aula_num < 1)      $aula_num = 1;
    if ($aula_num > $total) $aula_num = $total;

    $aula = $curso['aulas'][$aula_num - 1];

    // Descobre quais aulas deste curso o aluno já concluiu (para a lista lateral)
    $feitas = [];
    $sql = "SELECT aula_num FROM aulas_concluidas WHERE aluno_id = ? AND curso_id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("is", $aluno_id, $curso_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($l = $res->fetch_assoc()) {
        $feitas[(int)$l['aula_num']] = true;
    }
    $aula_concluida = isset($feitas[$aula_num]);
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
    <title><?= htmlspecialchars($curso['nome']) ?> — Aula <?= $aula_num ?></title>
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

        .aula-texto img {
            max-width: 100%;
            border-radius: 8px;
            margin: 10px 0;
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

        .btn-curso.secundario {
            background-color: #c0c0c0;
            color: #193e8f;
        }

        .btn-curso.secundario:hover {
            background-color: #a9a9a9;
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

            .aula-layout {
                grid-template-columns: 1fr;
            }

            .aula-menu {
                position: static;
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
                <a href="dashboard.php"><li>PAINEL</li></a>
                <a href="index.php"><li>INÍCIO</li></a>
            </ul>
        </div>
        <a class="b_login" href="logout.php">Sair</a>
    </nav>

    <main class="aula-layout">

        <!-- Lista de aulas do curso -->
        <aside class="aula-menu" aria-label="Aulas do curso">
            <h2><?= htmlspecialchars($curso['nome']) ?></h2>
            <ol>
                <?php foreach ($curso['aulas'] as $i => $a):
                    $num = $i + 1;
                    $classe = $num === $aula_num ? 'ativa' : '';
                    $ok = isset($feitas[$num]);
                ?>
                    <li class="<?= $classe ?>">
                        <a href="curso.php?curso=<?= urlencode($curso_id) ?>&amp;aula=<?= $num ?>">
                            <span class="marcador <?= $ok ? 'ok' : '' ?>" aria-hidden="true"></span>
                            <?= $num ?>. <?= htmlspecialchars($a['titulo']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        </aside>

        <!-- Conteúdo da aula -->
        <article class="aula-conteudo">
            <p class="aula-breadcrumb">
                <a href="dashboard.php">Painel</a> &rsaquo; <?= htmlspecialchars($curso['nome']) ?>
            </p>
            <h1> <?= $aula_num ?>. <?= htmlspecialchars($aula['titulo']) ?></h1>

            <div class="aula-texto">
                <?php if ($aula['midia_tipo'] === 'imagem' && $aula['midia_arquivo']): ?>
                    <img src="<?= htmlspecialchars($aula['midia_arquivo']) ?>" alt=""
                         style="max-width:100%;border-radius:10px;margin-bottom:16px;">
                <?php elseif ($aula['midia_tipo'] === 'video' && $aula['midia_arquivo']): ?>
                    <video controls style="max-width:100%;border-radius:10px;margin-bottom:16px;">
                        <source src="<?= htmlspecialchars($aula['midia_arquivo']) ?>">
                    </video>
                <?php endif; ?>
                <?= $aula['conteudo'] // HTML sanitizado, vindo do painel admin (admin_cursos.php) ?>
            </div>

            <div class="aula-acoes">
                <!-- Anterior -->
                <?php if ($aula_num > 1): ?>
                    <a class="btn-curso secundario"
                       href="curso.php?curso=<?= urlencode($curso_id) ?>&amp;aula=<?= $aula_num - 1 ?>">
                        &larr; Aula anterior
                    </a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <!-- Concluir e ir para a próxima -->
                <form method="POST" action="curso.php">
                    <input type="hidden" name="curso" value="<?= htmlspecialchars($curso_id) ?>">
                    <input type="hidden" name="aula" value="<?= $aula_num ?>">
                    <button type="submit" name="concluir" class="btn-curso">
                        <?php if ($aula_num < $total): ?>
                            <?= $aula_concluida ? 'Próxima aula →' : 'Concluir e continuar →' ?>
                        <?php elseif (curso_tem_quiz($CURSOS, $curso_id)): ?>
                            Ir para o quiz →
                        <?php else: ?>
                            <?= $aula_concluida ? 'Voltar ao painel' : 'Concluir curso' ?>
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </article>

    </main>

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
    </script>
</body>
</html>
