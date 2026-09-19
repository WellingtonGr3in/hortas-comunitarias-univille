<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">Gestão de Canteiros</h2>
        <p class="text-muted mb-0">Controle geral dos canteiros comunitários</p>
      </div>

      <router-link to="/canteiros/criar" class="btn btn-success">
        + Novo Canteiro
      </router-link>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card p-3 h-100">
          <small class="text-muted">Total de Canteiros</small>
          <div class="h4 text-primary mt-2">{{ resumo.total }}</div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <small class="text-muted">Ocupados</small>
          <div class="h4 text-warning mt-2">{{ resumo.ocupados }}</div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <small class="text-muted">Disponíveis</small>
          <div class="h4 text-success mt-2">{{ resumo.disponiveis }}</div>
        </div>
      </div>
    </div>

    <div class="card mb-4 p-3">
      <div class="row g-3 align-items-end">
        <div class="col-md-5">
          <label class="form-label">Busca avançada</label>
          <input
            v-model="q"
            class="form-control"
            placeholder="Número, canteirista, CPF ou horta..."
          />
        </div>

        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select v-model="statusFiltro" class="form-select">
            <option value="">Todos</option>
            <option value="Ocupado">Ocupado</option>
            <option value="Disponível">Disponível</option>
            <option value="Em Preparo">Em Preparo</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Horta</label>
          <select v-model="hortaFiltro" class="form-select">
            <option value="">Todas</option>
            <option v-for="h in hortas" :key="h.uuid" :value="h.uuid">
              {{ h.nome_da_horta || h.nome }}
            </option>
          </select>
        </div>

        <div class="col-md-1">
          <button class="btn btn-outline-secondary w-100" @click="limparFiltros">
            Limpar
          </button>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger" role="alert">{{ errorMessage }}</div>
    <p v-if="loading">Carregando canteiros...</p>
    <div v-else class="card">
      <div v-if="canteirosFiltrados.length === 0" class="card-body text-center text-muted">
        Nenhum canteiro encontrado.
      </div>

      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Número</th>
              <th>Tamanho</th>
              <th>Localização</th>
              <th>Canteirista</th>
              <th>CPF</th>
              <th>Horta</th>
              <th>Plantio</th>
              <th>Última Colheita</th>
              <th>Status</th>
              <th class="text-end">Ações</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="c in canteirosFiltrados" :key="c.id">
              <td class="fw-bold">{{ c.numero_identificador }}</td>
              <td>{{ c.tamanho_m2 }}m²</td>
              <td>{{ c.localizacao || '—' }}</td>
              <td>{{ c.usuario_responsavel || '—' }}</td>
              <td>{{ c.usuario_responsavel_cpf || '—' }}</td>
              <td>{{ c.horta_nome || '—' }}</td>
              <td>{{ c.plantio_atual || '—' }}</td>
              <td>{{ formatarData(c.data_ultima_colheita) }}</td>
              <td>
                <span class="badge" :class="classeStatus(c.status)">
                  {{ c.status }}
                </span>
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-info me-1" @click="abrirDetalhes(c)">
                  Visualizar
                </button>

                <router-link :to="`/canteiros/${c.id}/editar`" class="btn btn-sm btn-primary me-1">
                  Editar
                </router-link>

                <button class="btn btn-sm btn-danger" @click="excluir(c)">
                  Excluir
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="detalhe" class="modal-backdrop-custom">
      <div class="modal-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Detalhes do Canteiro</h5>
          <button class="btn-close" @click="detalhe = null"></button>
        </div>

        <p><strong>Número:</strong> {{ detalhe.numero_identificador }}</p>
        <p><strong>Tamanho:</strong> {{ detalhe.tamanho_m2 }}m²</p>
        <p><strong>Localização:</strong> {{ detalhe.localizacao || '—' }}</p>
        <p><strong>Horta:</strong> {{ detalhe.horta_nome || '—' }}</p>
        <p><strong>Canteirista:</strong> {{ detalhe.usuario_responsavel || '—' }}</p>
        <p><strong>CPF:</strong> {{ detalhe.usuario_responsavel_cpf || '—' }}</p>
        <p><strong>Plantio Atual:</strong> {{ detalhe.plantio_atual || '—' }}</p>
        <p><strong>Última Colheita:</strong> {{ formatarData(detalhe.data_ultima_colheita) }}</p>
        <p><strong>Status:</strong> {{ detalhe.status }}</p>


      </div>
    </div>
  </div>
</template>

<script>
import api from '@/services/api'
import canteirosService from '@/services/canteiros.service'

export default {
  name: 'CanteirosAdminView',
  data() {
    return {
      q: '',
      statusFiltro: '',
      hortaFiltro: '',
      detalhe: null,
      hortas: [],
      errorMessage: '',
      loading: true,
      canteiros: []
    }
  },
  computed: {
    canteirosFiltrados() {
      const termo = this.q.toLowerCase().trim()

      return this.canteiros.filter(c => {
        const texto = [
          c.numero_identificador,
          c.usuario_responsavel,
          c.usuario_responsavel_cpf,
          c.horta_nome,
          c.localizacao
        ].join(' ').toLowerCase()

        const passaBusca = !termo || texto.includes(termo)
        const passaStatus = !this.statusFiltro || c.status === this.statusFiltro
        const passaHorta = !this.hortaFiltro || c.horta_uuid === this.hortaFiltro

        return passaBusca && passaStatus && passaHorta
      })
    },
    resumo() {
      return {
        total: this.canteirosFiltrados.length,
        ocupados: this.canteirosFiltrados.filter(c => c.status === 'Ocupado').length,
        disponiveis: this.canteirosFiltrados.filter(c => c.status === 'Disponível').length
      }
    }
  },
  mounted() { this.carregar() },
  methods: {
    async carregar() {
      this.loading = true
      this.errorMessage = ''
      try {
        const [canteiros, hortas] = await Promise.all([api.get('/canteiros/search'), api.get('/hortas')])
        this.canteiros = canteiros.data
        this.hortas = hortas.data
      } catch (e) {
        this.errorMessage = e.response?.data?.error || 'Não foi possível carregar os canteiros.'
      } finally { this.loading = false }
    },
    limparFiltros() {
      this.q = ''
      this.statusFiltro = ''
      this.hortaFiltro = ''
    },
    abrirDetalhes(canteiro) {
      this.detalhe = canteiro
    },
    async excluir(canteiro) {
      if (canteiro.usuario_responsavel_uuid && canteiro.status === 'Ocupado') {
        alert('Não é possível excluir um canteiro com usuário ativo vinculado.')
        return
      }

      if (!confirm(`Excluir o canteiro ${canteiro.numero_identificador}?`)) return

      try {
        await canteirosService.delete(canteiro.id)
        await this.carregar()
      } catch (e) { this.errorMessage = e.response?.data?.error || 'Erro ao excluir canteiro.' }
    },
    classeStatus(status) {
      if (status === 'Ocupado') return 'bg-warning text-dark'
      if (status === 'Disponível') return 'bg-success'
      if (status === 'Em Preparo') return 'bg-info text-dark'
      return 'bg-secondary'
    },
    formatarData(data) {
      if (!data) return '—'
      return new Date(`${data}T00:00:00`).toLocaleDateString('pt-BR')
    }
  }
}
</script>

<style scoped>
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-card {
  background: white;
  width: min(640px, 95vw);
  max-height: 90vh;
  overflow: auto;
  border-radius: 12px;
  padding: 24px;
}
</style>
