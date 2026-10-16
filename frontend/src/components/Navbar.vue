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
          <li class="nav-item">
            <router-link to="/" class="nav-link">Home</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/associacoes" class="nav-link">Associações</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/hortas" class="nav-link">Hortas</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/canteiros" class="nav-link">Canteiros</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/canteiristas" class="nav-link">Canteiristas</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/usuarios" class="nav-link">Usuários</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/pagamentos" class="nav-link">Pagamentos</router-link>
          </li>
          <li v-if="isAdminPlataforma" class="nav-item">
            <router-link to="/planos" class="nav-link">Planos</router-link>
          </li>
          <li v-if="isAdmin" class="nav-item">
            <router-link to="/mensalidades" class="nav-link">Mensalidades</router-link>
          </li>
          <li v-if="isAdmin" class="nav-item">
            <router-link to="/mensalidades-associacao" class="nav-link">Mensalidades da associação</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/dependentes" class="nav-link">Dependentes</router-link>
          </li>
          <li v-if="podeVerInventario" class="nav-item">
            <router-link to="/inventario" class="nav-link">Inventário</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/notificacoes" class="nav-link">Notificações</router-link>
          </li>
        </ul>
        
        <div v-if="isAuthenticated" class="d-flex align-items-center">
          <span class="text-white me-3">
            Olá, {{ currentUser?.nome || 'Usuário' }}
          </span>
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

export default {
  name: 'Navbar',
  setup() {
    const store = useStore()
    const router = useRouter()
    
    const isAuthenticated = computed(() => store.getters['auth/isAuthenticated'])
    const currentUser = computed(() => store.getters['auth/currentUser'])
    const cargoSlug = computed(() => store.getters['auth/cargoSlug'])
    const isAdminPlataforma = computed(() => cargoSlug.value === 'admin_plataforma')
    const isAdmin = computed(() => ['admin_plataforma', 'admin_associacao_geral'].includes(cargoSlug.value))
    // O inventário também é gerenciado pelo admin da horta (só a horta dele).
    const podeVerInventario = computed(() => isAdmin.value || cargoSlug.value === 'admin_horta_geral')

    const handleLogout = () => {
      store.dispatch('auth/logout')
      router.push('/login')
    }
    
    return {
      isAuthenticated,
      currentUser,
      isAdminPlataforma,
      isAdmin,
      podeVerInventario,
      handleLogout
    }
  }
}
</script>
