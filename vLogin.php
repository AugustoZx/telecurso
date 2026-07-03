<?php
    session_start();

    if (isset($_POST['submit']) && !empty($_POST['user']) && !empty($_POST['pass'])) {

        include_once('config.php');

        $user = trim($_POST['user']);
        $pass = $_POST['pass'];

        /* Prepared statement: evita SQL injection.
           Buscamos o aluno só pelo usuário e conferimos a senha no PHP. */
        $sql = "SELECT id, nome, user, pass, adm FROM alunos WHERE user = ? LIMIT 1";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $aluno = $result->fetch_assoc();

            $senha_ok    = false;
            $hash_no_bd  = $aluno['pass'];

            /* A senha no banco está com hash? (começa com $2y$, $argon, etc.) */
            $eh_hash = (strlen($hash_no_bd) > 0 && $hash_no_bd[0] === '$');

            if ($eh_hash) {
                // Caminho novo e seguro
                $senha_ok = password_verify($pass, $hash_no_bd);
            } else {
                /* Caminho de MIGRAÇÃO: senha antiga em texto puro.
                   Se bater, criamos o hash agora e atualizamos o banco,
                   para que no próximo login já use o formato seguro. */
                if ($pass === $hash_no_bd) {
                    $senha_ok = true;
                    $novo_hash = password_hash($pass, PASSWORD_DEFAULT);
                    $up = $conexao->prepare("UPDATE alunos SET pass = ? WHERE id = ?");
                    $up->bind_param("si", $novo_hash, $aluno['id']);
                    $up->execute();
                }
            }

            if ($senha_ok) {
                // Login válido: guarda os dados na sessão
                session_regenerate_id(true);
                $_SESSION['aluno_id']   = $aluno['id'];
                $_SESSION['aluno_nome'] = $aluno['nome'];
                $_SESSION['aluno_user'] = $aluno['user'];
                $_SESSION['adm']        = $aluno['adm'];

                header('Location: dashboard.php');
                exit;
            }
        }

        // Usuário ou senha incorretos
        header('Location: login.php?erro=1');
        exit;

    } else {
        header('Location: login.php');
        exit;
    }
?>
