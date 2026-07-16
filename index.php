<?php
session_start();
$logado = isset($_SESSION['aluno_id']);
// Detecta se o login falhou para reabrir o modal automaticamente
$erroLogin = isset($_GET['erro']) && $_GET['erro'] == '1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="imagex/png" href="https://salesianossp.org.br/ositaquera/wp-content/uploads/2024/03/wp-favicon-pvi-os-150x150.webp">
    <title>Home</title>

    <style>
        .no-select {
            -webkit-user-select: none;  /* Chrome, Safari, Opera */
            -moz-user-select: none;     /* Firefox */
            -ms-user-select: none;      /* IE10, IE11 */
            user-select: none;          /* Standard */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: sans-serif;
            color: #193e8f;
        }

        body {
            background-color: #eee;
        }

        /* ===== NAVBAR ===== */
        /* Estado inicial: transparente, largura cheia, colada no topo */
        nav {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 100%;
            z-index: 1000;

            display: flex;
            align-items: center;
            justify-content: space-around;

            padding: 16px 40px;
            background-color: transparent;
            backdrop-filter: blur(0px);
            -webkit-backdrop-filter: blur(0px);
            border-radius: 0;
            box-shadow: none;

            transition:
                max-width 0.4s ease,
                padding 0.4s ease,
                top 0.4s ease,
                background-color 0.4s ease,
                backdrop-filter 0.4s ease,
                border-radius 0.4s ease,
                box-shadow 0.4s ease;
        }

        /* Estado com scroll: pílula branca translúcida, arredondada e recuada */
        nav.scrolled {
            top: 14px;
            max-width: 1200px;
            padding: 10px 32px;
            background-color: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 9999px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .logo img {
            width: 150px;
            display: block;
            transition: width 0.4s ease;
        }

        /* Logo encolhe junto com a navbar */
        nav.scrolled .logo img {
            width: 120px;
        }

        .elem ul {
            display: flex;
            gap: 25px;
        }

        .elem ul a {
            text-decoration: none;
            font-weight: bold;
        }

        .elem ul a li {
            transition: color 0.3s ease;
            background-color: transparent;
        }

        .elem ul a li:hover {
            color: #ea3e44;
        }

        .elem ul li {
            list-style: none;
            font: 17px;
        }

        .b_login {
            padding: 10px 30px;
            background-color: #193e8f;
            font-size: 17px;
            border: none;
            outline: none;
            border-radius: 25px;
            color: #eee;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.5s ease, padding 0.4s ease;
        }

        .b_login:hover {
            background-color: #ea3e44;
        }

        nav.scrolled .b_login {
            padding: 8px 26px;
        }

        /* ===== SEÇÕES ===== */
        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 120px 16px 60px;
        }

        .aprendizado {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin: 40px 0 10px;
        }

        .aprendizado h2 {
            font-size: 65px;
            color: #193e8f;
        }

        .conteudo-curso {
            display: flex;
            align-items: center;
            gap: 24px;
            background-color: rgba(194, 193, 194, 0.133);
            border-radius: 12px;
            padding: 20px;
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease;
        }

        .conteudo-curso.animate {
            opacity: 1;
            transform: translateY(0);
        }

        .conteudo-curso.invertido {
            flex-direction: row-reverse;
        }

        .conteudo-curso .imagem {
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s ease 0.2s;
        }

        .conteudo-curso.animate .imagem {
            opacity: 1;
            transform: translateX(0);
        }

        .conteudo-curso.invertido .imagem {
            transform: translateX(50px);
        }

        .conteudo-curso.invertido.animate .imagem {
            transform: translateX(0);
        }

        .conteudo-curso .texto {
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.8s ease 0.4s;
        }

        .conteudo-curso.animate .texto {
            opacity: 1;
            transform: translateX(0);
        }

        .conteudo-curso.invertido .texto {
            transform: translateX(-50px);
        }

        .conteudo-curso.invertido.animate .texto {
            transform: translateX(0);
        }

        .conteudo-curso .imagem img {
            width: 850px;
            max-width: 100%;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .conteudo-curso .imagem img:hover {
            transform: scale(1.05);
            animation: balanco 2s ease-in-out infinite;
        }

        @keyframes balanco {
            0%, 100% {
                transform: scale(1.05) rotate(0deg);
            }
            25% {
                transform: scale(1.05) rotate(1deg);
            }
            75% {
                transform: scale(1.05) rotate(-1deg);
            }
        }

        .conteudo-curso .texto h3 {
            font-size: 35px;
            margin-bottom: 8px;
        }

        .conteudo-curso .texto p {
            color: #333;
            font-size: 20px;
        }

        /* ===== MODAL DE LOGIN ===== */
        /* Overlay com fundo do site borrado */
        .login-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-color: rgba(25, 62, 143, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .login-overlay.aberto {
            display: flex;
            opacity: 1;
        }

        /* Card do modal (apenas formulário) */
        .login-card {
            display: flex;
            width: 100%;
            max-width: 420px;
            background-color: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            transform: translateY(20px) scale(0.98);
            transition: transform 0.3s ease;
        }

        .login-overlay.aberto .login-card {
            transform: translateY(0) scale(1);
        }

        /* Painel do formulário */
        .login-form-lado {
            flex: 1;
            padding: 44px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: #ffffff;
        }

        .login-topo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .login-form-lado h2 {
            font-size: 34px;
            font-weight: 800;
            color: #193e8f;
        }

        .btn-voltar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            color: #193e8f;
            background-color: #eef1f8;
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-voltar:hover {
            background-color: #dfe4f2;
        }

        .login-form-lado .subtitulo {
            color: #666;
            font-size: 15px;
            margin-bottom: 26px;
        }

        .campo {
            margin-bottom: 16px;
        }

        .campo label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #193e8f;
            margin-bottom: 6px;
        }

        .campo-senha {
            position: relative;
        }

        .login-form-lado input {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            color: #193e8f;
            background-color: #f4f6fb;
            border: 1px solid #dfe4f2;
            border-radius: 10px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .login-form-lado input::placeholder {
            color: #9aa3bd;
        }

        .login-form-lado input:focus {
            border-color: #193e8f;
            box-shadow: 0 0 0 3px rgba(25, 62, 143, 0.15);
        }

        .toggle-senha {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            color: #9aa3bd;
        }

        .erro-msg {
            display: none;
            background-color: #fdeaea;
            color: #ea3e44;
            border: 1px solid #f5c6c8;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .erro-msg.visivel {
            display: block;
        }

        .btn-entrar {
            width: 100%;
            padding: 15px;
            margin-top: 8px;
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            background-color: #193e8f;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.4s ease;
        }

        .btn-entrar:hover {
            background-color: #ea3e44;
        }

        @media (max-width: 768px) {
            nav {
                padding: 12px 20px;
            }

            nav.scrolled {
                max-width: calc(100% - 24px);
                border-radius: 24px;
            }

            .conteudo-curso {
                flex-direction: column;
                text-align: center;
            }

            .conteudo-curso.invertido {
                flex-direction: column;
            }

            .logo img,
            nav.scrolled .logo img {
                width: 100px;
            }

            .login-form-lado {
                padding: 32px 24px;
            }
        }
    </style>
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
            <button type="button" class="b_login" id="abrirLogin">Login</button>
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

    <!-- ===== MODAL DE LOGIN ===== -->
    <div class="login-overlay <?php echo $erroLogin ? 'aberto' : ''; ?>" id="loginOverlay">
        <div class="login-card">
            <div class="login-form-lado">
                <div class="login-topo">
                    <h2>Bem-vindo</h2>
                    <button type="button" class="btn-voltar" id="fecharLogin">Voltar ao site &rarr;</button>
                </div>
                <p class="subtitulo">Acesse a sua conta para continuar.</p>

                <div class="erro-msg <?php echo $erroLogin ? 'visivel' : ''; ?>" id="erroMsg">
                    Usuário ou senha incorretos.
                </div>

                <form action="vLogin.php" method="POST">
                    <div class="campo">
                        <label for="user">Usuário</label>
                        <input type="text" id="user" name="user" placeholder="Digite seu usuário" required>
                    </div>

                    <div class="campo">
                        <label for="pass">Senha</label>
                        <div class="campo-senha">
                            <input type="password" id="pass" name="pass" placeholder="Digite sua senha" required>
                            <button type="button" class="toggle-senha" id="toggleSenha" aria-label="Mostrar senha">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="btn-entrar">Entrar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Bloqueios (menu de contexto e F12)
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

        document.addEventListener('keydown', function (e) {
            if (e.keyCode === 123) { // F12
                e.preventDefault();
            }
        });

        // ===== Efeito de encolher/opacar a navbar ao rolar =====
        const navbar = document.querySelector("nav");

        function handleNavScroll() {
            if (window.scrollY > 40) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }
        }

        window.addEventListener("scroll", handleNavScroll);
        handleNavScroll(); // executa uma vez ao carregar

        // ===== Animações ao entrar na viewport =====
        function isElementVisible(el) {
            const rect = el.getBoundingClientRect();
            const windowHeight = window.innerHeight || document.documentElement.clientHeight;
            return rect.top < windowHeight * 0.8;
        }

        function handleScrollAnimations() {
            const sections = document.querySelectorAll('.conteudo-curso');
            sections.forEach(section => {
                if (isElementVisible(section) && !section.classList.contains('animate')) {
                    section.classList.add('animate');
                }
            });
        }

        window.addEventListener('scroll', handleScrollAnimations);
        window.addEventListener('load', handleScrollAnimations);

        // ===== Controle do modal de login =====
        const loginOverlay = document.getElementById('loginOverlay');
        const abrirLogin = document.getElementById('abrirLogin');
        const fecharLogin = document.getElementById('fecharLogin');

        function abrirModal() {
            loginOverlay.classList.add('aberto');
            document.body.style.overflow = 'hidden';
        }

        function fecharModal() {
            loginOverlay.classList.remove('aberto');
            document.body.style.overflow = '';
        }

        if (abrirLogin) {
            abrirLogin.addEventListener('click', abrirModal);
        }

        if (fecharLogin) {
            fecharLogin.addEventListener('click', fecharModal);
        }

        // Fecha ao clicar fora do card
        loginOverlay.addEventListener('click', function (e) {
            if (e.target === loginOverlay) {
                fecharModal();
            }
        });

        // Fecha com a tecla ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                fecharModal();
            }
        });

        // ===== Mostrar/ocultar senha =====
        const toggleSenha = document.getElementById('toggleSenha');
        const inputSenha = document.getElementById('pass');

        if (toggleSenha) {
            toggleSenha.addEventListener('click', function () {
                const tipo = inputSenha.getAttribute('type') === 'password' ? 'text' : 'password';
                inputSenha.setAttribute('type', tipo);
            });
        }

        // Se o login falhou, foca no campo de usuário ao abrir
        <?php if ($erroLogin): ?>
        document.body.style.overflow = 'hidden';
        document.getElementById('user').focus();
        <?php endif; ?>
    </script>
</body>
</html>
