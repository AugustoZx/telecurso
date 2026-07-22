<?php
    session_start();

    /* -------- Proteção: só entra quem está logado -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: index.php');
        exit;
    }

    include_once('config.php');
    include_once('cursos.php');

    $CURSOS    = buscar_cursos_ativos($conexao);
    $aluno_id  = $_SESSION['aluno_id'];
    $progresso = buscar_progresso($conexao, $aluno_id);

    // Curso vindo tanto do GET quanto do POST
    $curso_id = $_POST['curso'] ?? $_GET['curso'] ?? '';

    /* -------- Validações -------- */
    if (!isset($CURSOS[$curso_id]) || !curso_tem_quiz($CURSOS, $curso_id)) {
        header('Location: dashboard.php');
        exit;
    }
    if (!curso_liberado($CURSOS, $curso_id, $progresso)) {
        header('Location: dashboard.php?bloqueado=' . urlencode($curso_id));
        exit;
    }
    if (!aulas_concluidas_todas($CURSOS, $curso_id, $progresso)) {
        header('Location: curso.php?curso=' . urlencode($curso_id) . '&aula=1');
        exit;
    }

    $curso      = $CURSOS[$curso_id];
    $perguntas  = $curso['quiz'];
    $total      = count($perguntas);
    $nota_minima = $curso['nota_minima_quiz'];

    $resultado = null; // preenchido após enviar as respostas

    /* -------- Correção do quiz (POST) -------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_quiz'])) {

        $respostas = $_POST['resp'] ?? []; // resp[pergunta_id] = opcao_id
        $acertos   = 0;
        $detalhe   = []; // pergunta_id => ['marcada'=>opcao_id, 'certa'=>opcao_id, 'ok'=>bool]

        foreach ($perguntas as $p) {
            $pid     = $p['id'];
            $marcada = isset($respostas[$pid]) ? (int) $respostas[$pid] : null;
            $certa   = $p['correta_id'];
            $ok      = ($marcada !== null && $marcada === $certa);
            if ($ok) $acertos++;
            $detalhe[$pid] = ['marcada' => $marcada, 'certa' => $certa, 'ok' => $ok];
        }

        $percentual = $total > 0 ? round(($acertos / $total) * 100, 2) : 0;
        $aprovado   = $percentual >= $nota_minima ? 1 : 0;

        // Salva a tentativa (mantém histórico; o desbloqueio usa a MELHOR nota)
        $sql = "INSERT INTO quiz_resultados (aluno_id, curso_id, acertos, total, percentual, aprovado)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("isiidi", $aluno_id, $curso_id, $acertos, $total, $percentual, $aprovado);
        $stmt->execute();

        $resultado = [
            'acertos'    => $acertos,
            'total'      => $total,
            'percentual' => $percentual,
            'aprovado'   => $aprovado,
            'detalhe'    => $detalhe,
        ];
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
    <title>Quiz — <?= htmlspecialchars($curso['nome']) ?></title>
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

        main { max-width: 760px; margin: 0 auto; padding: 110px 16px 60px; }

        .quiz-breadcrumb { color: #777; font-size: 14px; margin-bottom: 8px; }
        .quiz-breadcrumb a { color: #193e8f; text-decoration: none; }
        .quiz-breadcrumb a:hover { color: #ea3e44; }
        main h1 { font-size: 28px; margin: 4px 0 4px; }
        .quiz-info { color: #333; margin-bottom: 24px; }
        .quiz-nota-min { font-weight: 700; color: #193e8f; }

        .quiz-pergunta {
            background: #fff; border-radius: 14px; box-shadow: 0 4px 14px rgba(25, 62, 143, 0.08);
            padding: 22px 24px; margin-bottom: 16px;
        }
        .quiz-pergunta .enunciado { font-weight: 700; margin: 0 0 12px; color: #193e8f; }
        .quiz-opcao {
            display: flex; align-items: center; gap: 10px; padding: 10px 12px;
            border: 1px solid #e2e6ec; border-radius: 8px; margin-bottom: 8px; cursor: pointer; color: #333;
        }
        .quiz-opcao:hover { background: #f2f5fc; }
        .quiz-opcao input { width: 18px; height: 18px; }

        .quiz-enviar {
            display: inline-block; background: #193e8f; color: #eee; border: none;
            padding: 14px 26px; border-radius: 25px; font-size: 16px; font-weight: 700;
            cursor: pointer; margin-top: 8px; transition: background-color 0.4s ease;
        }
        .quiz-enviar:hover { background: #ea3e44; }

        .quiz-resultado { border-radius: 14px; padding: 22px 24px; margin-bottom: 24px; color: #fff; }
        .quiz-resultado.aprovado  { background: #2e8b57; }
        .quiz-resultado.reprovado { background: #c53434; }
        .quiz-resultado h2 { margin: 0 0 6px; font-size: 22px; color: #fff; }
        .quiz-resultado p  { margin: 0; opacity: .95; color: #fff; }
        .quiz-resultado .nota { font-size: 34px; font-weight: 700; color: #fff; }

        .quiz-acoes { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; margin-bottom: 24px; }
        .quiz-btn {
            display: inline-block; padding: 12px 22px; border-radius: 25px; font-weight: 700;
            text-decoration: none; font-size: 15px;
        }
        .quiz-btn.primario   { background: #193e8f; color: #eee; }
        .quiz-btn.primario:hover { background: #ea3e44; }
        .quiz-btn.secundario { background: #c0c0c0; color: #193e8f; }
        .quiz-btn.secundario:hover { background: #a9a9a9; }

        .quiz-revisao .quiz-opcao.certa  { border-color: #2e8b57; background: #e8f5e9; }
        .quiz-revisao .quiz-opcao.errada { border-color: #c53434; background: #fdeaea; }
        .quiz-revisao .tag { font-size: 12px; font-weight: 700; margin-left: auto; }
        .quiz-revisao .tag.ok { color: #2e8b57; }
        .quiz-revisao .tag.no { color: #c53434; }

        @media (max-width: 768px) {
            nav { padding: 12px 20px; }
            nav.scrolled { max-width: calc(100% - 24px); border-radius: 24px; }
            .logo img, nav.scrolled .logo img { width: 100px; }
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

    <main>

        <p class="quiz-breadcrumb">
            <a href="dashboard.php">Painel</a> &rsaquo;
            <a href="curso.php?curso=<?= urlencode($curso_id) ?>&amp;aula=1"><?= htmlspecialchars($curso['nome']) ?></a>
            &rsaquo; Quiz final
        </p>

        <?php if ($resultado === null): ?>

            <!-- ================= FORMULÁRIO DO QUIZ ================= -->
            <h1>Quiz final — <?= htmlspecialchars($curso['nome']) ?></h1>
            <p class="quiz-info">
                Responda às <?= $total ?> perguntas abaixo. Você precisa acertar
                <span class="quiz-nota-min"><?= $nota_minima ?>%</span> ou mais para liberar o próximo curso.
                Pode refazer quantas vezes precisar.
            </p>

            <form method="POST" action="quiz.php" id="form-quiz">
                <input type="hidden" name="curso" value="<?= htmlspecialchars($curso_id) ?>">

                <?php foreach ($perguntas as $i => $p): ?>
                    <div class="quiz-pergunta">
                        <p class="enunciado"><?= ($i + 1) ?>. <?= htmlspecialchars($p['pergunta']) ?></p>
                        <?php foreach ($p['opcoes'] as $opcao): ?>
                            <label class="quiz-opcao">
                                <input type="radio" name="resp[<?= $p['id'] ?>]" value="<?= $opcao['id'] ?>" required>
                                <span><?= htmlspecialchars($opcao['texto']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" name="enviar_quiz" class="quiz-enviar">Enviar respostas</button>
            </form>

        <?php else: ?>

            <!-- ================= RESULTADO ================= -->
            <?php $aprovado = $resultado['aprovado'] === 1; ?>
            <div class="quiz-resultado <?= $aprovado ? 'aprovado' : 'reprovado' ?>">
                <h2><?= $aprovado ? 'Parabéns, você foi aprovado! 🎉' : 'Ainda não foi dessa vez' ?></h2>
                <p class="nota"><?= rtrim(rtrim(number_format($resultado['percentual'], 2, ',', ''), '0'), ',') ?>%</p>
                <p>
                    Você acertou <?= $resultado['acertos'] ?> de <?= $resultado['total'] ?> perguntas.
                    <?php if ($aprovado): ?>
                        O próximo curso já está liberado no seu painel.
                    <?php else: ?>
                        A nota mínima é <?= $nota_minima ?>%. Revise as aulas e tente novamente.
                    <?php endif; ?>
                </p>
            </div>

            <div class="quiz-acoes">
                <?php if ($aprovado): ?>
                    <a class="quiz-btn primario" href="dashboard.php?concluido=<?= urlencode($curso_id) ?>">Ir para o painel →</a>
                <?php else: ?>
                    <a class="quiz-btn primario" href="quiz.php?curso=<?= urlencode($curso_id) ?>">Refazer o quiz</a>
                    <a class="quiz-btn secundario" href="curso.php?curso=<?= urlencode($curso_id) ?>&amp;aula=1">Revisar as aulas</a>
                <?php endif; ?>
            </div>

            <!-- Revisão (mostra o que acertou e a resposta certa) -->
            <div class="quiz-revisao">
                <?php foreach ($perguntas as $i => $p):
                    $d = $resultado['detalhe'][$p['id']];
                ?>
                    <div class="quiz-pergunta">
                        <p class="enunciado"><?= ($i + 1) ?>. <?= htmlspecialchars($p['pergunta']) ?></p>
                        <?php foreach ($p['opcoes'] as $opcao):
                            $classe = '';
                            if ($opcao['id'] === $d['certa'])                          $classe = 'certa';
                            elseif ($opcao['id'] === $d['marcada'] && !$d['ok'])       $classe = 'errada';
                        ?>
                            <div class="quiz-opcao <?= $classe ?>">
                                <span><?= htmlspecialchars($opcao['texto']) ?></span>
                                <?php if ($opcao['id'] === $d['certa']): ?>
                                    <span class="tag ok">Resposta certa</span>
                                <?php elseif ($opcao['id'] === $d['marcada'] && !$d['ok']): ?>
                                    <span class="tag no">Sua resposta</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </main>

    <script>
        document.addEventListener('contextmenu', function(e) { e.preventDefault(); });
        document.addEventListener('keydown', function(e) { if (e.keyCode === 123) { e.preventDefault(); } });

        const navbar = document.querySelector("nav");
        function handleNavScroll() {
            if (window.scrollY > 40) { navbar.classList.add("scrolled"); }
            else { navbar.classList.remove("scrolled"); }
        }
        window.addEventListener("scroll", handleNavScroll);
        handleNavScroll();
    </script>
</body>
</html>
