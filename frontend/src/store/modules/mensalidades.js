import mensalidadesService from '@/services/mensalidades.service'

// Códigos de status das tabelas mensalidades_da_plataforma e mensalidades_da_associacao
// (docs/db/README.md e MensalidadeDaPlataformaService::STATUS_*)
export const STATUS = {
  AGUARDANDO: 0,
  PAGO: 1,
  COMPENSADO: 2,
  CANCELADO: 3,
  EM_ATRASO: 4
}

export const STATUS_LABEL = {
  [STATUS.AGUARDANDO]: 'Aguardando pagamento',
  [STATUS.PAGO]: 'Pago',
  [STATUS.COMPENSADO]: 'Compensado',
  [STATUS.CANCELADO]: 'Cancelado',
  [STATUS.EM_ATRASO]: 'Em atraso'
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

const state = {
  mensalidades: [],
  loading: false,
  error: null
}

const getters = {
  allMensalidades: state => state.mensalidades,
  isLoading: state => state.loading
}

const mutations = {
  SET_MENSALIDADES(state, list) {
    state.mensalidades = list
  },
  ADD_MENSALIDADE(state, mensalidade) {
    state.mensalidades.unshift(mensalidade)
  },
  UPDATE_MENSALIDADE(state, updated) {
    const i = state.mensalidades.findIndex(x => x.uuid === updated.uuid)
    if (i !== -1) state.mensalidades.splice(i, 1, updated)
  },
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  SET_ERROR(state, error) {
    state.error = error
  }
}

const actions = {
  async fetchMensalidades({ commit }, params = {}) {
    commit('SET_LOADING', true)
    try {
      const res = await mensalidadesService.getAll(params)
      commit('SET_MENSALIDADES', res.data?.data || res.data || [])
      commit('SET_ERROR', null)
    } catch (err) {
      commit('SET_ERROR', err.response?.data?.error || err.message)
    } finally {
      commit('SET_LOADING', false)
    }
  },
  async createMensalidade({ commit }, data) {
    try {
      const res = await mensalidadesService.create({
        ...data,
        status: STATUS.AGUARDANDO,
        dias_atraso: 0
      })
      commit('ADD_MENSALIDADE', res.data)
      return { success: true, data: res.data }
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.error || err.response?.data?.message || 'Erro ao criar mensalidade'
      }
    }
  },
  async marcarComoPaga({ commit }, uuid) {
    try {
      const res = await mensalidadesService.update(uuid, {
        data_pagamento: hojeISO(),
        status: STATUS.PAGO
      })
      commit('UPDATE_MENSALIDADE', res.data)
      return { success: true, data: res.data }
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.error || 'Erro ao marcar mensalidade como paga'
      }
    }
  }
}

export default { namespaced: true, state, getters, mutations, actions }
