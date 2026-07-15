<?php
session_start();
$logado = isset($_SESSION['aluno_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/index.css">
    <link rel="shortcut icon" type="imagex/png" href="https://salesianossp.org.br/ositaquera/wp-content/uploads/2024/03/wp-favicon-pvi-os-150x150.webp">
    <title>Home</title>
</head>
<body class="no-select">
    <nav>
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt=""></a>
        </div>

        <div class="elem">
            <ul>
                <a href="index.php"><li>HOME</li></a>
                <a href="curso.php"><li>CURSO</li></a>
            </ul>
        </div>

        <?php if ($logado): ?>
            <a class="b_login" href="logout.php">Sair</a>
        <?php else: ?>
            <a class="b_login" href="login.php">Login</a>
        <?php endif; ?>
    </nav>

    <main>
        <section class="aprendizado">
            <h2>O que você vai aprender no curso?</h2>
        </section>

        <section class="conteudo-curso">
            <div class="imagem">
                <img src="img/computer.png" alt="Conteúdo do curso 3">
            </div>
            <div class="texto">
                <h3>Informática Básica</h3>
                <p>Informática básica é o conhecimento fundamental sobre o uso de computadores e aplicativos de software. Inclui habilidades como navegação na internet, uso de aplicativos de processamento de texto, planilhas e apresentações, além de conceitos básicos de segurança e manutenção de equipamentos.</p>
            </div>
        </section>

        <section class="conteudo-curso invertido">
            <div class="imagem">
                <img src="img/keyboard.png" alt="Conteúdo do curso 4">
            </div>
            <div class="texto">
                <h3>Atalhos do Teclado</h3>
                <p>Atalhos do teclado são combinações de teclas que permitem realizar ações rapidamente sem precisar usar o mouse. Eles são úteis para aumentar a produtividade e a eficiência na utilização de aplicativos e sistemas operacionais.</p>
            </div>
        </section>

        <section class="conteudo-curso">
            <div class="imagem">
                <img src="img/microsoft-word.png" alt="Conteúdo do curso">
            </div>
            <div class="texto">
                <h3>Microsoft Word</h3>
                <p>Word é um editor de texto que permite criar, editar e formatar documentos de forma fácil e eficiente. Ele oferece uma ampla gama de ferramentas para personalizar o layout e o estilo do seu texto, incluindo a formatação de parágrafos, títulos, listas, tabelas e muito mais.</p>
            </div>
        </section>

        <section class="conteudo-curso invertido">
            <div class="imagem">
                <img src="img/microsoft-exel.png" alt="Conteúdo do curso 2">
            </div>
            <div class="texto">
                <h3>Microsoft Excel</h3>
                <p>Excel é uma planilha eletrônica que permite criar, editar e formatar tabelas de dados de forma fácil e eficiente. Ele oferece uma ampla gama de ferramentas para organizar, calcular e analisar informações, incluindo a criação de gráficos, tabelas e fórmulas matemáticas.</p>
            </div>
        </section>
    </main>

    <script src="js/index.js"></script>

</body>
</html>