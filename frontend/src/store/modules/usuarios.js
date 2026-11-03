import usuariosService from '@/services/usuarios.service'

const state = {
  usuarios: [],
  currentUsuario: null,
  loading: false,
  error: null
}

const getters = {
  allUsuarios: state => state.usuarios,
  isLoading: state => state.loading
}

const mutations = {
  SET_USUARIOS(state, list) {
    state.usuarios = list
  },
  SET_CURRENT_USUARIO(state, usuario) {
    state.currentUsuario = usuario
  },
  ADD_USUARIO(state, usuario) {
    state.usuarios.push(usuario)
  },
  UPDATE_USUARIO(state, usuario) {
    const index = state.usuarios.findIndex(item => item.uuid === usuario.uuid)
    if (index !== -1) state.usuarios.splice(index, 1, usuario)
    if (state.currentUsuario?.uuid === usuario.uuid) state.currentUsuario = usuario
  },
  DELETE_USUARIO(state, uuid) {
    state.usuarios = state.usuarios.filter(usuario => usuario.uuid !== uuid)
    if (state.currentUsuario?.uuid === uuid) state.currentUsuario = null
  },
  SET_LOADING(state, loading) {
    state.loading = loading
  },
  SET_ERROR(state, error) {
    state.error = error
  }
}

function mensagemErro(error, fallback) {
  const body = error.response?.data
  const details = body?.details
  if (details && typeof details === 'object') {
    const message = Object.values(details).flat().filter(value => typeof value === 'string').join('; ')
    if (message) return message
  }
  return [body?.detail, body?.message, body?.error, error.message]
    .find(value => typeof value === 'string' && value.trim()) || fallback
}

async function executar({ commit }, operacao, sucesso, falha) {
  commit('SET_LOADING', true)
  commit('SET_ERROR', null)
  try {
    const data = await operacao()
    return { success: true, message: sucesso, data }
  } catch (error) {
    const message = mensagemErro(error, falha)
    commit('SET_ERROR', message)
    return { success: false, message }
  } finally {
    commit('SET_LOADING', false)
  }
}

const actions = {
  fetchUsuarios(context, params = {}) {
    return executar(context, async () => {
      const { data } = await usuariosService.getAll(params)
      context.commit('SET_USUARIOS', data)
      return data
    }, 'Usuários carregados.', 'Não foi possível carregar os usuários.')
  },
  fetchUsuario(context, uuid) {
    context.commit('SET_CURRENT_USUARIO', null)
    return executar(context, async () => {
      const { data } = await usuariosService.getById(uuid)
      context.commit('SET_CURRENT_USUARIO', data)
      return data
    }, 'Usuário carregado.', 'Não foi possível carregar o usuário.')
  },
  createUsuario(context, data) {
    return executar(context, async () => {
      const response = await usuariosService.create(data)
      context.commit('ADD_USUARIO', response.data)
      return response.data
    }, 'Usuário cadastrado.', 'Não foi possível cadastrar o usuário.')
  },
  updateUsuario(context, { uuid, data }) {
    return executar(context, async () => {
      const response = await usuariosService.update(uuid, data)
      context.commit('UPDATE_USUARIO', response.data)
      return response.data
    }, 'Usuário atualizado.', 'Não foi possível atualizar o usuário.')
  },
  deleteUsuario(context, uuid) {
    return executar(context, async () => {
      await usuariosService.delete(uuid)
      context.commit('DELETE_USUARIO', uuid)
    }, 'Usuário excluído.', 'Não foi possível excluir o usuário.')
  }
}

export default { namespaced: true, state, getters, mutations, actions }
