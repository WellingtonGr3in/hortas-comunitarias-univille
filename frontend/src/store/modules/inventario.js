import inventarioService from '@/services/inventario.service'

export const TIPOS_DE_ITEM = { insumo: 'Insumo', ferramenta: 'Ferramenta' }
export const TIPOS_DE_MOVIMENTACAO = { entrada: 'Entrada', saida: 'Saída', ajuste: 'Ajuste' }

// Quantidades vêm como número com até 3 casas (ex.: 12.5 kg); exibe no formato brasileiro.
export const formatarQuantidade = q =>
  Number(q || 0).toLocaleString('pt-BR', { maximumFractionDigits: 3 })

export const estoqueBaixo = item =>
  item.quantidade_minima !== null && item.quantidade_minima !== undefined &&
  Number(item.quantidade) <= Number(item.quantidade_minima)

// Erros 422 de validação trazem os campos em `details`; os demais, só `error`.
export const mensagemDeErro = (err, padrao) => {
  const data = err.response?.data
  if (data?.details) return Object.values(data.details).join(' ')
  return data?.error || padrao
}

const state = {
  itens: [],
  loading: false,
  error: null
}

const getters = {
  allItens: state => state.itens,
  isLoading: state => state.loading
}

const mutations = {
  SET_ITENS(state, list) {
    state.itens = list
  },
  UPSERT_ITEM(state, item) {
    const i = state.itens.findIndex(x => x.uuid === item.uuid)
    if (i === -1) state.itens.push(item)
    else state.itens.splice(i, 1, item)
  },
  REMOVE_ITEM(state, uuid) {
    state.itens = state.itens.filter(x => x.uuid !== uuid)
  },
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  SET_ERROR(state, error) {
    state.error = error
  }
}

const actions = {
  async fetchItens({ commit }) {
    commit('SET_LOADING', true)
    try {
      const res = await inventarioService.getItens()
      commit('SET_ITENS', res.data || [])
      commit('SET_ERROR', null)
    } catch (err) {
      commit('SET_ERROR', mensagemDeErro(err, 'Erro ao carregar o inventário'))
    } finally {
      commit('SET_LOADING', false)
    }
  },
  async createItem({ commit }, data) {
    try {
      const res = await inventarioService.createItem(data)
      commit('UPSERT_ITEM', res.data)
      return { success: true, data: res.data }
    } catch (err) {
      return { success: false, message: mensagemDeErro(err, 'Erro ao cadastrar o item') }
    }
  },
  async updateItem({ commit }, { id, data }) {
    try {
      const res = await inventarioService.updateItem(id, data)
      commit('UPSERT_ITEM', res.data)
      return { success: true, data: res.data }
    } catch (err) {
      return { success: false, message: mensagemDeErro(err, 'Erro ao salvar o item') }
    }
  },
  async deleteItem({ commit }, id) {
    try {
      await inventarioService.deleteItem(id)
      commit('REMOVE_ITEM', id)
      return { success: true }
    } catch (err) {
      return { success: false, message: mensagemDeErro(err, 'Erro ao excluir o item') }
    }
  },
  // Depois de movimentar, recarrega o item para a lista mostrar o saldo novo.
  async registrarMovimentacao({ commit }, data) {
    try {
      const res = await inventarioService.createMovimentacao(data)
      const item = await inventarioService.getItem(data.item_uuid)
      commit('UPSERT_ITEM', item.data)
      return { success: true, data: res.data }
    } catch (err) {
      return { success: false, message: mensagemDeErro(err, 'Erro ao registrar a movimentação') }
    }
  }
}

export default { namespaced: true, state, getters, mutations, actions }
