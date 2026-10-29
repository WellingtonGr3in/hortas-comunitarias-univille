import mensalidadesService from '@/services/mensalidades.service'
import pagamentosService from '@/services/pagamentos.service'

// Códigos de status das mensalidades da plataforma e da associação (docs/db/README.md)
export const STATUS = {
  AGUARDANDO: 0,
  PAGO: 1,
  COMPENSADO: 2,
  CANCELADO: 3,
  EM_ATRASO: 4
}

// Data de hoje no fuso local, no formato YYYY-MM-DD que o backend espera
export const hojeISO = () => {
  const d = new Date()
  const pad = n => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

// Situação exibida na tela: paga, pendente, atrasada ou cancelada.
// Uma mensalidade aguardando pagamento com vencimento já passado conta como atrasada.
export const situacao = m => {
  const status = Number(m.status)
  if (status === STATUS.PAGO || status === STATUS.COMPENSADO) return 'paga'
  if (status === STATUS.CANCELADO) return 'cancelada'
  if (status === STATUS.EM_ATRASO) return 'atrasada'
  return m.data_vencimento && m.data_vencimento < hojeISO() ? 'atrasada' : 'pendente'
}

// Qual tabela de mensalidades o cargo gerencia: o admin da plataforma cobra as associações
// (mensalidades da plataforma); o admin da associação cobra os canteiristas (mensalidades da associação)
export const tipoDoCargo = cargoSlug => (cargoSlug === 'admin_plataforma' ? 'plataforma' : 'associacao')

const state = {
  mensalidades: [],
  tipo: null,
  loading: false,
  error: null
}

const getters = {
  allMensalidades: state => state.mensalidades,
  tipo: state => state.tipo,
  isLoading: state => state.loading
}

const mutations = {
  SET_MENSALIDADES(state, { list, tipo }) {
    state.mensalidades = list
    state.tipo = tipo
  },
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  SET_ERROR(state, error) {
    state.error = error
  }
}

const actions = {
  async fetchMensalidades({ commit, rootState }) {
    const tipo = tipoDoCargo(rootState.auth.user?.cargo_slug)
    commit('SET_LOADING', true)
    try {
      const res = await mensalidadesService.getAll(tipo)
      const list = res.data?.data || res.data || []
      commit('SET_MENSALIDADES', { list, tipo })
      commit('SET_ERROR', null)
    } catch (err) {
      commit('SET_ERROR', err.response?.data?.error || err.message)
    } finally {
      commit('SET_LOADING', false)
    }
  },
  // Lança o pagamento e quita a mensalidade no backend (mesma transação), depois recarrega a lista
  async registrarPagamento({ dispatch, state }, { mensalidade, valor, forma_pagamento, data_pagamento, observacao }) {
    try {
      const res = await pagamentosService.registrarDeMensalidade({
        mensalidade_tipo: state.tipo,
        mensalidade_uuid: mensalidade.uuid,
        valor,
        forma_pagamento,
        data_pagamento,
        observacao: observacao || null
      })
      await dispatch('fetchMensalidades')
      return { success: true, data: res.data?.data }
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.error || 'Erro ao registrar pagamento'
      }
    }
  }
}

export default { namespaced: true, state, getters, mutations, actions }
