<?php
require_once __DIR__ . '/conexao.php';

$mensagem = '';
$tipoMensagem = '';
$valores = [
    'nome' => '',
    'nascimento' => '',
    'cpf' => '',
    'telefone' => '',
    'email' => '',
    'username' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($valores as $campo => $_) {
        $valores[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }

    $senhaFormulario = (string) ($_POST['senha'] ?? '');
    $confirmacao = (string) ($_POST['confirma_senha'] ?? '');
    $camposObrigatorios = array_merge(array_values($valores), [$senhaFormulario, $confirmacao]);

    if (in_array('', $camposObrigatorios, true)) {
        $mensagem = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um endereço de e-mail válido.';
    } elseif ($senhaFormulario !== $confirmacao) {
        $mensagem = 'A senha e a confirmação precisam ser iguais.';
    } else {
        $cpf = preg_replace('/\D+/', '', $valores['cpf']);
        $data = DateTime::createFromFormat('!Y-m-d', $valores['nascimento']);
        $dataValida = $data && $data->format('Y-m-d') === $valores['nascimento'];

        if (strlen($cpf) !== 11 || !$dataValida) {
            $mensagem = 'Confira o CPF (11 dígitos) e a data de nascimento.';
        } else {
            try {
                $consulta = $pdo->prepare('SELECT cpf, username FROM usuarios WHERE cpf = :cpf OR username = :username OR REPLACE(REPLACE(REPLACE(cpf, ".", ""), "-", ""), "/", "") = :cpf_formatado');
                $consulta->execute([
                    ':cpf' => $cpf,
                    ':username' => $valores['username'],
                    ':cpf_formatado' => $cpf,
                ]);
                $existente = $consulta->fetch();

                if ($existente && preg_replace('/\D+/', '', $existente['cpf']) === $cpf) {
                    $mensagem = 'Este CPF já está cadastrado.';
                } elseif ($existente && $existente['username'] === $valores['username']) {
                    $mensagem = 'Este username já está em uso.';
                } else {
                    $insercao = $pdo->prepare(
                        'INSERT INTO usuarios (nome_completo, data_nascimento, cpf, telefone, email, username, senha) '
                        . 'VALUES (:nome, :nascimento, :cpf, :telefone, :email, :username, :senha)'
                    );
                    $insercao->execute([
                        ':nome' => $valores['nome'],
                        ':nascimento' => $valores['nascimento'],
                        ':cpf' => $cpf,
                        ':telefone' => $valores['telefone'],
                        ':email' => $valores['email'],
                        ':username' => $valores['username'],
                        ':senha' => password_hash($senhaFormulario, PASSWORD_DEFAULT),
                    ]);

                    $mensagem = 'Cadastro realizado com sucesso! Você já pode voltar à tela inicial.';
                    $tipoMensagem = 'sucesso';
                    $valores = array_fill_keys(array_keys($valores), '');
                }
            } catch (PDOException $e) {
                // Os índices UNIQUE do banco também protegem contra cadastros concorrentes.
                if ($e->getCode() === '23000') {
                    $mensagem = 'CPF ou username já cadastrado.';
                } else {
                    error_log('Falha ao cadastrar jogador: ' . $e->getMessage());
                    $mensagem = 'Não foi possível concluir o cadastro agora. Tente novamente.';
                }
            }
        }
    }

    if ($tipoMensagem === '') {
        $tipoMensagem = 'erro';
    }
}

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acerte a Toupeira - Cadastro</title>
    <link rel="stylesheet" href="style_login_cadastro.css">
    <link rel="icon" href="imagens/topeira_capacete.png">
    <style>
        .ui-container { max-width: 700px; margin-top: 30px; margin-bottom: 30px; }
        .grid-2-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .mensagem { padding: 12px; margin-bottom: 20px; font-size: 10px; line-height: 1.7; }
        .mensagem.erro { color: #FF4D6D; border: 1px solid #FF4D6D; }
        .mensagem.sucesso { color: #8DFFB3; border: 1px solid #8DFFB3; }
        @media (max-width: 600px) { .grid-2-cols { grid-template-columns: 1fr; gap: 0; } }
    </style>
</head>
<body>
    <main class="ui-container">
        <h1>NOVO JOGADOR</h1>
        <?php if ($mensagem !== ''): ?>
            <p class="mensagem <?= escapar($tipoMensagem) ?>" role="status"><?= escapar($mensagem) ?></p>
        <?php endif; ?>

        <form action="cadastro.php" method="post" id="formCadastro">
            <div class="form-group">
                <label for="nome">NOME COMPLETO</label>
                <input type="text" id="nome" name="nome" maxlength="150" autocomplete="name" value="<?= escapar($valores['nome']) ?>" required>
            </div>
            <div class="grid-2-cols">
                <div class="form-group">
                    <label for="username">NOME DE USUÁRIO</label>
                    <input type="text" id="username" name="username" maxlength="50" autocomplete="username" value="<?= escapar($valores['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="nascimento">DATA DE NASC.</label>
                    <input type="date" id="nascimento" name="nascimento" autocomplete="bday" value="<?= escapar($valores['nascimento']) ?>" required>
                </div>
            </div>
            <div class="grid-2-cols">
                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" value="<?= escapar($valores['cpf']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="telefone">TELEFONE</label>
                    <input type="tel" id="telefone" name="telefone" maxlength="20" autocomplete="tel" placeholder="11 999999999" value="<?= escapar($valores['telefone']) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">E-MAIL</label>
                <input type="email" id="email" name="email" maxlength="120" autocomplete="email" value="<?= escapar($valores['email']) ?>" required>
            </div>
            <div class="grid-2-cols">
                <div class="form-group">
                    <label for="senha">SENHA</label>
                    <input type="password" id="senha" name="senha" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                    <label for="confirma_senha">CONFIRMAR SENHA</label>
                    <input type="password" id="confirma_senha" name="confirma_senha" autocomplete="new-password" required>
                </div>
            </div>
            <button type="submit" class="btn-primary">GRAVAR DADOS</button>
        </form>
        <a href="titulo.html" class="link">VOLTAR</a>
    </main>
</body>
</html>
