<?php

function destino(string $base): string
{
    return is_file(__DIR__ . "/{$base}.php") ? "{$base}.php" : "{$base}.html";
}

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (is_file(__DIR__ . '/verifica_sessao.php')) {
    require_once __DIR__ . '/verifica_sessao.php';
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . destino('login'));
        exit;
    }
}

require_once __DIR__ . '/conexao.php';

$usuarioId = (int) $_SESSION['usuario_id']; //Sessão do usuário

if (empty($_SESSION['csrf_edicao'])) {
    $_SESSION['csrf_edicao'] = bin2hex(random_bytes(32));
}

$mensagem = '';
$tipoMensagem = '';

try {
    $consulta = $pdo->prepare(
        'SELECT nome_completo, data_nascimento, cpf, telefone, email, username FROM usuarios WHERE id = :id'
    );
    $consulta->execute([':id' => $usuarioId]);
    $usuario = $consulta->fetch();
} catch (PDOException $e) {
    error_log('Falha ao carregar perfil: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar os dados do jogador.');
}

if (!$usuario) {
    // Sessão aponta para um usuário que não existe mais.
    session_destroy();
    header('Location: ' . destino('login'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecebido = (string) ($_POST['csrf'] ?? '');
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $telefone = trim((string) ($_POST['telefone'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $novaSenha = (string) ($_POST['senha'] ?? '');
    $confirmacao = (string) ($_POST['confirma_senha'] ?? '');

    // Mantém o que o jogador digitou caso haja erro de validação.
    $usuario['nome_completo'] = $nome;
    $usuario['telefone'] = $telefone;
    $usuario['email'] = $email;

    if (!hash_equals($_SESSION['csrf_edicao'], $tokenRecebido)) {
        $mensagem = 'Sessão do formulário expirada. Recarregue a página e tente novamente.';
    } elseif ($nome === '' || $telefone === '' || $email === '') {
        $mensagem = 'Nome, e-mail e telefone são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um endereço de e-mail válido.';
    } elseif ($novaSenha !== $confirmacao) {
        $mensagem = 'A nova senha e a confirmação precisam ser iguais.';
    } else {
        try {
            // Somente os campos editáveis; cpf, data_nascimento e username ficam de fora.
            $sql = 'UPDATE usuarios SET nome_completo = :nome, telefone = :telefone, email = :email';
            $parametros = [
                ':nome' => $nome,
                ':telefone' => $telefone,
                ':email' => $email,
                ':id' => $usuarioId,
            ];

            if ($novaSenha !== '') {
                $sql .= ', senha = :senha';
                $parametros[':senha'] = password_hash($novaSenha, PASSWORD_DEFAULT);
            }

            $sql .= ' WHERE id = :id';
            $pdo->prepare($sql)->execute($parametros);

            $mensagem = 'Dados atualizados com sucesso!';
            $tipoMensagem = 'sucesso';
        } catch (PDOException $e) {
            error_log('Falha ao atualizar perfil: ' . $e->getMessage());
            $mensagem = 'Não foi possível salvar as alterações agora. Tente novamente.';
        }
    }

    if ($tipoMensagem === '') {
        $tipoMensagem = 'erro';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edição de perfil</title>
	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="layout_edicao.css">
	<link rel="icon" href="imagens/topeira_capacete.png">
</head>
<body>
    <div class="conteiner">
        <div class="menu">
            <a href="<?= destino('main_menu') ?>" class="a">ACERTE A TOUPEIRA</a>
            <div class="lineBottom"></div>
        </div>
        <header>
            <span class="line"></span>
            <h1>Alteração de informações</h1>
            <span class="line"></span>
        </header>
        <main>
            <div class="divImg">
                <img src="imagens/fotoPerfil.png" alt="Foto de perfil">
            </div>
            <form class="divInfo" action="edicao.php" method="post">
                <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf_edicao']) ?>">

                <?php if ($mensagem !== ''): ?>
                    <p class="mensagem <?= escapar($tipoMensagem) ?>" role="status"><?= escapar($mensagem) ?></p>
                <?php endif; ?>

                <p><label for="nome">Nome</label></p>
                <div class="divUser"><input class="input" type="text" id="nome" name="nome" maxlength="150" value="<?= escapar($usuario['nome_completo']) ?>" required></div>
                <br>
                <p><label for="username">Nome de usuário (não pode ser alterado)</label></p>
                <div class="divUser"><input class="input somenteLeitura" type="text" id="username" value="<?= escapar($usuario['username']) ?>" readonly></div>
                <br>
                <p><label for="cpf">CPF (não pode ser alterado)</label></p>
                <div class="divUser"><input class="input somenteLeitura" type="text" id="cpf" value="<?= escapar($usuario['cpf']) ?>" readonly></div>
                <br>
                <p><label for="nascimento">Data de nascimento (não pode ser alterada)</label></p>
                <div class="divUser"><input class="input somenteLeitura" type="date" id="nascimento" value="<?= escapar($usuario['data_nascimento']) ?>" readonly></div>
                <br>
                <p><label for="email">E-mail</label></p>
                <div class="divUser"><input class="input" type="email" id="email" name="email" maxlength="120" value="<?= escapar($usuario['email']) ?>" required></div>
                <br>
                <p><label for="telefone">Telefone</label></p>
                <div class="divUser"><input class="input" type="tel" id="telefone" name="telefone" maxlength="20" value="<?= escapar($usuario['telefone']) ?>" required></div>
                <br>
                <p><label for="senha">Nova senha (deixe em branco para manter a atual)</label></p>
                <div class="divUser"><input class="input" type="password" id="senha" name="senha" autocomplete="new-password"></div>
                <br>
                <p><label for="confirma_senha">Confirmar nova senha</label></p>
                <div class="divUser"><input class="input" type="password" id="confirma_senha" name="confirma_senha" autocomplete="new-password"></div>
                <br>
                <button type="submit" class="buttonSave">Confirmar</button>
                <a href="<?= destino('perfil') ?>" class="voltar">Voltar ao perfil</a>
            </form>
        </main>
    </div>
</body>
</html>
