<?php
    session_start();

    // Limpa todos os dados da sessão
    $_SESSION = [];
    session_destroy();

    header('Location: index.php');
    exit;
?>
