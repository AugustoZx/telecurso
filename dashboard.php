<?php
    session_start();

    /* -------- Proteção: só entra quem está logado -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: login.php');
        exit;
    }

    include_once('config.php');
    include_once('cursos.php');

    $aluno_id   = $_SESSION['aluno_id'];
    $aluno_nome = $_SESSION['aluno_nome'] ?? $_SESSION['aluno_user'] ?? 'Aluno';

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
    <link rel="stylesheet" href="css/dashboard.css">
    <title>Meu Painel</title>
</head>
<body class="no-select">

    <nav>
        <div class="logo">
            <a href="dashboard.php"><img src="img/logo.png" alt="Logo do Telecentro"></a>
        </div>
        <div class="elem">
            <ul>
                <a href="dashboard.php"><li>PAINEL</li></a>
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
            <p>Continue seus estudos de onde parou ou escolha um novo curso.</p>
        </header>

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
            <h2 class="titulo-secao">Seus cursos</h2>

            <div class="grade-cursos">
                <?php foreach ($CURSOS as $curso_id => $curso):
                    $total       = count($curso['aulas']);
                    $concluidas  = isset($progresso[$curso_id]) ? (int)$progresso[$curso_id]['concluidas'] : 0;
                    $percentual  = $total > 0 ? round(($concluidas / $total) * 100) : 0;
                    $ultima      = isset($progresso[$curso_id]) ? (int)$progresso[$curso_id]['ultima_aula'] : 0;
                    $proxima     = min($ultima + 1, $total);
                    $terminou    = $concluidas >= $total && $total > 0;
                ?>
                    <article class="card-curso">
                        <div class="card-img">
                            <img src="<?= htmlspecialchars($curso['imagem']) ?>" alt="<?= htmlspecialchars($curso['nome']) ?>">
                        </div>
                        <div class="card-corpo">
                            <h3><?= htmlspecialchars($curso['nome']) ?></h3>
                            <p class="card-desc"><?= htmlspecialchars($curso['descricao']) ?></p>

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
                                    Começar curso
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

</body>
</html>
