<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="stylesheet" href="css/login.css">
    <link rel="shortcut icon" type="imagex/png" href="https://salesianossp.org.br/ositaquera/wp-content/uploads/2024/03/wp-favicon-pvi-os-150x150.webp">
  <title>Entrar</title>
</head>
  <body id="principal" class="no-select">

    <a class="b_voltar" href="index.php">Voltar</a>

    <form action="vLogin.php" method="POST">
    <div id="login">

        <div class="caixa">

            <a href="index.php"><img src="img/logo.png" alt="logo"></a>
            <h1>LOGIN</h1>

            <?php if (isset($_GET['erro'])): ?>
                <p class="erro-login">Usuário ou senha incorretos. Tente novamente.</p>
            <?php endif; ?>

            <div class="usuario">
                <input type="text" name="user" placeholder="Usuário" required>
            </div>

            <div class="senha">
                <input type="password" name="pass" placeholder="Senha" required>
            </div>

            <div class="entrar">
                <input type="submit" name="submit" value="Entrar">
            </div>

        </div>

    </div>
    </form>



    <script src="js/login.js"></script>

  </body>
</html>
