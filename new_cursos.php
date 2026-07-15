<?php
/* ============================================================
   CURSOS FIXOS DA PLATAFORMA
   ------------------------------------------------------------
   Aqui ficam os cursos, as aulas e o QUIZ de cada um.
   Para adicionar/editar uma aula ou pergunta, mexa só neste arquivo.

   Cada curso tem:
     - nome, descricao, imagem (usa as imagens da pasta img/)
     - aulas: lista de aulas, cada uma com titulo e conteudo (HTML)
     - quiz:  lista de perguntas do quiz final (opcional)

   Cada pergunta do quiz tem:
     - pergunta : o texto da pergunta
     - opcoes   : lista de alternativas (quantas você quiser)
     - correta  : o ÍNDICE da alternativa certa (começa em 0)
                  ex: correta => 0  -> a 1ª opção é a certa
                      correta => 2  -> a 3ª opção é a certa

   O "id" do curso (ex: 'informatica') é usado no banco,
   nas tabelas aulas_concluidas e quiz_resultados. Se mudar o id
   depois, o progresso antigo daquele curso deixa de aparecer.

   NOTA DE APROVAÇÃO: para desbloquear o próximo curso, o aluno
   precisa concluir TODAS as aulas E acertar pelo menos 70% do quiz.
   Esse valor fica na constante NOTA_MINIMA logo abaixo.
   ============================================================ */

// Porcentagem mínima de acerto no quiz para liberar o próximo curso.
if (!defined('NOTA_MINIMA')) {
    define('NOTA_MINIMA', 70);
}

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
        'quiz'      => [
            [
                'pergunta' => 'Qual destas partes é usada para exibir a imagem do computador?',
                'opcoes'   => ['Monitor', 'Gabinete', 'Mouse', 'Teclado'],
                'correta'  => 0,
            ],
            [
                'pergunta' => 'Qual é a forma correta de desligar o computador?',
                'opcoes'   => [
                    'Tirar o cabo da tomada direto',
                    'Usar a opção "Desligar" do sistema',
                    'Segurar o botão até apagar toda vez',
                    'Fechar só o monitor',
                ],
                'correta'  => 1,
            ],
            [
                'pergunta' => 'Para abrir um item na tela normalmente usamos:',
                'opcoes'   => ['Um clique com o botão direito', 'O clique duplo', 'Apenas o teclado', 'O botão de ligar'],
                'correta'  => 1,
            ],
            [
                'pergunta' => 'Qual site é um exemplo de ferramenta de pesquisa?',
                'opcoes'   => ['Google', 'Gabinete', 'Word', 'Monitor'],
                'correta'  => 0,
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
        'quiz'      => [
            [
                'pergunta' => 'Qual atalho é usado para COPIAR?',
                'opcoes'   => ['Ctrl+V', 'Ctrl+C', 'Ctrl+X', 'Ctrl+Z'],
                'correta'  => 1,
            ],
            [
                'pergunta' => 'Qual atalho é usado para COLAR?',
                'opcoes'   => ['Ctrl+V', 'Ctrl+C', 'Ctrl+Y', 'Alt+Tab'],
                'correta'  => 0,
            ],
            [
                'pergunta' => 'Errou algo. Qual atalho DESFAZ a última ação?',
                'opcoes'   => ['Ctrl+Y', 'Ctrl+X', 'Ctrl+Z', 'Ctrl+C'],
                'correta'  => 2,
            ],
            [
                'pergunta' => 'Como alternar rapidamente entre os programas abertos?',
                'opcoes'   => ['Ctrl+C', 'Alt+Tab', 'Ctrl+V', 'Ctrl+Z'],
                'correta'  => 1,
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
        'quiz'      => [
            [
                'pergunta' => 'Para que serve o Microsoft Word?',
                'opcoes'   => ['Criar planilhas', 'Editar textos e documentos', 'Navegar na internet', 'Ouvir música'],
                'correta'  => 1,
            ],
            [
                'pergunta' => 'Deixar o texto em NEGRITO faz parte de qual etapa?',
                'opcoes'   => ['Salvar o arquivo', 'Formatar o texto', 'Imprimir', 'Fechar o programa'],
                'correta'  => 1,
            ],
            [
                'pergunta' => 'Depois de escrever, o que devemos fazer para não perder o trabalho?',
                'opcoes'   => ['Salvar o documento', 'Apagar o texto', 'Desligar o monitor', 'Trocar de fonte'],
                'correta'  => 0,
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
        'quiz'      => [
            [
                'pergunta' => 'No Excel, o cruzamento de uma linha com uma coluna é chamado de:',
                'opcoes'   => ['Célula', 'Gráfico', 'Fórmula', 'Documento'],
                'correta'  => 0,
            ],
            [
                'pergunta' => 'Qual fórmula soma automaticamente uma coluna de números?',
                'opcoes'   => ['=MEDIA()', '=SOMA()', '=TEXTO()', '=HOJE()'],
                'correta'  => 1,
            ],
            [
                'pergunta' => 'Para visualizar dados de forma visual, podemos criar um:',
                'opcoes'   => ['Gráfico', 'Documento de texto', 'E-mail', 'Atalho'],
                'correta'  => 0,
            ],
        ],
    ],

];

/* -------- Funções auxiliares dos cursos -------- */

// Total de aulas de um curso
function total_aulas($CURSOS, $curso_id) {
    return isset($CURSOS[$curso_id]) ? count($CURSOS[$curso_id]['aulas']) : 0;
}

// Total de perguntas do quiz de um curso (0 se não tiver quiz)
function total_perguntas($CURSOS, $curso_id) {
    return isset($CURSOS[$curso_id]['quiz']) ? count($CURSOS[$curso_id]['quiz']) : 0;
}

// O curso tem quiz?
function curso_tem_quiz($CURSOS, $curso_id) {
    return total_perguntas($CURSOS, $curso_id) > 0;
}

// Todas as aulas do curso foram concluídas?
function aulas_concluidas_todas($CURSOS, $curso_id, $progresso) {
    $total = total_aulas($CURSOS, $curso_id);
    if ($total <= 0) return false;
    $concluidas = isset($progresso[$curso_id]) ? (int)$progresso[$curso_id]['concluidas'] : 0;
    return $concluidas >= $total;
}

// Melhor porcentagem que o aluno já tirou no quiz deste curso (0 se nunca fez)
function melhor_nota_quiz($curso_id, $progresso) {
    return isset($progresso[$curso_id]['melhor_quiz'])
        ? (float)$progresso[$curso_id]['melhor_quiz']
        : 0;
}

// O aluno foi aprovado no quiz do curso? (>= NOTA_MINIMA)
function quiz_aprovado($CURSOS, $curso_id, $progresso) {
    if (!curso_tem_quiz($CURSOS, $curso_id)) return true; // sem quiz = não exige nota
    return melhor_nota_quiz($curso_id, $progresso) >= NOTA_MINIMA;
}

/* ============================================================
   BLOQUEIO SEQUENCIAL DOS CURSOS
   ------------------------------------------------------------
   A ordem dos cursos é a mesma ordem em que aparecem no
   array $CURSOS acima. O aluno só desbloqueia o próximo curso
   depois de CONCLUIR o curso anterior.
   O primeiro curso está sempre liberado.

   CONCLUIR um curso = terminar TODAS as aulas
   E ser aprovado no quiz (>= NOTA_MINIMA %), quando houver quiz.
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
// Retorna: curso_id => [
//     'concluidas'    => n,      (aulas concluídas)
//     'ultima_aula'   => n,
//     'ultimo_acesso' => data,
//     'melhor_quiz'   => nota,   (melhor % já feita no quiz, 0 se nunca fez)
// ]
function buscar_progresso($conexao, $aluno_id) {
    $progresso = [];

    // 1) Aulas concluídas por curso
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
        $linha['melhor_quiz'] = 0; // preenchido logo abaixo se existir
        $progresso[$linha['curso_id']] = $linha;
    }

    // 2) Melhor nota do quiz por curso
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
            // aluno fez o quiz mas (por algum motivo) não tem aulas registradas
            $progresso[$cid] = [
                'curso_id'      => $cid,
                'concluidas'    => 0,
                'ultima_aula'   => 0,
                'ultimo_acesso' => null,
                'melhor_quiz'   => 0,
            ];
        }
        $progresso[$cid]['melhor_quiz'] = (float)$linha['melhor_quiz'];
    }

    return $progresso;
}
?>
