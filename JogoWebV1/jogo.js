// =========================================================
// ACERTE A TOUPEIRA
// Depende de config.js (lerConfiguracao), carregado antes.
//
//   1. Parâmetros do jogo
//   2. Estado da partida
//   3. Tabuleiro (desenho e tamanho dos buracos)
//   4. Toupeiras (aparecer, expirar, congelar)
//   5. Regras (cliques, pontos, combo, explosões)
//   6. Ciclo da partida (iniciar, pausar, encerrar, reiniciar)
//   7. Modal (pausa / fim de jogo)
//   8. Eventos
// =========================================================

// ---------------------------------------------------------
// 1. PARÂMETROS DO JOGO
// ---------------------------------------------------------
const configuracao = lerConfiguracao();

const DURACAO_PARTIDA = 60;   // segundos
const TEMPO_MAXIMO = 99;      // limite do bônus de tempo
const TEMPO_CRITICO = 10;     // o cronômetro fica vermelho a partir daqui

const LAYOUTS = {
    facil:   [3, 3],
    medio:   [3, 4, 3],
    dificil: [4, 5, 4, 5]
};

const GAP_BURACO = 12;        // px entre buracos
const TAMANHO_MIN_BURACO = 48;
const TAMANHO_MAX_BURACO = 200;

// Fácil: aparece a cada 2s e fica 1.7s | Médio: 1.4s / 1.1s | Difícil: 1s / 0.85s
const VELOCIDADE_DIFICULDADE = {
    facil:   { intervalo: 2000, exposicao: 1700 },
    medio:   { intervalo: 1400, exposicao: 1100 },
    dificil: { intervalo: 1000, exposicao: 850 }
};

// Pontuação mínima ao fim do tempo para vencer a partida
const META_PONTOS = {
    facil:   2500,
    medio:   4000,
    dificil: 6000
};

const ROTULO_MODO = { normal: 'NORMAL', explosivo: 'EXPLOSIVO' };
const ROTULO_NIVEL = { facil: 'FÁCIL', medio: 'MÉDIO', dificil: 'DIFÍCIL' };

// Tudo o que pode aparecer num buraco
const TIPOS = {
    base:     { img: 'topera-base.png',      alt: 'Toupeira' },
    base2:    { img: 'topeira_base2.png',    alt: 'Toupeira sem capacete' },
    capacete: { img: 'topeira_capacete.png', alt: 'Toupeira de Capacete' },
    nerd:     { img: 'topera-nerd.png',      alt: 'Toupeira Bônus' },
    bomba:    { img: 'bomba.png',            alt: 'Bomba',          perigo: true },
    mina:     { img: 'mina_terrestre.png',   alt: 'Mina Explosiva', perigo: true }
};

// Tabelas de sorteio: [limite acumulado, tipo]. Se a dificuldade não tiver
// tabela própria, usa a "padrao" do modo.
const PROBABILIDADES = {
    normal: {
        padrao: [[0.20, 'capacete'], [0.35, 'nerd'], [Infinity, 'base']]
    },
    explosivo: {
        padrao:  [[0.20, 'mina'], [0.40, 'capacete'], [0.50, 'nerd'], [Infinity, 'base']],
        dificil: [[0.10, 'bomba'], [0.30, 'mina'], [0.55, 'capacete'], [0.65, 'nerd'], [Infinity, 'base']]
    }
};

// ---------------------------------------------------------
// 2. ESTADO DA PARTIDA
// ---------------------------------------------------------
const estado = {
    fase: 'rodando',          // 'rodando' | 'pausado' | 'encerrado'
    pontuacao: 0,
    combo: 1,
    acertosSeguidos: 0,
    melhorCombo: 1,
    acertos: 0,
    erros: 0,
    tempoRestante: DURACAO_PARTIDA,
    intervaloSpawn: null,
    intervaloTempo: null,
    audioCtx: null
};

// Timers de expiração das toupeiras visíveis: buraco -> { timerId, fim, restante }
const expiracoes = new Map();

const tabuleiro = document.getElementById('tabuleiro');
const elPontuacao = document.getElementById('pontValue');
const elTempo = document.getElementById('timeValue');
const elCombo = document.getElementById('comboValue');
const btnPause = document.getElementById('btnPause');

// ---------------------------------------------------------
// 3. TABULEIRO
// ---------------------------------------------------------
function desenharTabuleiro() {
    const linhas = LAYOUTS[configuracao.dificuldade];
    tabuleiro.innerHTML = '';
    tabuleiro.dataset.modo = configuracao.modo;
    tabuleiro.dataset.dificuldade = configuracao.dificuldade;
    tabuleiro.style.setProperty('--gap-buraco', GAP_BURACO + 'px');

    let indice = 0;
    linhas.forEach(function (quantidade) {
        const linha = document.createElement('div');
        linha.className = 'row';

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

// O tamanho dos buracos sai do espaço realmente disponível no tabuleiro
// (largura E altura), então o jogo sempre cabe na tela.
function ajustarTamanhoBuracos() {
    const linhas = LAYOUTS[configuracao.dificuldade];
    const maxColunas = Math.max(...linhas);
    const largura = tabuleiro.clientWidth;
    const altura = tabuleiro.clientHeight;
    if (!largura || !altura) return;

    const porLargura = (largura - (maxColunas - 1) * GAP_BURACO) / maxColunas;
    const porAltura = (altura - (linhas.length - 1) * GAP_BURACO) / linhas.length;
    const tamanho = Math.max(TAMANHO_MIN_BURACO, Math.min(TAMANHO_MAX_BURACO, porLargura, porAltura));
    tabuleiro.style.setProperty('--tam-buraco', Math.floor(tamanho) + 'px');
}

// ---------------------------------------------------------
// 4. TOUPEIRAS
// ---------------------------------------------------------
function todosBuracos() {
    return Array.from(tabuleiro.querySelectorAll('.hole'));
}

function mostrarToupeira(buraco, tipo) {
    const def = TIPOS[tipo];
    const img = buraco.querySelector('.holeimg');
    img.src = 'imagens/' + def.img;
    img.alt = def.alt;
    buraco.dataset.tipo = tipo;
    buraco.classList.toggle('armadilha-ativa', Boolean(def.perigo));
}

function esconderToupeira(buraco) {
    cancelarExpiracao(buraco);
    const img = buraco.querySelector('.holeimg');
    delete buraco.dataset.tipo;
    buraco.classList.remove('armadilha-ativa');
    img.src = 'imagens/buraco.png';
    img.alt = 'Buraco';
}

// Regra de 1 toupeira por vez: limpa a tela inteira
function esconderTodasToupeiras() {
    todosBuracos().forEach(function (buraco) {
        if (buraco.dataset.tipo) esconderToupeira(buraco);
    });
    expiracoes.forEach(function (e) { clearTimeout(e.timerId); });
    expiracoes.clear();
}

function agendarExpiracao(buraco, ms) {
    const timerId = setTimeout(function () { expirarToupeira(buraco); }, ms);
    expiracoes.set(buraco, { timerId: timerId, fim: Date.now() + ms, restante: ms });
}

function cancelarExpiracao(buraco) {
    const e = expiracoes.get(buraco);
    if (e) {
        clearTimeout(e.timerId);
        expiracoes.delete(buraco);
    }
}

function expirarToupeira(buraco) {
    expiracoes.delete(buraco);
    if (buraco.dataset.tipo === 'bomba') {
        explodirBombaComum(buraco);   // bomba que ninguém desarmou
    } else {
        esconderToupeira(buraco);
    }
}

// Pausa: guarda quanto tempo faltava para cada toupeira sumir...
function congelarExpiracoes() {
    expiracoes.forEach(function (e) {
        clearTimeout(e.timerId);
        e.restante = Math.max(0, e.fim - Date.now());
    });
}

// ...e retoma exatamente de onde parou.
function retomarExpiracoes() {
    expiracoes.forEach(function (e, buraco) {
        e.fim = Date.now() + e.restante;
        e.timerId = setTimeout(function () { expirarToupeira(buraco); }, e.restante);
    });
}

function sortearTipo() {
    const tabelas = PROBABILIDADES[configuracao.modo];
    const tabela = tabelas[configuracao.dificuldade] || tabelas.padrao;
    const sorteio = Math.random();
    return tabela.find(function (faixa) { return sorteio < faixa[0]; })[1];
}

function sortearToupeira() {
    if (estado.fase !== 'rodando') return;

    esconderTodasToupeiras();

    const buracos = todosBuracos();
    const escolhido = buracos[Math.floor(Math.random() * buracos.length)];
    mostrarToupeira(escolhido, sortearTipo());
    agendarExpiracao(escolhido, VELOCIDADE_DIFICULDADE[configuracao.dificuldade].exposicao);
}

// ---------------------------------------------------------
// 5. REGRAS
// ---------------------------------------------------------
function processarCliqueBuraco(buraco) {
    switch (buraco.dataset.tipo) {
        case undefined:  registrarFalha(buraco); break;
        case 'mina':     processarExplosao(buraco); break;
        case 'bomba':    desarmarBomba(buraco); break;
        case 'capacete': acertarCapacete(buraco); break;
        case 'nerd':     acertarNerd(buraco); break;
        default:         acertarBase(buraco);      // base e base2
    }
}

function pontuar(buraco, pontos, texto, tipoCor, efeito) {
    estado.pontuacao += pontos;
    aplicarEfeitoBuraco(buraco, efeito);
    mostrarTextoFlutuante(buraco, texto, tipoCor);
    atualizarPlacar();
}

// O capacete aguenta 1 batida: rende pontos, mas a toupeira continua lá (sem capacete)
function acertarCapacete(buraco) {
    const pontos = 50 * estado.combo;
    tocarSomArcade(320, 'square', 0.08);
    mostrarToupeira(buraco, 'base2');
    pontuar(buraco, pontos, 'CAPACETE! +' + pontos, 'bonus', 'hit-capacete');
}

function acertarNerd(buraco) {
    aumentarCombo();
    const pontos = 250 * estado.combo;
    estado.tempoRestante = Math.min(TEMPO_MAXIMO, estado.tempoRestante + 2);
    tocarSomArcade(680, 'triangle', 0.12);
    esconderToupeira(buraco);
    pontuar(buraco, pontos, '+' + pontos + ' (+2s)', 'bonus', 'hit-acerto');
}

function acertarBase(buraco) {
    aumentarCombo();
    const pontos = 100 * estado.combo;
    tocarSomArcade(520, 'sine', 0.09);
    esconderToupeira(buraco);
    pontuar(buraco, pontos, '+' + pontos, 'positivo', 'hit-acerto');
}

function aumentarCombo() {
    estado.acertos++;
    estado.acertosSeguidos++;
    estado.combo = Math.min(5, 1 + Math.floor(estado.acertosSeguidos / 3));
    if (estado.combo > estado.melhorCombo) estado.melhorCombo = estado.combo;
}

function quebrarCombo() {
    estado.acertosSeguidos = 0;
    estado.combo = 1;
}

function registrarFalha(buraco) {
    const perda = configuracao.modo === 'explosivo' ? 80 : 25;
    estado.erros++;
    quebrarCombo();
    estado.pontuacao = Math.max(0, estado.pontuacao - perda);
    tocarSomArcade(150, 'sawtooth', 0.1);
    aplicarEfeitoBuraco(buraco, 'hit-erro');
    mostrarTextoFlutuante(buraco, '-' + perda, 'negativo');
    atualizarPlacar();
}

// Mina: -300 pontos e -5s. Se o tempo zerar, a partida acaba em derrota.
function processarExplosao(buraco) {
    estado.erros++;
    quebrarCombo();
    estado.pontuacao = Math.max(0, estado.pontuacao - 300);
    estado.tempoRestante = Math.max(0, estado.tempoRestante - 5);
    tocarSomArcade(90, 'sawtooth', 0.25);
    esconderToupeira(buraco);
    aplicarEfeitoBuraco(document.body, 'tremor-explosao');
    mostrarTextoFlutuante(buraco, 'BOOM! -300 (-5s)', 'negativo');
    atualizarPlacar();

    if (estado.tempoRestante <= 0) encerrarPartida('explosao');
}

// Bomba comum: clicar a desarma (sem pontos, mas quebra o combo)
function desarmarBomba(buraco) {
    estado.acertos++;
    quebrarCombo();
    tocarSomArcade(760, 'triangle', 0.1);
    esconderToupeira(buraco);
    aplicarEfeitoBuraco(buraco, 'hit-capacete');
    mostrarTextoFlutuante(buraco, 'DESARMADA!', 'bonus');
    atualizarPlacar();
}

// Bomba que ficou tempo demais na tela explode sozinha: -150
function explodirBombaComum(buraco) {
    estado.erros++;
    quebrarCombo();
    estado.pontuacao = Math.max(0, estado.pontuacao - 150);
    tocarSomArcade(90, 'sawtooth', 0.2);
    esconderToupeira(buraco);
    aplicarEfeitoBuraco(document.body, 'tremor-explosao');
    mostrarTextoFlutuante(buraco, 'BOOM! -150', 'negativo');
    atualizarPlacar();
}

function mostrarTextoFlutuante(buraco, texto, tipoCor) {
    const aviso = document.createElement('span');
    aviso.className = 'feedback-flutuante ' + tipoCor;
    aviso.textContent = texto;
    buraco.appendChild(aviso);
    setTimeout(function () { aviso.remove(); }, 700);
}

function aplicarEfeitoBuraco(elemento, classe) {
    elemento.classList.add(classe);
    setTimeout(function () { elemento.classList.remove(classe); }, 220);
}

function tocarSomArcade(freq, tipo, duracao) {
    try {
        if (!estado.audioCtx) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            estado.audioCtx = new AudioContextClass();
        }
        const ctx = estado.audioCtx;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = tipo;
        osc.frequency.setValueAtTime(freq, ctx.currentTime);
        gain.gain.setValueAtTime(0.07, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duracao);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + duracao);
    } catch (e) { /* sem áudio: o jogo continua normalmente */ }
}

// ---------------------------------------------------------
// 6. CICLO DA PARTIDA
// ---------------------------------------------------------
function atualizarPlacar() {
    elPontuacao.textContent = estado.pontuacao;
    elTempo.textContent = estado.tempoRestante + 's';
    elTempo.classList.toggle('critico', estado.tempoRestante <= TEMPO_CRITICO);
    elCombo.textContent = 'x' + estado.combo;
}

function atualizarBotaoPause() {
    const pausado = estado.fase === 'pausado';
    btnPause.setAttribute('aria-pressed', String(pausado));
    btnPause.setAttribute('aria-label', pausado ? 'Retomar' : 'Pausar');
    btnPause.title = pausado ? 'Retomar (P)' : 'Pausar (P)';
}

function iniciarIntervalos() {
    const vel = VELOCIDADE_DIFICULDADE[configuracao.dificuldade];
    estado.intervaloSpawn = setInterval(sortearToupeira, vel.intervalo);
    estado.intervaloTempo = setInterval(passarSegundo, 1000);
}

function pararIntervalos() {
    clearInterval(estado.intervaloSpawn);
    clearInterval(estado.intervaloTempo);
    estado.intervaloSpawn = null;
    estado.intervaloTempo = null;
}

function passarSegundo() {
    if (estado.fase !== 'rodando') return;
    estado.tempoRestante--;
    atualizarPlacar();
    if (estado.tempoRestante <= 0) encerrarPartida('tempo');
}

// Começa (ou recomeça) uma partida sem recarregar a página
function iniciarPartida() {
    pararIntervalos();
    esconderTodasToupeiras();

    estado.pontuacao = 0;
    estado.combo = 1;
    estado.acertosSeguidos = 0;
    estado.melhorCombo = 1;
    estado.acertos = 0;
    estado.erros = 0;
    estado.tempoRestante = DURACAO_PARTIDA;
    estado.fase = 'rodando';

    fecharModal();
    atualizarPlacar();
    atualizarBotaoPause();
    iniciarIntervalos();
}

// Pausa: para os dois setInterval e congela as toupeiras que estão na tela
function pausarPartida() {
    if (estado.fase !== 'rodando') return;
    estado.fase = 'pausado';
    pararIntervalos();
    congelarExpiracoes();
    atualizarBotaoPause();
    abrirModal({
        titulo: 'JOGO PAUSADO',
        subtitulo: 'PONTUAÇÃO ATUAL: ' + estado.pontuacao,
        estatisticas: [
            ['TEMPO RESTANTE', estado.tempoRestante + 's'],
            ['ACERTOS', estado.acertos],
            ['MAIOR COMBO', 'x' + estado.melhorCombo]
        ],
        podeContinuar: true
    });
}

function retomarPartida() {
    if (estado.fase !== 'pausado') return;
    estado.fase = 'rodando';
    fecharModal();
    retomarExpiracoes();
    iniciarIntervalos();
    atualizarBotaoPause();
}

function alternarPausa() {
    if (estado.fase === 'rodando') pausarPartida();
    else if (estado.fase === 'pausado') retomarPartida();
}

// motivo: 'tempo' (acabou o relógio) ou 'explosao' (mina zerou o tempo)
function encerrarPartida(motivo) {
    if (estado.fase === 'encerrado') return;
    estado.fase = 'encerrado';
    pararIntervalos();
    esconderTodasToupeiras();
    atualizarBotaoPause();

    const meta = META_PONTOS[configuracao.dificuldade];
    const venceu = motivo === 'tempo' && estado.pontuacao >= meta;
    const recorde = salvarPontuacao();

    const estatisticas = [
        ['PONTUAÇÃO FINAL', estado.pontuacao],
        ['META', meta],
        ['ACERTOS', estado.acertos],
        ['ERROS (VAZIO/EXPLOSÕES)', estado.erros],
        ['MAIOR COMBO', 'x' + estado.melhorCombo],
        ['RECORDE', recorde.valor]
    ];

    let subtitulo = motivo === 'explosao' ? 'EXPLOSÃO FATAL!' : 'FIM DO TEMPO!';
    if (recorde.novo) subtitulo += ' NOVO RECORDE!';

    abrirModal({
        titulo: venceu ? 'VITÓRIA!' : 'DERROTA!',
        subtitulo: subtitulo,
        estatisticas: estatisticas,
        podeContinuar: false,
        classe: venceu ? 'vitoria' : 'derrota'
    });
}

function salvarPontuacao() {
    let recorde = 0;
    let novo = false;
    try {
        recorde = parseInt(localStorage.getItem('recordePontuacao') || '0', 10) || 0;
        localStorage.setItem('ultimaPontuacao', String(estado.pontuacao));
        if (estado.pontuacao > recorde) {
            novo = true;
            recorde = estado.pontuacao;
            localStorage.setItem('recordePontuacao', String(recorde));
        }
    } catch (e) { /* localStorage indisponível: só não salva */ }
    return { valor: recorde, novo: novo };
}

// ---------------------------------------------------------
// 7. MODAL (PAUSA / FIM DE JOGO)
// ---------------------------------------------------------
const modal = document.getElementById('modalFimPause');
const modalCard = document.getElementById('modalCard');
const modalTitulo = document.getElementById('modalTitulo');
const modalSubtitulo = document.getElementById('modalSubtitulo');
const modalStats = document.getElementById('modalStats');
const btnContinuar = document.getElementById('btnContinuar');
const btnReiniciar = document.getElementById('btnReiniciar');

function abrirModal(dados) {
    modalCard.className = 'modal-card' + (dados.classe ? ' ' + dados.classe : '');
    modalTitulo.textContent = dados.titulo;
    modalSubtitulo.textContent = dados.subtitulo;

    modalStats.innerHTML = '';
    dados.estatisticas.forEach(function (par) {
        const linha = document.createElement('div');
        const rotulo = document.createElement('dt');
        const valor = document.createElement('dd');
        rotulo.textContent = par[0];
        valor.textContent = par[1];
        linha.append(rotulo, valor);
        modalStats.appendChild(linha);
    });

    btnContinuar.hidden = !dados.podeContinuar;
    modal.hidden = false;
    (dados.podeContinuar ? btnContinuar : btnReiniciar).focus();
}

function fecharModal() {
    modal.hidden = true;
}

// ---------------------------------------------------------
// 8. EVENTOS
// ---------------------------------------------------------
tabuleiro.addEventListener('pointerdown', function (evento) {
    if (evento.button !== 0 || estado.fase !== 'rodando') return;
    const buraco = evento.target.closest('.hole');
    if (buraco) processarCliqueBuraco(buraco);
});

// Teclado (Tab + Enter/Espaço): o clique "sem ponteiro" tem detail === 0
tabuleiro.addEventListener('click', function (evento) {
    if (evento.detail !== 0 || estado.fase !== 'rodando') return;
    const buraco = evento.target.closest('.hole');
    if (buraco) processarCliqueBuraco(buraco);
});

btnPause.addEventListener('click', alternarPausa);

btnContinuar.addEventListener('click', function () {
    retomarPartida();
    btnPause.focus();
});

btnReiniciar.addEventListener('click', iniciarPartida);

document.addEventListener('keydown', function (evento) {
    if (evento.key === 'p' || evento.key === 'P' || evento.key === 'Escape') {
        alternarPausa();
    }
});

// Trocou de aba ou minimizou: pausa para não perder a partida
document.addEventListener('visibilitychange', function () {
    if (document.hidden) pausarPartida();
});

// Saiu no meio da partida: guarda o que já foi feito
window.addEventListener('pagehide', function () {
    if (estado.fase !== 'encerrado' && estado.pontuacao > 0) salvarPontuacao();
});

// O modal começa logo abaixo do header, assim o botão pause nunca fica coberto
function ajustarAlturaHeader() {
    const header = document.querySelector('.header');
    document.documentElement.style.setProperty('--altura-header', header.offsetHeight + 'px');
}

if (typeof ResizeObserver === 'function') {
    new ResizeObserver(ajustarTamanhoBuracos).observe(tabuleiro);
    new ResizeObserver(ajustarAlturaHeader).observe(document.querySelector('.header'));
} else {
    window.addEventListener('resize', ajustarTamanhoBuracos);
    window.addEventListener('resize', ajustarAlturaHeader);
}

// ---------------------------------------------------------
// INÍCIO
// ---------------------------------------------------------
document.getElementById('modoValue').textContent = ROTULO_MODO[configuracao.modo];
document.getElementById('nivelValue').textContent = ROTULO_NIVEL[configuracao.dificuldade];
document.getElementById('metaValue').textContent = META_PONTOS[configuracao.dificuldade];

ajustarAlturaHeader();
desenharTabuleiro();
iniciarPartida();
