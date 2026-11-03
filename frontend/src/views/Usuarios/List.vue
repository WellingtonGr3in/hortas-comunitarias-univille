<template>
  <div class="container-fluid px-4 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div>
        <h2 class="fw-bold mb-1">Gestão de Usuários</h2>
        <p class="text-muted mb-0">Consulte os usuários, seus vínculos e status de acesso.</p>
      </div>
      <button type="button" class="btn btn-outline-success" :disabled="isLoading" @click="carregarDados">
        <i class="fas fa-rotate-right me-2" aria-hidden="true"></i>Atualizar
      </button>
    </div>

    <div class="row g-3 mb-4" :aria-busy="isLoading">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <p class="text-muted small mb-1">Total de usuários</p>
            <h3 class="fw-bold text-success mb-0">{{ isLoading ? '—' : total }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <p class="text-muted small mb-2">Distribuição por cargo</p>
            <span v-if="isLoading" class="text-muted">—</span>
            <ul v-else-if="distribuicaoCargos.length" class="list-unstyled small mb-0">
              <li v-for="cargo in distribuicaoCargos" :key="cargo.uuid" class="d-flex justify-content-between gap-2">
                <span>{{ cargo.nome }}</span><strong>{{ cargo.quantidade }}</strong>
              </li>
            </ul>
            <span v-else class="text-muted small">Nenhum usuário cadastrado.</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <p class="text-muted small mb-2">Ativos vs. inativos</p>
            <span v-if="isLoading" class="text-muted">—</span>
            <div v-else class="d-flex flex-wrap gap-3">
              <span class="text-success"><strong>{{ totalAtivos }}</strong> ativos</span>
              <span class="text-secondary"><strong>{{ total - totalAtivos }}</strong> inativos</span>
            </div>
            <p class="text-muted small mt-2 mb-0">Inativos incluem bloqueados, suspensos e pendentes.</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">
      {{ error }}
      <button type="button" class="btn btn-sm btn-outline-danger ms-2" :disabled="isLoading" @click="carregarDados">
        Tentar novamente
      </button>
    </div>
    <output v-if="avisoReferencias" class="alert alert-warning">{{ avisoReferencias }}</output>

    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <div class="row align-items-center g-3">
          <div class="col-md-9">
            <FormInput
              id="usuarios-busca"
              v-model="busca"
              label="Buscar usuários"
              type="search"
              placeholder="Nome, e-mail ou CPF"
              :disabled="isLoading"
            />
          </div>
          <div class="col-md-3">
            <button
              type="button"
              class="btn btn-outline-secondary w-100"
              aria-controls="usuarios-filtros"
              :aria-expanded="mostrarFiltros"
              @click="mostrarFiltros = !mostrarFiltros"
            >
              <i class="fas fa-filter me-2" aria-hidden="true"></i>Filtros
            </button>
          </div>
        </div>
        <div v-if="mostrarFiltros" id="usuarios-filtros" class="row g-3 border-top pt-3">
          <div class="col-md-4">
            <label for="usuarios-cargo" class="form-label">Cargo</label>
            <select id="usuarios-cargo" v-model="filtros.cargo" class="form-select" :disabled="isLoading">
              <option value="">Todos os cargos</option>
              <option v-for="item in opcoesCargos" :key="item.uuid" :value="item.uuid">{{ item.nome }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="usuarios-associacao" class="form-label">Associação</label>
            <select id="usuarios-associacao" v-model="filtros.associacao" class="form-select" :disabled="isLoading">
              <option value="">Todas as associações</option>
              <option v-for="item in opcoesAssociacoes" :key="item.uuid" :value="item.uuid">{{ item.nome }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="usuarios-horta" class="form-label">Horta</label>
            <select id="usuarios-horta" v-model="filtros.horta" class="form-select" :disabled="isLoading">
              <option value="">Todas as hortas</option>
              <option v-for="item in opcoesHortas" :key="item.uuid" :value="item.uuid">{{ item.nome }}</option>
            </select>
          </div>
          <div class="col-12 text-end">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="limparFiltros">Limpar busca e filtros</button>
          </div>
        </div>
      </div>
    </div>

    <output v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-success" aria-hidden="true"></div>
      <p class="mt-2">Carregando usuários...</p>
    </output>
    <div v-else-if="!error" class="card border-0 shadow-sm">
      <div class="px-4 py-3 border-bottom">
        <h6 class="mb-0 fw-semibold">Lista de usuários — {{ listaFiltrada.length }} de {{ total }}</h6>
      </div>
      <output v-if="!listaFiltrada.length" class="text-center py-5 text-muted">
        <i class="fas fa-users fa-2x mb-3 d-block opacity-25" aria-hidden="true"></i>
        <p>{{ total ? 'Nenhum usuário corresponde à busca e aos filtros.' : 'Nenhum usuário cadastrado.' }}</p>
        <button v-if="total" type="button" class="btn btn-outline-secondary btn-sm" @click="limparFiltros">Limpar busca e filtros</button>
      </output>
      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <caption class="visually-hidden">Usuários cadastrados, seus vínculos e status de acesso</caption>
          <thead class="table-light">
            <tr>
              <th scope="col" class="px-4">Nome</th>
              <th scope="col">E-mail</th>
              <th scope="col">CPF</th>
              <th scope="col">Cargo</th>
              <th scope="col">Associação</th>
              <th scope="col">Horta</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="usuario in listaFiltrada" :key="usuario.uuid">
              <td class="px-4 fw-semibold">{{ usuario.nome_completo || usuario.apelido || '—' }}</td>
              <td>{{ usuario.email || '—' }}</td>
              <td class="text-nowrap">{{ formatarCpf(usuario.cpf) }}</td>
              <td>{{ nomeVinculo(usuario, 'cargo') }}</td>
              <td>{{ nomeVinculo(usuario, 'associacao') }}</td>
              <td>{{ nomeVinculo(usuario, 'horta') }}</td>
              <td>
                <span class="badge" :class="estaAtivo(usuario) ? 'bg-success' : 'bg-secondary'">{{ nomeStatus(usuario) }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'
import FormInput from '@/components/FormInput.vue'
import api from '@/services/api'
import associacoesService from '@/services/associacoes.service'
import hortasService from '@/services/hortas.service'
import { formatarCpf } from '@/utils/format'

export default {
  name: 'UsuariosList',
  components: { FormInput },
  setup() {
    const store = useStore()
    const busca = ref('')
    const mostrarFiltros = ref(false)
    const filtros = reactive({ cargo: '', associacao: '', horta: '' })
    const referencias = reactive({ cargo: [], associacao: [], horta: [] })
    const carregandoReferencias = ref(false)
    const avisoReferencias = ref('')
    const usuarios = computed(() => store.getters['usuarios/allUsuarios'])
    const isLoading = computed(() => store.getters['usuarios/isLoading'] || carregandoReferencias.value)
    const error = computed(() => store.state.usuarios.error)
    const total = computed(() => usuarios.value.length)
    const normalizar = value => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim()
    const status = usuario => normalizar(usuario.status_de_acesso)
    const estaAtivo = usuario => ['1', 'ativo', 'true'].includes(status(usuario))
    const totalAtivos = computed(() => usuarios.value.filter(estaAtivo).length)
    const semVinculo = '__sem_vinculo__'
    const uuidVinculo = (usuario, tipo) => usuario[`${tipo}_uuid`] || usuario[tipo]?.uuid || semVinculo
    const camposNome = { cargo: ['nome', 'slug'], associacao: ['nome_fantasia', 'razao_social'], horta: ['nome_da_horta'] }

    function nomeVinculo(usuario, tipo) {
      const uuid = uuidVinculo(usuario, tipo)
      if (uuid === semVinculo) return `Sem ${tipo === 'associacao' ? 'associação' : tipo}`
      const vinculo = usuario[tipo] || referencias[tipo].find(item => item.uuid === uuid)
      return camposNome[tipo].map(campo => vinculo?.[campo]).find(Boolean) || uuid
    }

    function opcoes(tipo) {
      const itens = new Map()
      usuarios.value.forEach(usuario => {
        const uuid = uuidVinculo(usuario, tipo)
        itens.set(uuid, { uuid, nome: nomeVinculo(usuario, tipo) })
      })
      return [...itens.values()].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'))
    }

    const opcoesCargos = computed(() => opcoes('cargo'))
    const opcoesAssociacoes = computed(() => opcoes('associacao'))
    const opcoesHortas = computed(() => opcoes('horta'))
    const distribuicaoCargos = computed(() => opcoesCargos.value.map(cargo => ({
      ...cargo,
      quantidade: usuarios.value.filter(usuario => uuidVinculo(usuario, 'cargo') === cargo.uuid).length
    })))

    const listaFiltrada = computed(() => {
      const termo = normalizar(busca.value)
      const cpfBusca = /^[\d.\-\s]+$/.test(termo) ? termo.replace(/\D/g, '') : ''
      return usuarios.value.filter(usuario => {
        const correspondeBusca = !termo || [usuario.nome_completo, usuario.apelido, usuario.email, usuario.cpf]
          .some(value => normalizar(value).includes(termo)) ||
          (cpfBusca && String(usuario.cpf ?? '').replace(/\D/g, '').includes(cpfBusca))
        return correspondeBusca && Object.entries(filtros)
          .every(([tipo, uuid]) => !uuid || uuidVinculo(usuario, tipo) === uuid)
      })
    })

    function limparFiltros() {
      busca.value = ''
      Object.assign(filtros, { cargo: '', associacao: '', horta: '' })
    }

    function nomeStatus(usuario) {
      if (estaAtivo(usuario)) return 'Ativo'
      const nomes = { '0': 'Bloqueado', '2': 'Suspenso', '3': 'Pendente de aprovação', inativo: 'Inativo', bloqueado: 'Bloqueado', suspenso: 'Suspenso', pendente: 'Pendente de aprovação', false: 'Inativo' }
      return nomes[status(usuario)] || 'Não informado'
    }

    async function carregarReferencias() {
      carregandoReferencias.value = true
      avisoReferencias.value = ''
      try {
        const tipos = ['cargo', 'associacao', 'horta']
        const resultados = await Promise.allSettled([
          api.get('/cargos'), associacoesService.getAll(), hortasService.getAll()
        ])
        resultados.forEach((resultado, index) => {
          referencias[tipos[index]] = resultado.status === 'fulfilled' && Array.isArray(resultado.value.data)
            ? resultado.value.data : []
        })
        if (resultados.some(resultado => resultado.status === 'rejected' || !Array.isArray(resultado.value.data))) {
          avisoReferencias.value = 'Alguns nomes de cargos, associações ou hortas não puderam ser carregados. Seus identificadores serão exibidos quando necessário.'
        }
      } finally {
        carregandoReferencias.value = false
      }
    }

    async function carregarDados() {
      await Promise.all([store.dispatch('usuarios/fetchUsuarios'), carregarReferencias()])
    }

    onMounted(carregarDados)

    return {
      busca, mostrarFiltros, filtros, isLoading, error, total, totalAtivos,
      distribuicaoCargos, opcoesCargos, opcoesAssociacoes, opcoesHortas,
      listaFiltrada, avisoReferencias, nomeVinculo, estaAtivo, nomeStatus,
      limparFiltros, carregarDados, formatarCpf
    }
  }
}
</script>
