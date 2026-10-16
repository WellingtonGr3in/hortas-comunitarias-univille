import api from './api'

export default {
  getItens() {
    return api.get('/itens-do-inventario')
  },

  getItem(id) {
    return api.get(`/itens-do-inventario/${id}`)
  },

  createItem(data) {
    return api.post('/itens-do-inventario', data)
  },

  updateItem(id, data) {
    return api.put(`/itens-do-inventario/${id}`, data)
  },

  deleteItem(id) {
    return api.delete(`/itens-do-inventario/${id}`)
  },

  getMovimentacoesDoItem(itemId) {
    return api.get(`/movimentacoes-do-inventario/item/${itemId}`)
  },

  createMovimentacao(data) {
    return api.post('/movimentacoes-do-inventario', data)
  }
}
