<?php
/* ============================================================
   CURSOS FIXOS DA PLATAFORMA
   ------------------------------------------------------------
   Aqui ficam os cursos e as aulas de cada um.
   Para adicionar/editar uma aula, mexa só neste arquivo.

   Cada curso tem:
     - nome, descricao, imagem (usa as imagens da pasta img/)
     - aulas: lista de aulas, cada uma com titulo e conteudo (HTML)

   O "id" do curso (ex: 'informatica') é usado no banco,
   na tabela aulas_concluidas. Se mudar o id depois, o
   progresso antigo daquele curso deixa de aparecer.
   ============================================================ */

$CURSOS = [

    'informatica' => [
        'nome'      => 'Informática Básica',
        'imagem'    => 'img/computer.png',
        'descricao' => 'Conhecimentos fundamentais sobre o uso de computadores: navegação na internet, uso de programas e conceitos básicos de segurança.',
        'aulas'     => [
            [
                'titulo'  => 'O que é um computador',
                'conteudo'=> '<p>Nesta aula você vai conhecer as partes de um computador: monitor, teclado, mouse e gabinete. Entenda para que serve cada uma delas no dia a dia.</p>',
            ],
            [
                'titulo'  => 'Ligando e desligando com segurança',
                'conteudo'=> '<p>Aprenda a forma correta de ligar e, principalmente, de desligar o computador para não perder seus trabalhos nem danificar o sistema.</p>',
            ],
            [
                'titulo'  => 'Usando o mouse e o teclado',
                'conteudo'=> '<p>Pratique o clique, o clique duplo e o arrastar. Conheça as teclas mais importantes do teclado.</p>',
            ],
            [
                'titulo'  => 'Navegando na internet',
                'conteudo'=> '<p>Descubra como abrir um navegador, pesquisar no Google e reconhecer sites confiáveis.</p>',
            ],
        ],
    ],

    'atalhos' => [
        'nome'      => 'Atalhos do Teclado',
        'imagem'    => 'img/keyboard.png',
        'descricao' => 'Combinações de teclas que agilizam o trabalho no computador sem precisar do mouse.',
        'aulas'     => [
            [
                'titulo'  => 'Copiar, colar e recortar',
                'conteudo'=> '<p>Ctrl+C para copiar, Ctrl+V para colar e Ctrl+X para recortar. Os atalhos que você mais vai usar.</p>',
            ],
            [
                'titulo'  => 'Desfazer e refazer',
                'conteudo'=> '<p>Errou? Ctrl+Z desfaz a última ação. Ctrl+Y refaz. Nunca mais tenha medo de errar.</p>',
            ],
            [
                'titulo'  => 'Alternar entre janelas',
                'conteudo'=> '<p>Use Alt+Tab para trocar rapidamente entre os programas abertos.</p>',
            ],
        ],
    ],

    'word' => [
        'nome'      => 'Microsoft Word',
        'imagem'    => 'img/microsoft-word.png',
        'descricao' => 'Editor de texto para criar, editar e formatar documentos de forma fácil e eficiente.',
        'aulas'     => [
            [
                'titulo'  => 'Criando seu primeiro documento',
                'conteudo'=> '<p>Abra o Word, crie um documento em branco e digite seu primeiro texto.</p>',
            ],
            [
                'titulo'  => 'Formatando o texto',
                'conteudo'=> '<p>Deixe o texto em negrito, itálico, mude a cor e o tamanho da fonte.</p>',
            ],
            [
                'titulo'  => 'Salvando e imprimindo',
                'conteudo'=> '<p>Aprenda a salvar seu documento no computador e a enviá-lo para a impressora.</p>',
            ],
        ],
    ],

    'excel' => [
        'nome'      => 'Microsoft Excel',
        'imagem'    => 'img/microsoft-exel.png',
        'descricao' => 'Planilha eletrônica para organizar, calcular e analisar dados com tabelas, gráficos e fórmulas.',
        'aulas'     => [
            [
                'titulo'  => 'Conhecendo as células',
                'conteudo'=> '<p>Entenda o que são linhas, colunas e células, e como se movimentar pela planilha.</p>',
            ],
            [
                'titulo'  => 'Somando valores',
                'conteudo'=> '<p>Use a fórmula =SOMA() para somar uma coluna de números automaticamente.</p>',
            ],
            [
                'titulo'  => 'Criando um gráfico simples',
                'conteudo'=> '<p>Transforme seus dados em um gráfico de barras em poucos cliques.</p>',
            ],
        ],
    ],

];

/* -------- Funções auxiliares dos cursos -------- */

// Total de aulas de um curso
function total_aulas($CURSOS, $curso_id) {
    return isset($CURSOS[$curso_id]) ? count($CURSOS[$curso_id]['aulas']) : 0;
}

/* ============================================================
   BLOQUEIO SEQUENCIAL DOS CURSOS
   ------------------------------------------------------------
   A ordem dos cursos é a mesma ordem em que aparecem no
   array $CURSOS acima. O aluno só desbloqueia o próximo curso
   depois de concluir TODAS as aulas do curso anterior.
   O primeiro curso está sempre liberado.
   ============================================================ */

// Verifica se um curso foi 100% concluído, com base no progresso
function curso_concluido($CURSOS, $curso_id, $progresso) {
    $total = total_aulas($CURSOS, $curso_id);
    if ($total <= 0) return false;
    $concluidas = isset($progresso[$curso_id]) ? (int)$progresso[$curso_id]['concluidas'] : 0;
    return $concluidas >= $total;
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
// Retorna: curso_id => ['concluidas' => n, 'ultima_aula' => n, 'ultimo_acesso' => data]
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
        $progresso[$linha['curso_id']] = $linha;
    }
    return $progresso;
}
?>

    <script>
    document.addEventListener('contextmenu', function(e) {
    e.preventDefault(); // Impede o menu de contexto padrão
    });

    document.addEventListener('keydown', function(e) {
    if (e.keyCode === 123) { // Código da tecla F12
    e.preventDefault();
    }
    });
    </script>