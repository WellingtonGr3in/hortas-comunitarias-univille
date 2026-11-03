<template>
  <div class="mensalidades-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">Mensalidades</h2>
        <small v-if="tipo" class="text-muted">{{ tipo === 'plataforma' ? 'Cobradas das associações pela plataforma' : 'Cobradas dos canteiristas pela associação' }}</small>
      </div>
      <div class="d-flex align-items-center gap-2">
        <label for="filtro-status" class="form-label mb-0">Status</label>
        <select id="filtro-status" v-model="filtroStatus" class="form-select">
          <option value="">Todas</option>
          <option value="paga">Pagas</option>
          <option value="pendente">Pendentes</option>
          <option value="atrasada">Atrasadas</option>
        </select>
        <button v-if="isAdminPlataforma" class="btn btn-success text-nowrap" @click="abrirNovaMensalidade">
          Nova mensalidade
        </button>
      </div>
    </div>

    <div v-if="mensagemErro" class="alert alert-danger">{{ mensagemErro }}</div>
    <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>

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
            <th v-if="tipo === 'plataforma'">Plano</th>
            <th class="text-end">Valor</th>
            <th>Vencimento</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in mensalidadesFiltradas" :key="m.uuid">
            <td>{{ nomeUsuario(m.usuario_uuid) }}</td>
            <td v-if="tipo === 'plataforma'">{{ nomePlano(m.plano_uuid) }}</td>
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
                @click="abrirModal(m)"
              >
                Registrar pagamento
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal "Registrar pagamento" -->
    <template v-if="mensalidadeSelecionada">
      <div class="modal d-block" tabindex="-1" role="dialog" aria-labelledby="titulo-modal-pagamento" @click.self="fecharModal">
        <div class="modal-dialog modal-dialog-centered">
          <form class="modal-content" @submit.prevent="salvarPagamento">
            <div class="modal-header">
              <h5 id="titulo-modal-pagamento" class="modal-title">Registrar pagamento</h5>
              <button type="button" class="btn-close" aria-label="Fechar" @click="fecharModal"></button>
            </div>
            <div class="modal-body">
              <p class="text-muted small mb-3">
                {{ nomeUsuario(mensalidadeSelecionada.usuario_uuid) }} · vencimento
                {{ formatarData(mensalidadeSelecionada.data_vencimento) }} ·
                {{ formatarValor(mensalidadeSelecionada.valor_em_centavos) }}
              </p>

              <div v-if="erroModal" class="alert alert-danger py-2">{{ erroModal }}</div>

              <div class="mb-3">
                <label for="pg-valor" class="form-label">Valor (R$)</label>
                <input id="pg-valor" v-model.number="form.valor" type="number" min="0.01" step="0.01" class="form-control" required />
              </div>
              <div class="mb-3">
                <label for="pg-data" class="form-label">Data do pagamento</label>
                <input id="pg-data" v-model="form.data_pagamento" type="date" :max="hoje" class="form-control" required />
              </div>
              <div class="mb-3">
                <label for="pg-forma" class="form-label">Forma de pagamento</label>
                <select id="pg-forma" v-model="form.forma_pagamento" class="form-select" required>
                  <option value="pix">PIX</option>
                  <option value="dinheiro">Dinheiro</option>
                </select>
              </div>
              <div>
                <label for="pg-observacao" class="form-label">Observação</label>
                <textarea id="pg-observacao" v-model="form.observacao" class="form-control" rows="2" maxlength="500"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" :disabled="salvando" @click="fecharModal">Cancelar</button>
              <button type="submit" class="btn btn-success" :disabled="salvando">
                {{ salvando ? 'Salvando...' : 'Registrar pagamento' }}
              </button>
            </div>
          </form>
        </div>
      </div>
      <div class="modal-backdrop show"></div>
    </template>

    <div v-if="modalNovaAberto" class="modal-backdrop-custom">
      <div class="modal-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Nova mensalidade</h5>
          <button class="btn-close" @click="modalNovaAberto = false"></button>
        </div>

        <div v-if="erroForm" class="alert alert-danger">{{ erroForm }}</div>

        <form @submit.prevent="salvarNova">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="nova-usuario">Associação / usuário *</label>
              <select id="nova-usuario" v-model="formNova.usuario_uuid" class="form-select" required>
                <option value="" disabled>Selecione</option>
                <option v-for="u in usuariosCobraveis" :key="u.uuid" :value="u.uuid">
                  {{ rotuloUsuarioCobravel(u) }}
                </option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="nova-plano">Plano</label>
              <select id="nova-plano" v-model="formNova.plano_uuid" class="form-select" @change="aplicarValorDoPlano">
                <option value="">Sem plano</option>
                <option v-for="p in listaPlanos" :key="p.uuid" :value="p.uuid">
                  {{ p.nome }} ({{ formatarValor(p.valor_em_centavos) }})
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="nova-valor">Valor (R$) *</label>
              <input id="nova-valor" v-model.number="formNova.valor" type="number" min="0.01" step="0.01" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label" for="nova-vencimento">Vencimento *</label>
              <input id="nova-vencimento" v-model="formNova.data_vencimento" type="date" class="form-control" required />
            </div>
          </div>
          <p class="small text-muted mt-3 mb-0">A mensalidade é criada em aberto (aguardando pagamento).</p>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-outline-secondary" @click="modalNovaAberto = false">Cancelar</button>
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
import { situacao, hojeISO, STATUS, STATUS_LABEL } from '@/store/modules/mensalidades'

export default {
  name: 'MensalidadesList',
  setup() {
    const store = useStore()
    const filtroStatus = ref('')
    const usuarios = ref({})
    const planos = ref({})
    const mensagemSucesso = ref('')

    const mensalidadeSelecionada = ref(null)
    const form = reactive({ valor: null, data_pagamento: '', forma_pagamento: 'pix', observacao: '' })
    const salvando = ref(false)
    const erroModal = ref('')
    const hoje = hojeISO()
    const cargos = ref({})
    const associacoes = ref({})

    const modalNovaAberto = ref(false)
    const criando = ref(false)
    const erroForm = ref('')
    const formNova = reactive({ usuario_uuid: '', plano_uuid: '', valor: null, data_vencimento: '' })

    const isAdminPlataforma = computed(() => store.getters['auth/cargoSlug'] === 'admin_plataforma')
    const mensalidades = computed(() => store.getters['mensalidades/allMensalidades'])
    const tipo = computed(() => store.getters['mensalidades/tipo'])
    const isLoading = computed(() => store.getters['mensalidades/isLoading'])
    const mensagemErro = computed(() => store.state.mensalidades.error)

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

    // Só mensalidade da plataforma tem plano (e /planos é restrito ao admin da plataforma)
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

    const abrirModal = m => {
      mensagemSucesso.value = ''
      erroModal.value = ''
      Object.assign(form, {
        valor: Number(m.valor_em_centavos || 0) / 100,
        data_pagamento: hoje,
        forma_pagamento: 'pix',
        observacao: ''
      })
      mensalidadeSelecionada.value = m
    }

    const fecharModal = () => {
      if (salvando.value) return
      mensalidadeSelecionada.value = null
    }

    const salvarPagamento = async () => {
      erroModal.value = ''
      salvando.value = true
      const m = mensalidadeSelecionada.value
      const result = await store.dispatch('mensalidades/registrarPagamento', { mensalidade: m, ...form })
      salvando.value = false
      if (!result.success) {
        erroModal.value = result.message
        return
      }
      mensalidadeSelecionada.value = null
      mensagemSucesso.value = `Pagamento de ${formatarValor(Math.round(form.valor * 100))} registrado para ${nomeUsuario(m.usuario_uuid)}.`
    }

    const abrirNovaMensalidade = () => {
      formNova.usuario_uuid = ''
      formNova.plano_uuid = ''
      formNova.valor = null
      formNova.data_vencimento = ''
      erroForm.value = ''
      modalNovaAberto.value = true
    }

    const aplicarValorDoPlano = () => {
      const plano = planos.value[formNova.plano_uuid]
      if (plano) formNova.valor = Number(plano.valor_em_centavos) / 100
    }

    const salvarNova = async () => {
      criando.value = true
      erroForm.value = ''
      const result = await store.dispatch('mensalidades/createMensalidade', {
        usuario_uuid: formNova.usuario_uuid,
        plano_uuid: formNova.plano_uuid || null,
        valor_em_centavos: Math.round(Number(formNova.valor) * 100),
        data_vencimento: formNova.data_vencimento
      })
      criando.value = false
      if (result.success) modalNovaAberto.value = false
      else erroForm.value = result.message
    }

    onMounted(() => {
      store.dispatch('mensalidades/fetchMensalidades')
      carregarNomes()
    })

    return {
      filtroStatus,
      tipo,
      isLoading,
      isAdminPlataforma,
      mensagemErro,
      mensagemSucesso,
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
      mensalidadeSelecionada,
      form,
      salvando,
      erroModal,
      hoje,
      abrirModal,
      fecharModal,
      salvarPagamento,
      modalNovaAberto,
      criando,
      erroForm,
      formNova,
      abrirNovaMensalidade,
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
