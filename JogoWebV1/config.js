// =========================================================
// CONFIGURAÇÃO COMPARTILHADA (selecao.html e jogo.html)
// Modo e dificuldade viajam pela URL (?modo=...&dificuldade=...)
// e também ficam salvos no localStorage como reserva.
// =========================================================
const CHAVE_CONFIG = 'configuracaoJogo';
const MODOS = ['normal', 'explosivo'];
const DIFICULDADES = ['facil', 'medio', 'dificil'];
const PADRAO = { modo: 'normal', dificuldade: 'facil' };

function configuracaoValida(config) {
    return {
        modo: MODOS.includes(config.modo) ? config.modo : PADRAO.modo,
        dificuldade: DIFICULDADES.includes(config.dificuldade) ? config.dificuldade : PADRAO.dificuldade
    };
}

// Prioridade: URL > localStorage > padrão
function lerConfiguracao() {
    let salvo = {};
    try {
        salvo = JSON.parse(localStorage.getItem(CHAVE_CONFIG)) || {};
    } catch (e) {
        salvo = {};
    }

    const params = new URLSearchParams(window.location.search);
    const daUrl = {};
    if (params.get('modo')) daUrl.modo = params.get('modo');
    if (params.get('dificuldade')) daUrl.dificuldade = params.get('dificuldade');

    return configuracaoValida({ ...PADRAO, ...salvo, ...daUrl });
}

function salvarConfiguracao(config) {
    try {
        localStorage.setItem(CHAVE_CONFIG, JSON.stringify(config));
    } catch (e) {
        console.warn('Não foi possível salvar a configuração:', e);
    }
}

function montarUrlJogo(config) {
    return 'jogo.html?' + new URLSearchParams(config).toString();
}
