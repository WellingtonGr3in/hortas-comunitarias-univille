<template>
  <div class="mensalidades-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Mensalidades da plataforma</h2>
      <div class="d-flex align-items-center gap-2">
        <label for="filtro-status" class="form-label mb-0">Status</label>
        <select id="filtro-status" v-model="filtroStatus" class="form-select">
          <option value="">Todas</option>
          <option value="paga">Pagas</option>
          <option value="pendente">Pendentes</option>
          <option value="atrasada">Atrasadas</option>
        </select>
        <button v-if="isAdminPlataforma" class="btn btn-success text-nowrap" @click="abrirModal">
          Nova mensalidade
        </button>
      </div>
    </div>

    <div v-if="mensagemErro" class="alert alert-danger">{{ mensagemErro }}</div>

    <div v-if="isLoading" class="text-center my-4">
      <div class="spinner-border text-success" role="status"></div>
    </div>

    <div v-else-if="mensalidadesFiltradas.length === 0" class="text-muted text-center my-4">
      Nenhuma mensalidade encontrada.
    </div>

    <div v-else class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Usuário</th>
            <th>Plano</th>
            <th class="text-end">Valor</th>
            <th>Vencimento</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in mensalidadesFiltradas" :key="m.uuid">
            <td>{{ nomeUsuario(m.usuario_uuid) }}</td>
            <td>{{ nomePlano(m.plano_uuid) }}</td>
            <td class="text-end">{{ formatarValor(m.valor_em_centavos) }}</td>
            <td>{{ formatarData(m.data_vencimento) }}</td>
            <td>
              <span class="badge" :class="classeSituacao[situacao(m)]">
                {{ rotuloStatus(m) }}
              </span>
              <div v-if="m.data_pagamento" class="small text-muted">
                em {{ formatarData(m.data_pagamento) }}
              </div>
            </td>
            <td class="text-end">
              <button
                v-if="situacao(m) === 'pendente' || situacao(m) === 'atrasada'"
                class="btn btn-sm btn-outline-success"
                :disabled="salvando === m.uuid"
                @click="marcarComoPaga(m)"
              >
                {{ salvando === m.uuid ? 'Salvando...' : 'Marcar como paga' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="modalAberto" class="modal-backdrop-custom">
      <div class="modal-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Nova mensalidade</h5>
          <button class="btn-close" @click="modalAberto = false"></button>
        </div>

        <div v-if="erroForm" class="alert alert-danger">{{ erroForm }}</div>

        <form @submit.prevent="salvarNova">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="nova-usuario">Associação / usuário *</label>
              <select id="nova-usuario" v-model="form.usuario_uuid" class="form-select" required>
                <option value="" disabled>Selecione</option>
                <option v-for="u in usuariosCobraveis" :key="u.uuid" :value="u.uuid">
                  {{ rotuloUsuarioCobravel(u) }}
                </option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="nova-plano">Plano</label>
              <select id="nova-plano" v-model="form.plano_uuid" class="form-select" @change="aplicarValorDoPlano">
                <option value="">Sem plano</option>
                <option v-for="p in listaPlanos" :key="p.uuid" :value="p.uuid">
                  {{ p.nome }} ({{ formatarValor(p.valor_em_centavos) }})
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="nova-valor">Valor (R$) *</label>
              <input id="nova-valor" v-model.number="form.valor" type="number" min="0.01" step="0.01" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label" for="nova-vencimento">Vencimento *</label>
              <input id="nova-vencimento" v-model="form.data_vencimento" type="date" class="form-control" required />
            </div>
          </div>
          <p class="small text-muted mt-3 mb-0">A mensalidade é criada em aberto (aguardando pagamento).</p>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-outline-secondary" @click="modalAberto = false">Cancelar</button>
            <button type="submit" class="btn btn-success" :disabled="criando">
              {{ criando ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import api from '@/services/api'
import { situacao, STATUS, STATUS_LABEL } from '@/store/modules/mensalidades'

export default {
  name: 'MensalidadesList',
  setup() {
    const store = useStore()
    const filtroStatus = ref('')
    const salvando = ref(null)
    const erroAcao = ref('')
    const usuarios = ref({})
    const planos = ref({})
    const cargos = ref({})
    const associacoes = ref({})

    const modalAberto = ref(false)
    const criando = ref(false)
    const erroForm = ref('')
    const form = reactive({ usuario_uuid: '', plano_uuid: '', valor: null, data_vencimento: '' })

    const isAdminPlataforma = computed(() => store.getters['auth/cargoSlug'] === 'admin_plataforma')
    const mensalidades = computed(() => store.getters['mensalidades/allMensalidades'])
    const isLoading = computed(() => store.getters['mensalidades/isLoading'])
    const mensagemErro = computed(() => erroAcao.value || store.state.mensalidades.error)

    const mensalidadesFiltradas = computed(() => {
      if (!filtroStatus.value) return mensalidades.value
      return mensalidades.value.filter(m => situacao(m) === filtroStatus.value)
    })

    const classeSituacao = { paga: 'bg-success', pendente: 'bg-warning text-dark', atrasada: 'bg-danger', cancelada: 'bg-secondary' }

    // Texto do código de status; "aguardando" com vencimento passado é exibido como vencida
    const rotuloStatus = m => {
      const status = Number(m.status)
      const rotulo = STATUS_LABEL[status] ?? `Status ${m.status}`
      return status === STATUS.AGUARDANDO && situacao(m) === 'atrasada' ? `${rotulo} (vencida)` : rotulo
    }

    // A API de mensalidades devolve só os UUIDs; os nomes vêm de /usuarios e /planos
    const indexarPorUuid = lista => Object.fromEntries((lista || []).map(item => [item.uuid, item]))

    const carregarNomes = async () => {
      try {
        usuarios.value = indexarPorUuid((await api.get('/usuarios')).data)
      } catch { /* Sem nomes: a tabela mostra "—". */ }
      // /planos, /cargos e /associacoes só interessam (e só são permitidos) para o admin da plataforma
      if (!isAdminPlataforma.value) return
      const [resPlanos, resCargos, resAssociacoes] = await Promise.allSettled([
        api.get('/planos'), api.get('/cargos'), api.get('/associacoes')
      ])
      if (resPlanos.status === 'fulfilled') planos.value = indexarPorUuid(resPlanos.value.data)
      if (resCargos.status === 'fulfilled') cargos.value = indexarPorUuid(resCargos.value.data)
      if (resAssociacoes.status === 'fulfilled') associacoes.value = indexarPorUuid(resAssociacoes.value.data)
    }

    const nomeUsuario = uuid => {
      const u = usuarios.value[uuid]
      return u ? (u.nome_completo || u.email) : '—'
    }
    const nomePlano = uuid => planos.value[uuid]?.nome || '—'
    const listaPlanos = computed(() => Object.values(planos.value))

    // Quem paga a mensalidade da plataforma é o admin da associação
    const usuariosCobraveis = computed(() => {
      const todos = Object.values(usuarios.value)
      if (Object.keys(cargos.value).length === 0) return todos
      return todos.filter(u => cargos.value[u.cargo_uuid]?.slug === 'admin_associacao_geral')
    })
    const rotuloUsuarioCobravel = u => {
      const a = associacoes.value[u.associacao_uuid]
      const nomeAssociacao = a ? (a.nome_fantasia || a.razao_social) : null
      return nomeAssociacao ? `${nomeAssociacao} — ${nomeUsuario(u.uuid)}` : nomeUsuario(u.uuid)
    }

    const formatarValor = centavos =>
      (Number(centavos || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

    // Datas chegam como YYYY-MM-DD; formatar sem new Date() evita deslocamento de fuso
    const formatarData = data => {
      if (!data) return '—'
      const [ano, mes, dia] = String(data).substring(0, 10).split('-')
      return `${dia}/${mes}/${ano}`
    }

    const marcarComoPaga = async m => {
      if (!confirm(`Marcar a mensalidade de ${nomeUsuario(m.usuario_uuid)} como paga hoje?`)) return
      erroAcao.value = ''
      salvando.value = m.uuid
      const result = await store.dispatch('mensalidades/marcarComoPaga', m.uuid)
      salvando.value = null
      if (!result.success) erroAcao.value = result.message
    }

    const abrirModal = () => {
      form.usuario_uuid = ''
      form.plano_uuid = ''
      form.valor = null
      form.data_vencimento = ''
      erroForm.value = ''
      modalAberto.value = true
    }

    const aplicarValorDoPlano = () => {
      const plano = planos.value[form.plano_uuid]
      if (plano) form.valor = Number(plano.valor_em_centavos) / 100
    }

    const salvarNova = async () => {
      criando.value = true
      erroForm.value = ''
      const result = await store.dispatch('mensalidades/createMensalidade', {
        usuario_uuid: form.usuario_uuid,
        plano_uuid: form.plano_uuid || null,
        valor_em_centavos: Math.round(Number(form.valor) * 100),
        data_vencimento: form.data_vencimento
      })
      criando.value = false
      if (result.success) modalAberto.value = false
      else erroForm.value = result.message
    }

    onMounted(() => {
      store.dispatch('mensalidades/fetchMensalidades')
      carregarNomes()
    })

    return {
      filtroStatus,
      salvando,
      isLoading,
      isAdminPlataforma,
      mensagemErro,
      mensalidadesFiltradas,
      classeSituacao,
      situacao,
      rotuloStatus,
      nomeUsuario,
      nomePlano,
      listaPlanos,
      usuariosCobraveis,
      rotuloUsuarioCobravel,
      formatarValor,
      formatarData,
      marcarComoPaga,
      modalAberto,
      criando,
      erroForm,
      form,
      abrirModal,
      aplicarValorDoPlano,
      salvarNova
    }
  }
}
</script>

<style scoped>
.mensalidades-list {
  padding: 20px;
}

#filtro-status {
  width: auto;
}

.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
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
