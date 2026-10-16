import { createRouter, createWebHistory } from 'vue-router'
import { jwtDecode } from 'jwt-decode'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/Home.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/associacoes',
    name: 'AssociacoesList',
    component: () => import('@/views/Associacoes/List.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/hortas',
    name: 'HortasList',
    component: () => import('@/views/Hortas/List.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/canteiros',
    name: 'CanteirosList',
    component: () => import('@/views/Canteiros/Admin.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/associacoes/criar',
    name: 'AssociacoesCreate',
    component: () => import('@/views/Associacoes/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/hortas/criar',
    name: 'HortasCreate',
    component: () => import('@/views/Hortas/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/canteiros/criar',
    name: 'CanteirosCreate',
    component: () => import('@/views/Canteiros/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/associacoes/:id/editar',
    name: 'AssociacoesEdit',
    component: () => import('@/views/Associacoes/Edit.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/hortas/:id/editar',
    name: 'HortasEdit',
    component: () => import('@/views/Hortas/Edit.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/canteiristas',
    name: 'CanteiristasList',
    component: () => import('@/views/Canteiristas/List.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/canteiristas/criar',
    name: 'CanteiristasCreate',
    component: () => import('@/views/Canteiristas/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/canteiristas/:id/editar',
    name: 'CanteiristasEdit',
    component: () => import('@/views/Canteiristas/Edit.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/pagamentos',
    name: 'PagamentosList',
    component: () => import('@/views/Pagamentos/List.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/pagamentos/criar',
    name: 'PagamentosCreate',
    component: () => import('@/views/Pagamentos/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/pagamentos/:id/editar',
    name: 'PagamentosEdit',
    component: () => import('@/views/Pagamentos/Edit.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/mensalidades',
    name: 'MensalidadesList',
    component: () => import('@/views/Mensalidades/List.vue'),
    meta: { requiresAuth: true, roles: ['admin_plataforma', 'admin_associacao_geral'] }
  },
  {
    path: '/mensalidades-associacao',
    name: 'MensalidadesAssociacaoList',
    component: () => import('@/views/MensalidadesAssociacao/List.vue'),
    meta: { requiresAuth: true, roles: ['admin_plataforma', 'admin_associacao_geral'] }
  },
  {
    path: '/inventario',
    name: 'InventarioList',
    component: () => import('@/views/Inventario/List.vue'),
    meta: { requiresAuth: true, roles: ['admin_plataforma', 'admin_associacao_geral', 'admin_horta_geral'] }
  },
  {
    path: '/inventario/:id',
    name: 'InventarioDetalhe',
    component: () => import('@/views/Inventario/Detalhe.vue'),
    meta: { requiresAuth: true, roles: ['admin_plataforma', 'admin_associacao_geral', 'admin_horta_geral'] }
  },
  {
    path: '/planos',
    name: 'PlanosList',
    component: () => import('@/views/Planos/List.vue'),
    meta: { requiresAuth: true, roles: ['admin_plataforma'] }
  },
  {
    path: '/dependentes',
    name: 'DependentesList',
    component: () => import('@/views/Dependentes/List.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/dependentes/criar',
    name: 'DependentesCreate',
    component: () => import('@/views/Dependentes/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/dependentes/:id/editar',
    name: 'DependentesEdit',
    component: () => import('@/views/Dependentes/Edit.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/notificacoes',
    name: 'NotificacoesList',
    component: () => import('@/views/Notificacoes/List.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/notificacoes/criar',
    name: 'NotificacoesCreate',
    component: () => import('@/views/Notificacoes/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/notificacoes/:id/editar',
    name: 'NotificacoesEdit',
    component: () => import('@/views/Notificacoes/Edit.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/canteiros/:id/editar',
    name: 'CanteirosEdit',
    component: () => import('@/views/Canteiros/Edit.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/usuarios',
    name: 'UsuariosList',
    component: () => import('@/views/Usuarios/List.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  let authenticated = false
  let cargoSlug = null
  try {
    const token = localStorage.getItem('token')
    const decoded = token ? jwtDecode(token) : null
    authenticated = !!decoded && decoded.exp * 1000 > Date.now()
    cargoSlug = decoded?.cargo_slug || null
  } catch { /* Token inválido: voltar ao login. */ }
  if (!authenticated) {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }
  if (to.meta.requiresAuth && !authenticated) return next('/login')
  if (to.meta.guest && authenticated) return next('/')
  // Rotas restritas por cargo (cargo_slug do JWT)
  if (to.meta.roles && !to.meta.roles.includes(cargoSlug)) return next('/')
  next()
})

export default router
