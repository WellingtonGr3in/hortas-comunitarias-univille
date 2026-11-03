import api from './api'

// tipo: 'plataforma' (associação paga à plataforma) ou 'associacao' (canteirista paga à associação)
const ENDPOINT = {
  plataforma: '/mensalidades-da-plataforma',
  associacao: '/mensalidades-da-associacao'
}

export default {
  getAll(tipo, params = {}) {
    return api.get(ENDPOINT[tipo], { params })
  },

  getById(tipo, id) {
    return api.get(`${ENDPOINT[tipo]}/${id}`)
  },

  // Só o admin da plataforma gera mensalidades por esta tela (mensalidades da plataforma)
  create(data) {
    return api.post(ENDPOINT.plataforma, data)
  },

  update(id, data) {
    return api.put(`${ENDPOINT.plataforma}/${id}`, data)
  },

  delete(id) {
    return api.delete(`${ENDPOINT.plataforma}/${id}`)
  }
}
