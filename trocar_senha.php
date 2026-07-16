<?php
    session_start();

    /* -------- Só quem está logado pode trocar a própria senha -------- */
    if (!isset($_SESSION['aluno_id'])) {
        header('Location: index.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && !empty($_POST['nova_senha'])
        && !empty($_POST['confirmar_senha'])) {

        include_once('config.php');

        $nova      = $_POST['nova_senha'];
        $confirma  = $_POST['confirmar_senha'];

        if ($nova !== $confirma) {
            header('Location: dashboard.php?senha_erro=confirmacao');
            exit;
        }

        if (strlen($nova) < 4) {
            header('Location: dashboard.php?senha_erro=tamanho');
            exit;
        }

        if ($nova === '123') {
            header('Location: dashboard.php?senha_erro=padrao');
            exit;
        }

        $hash = password_hash($nova, PASSWORD_DEFAULT);

        $upd = $conexao->prepare("UPDATE alunos SET pass = ?, trocar_senha = 0 WHERE id = ?");
        $upd->bind_param("si", $hash, $_SESSION['aluno_id']);
        $upd->execute();

        // Atualiza a sessão para o modal não aparecer mais nesta mesma sessão
        $_SESSION['trocar_senha'] = 0;

        header('Location: dashboard.php?senha=ok');
        exit;
    }

    header('Location: dashboard.php');
    exit;
?>
