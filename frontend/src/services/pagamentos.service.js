import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/pagamentos', { params })
  },

  getById(id) {
    return api.get(`/pagamentos/${id}`)
  },

  create(data) {
    return api.post('/pagamentos', data)
  },

  // Registra o pagamento de uma mensalidade e a quita (mensalidade_tipo: associacao | plataforma)
  registrarDeMensalidade(data) {
    return api.post('/pagamentos/mensalidade', data)
  },

  update(id, data) {
    return api.put(`/pagamentos/${id}`, data)
  },

  delete(id) {
    return api.delete(`/pagamentos/${id}`)
  }
}
