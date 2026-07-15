<?php
    session_start();

    /* -------- Proteção: só entra quem está logado -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: login.php');
        exit;
    }

    include_once('config.php');
    include_once('cursos.php');

    $aluno_id  = $_SESSION['aluno_id'];
    $progresso = buscar_progresso($conexao, $aluno_id);

    // Curso vindo tanto do GET quanto do POST
    $curso_id = $_POST['curso'] ?? $_GET['curso'] ?? '';

    /* -------- Validações -------- */
    // Curso precisa existir e ter quiz
    if (!isset($CURSOS[$curso_id]) || !curso_tem_quiz($CURSOS, $curso_id)) {
        header('Location: dashboard.php');
        exit;
    }
    // Curso precisa estar liberado para o aluno
    if (!curso_liberado($CURSOS, $curso_id, $progresso)) {
        header('Location: dashboard.php?bloqueado=' . urlencode($curso_id));
        exit;
    }
    // Aluno só faz o quiz depois de concluir TODAS as aulas
    if (!aulas_concluidas_todas($CURSOS, $curso_id, $progresso)) {
        header('Location: curso.php?curso=' . urlencode($curso_id) . '&aula=1');
        exit;
    }

    $curso    = $CURSOS[$curso_id];
    $perguntas = $curso['quiz'];
    $total     = count($perguntas);

    $resultado = null; // preenchido após enviar as respostas

    /* -------- Correção do quiz (POST) -------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_quiz'])) {

        $respostas = $_POST['resp'] ?? []; // resp[indice_pergunta] = indice_opcao
        $acertos   = 0;
        $detalhe   = []; // para mostrar o que acertou/errou

        foreach ($perguntas as $i => $p) {
            $marcada = isset($respostas[$i]) ? (int) $respostas[$i] : -1;
            $certa   = (int) $p['correta'];
            $ok      = ($marcada === $certa);
            if ($ok) $acertos++;
            $detalhe[$i] = [
                'marcada' => $marcada,
                'certa'   => $certa,
                'ok'      => $ok,
            ];
        }

        $percentual = $total > 0 ? round(($acertos / $total) * 100, 2) : 0;
        $aprovado   = $percentual >= NOTA_MINIMA ? 1 : 0;

        // Salva a tentativa no banco (mantém histórico; o desbloqueio usa a MELHOR nota)
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
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="shortcut icon" type="imagex/png" href="https://salesianossp.org.br/ositaquera/wp-content/uploads/2024/03/wp-favicon-pvi-os-150x150.webp">
    <title>Quiz — <?= htmlspecialchars($curso['nome']) ?></title>
    <style>
        /* Estilos próprios do quiz (independentes do dashboard.css) */
        .quiz-wrap { max-width: 760px; margin: 32px auto; padding: 0 16px; font-family: 'Roboto', sans-serif; }
        .quiz-breadcrumb { color: #667; font-size: 14px; margin-bottom: 8px; }
        .quiz-breadcrumb a { color: #1565c0; text-decoration: none; }
        .quiz-wrap h1 { font-size: 26px; margin: 4px 0 4px; }
        .quiz-info { color: #556; margin-bottom: 24px; }
        .quiz-nota-min { font-weight: 700; color: #1565c0; }

        .quiz-pergunta {
            background: #fff; border: 1px solid #e2e6ec; border-radius: 12px;
            padding: 18px 20px; margin-bottom: 16px;
        }
        .quiz-pergunta .enunciado { font-weight: 700; margin: 0 0 12px; }
        .quiz-opcao {
            display: flex; align-items: center; gap: 10px; padding: 10px 12px;
            border: 1px solid #e2e6ec; border-radius: 8px; margin-bottom: 8px; cursor: pointer;
        }
        .quiz-opcao:hover { background: #f5f8ff; }
        .quiz-opcao input { width: 18px; height: 18px; }

        .quiz-enviar {
            display: inline-block; background: #1565c0; color: #fff; border: none;
            padding: 14px 26px; border-radius: 8px; font-size: 16px; font-weight: 700;
            cursor: pointer; margin-top: 8px;
        }
        .quiz-enviar:hover { background: #0d4f9e; }

        /* Resultado */
        .quiz-resultado {
            border-radius: 12px; padding: 22px 24px; margin-bottom: 24px; color: #fff;
        }
        .quiz-resultado.aprovado   { background: #2e7d32; }
        .quiz-resultado.reprovado  { background: #c62828; }
        .quiz-resultado h2 { margin: 0 0 6px; font-size: 22px; }
        .quiz-resultado p  { margin: 0; opacity: .95; }
        .quiz-resultado .nota { font-size: 34px; font-weight: 700; }

        .quiz-acoes { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; }
        .quiz-btn {
            display: inline-block; padding: 12px 22px; border-radius: 8px; font-weight: 700;
            text-decoration: none; font-size: 15px;
        }
        .quiz-btn.primario  { background: #1565c0; color: #fff; }
        .quiz-btn.secundario{ background: #eef1f6; color: #333; }

        /* Revisão das respostas */
        .quiz-revisao .quiz-opcao.certa   { border-color: #2e7d32; background: #e8f5e9; }
        .quiz-revisao .quiz-opcao.errada  { border-color: #c62828; background: #ffebee; }
        .quiz-revisao .tag { font-size: 12px; font-weight: 700; margin-left: auto; }
        .quiz-revisao .tag.ok  { color: #2e7d32; }
        .quiz-revisao .tag.no  { color: #c62828; }
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

    <main class="quiz-wrap">

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
                <span class="quiz-nota-min"><?= NOTA_MINIMA ?>%</span> ou mais para liberar o próximo curso.
                Pode refazer quantas vezes precisar.
            </p>

            <form method="POST" action="quiz.php" id="form-quiz">
                <input type="hidden" name="curso" value="<?= htmlspecialchars($curso_id) ?>">

                <?php foreach ($perguntas as $i => $p): ?>
                    <div class="quiz-pergunta">
                        <p class="enunciado"><?= ($i + 1) ?>. <?= htmlspecialchars($p['pergunta']) ?></p>
                        <?php foreach ($p['opcoes'] as $j => $opcao): ?>
                            <label class="quiz-opcao">
                                <input type="radio" name="resp[<?= $i ?>]" value="<?= $j ?>" required>
                                <span><?= htmlspecialchars($opcao) ?></span>
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
                        A nota mínima é <?= NOTA_MINIMA ?>%. Revise as aulas e tente novamente.
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
                    $d = $resultado['detalhe'][$i];
                ?>
                    <div class="quiz-pergunta">
                        <p class="enunciado"><?= ($i + 1) ?>. <?= htmlspecialchars($p['pergunta']) ?></p>
                        <?php foreach ($p['opcoes'] as $j => $opcao):
                            $classe = '';
                            if ($j === $d['certa'])                       $classe = 'certa';
                            elseif ($j === $d['marcada'] && !$d['ok'])    $classe = 'errada';
                        ?>
                            <div class="quiz-opcao <?= $classe ?>">
                                <span><?= htmlspecialchars($opcao) ?></span>
                                <?php if ($j === $d['certa']): ?>
                                    <span class="tag ok">Resposta certa</span>
                                <?php elseif ($j === $d['marcada'] && !$d['ok']): ?>
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
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });
    document.addEventListener('keydown', function(e) {
        if (e.keyCode === 123) { e.preventDefault(); }
    });
    </script>
</body>
</html>
