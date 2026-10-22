import axios from 'axios'
import { avisarAcessoNegado } from '@/utils/permissoes'

const api = axios.create({
  baseURL: process.env.VUE_APP_API_URL || '/api/v1',
  timeout: 10000,
})

api.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => Promise.reject(error)
)

api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401 && !error.config?.url?.startsWith('/sessoes/')) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    if (error.response?.status === 403) {
      const data = error.response.data || {}
      avisarAcessoNegado(data.error && data.error !== 'Acesso negado' ? data.error : data.message)
    }
    return Promise.reject(error)
  }
)

export default api
