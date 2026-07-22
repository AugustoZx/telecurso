<?php
/* ============================================================
   CAMADA DE ACESSO A DADOS DOS CURSOS
   ------------------------------------------------------------
   Cursos, aulas e quiz agora vivem no banco (tabelas cursos,
   aulas, quiz_perguntas, quiz_opcoes) e são editados pelo painel
   admin_cursos.php. Este arquivo só busca esses dados e monta um
   array no mesmo formato que curso.php/dashboard.php/admin.php
   já esperavam, além das funções de progresso/bloqueio.

   O "slug" do curso (ex: 'informatica') é o mesmo id que já era
   usado nas tabelas aulas_concluidas/certificados/quiz_resultados.
   ============================================================ */

// Monta o array de cursos ativos, com aulas e quiz, no formato:
// slug => ['id','nome','imagem','descricao','nota_minima_quiz','certifica_conclusao','aulas'=>[...],'quiz'=>[...]]
function buscar_cursos_ativos($conexao) {
    $CURSOS = [];

    $res = $conexao->query(
        "SELECT id, slug, nome, descricao, imagem, nota_minima_quiz, certifica_conclusao
         FROM cursos WHERE ativo = 1 ORDER BY ordem ASC, id ASC"
    );
    while ($c = $res->fetch_assoc()) {
        $CURSOS[$c['slug']] = [
            'id'                  => (int) $c['id'],
            'nome'                => $c['nome'],
            'imagem'              => $c['imagem'],
            'descricao'           => $c['descricao'],
            'nota_minima_quiz'    => (int) $c['nota_minima_quiz'],
            'certifica_conclusao' => (int) $c['certifica_conclusao'],
            'aulas'               => [],
            'quiz'                => [],
        ];
    }
    if (empty($CURSOS)) return $CURSOS;

    $id_para_slug = [];
    foreach ($CURSOS as $slug => $c) $id_para_slug[$c['id']] = $slug;
    $ids_str = implode(',', array_map('intval', array_keys($id_para_slug)));

    // Aulas de todos os cursos ativos, em ordem
    $res = $conexao->query(
        "SELECT id, curso_id, titulo, conteudo, midia_tipo, midia_arquivo
         FROM aulas WHERE curso_id IN ($ids_str) ORDER BY curso_id ASC, ordem ASC"
    );
    while ($a = $res->fetch_assoc()) {
        $slug = $id_para_slug[$a['curso_id']];
        $CURSOS[$slug]['aulas'][] = [
            'id'            => (int) $a['id'],
            'titulo'        => $a['titulo'],
            'conteudo'      => $a['conteudo'],
            'midia_tipo'    => $a['midia_tipo'],
            'midia_arquivo' => $a['midia_arquivo'],
        ];
    }

    // Perguntas do quiz de todos os cursos ativos, em ordem
    $perguntas_por_id = []; // pergunta_id => ['slug' => ..., 'idx' => posição em $CURSOS[$slug]['quiz']]
    $res = $conexao->query(
        "SELECT id, curso_id, enunciado
         FROM quiz_perguntas WHERE curso_id IN ($ids_str) ORDER BY curso_id ASC, ordem ASC"
    );
    while ($p = $res->fetch_assoc()) {
        $slug = $id_para_slug[$p['curso_id']];
        $CURSOS[$slug]['quiz'][] = [
            'id'         => (int) $p['id'],
            'pergunta'   => $p['enunciado'],
            'opcoes'     => [],
            'correta_id' => null,
        ];
        $perguntas_por_id[(int) $p['id']] = ['slug' => $slug, 'idx' => count($CURSOS[$slug]['quiz']) - 1];
    }

    // Opções de cada pergunta
    if (!empty($perguntas_por_id)) {
        $pids_str = implode(',', array_map('intval', array_keys($perguntas_por_id)));
        $res = $conexao->query(
            "SELECT id, pergunta_id, texto, correta
             FROM quiz_opcoes WHERE pergunta_id IN ($pids_str) ORDER BY pergunta_id ASC, ordem ASC"
        );
        while ($o = $res->fetch_assoc()) {
            $info = $perguntas_por_id[(int) $o['pergunta_id']];
            $slug = $info['slug'];
            $idx  = $info['idx'];
            $CURSOS[$slug]['quiz'][$idx]['opcoes'][] = ['id' => (int) $o['id'], 'texto' => $o['texto']];
            if ((int) $o['correta'] === 1) {
                $CURSOS[$slug]['quiz'][$idx]['correta_id'] = (int) $o['id'];
            }
        }
    }

    return $CURSOS;
}

/* -------- Funções auxiliares dos cursos -------- */

// Total de aulas de um curso
function total_aulas($CURSOS, $curso_id) {
    return isset($CURSOS[$curso_id]) ? count($CURSOS[$curso_id]['aulas']) : 0;
}

// Total de perguntas do quiz de um curso (0 se não tiver quiz)
function total_perguntas($CURSOS, $curso_id) {
    return isset($CURSOS[$curso_id]['quiz']) ? count($CURSOS[$curso_id]['quiz']) : 0;
}

// O curso tem quiz cadastrado?
function curso_tem_quiz($CURSOS, $curso_id) {
    return total_perguntas($CURSOS, $curso_id) > 0;
}

// Todas as aulas do curso foram concluídas?
function aulas_concluidas_todas($CURSOS, $curso_id, $progresso) {
    $total = total_aulas($CURSOS, $curso_id);
    if ($total <= 0) return false;
    $concluidas = isset($progresso[$curso_id]) ? (int) $progresso[$curso_id]['concluidas'] : 0;
    return $concluidas >= $total;
}

// Melhor porcentagem que o aluno já tirou no quiz deste curso (0 se nunca fez)
function melhor_nota_quiz($curso_id, $progresso) {
    return isset($progresso[$curso_id]['melhor_quiz']) ? (float) $progresso[$curso_id]['melhor_quiz'] : 0;
}

// O aluno foi aprovado no quiz do curso? (>= nota_minima_quiz do curso)
function quiz_aprovado($CURSOS, $curso_id, $progresso) {
    if (!curso_tem_quiz($CURSOS, $curso_id)) return true; // sem quiz cadastrado = não exige nota
    $nota_minima = $CURSOS[$curso_id]['nota_minima_quiz'] ?? 70;
    return melhor_nota_quiz($curso_id, $progresso) >= $nota_minima;
}

/* ============================================================
   BLOQUEIO SEQUENCIAL DOS CURSOS
   ------------------------------------------------------------
   A ordem dos cursos é a mesma ordem em que aparecem no array
   $CURSOS (definida pela coluna `ordem` da tabela cursos). O
   aluno só desbloqueia o próximo curso depois de CONCLUIR o
   curso anterior. O primeiro curso está sempre liberado.

   CONCLUIR um curso = terminar TODAS as aulas E ser aprovado no
   quiz (quando o curso tiver perguntas cadastradas).
   ============================================================ */

// Verifica se um curso foi concluído: todas as aulas + quiz aprovado
function curso_concluido($CURSOS, $curso_id, $progresso) {
    if (!aulas_concluidas_todas($CURSOS, $curso_id, $progresso)) return false;
    return quiz_aprovado($CURSOS, $curso_id, $progresso);
}

// Verifica se um curso está liberado para o aluno.
// Liberado = é o primeiro curso OU o curso anterior já foi concluído.
function curso_liberado($CURSOS, $curso_id, $progresso) {
    $ordem = array_keys($CURSOS);
    $idx   = array_search($curso_id, $ordem, true);

    if ($idx === false) return false; // curso não existe
    if ($idx === 0)      return true;  // primeiro curso sempre liberado

    $curso_anterior = $ordem[$idx - 1];
    return curso_concluido($CURSOS, $curso_anterior, $progresso);
}

// Nome do curso anterior (útil para mensagens de bloqueio). Retorna null se for o primeiro.
function curso_anterior_nome($CURSOS, $curso_id) {
    $ordem = array_keys($CURSOS);
    $idx   = array_search($curso_id, $ordem, true);
    if ($idx === false || $idx === 0) return null;
    $anterior = $ordem[$idx - 1];
    return $CURSOS[$anterior]['nome'] ?? null;
}

// Busca no banco o progresso do aluno em todos os cursos.
// Retorna: curso_id => ['concluidas'=>n, 'ultima_aula'=>n, 'ultimo_acesso'=>data, 'melhor_quiz'=>nota]
function buscar_progresso($conexao, $aluno_id) {
    $progresso = [];

    $sql = "SELECT curso_id,
                   COUNT(*)      AS concluidas,
                   MAX(aula_num) AS ultima_aula,
                   MAX(data)     AS ultimo_acesso
            FROM aulas_concluidas
            WHERE aluno_id = ?
            GROUP BY curso_id";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $aluno_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($linha = $res->fetch_assoc()) {
        $linha['melhor_quiz'] = 0;
        $progresso[$linha['curso_id']] = $linha;
    }

    $sql2 = "SELECT curso_id, MAX(percentual) AS melhor_quiz
             FROM quiz_resultados
             WHERE aluno_id = ?
             GROUP BY curso_id";
    $stmt2 = $conexao->prepare($sql2);
    $stmt2->bind_param("i", $aluno_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($linha = $res2->fetch_assoc()) {
        $cid = $linha['curso_id'];
        if (!isset($progresso[$cid])) {
            $progresso[$cid] = [
                'curso_id'      => $cid,
                'concluidas'    => 0,
                'ultima_aula'   => 0,
                'ultimo_acesso' => null,
                'melhor_quiz'   => 0,
            ];
        }
        $progresso[$cid]['melhor_quiz'] = (float) $linha['melhor_quiz'];
    }

    return $progresso;
}
