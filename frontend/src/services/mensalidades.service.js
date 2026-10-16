import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/mensalidades-da-plataforma', { params })
  },

  getById(id) {
    return api.get(`/mensalidades-da-plataforma/${id}`)
  },

  create(data) {
    return api.post('/mensalidades-da-plataforma', data)
  },

  update(id, data) {
    return api.put(`/mensalidades-da-plataforma/${id}`, data)
  },

  delete(id) {
    return api.delete(`/mensalidades-da-plataforma/${id}`)
  }
}
