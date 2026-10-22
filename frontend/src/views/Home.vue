<template>
  <div class="container mt-5">
    <div class="row">
      <div class="col-12">
        <h1 class="display-4 text-center mb-4">
          🌱 Sistema de Gestão de Hortas Comunitárias
        </h1>
        <p class="lead text-center text-muted mb-5">
          Gerencie associações, hortas, canteiros e canteiristas
        </p>
      </div>
    </div>
    
    <div class="row g-4">
      <div v-for="card in cards" :key="card.to" class="col-md-6 col-lg-3">
        <div class="card text-center shadow-sm h-100">
          <div class="card-body">
            <div class="fs-1 text-success mb-3">{{ card.icone }}</div>
            <h5 class="card-title">{{ card.titulo }}</h5>
            <p class="card-text text-muted">{{ card.texto }}</p>
            <router-link :to="card.to" class="btn btn-success">Acessar</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { podeAcessar } from '@/utils/permissoes'

const CARDS = [
  { to: '/associacoes', icone: '🏢', titulo: 'Associações', texto: 'Gerencie as associações' },
  { to: '/hortas', icone: '🌳', titulo: 'Hortas', texto: 'Cadastre hortas' },
  { to: '/canteiros', icone: '📦', titulo: 'Canteiros', titulo_canteirista: 'Meus canteiros', texto: 'Organize canteiros', texto_canteirista: 'Veja seus canteiros' },
  { to: '/canteiristas', icone: '👥', titulo: 'Canteiristas', texto: 'Gerencie responsáveis' },
  { to: '/pagamentos', icone: '💳', titulo: 'Pagamentos', titulo_canteirista: 'Meus pagamentos', texto: 'Acompanhe pagamentos e mensalidades', texto_canteirista: 'Confira seus pagamentos' },
  { to: '/dependentes', icone: '🧑‍🌾', titulo: 'Dependentes', texto: 'Gerencie dependentes dos canteiristas', texto_canteirista: 'Veja seus dependentes' },
  { to: '/notificacoes', icone: '🔔', titulo: 'Notificações', titulo_canteirista: 'Avisos', texto: 'Acompanhe avisos e alertas' }
]

export default {
  name: 'Home',
  computed: {
    // Cada perfil vê só os atalhos das páginas que pode abrir
    cards() {
      const cargo = this.$store.getters['auth/cargoSlug']
      const canteirista = cargo === 'canteirista'
      return CARDS
        .filter(card => podeAcessar(this.$router.resolve(card.to), cargo))
        .map(card => ({
          ...card,
          titulo: (canteirista && card.titulo_canteirista) || card.titulo,
          texto: (canteirista && card.texto_canteirista) || card.texto
        }))
    }
  }
}
</script>
