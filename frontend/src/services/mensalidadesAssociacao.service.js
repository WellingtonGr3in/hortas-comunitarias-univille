import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/mensalidades-da-associacao', { params })
  },

  getById(id) {
    return api.get(`/mensalidades-da-associacao/${id}`)
  },

  create(data) {
    return api.post('/mensalidades-da-associacao', data)
  },

  update(id, data) {
    return api.put(`/mensalidades-da-associacao/${id}`, data)
  },

  delete(id) {
    return api.delete(`/mensalidades-da-associacao/${id}`)
  }
}
