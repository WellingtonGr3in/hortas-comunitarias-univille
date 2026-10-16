import { createStore } from 'vuex'
import auth from './modules/auth'
import associacoes from './modules/associacoes'
import hortas from './modules/hortas'
import canteiros from './modules/canteiros'
import canteiristas from './modules/canteiristas'
import usuarios from './modules/usuarios'
import pagamentos from './modules/pagamentos'
import mensalidades from './modules/mensalidades'
import dependentes from './modules/dependentes'
import notificacoes from './modules/notificacoes'

export default createStore({
  modules: {
    auth,
    associacoes,
    hortas,
    canteiros,
    canteiristas,
    usuarios,
    pagamentos,
    mensalidades,
    dependentes,
    notificacoes
  }
})
