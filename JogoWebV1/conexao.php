<?php
$host = 'localhost';
$banco = 'whack_a_mole';
$usuario = 'root';
$senha = ''; // No XAMPP por padrão a senha do root é vazia

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados. Execute primeiro o arquivo instalar_banco.php.");
}
