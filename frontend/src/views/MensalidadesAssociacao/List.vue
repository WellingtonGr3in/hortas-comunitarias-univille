<template>
  <div class="mensalidades-associacao-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Mensalidades da associação</h2>
      <div class="d-flex align-items-center gap-2">
        <label for="filtro-status-assoc" class="form-label mb-0">Status</label>
        <select id="filtro-status-assoc" v-model="filtroStatus" class="form-select">
          <option value="">Todas</option>
          <option value="paga">Pagas</option>
          <option value="pendente">Pendentes</option>
          <option value="atrasada">Atrasadas</option>
        </select>
        <button class="btn btn-success text-nowrap" @click="abrirModal">Nova mensalidade</button>
      </div>
    </div>

    <div v-if="mensagemErro" class="alert alert-danger">{{ mensagemErro }}</div>

    <div v-if="carregando" class="text-center my-4">
      <div class="spinner-border text-success" role="status"></div>
    </div>

    <div v-else-if="mensalidadesFiltradas.length === 0" class="text-muted text-center my-4">
      Nenhuma mensalidade encontrada.
    </div>

    <div v-else class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Canteirista</th>
            <th v-if="isAdminPlataforma">Associação</th>
            <th class="text-end">Valor</th>
            <th>Vencimento</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in mensalidadesFiltradas" :key="m.uuid">
            <td>{{ nomeUsuario(m.usuario_uuid) }}</td>
            <td v-if="isAdminPlataforma">{{ nomeAssociacao(m.associacao_uuid) }}</td>
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
            <div v-if="isAdminPlataforma" class="col-12">
              <label class="form-label" for="assoc-associacao">Associação *</label>
              <select id="assoc-associacao" v-model="form.associacao_uuid" class="form-select" required @change="form.usuario_uuid = ''">
                <option value="" disabled>Selecione</option>
                <option v-for="a in listaAssociacoes" :key="a.uuid" :value="a.uuid">
                  {{ a.nome }}
                </option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="assoc-usuario">Canteirista *</label>
              <select id="assoc-usuario" v-model="form.usuario_uuid" class="form-select" required>
                <option value="" disabled>Selecione</option>
                <option v-for="u in canteiristasDaAssociacao" :key="u.uuid" :value="u.uuid">
                  {{ u.nome_completo || u.email }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="assoc-valor">Valor (R$) *</label>
              <input id="assoc-valor" v-model.number="form.valor" type="number" min="0.01" step="0.01" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label" for="assoc-vencimento">Vencimento *</label>
              <input id="assoc-vencimento" v-model="form.data_vencimento" type="date" class="form-control" required />
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
import mensalidadesAssociacaoService from '@/services/mensalidadesAssociacao.service'
import { situacao, hojeISO, STATUS, STATUS_LABEL } from '@/store/modules/mensalidades'

const mensagemDaApi = (err, padrao) => {
  const data = err.response?.data
  if (!data) return padrao
  const detalhes = data.details ? Object.values(data.details).join(' ') : ''
  return [data.error, detalhes].filter(Boolean).join(': ') || padrao
}

export default {
  name: 'MensalidadesAssociacaoList',
  setup() {
    const store = useStore()
    const mensalidades = ref([])
    const carregando = ref(false)
    const mensagemErro = ref('')
    const filtroStatus = ref('')
    const salvando = ref(null)
    const usuarios = ref({})
    const cargos = ref({})
    const associacoes = ref({})

    const modalAberto = ref(false)
    const criando = ref(false)
    const erroForm = ref('')
    const form = reactive({ associacao_uuid: '', usuario_uuid: '', valor: null, data_vencimento: '' })

    const currentUser = computed(() => store.getters['auth/currentUser'])
    const isAdminPlataforma = computed(() => store.getters['auth/cargoSlug'] === 'admin_plataforma')

    const mensalidadesFiltradas = computed(() => {
      if (!filtroStatus.value) return mensalidades.value
      return mensalidades.value.filter(m => situacao(m) === filtroStatus.value)
    })

    const classeSituacao = { paga: 'bg-success', pendente: 'bg-warning text-dark', atrasada: 'bg-danger', cancelada: 'bg-secondary' }

    const rotuloStatus = m => {
      const status = Number(m.status)
      const rotulo = STATUS_LABEL[status] ?? `Status ${m.status}`
      return status === STATUS.AGUARDANDO && situacao(m) === 'atrasada' ? `${rotulo} (vencida)` : rotulo
    }

    const indexarPorUuid = lista => Object.fromEntries((lista || []).map(item => [item.uuid, item]))

    const carregar = async () => {
      carregando.value = true
      mensagemErro.value = ''
      try {
        const res = await mensalidadesAssociacaoService.getAll()
        mensalidades.value = res.data?.data || res.data || []
      } catch (err) {
        mensagemErro.value = mensagemDaApi(err, 'Erro ao carregar mensalidades')
      } finally {
        carregando.value = false
      }
    }

    const carregarNomes = async () => {
      const [resUsuarios, resCargos, resAssociacoes] = await Promise.allSettled([
        api.get('/usuarios'), api.get('/cargos'), api.get('/associacoes')
      ])
      if (resUsuarios.status === 'fulfilled') usuarios.value = indexarPorUuid(resUsuarios.value.data)
      if (resCargos.status === 'fulfilled') cargos.value = indexarPorUuid(resCargos.value.data)
      // /associacoes devolve já formatado ({ id, nome }), diferente das outras rotas.
      if (resAssociacoes.status === 'fulfilled') {
        associacoes.value = indexarPorUuid((resAssociacoes.value.data || []).map(a => ({
          ...a,
          uuid: a.uuid || a.id,
          nome: a.nome || a.nome_fantasia || a.razao_social
        })))
      }
    }

    const nomeUsuario = uuid => {
      const u = usuarios.value[uuid]
      return u ? (u.nome_completo || u.email) : '—'
    }
    const nomeAssociacao = uuid => {
      const a = associacoes.value[uuid]
      return a ? a.nome : '—'
    }
    const listaAssociacoes = computed(() => Object.values(associacoes.value))

    // Admin da associação só lança para a própria associação; o admin da plataforma escolhe
    const associacaoAlvo = computed(() =>
      isAdminPlataforma.value ? form.associacao_uuid : currentUser.value?.associacao_uuid
    )

    const canteiristasDaAssociacao = computed(() => {
      const daAssociacao = Object.values(usuarios.value).filter(u => u.associacao_uuid === associacaoAlvo.value)
      if (Object.keys(cargos.value).length === 0) return daAssociacao
      return daAssociacao.filter(u => cargos.value[u.cargo_uuid]?.slug === 'canteirista')
    })

    const formatarValor = centavos =>
      (Number(centavos || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

    const formatarData = data => {
      if (!data) return '—'
      const [ano, mes, dia] = String(data).substring(0, 10).split('-')
      return `${dia}/${mes}/${ano}`
    }

    const marcarComoPaga = async m => {
      if (!confirm(`Marcar a mensalidade de ${nomeUsuario(m.usuario_uuid)} como paga hoje?`)) return
      mensagemErro.value = ''
      salvando.value = m.uuid
      try {
        const res = await mensalidadesAssociacaoService.update(m.uuid, {
          data_pagamento: hojeISO(),
          status: STATUS.PAGO
        })
        const i = mensalidades.value.findIndex(x => x.uuid === m.uuid)
        if (i !== -1) mensalidades.value.splice(i, 1, res.data)
      } catch (err) {
        mensagemErro.value = mensagemDaApi(err, 'Erro ao marcar mensalidade como paga')
      } finally {
        salvando.value = null
      }
    }

    const abrirModal = () => {
      form.associacao_uuid = ''
      form.usuario_uuid = ''
      form.valor = null
      form.data_vencimento = ''
      erroForm.value = ''
      modalAberto.value = true
    }

    const salvarNova = async () => {
      criando.value = true
      erroForm.value = ''
      try {
        const res = await mensalidadesAssociacaoService.create({
          usuario_uuid: form.usuario_uuid,
          associacao_uuid: associacaoAlvo.value,
          valor_em_centavos: Math.round(Number(form.valor) * 100),
          data_vencimento: form.data_vencimento,
          status: STATUS.AGUARDANDO,
          dias_atraso: 0
        })
        mensalidades.value.unshift(res.data)
        modalAberto.value = false
      } catch (err) {
        erroForm.value = mensagemDaApi(err, 'Erro ao criar mensalidade')
      } finally {
        criando.value = false
      }
    }

    onMounted(() => {
      carregar()
      carregarNomes()
    })

    return {
      carregando,
      mensagemErro,
      filtroStatus,
      salvando,
      isAdminPlataforma,
      mensalidadesFiltradas,
      classeSituacao,
      situacao,
      rotuloStatus,
      nomeUsuario,
      nomeAssociacao,
      listaAssociacoes,
      canteiristasDaAssociacao,
      formatarValor,
      formatarData,
      marcarComoPaga,
      modalAberto,
      criando,
      erroForm,
      form,
      abrirModal,
      salvarNova
    }
  }
}
</script>

<style scoped>
.mensalidades-associacao-list {
  padding: 20px;
}

#filtro-status-assoc {
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
