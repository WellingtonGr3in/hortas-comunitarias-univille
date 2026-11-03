import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/usuarios', { params })
  },
  getById(id) {
    return api.get(`/usuarios/${id}`)
  },
  create(data) {
    return api.post('/usuarios', data)
  },
  update(id, data) {
    return api.put(`/usuarios/${id}`, data)
  },
  delete(id) {
    return api.delete(`/usuarios/${id}`)
  }
}
