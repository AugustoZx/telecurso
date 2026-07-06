<?php
    session_start();

    /* -------- Proteção dupla: precisa estar logado E ser admin -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: login.php');
        exit;
    }
    if (empty($_SESSION['adm']) || (int)$_SESSION['adm'] !== 1) {
        // Não é administrador: manda de volta ao painel comum
        header('Location: dashboard.php');
        exit;
    }

    include_once('config.php');
    include_once('cursos.php');

    /* -------- Filtro de busca por nome/usuário (opcional) -------- */
    $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

    /* -------- Total de aulas de todos os cursos somados -------- */
    $total_aulas_plataforma = 0;
    foreach ($CURSOS as $c) {
        $total_aulas_plataforma += count($c['aulas']);
    }

    /* -------- Busca todos os alunos (com filtro opcional) -------- */
    if ($busca !== '') {
        $sql = "SELECT id, nome, user, adm FROM alunos
                WHERE nome LIKE ? OR user LIKE ?
                ORDER BY nome ASC";
        $stmt = $conexao->prepare($sql);
        $curinga = '%' . $busca . '%';
        $stmt->bind_param("ss", $curinga, $curinga);
    } else {
        $sql = "SELECT id, nome, user, adm FROM alunos ORDER BY nome ASC";
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
    <link rel="stylesheet" href="css/dashboard.css">
    <title>Administração</title>
</head>
<body class="no-select">

    <nav>
        <div class="logo">
            <a href="admin.php"><img src="img/logo.png" alt="Logo do Telecentro"></a>
        </div>
        <div class="elem">
            <ul>
                <a href="admin.php"><li>ADMIN</li></a>
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
                <span class="rot">Cursos disponíveis</span>
            </div>
        </section>

        <!-- Busca -->
        <section class="barra-busca" aria-label="Buscar aluno">
            <form method="get" action="admin.php">
                <input type="text" name="busca" placeholder="Buscar por nome ou usuário..."
                       value="<?= htmlspecialchars($busca) ?>">
                <button type="submit" class="btn-buscar">Buscar</button>
                <?php if ($busca !== ''): ?>
                    <a class="btn-limpar" href="admin.php">Limpar</a>
                <?php endif; ?>
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
                            <td class="cel-total"><?= $pct_total ?>%</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>
