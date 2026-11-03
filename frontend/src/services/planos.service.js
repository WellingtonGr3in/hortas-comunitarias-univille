import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/planos', { params })
  },

  getById(id) {
    return api.get(`/planos/${id}`)
  },

  create(data) {
    return api.post('/planos', data)
  },

  update(id, data) {
    return api.put(`/planos/${id}`, data)
  },

  delete(id) {
    return api.delete(`/planos/${id}`)
  }
}
