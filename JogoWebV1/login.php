<?php
require_once __DIR__ . '/sessao.php';

if (!empty($_SESSION['usuario_id'])) {
    header('Location: main_menu.php');
    exit;
}

$erro  = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senhaDigitada = $_POST['senha'] ?? '';

    if ($login === '' || $senhaDigitada === '') {
        $erro = 'Preencha usuário e senha.';
    } else {
        try {
            require_once __DIR__ . '/conexao.php';

            $stmt = $pdo->prepare(
                'SELECT id, nome_completo, username, senha
                   FROM usuarios
                  WHERE username = ? OR email = ?
               ORDER BY (username = ?) DESC'
            );
            $stmt->execute([$login, $login, $login]);

            $autenticado = null;
            foreach ($stmt->fetchAll() as $u) {
                if (password_verify($senhaDigitada, $u['senha'])) {
                    $autenticado = $u;
                    break;
                }
            }

            if ($autenticado) {
                if (password_needs_rehash($autenticado['senha'], PASSWORD_DEFAULT)) {
                    $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')
                        ->execute([password_hash($senhaDigitada, PASSWORD_DEFAULT), $autenticado['id']]);
                }

                session_regenerate_id(true);
                $_SESSION['usuario_id'] = (int) $autenticado['id'];
                $_SESSION['username']   = $autenticado['username'];
                $_SESSION['nome']       = $autenticado['nome_completo'];

                header('Location: main_menu.php');
                exit;
            }

            $erro = 'Usuário ou senha inválidos.';
        } catch (PDOException $e) {
            $erro = 'Erro ao consultar o banco. Execute primeiro o instalar_banco.php.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acerte a Toupeira - Login</title>
    <link rel="stylesheet" href="style_login_cadastro.css">
    <link rel="icon" href="imagens/topeira_capacete.png">
    <style>
        .msg-erro {
            margin-bottom: 20px;
            font-size: 9px;
            line-height: 1.6;
            color: #FF4D6D;
            text-shadow: 0 0 5px #FF4D6D;
        }
    </style>
</head>
<body>

    <div class="ui-container">
        <h1>ACERTE A<br>TOUPEIRA</h1>

        <?php if ($erro !== ''): ?>
            <p class="msg-erro" role="alert"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="login">USUARIO OU EMAIL</label>
                <input type="text" id="login" name="login" value="<?= htmlspecialchars($login) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="senha">SENHA</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <button type="submit" class="btn-primary">START</button>
        </form>

        <a href="cadastro.php" class="link">NOVO JOGADOR? REGISTE-SE</a>

        <a href="titulo.html" class="link">SAIR</a>
    </div>

</body>
</html>
