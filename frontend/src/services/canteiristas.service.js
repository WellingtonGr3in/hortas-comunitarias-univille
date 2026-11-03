import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/canteiristas', { params })
  },

  getById(id) {
    return api.get(`/canteiristas/${id}`)
  },

  create(data) {
    return api.post('/canteiristas', data)
  },

  update(id, data) {
    return api.put(`/canteiristas/${id}`, data)
  },

  delete(id) {
    return api.delete(`/canteiristas/${id}`)
  }
}
