const botoes = document.querySelectorAll('.divSelecao');
const btnJogar = document.getElementById('btnJogar');

// Parte da URL/localStorage: ao voltar de "Mudar dificuldade", a última escolha continua marcada
const escolha = lerConfiguracao();

function atualizarDestaque() {
    botoes.forEach(function (botao) {
        const ativo = escolha[botao.dataset.grupo] === botao.dataset.valor;
        botao.classList.toggle('selecionado', ativo);
        botao.setAttribute('aria-pressed', String(ativo));
    });
    // O link já fica pronto com a escolha atual (funciona até com clique do meio/Ctrl+clique)
    btnJogar.href = montarUrlJogo(escolha);
}

botoes.forEach(function (botao) {
    botao.addEventListener('click', function () {
        escolha[botao.dataset.grupo] = botao.dataset.valor;
        atualizarDestaque();
    });
});

btnJogar.addEventListener('click', function () {
    salvarConfiguracao(escolha);
});

atualizarDestaque();
