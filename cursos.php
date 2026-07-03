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
?>
