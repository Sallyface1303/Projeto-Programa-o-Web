const CHAVE_CONFIG = 'configuracaoJogo';
const PADRAO = { modo: 'normal', dificuldade: 'facil' };

const LAYOUTS = {
    facil:   [3, 3],
    medio:   [3, 4, 3],
    dificil: [4, 5, 4, 5]
};

function lerConfiguracao() {
    let salvo = {};
    try {
        salvo = JSON.parse(localStorage.getItem(CHAVE_CONFIG)) || {};
    } catch (e) {
        salvo = {};
    }
    const config = { ...PADRAO, ...salvo };
    if (!LAYOUTS[config.dificuldade]) config.dificuldade = PADRAO.dificuldade;
    if (config.modo !== 'normal' && config.modo !== 'explosivo') config.modo = PADRAO.modo;
    return config;
}

const configuracao = lerConfiguracao();

function desenharTabuleiro() {
    const tabuleiro = document.getElementById('tabuleiro');
    const linhas = LAYOUTS[configuracao.dificuldade];
    tabuleiro.innerHTML = '';
    tabuleiro.dataset.modo = configuracao.modo;
    tabuleiro.dataset.dificuldade = configuracao.dificuldade;

    let indice = 0;
    linhas.forEach(function (quantidade) {
        const linha = document.createElement('div');
        linha.className = 'row';
        if (quantidade > linhas[0]) linha.classList.add('row2');

        for (let i = 0; i < quantidade; i++) {
            const buraco = document.createElement('button');
            buraco.type = 'button';
            buraco.className = 'hole';
            buraco.dataset.indice = indice++;

            const img = document.createElement('img');
            img.src = 'imagens/buraco.png';
            img.className = 'holeimg';
            img.alt = 'Buraco';

            buraco.appendChild(img);
            linha.appendChild(buraco);
        }
        tabuleiro.appendChild(linha);
    });

    ajustarTamanhoBuracos();
}

function ajustarTamanhoBuracos() {
    const tabuleiro = document.getElementById('tabuleiro');
    const linhas = LAYOUTS[configuracao.dificuldade];
    const maxColunas = Math.max(...linhas);
    const porLargura = (window.innerWidth - 40) / maxColunas - 10;
    const porAltura = tabuleiro.clientHeight / linhas.length - 20;
    const tamanho = Math.max(60, Math.min(200, porLargura, porAltura));
    tabuleiro.style.setProperty('--tam-buraco', Math.floor(tamanho) + 'px');
}

window.addEventListener('resize', ajustarTamanhoBuracos);
desenharTabuleiro();
