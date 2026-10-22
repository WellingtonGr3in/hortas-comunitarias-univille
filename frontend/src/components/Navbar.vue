<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container-fluid">
      <router-link to="/" class="navbar-brand">
        🌱 Hortas Comunitárias
      </router-link>
      
      <button 
        class="navbar-toggler" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#navbarNav"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li v-for="item in itensDoMenu" :key="item.to" class="nav-item">
            <router-link :to="item.to" class="nav-link">{{ item.label }}</router-link>
          </li>
        </ul>
        
        <div v-if="isAuthenticated" class="d-flex align-items-center">
          <router-link to="/perfil" class="text-white me-3 text-decoration-none" title="Meu perfil">
            Olá, {{ currentUser?.nome || 'Usuário' }}
          </router-link>
          <button @click="handleLogout" class="btn btn-outline-light btn-sm">
            Sair
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { podeAcessar } from '@/utils/permissoes'

// Itens do menu. Cada perfil vê só as páginas que o router libera para ele (meta.roles).
const MENU = [
  { to: '/', label: 'Início' },
  { to: '/associacoes', label: 'Associações' },
  { to: '/hortas', label: 'Hortas' },
  { to: '/canteiros', label: 'Canteiros', labelCanteirista: 'Meus canteiros' },
  { to: '/canteiristas', label: 'Canteiristas' },
  { to: '/usuarios', label: 'Usuários' },
  { to: '/pagamentos', label: 'Pagamentos', labelCanteirista: 'Meus pagamentos' },
  { to: '/planos', label: 'Planos' },
  { to: '/mensalidades', label: 'Mensalidades' },
  { to: '/mensalidades-associacao', label: 'Mensalidades da associação' },
  { to: '/dependentes', label: 'Dependentes' },
  { to: '/inventario', label: 'Inventário' },
  { to: '/notificacoes', label: 'Notificações', labelCanteirista: 'Avisos' },
  { to: '/perfil', label: 'Perfil' }
]

export default {
  name: 'Navbar',
  setup() {
    const store = useStore()
    const router = useRouter()
    
    const isAuthenticated = computed(() => store.getters['auth/isAuthenticated'])
    const currentUser = computed(() => store.getters['auth/currentUser'])
    const cargoSlug = computed(() => store.getters['auth/cargoSlug'])
    const itensDoMenu = computed(() => {
      const canteirista = cargoSlug.value === 'canteirista'
      return MENU
        .filter(item => podeAcessar(router.resolve(item.to), cargoSlug.value))
        .map(item => ({ to: item.to, label: (canteirista && item.labelCanteirista) || item.label }))
    })

    const handleLogout = () => {
      store.dispatch('auth/logout')
      router.push('/login')
    }
    
    return {
      isAuthenticated,
      currentUser,
      itensDoMenu,
      handleLogout
    }
  }
}
</script>
