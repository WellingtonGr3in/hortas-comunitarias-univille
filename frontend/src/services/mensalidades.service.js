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
  }
}
