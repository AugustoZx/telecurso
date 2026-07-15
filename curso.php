<?php
    session_start();

    /* -------- Proteção: só entra quem está logado -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: login.php');
        exit;
    }

    include_once('config.php');
    include_once('cursos.php');

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

        // Redireciona para a próxima aula (ou volta ao painel se acabou)
        $total = total_aulas($CURSOS, $curso_id);
        if ($aula_num < $total) {
            header('Location: curso.php?curso=' . urlencode($curso_id) . '&aula=' . ($aula_num + 1));
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
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="shortcut icon" type="imagex/png" href="https://salesianossp.org.br/ositaquera/wp-content/uploads/2024/03/wp-favicon-pvi-os-150x150.webp">
    <title><?= htmlspecialchars($curso['nome']) ?> — Aula <?= $aula_num ?></title>
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
            <h1>Aula <?= $aula_num ?>: <?= htmlspecialchars($aula['titulo']) ?></h1>

            <div class="aula-texto">
                <?= $aula['conteudo'] // HTML controlado por você em cursos.php ?>
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
                        <?php else: ?>
                            <?= $aula_concluida ? 'Voltar ao painel' : 'Concluir curso' ?>
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </article>

    </main>

</body>
</html>
