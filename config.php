<?php
/**
 * Conexão com o banco de dados - Telecentro
 * Banco: curso_tele (MySQL local)
 *
 * Este arquivo é incluído em todas as páginas que acessam o banco
 * (vLogin.php, dashboard.php, curso.php, admin.php).
 */

// ---- Dados da conexão (ajuste se necessário) ----
$dbHost     = '127.0.0.1';
$dbUsername = 'root';
$dbPassword = '';
$dbName     = 'curso_tele';

// Faz o mysqli lançar exceção em caso de erro, em vez de warning solto.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Variável usada nas demais páginas
    $conexao = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

    // Charset completo (acentos e emojis)
    mysqli_set_charset($conexao, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Em rede local, mostramos uma mensagem amigável e paramos a execução.
    http_response_code(500);
    die('Não foi possível conectar ao banco de dados. Verifique se o MySQL está ligado.');
}
?>
