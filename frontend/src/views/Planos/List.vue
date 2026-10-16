<template>
  <div class="planos-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Planos</h2>
      <button class="btn btn-success" @click="abrirModal()">Novo plano</button>
    </div>

    <div v-if="mensagemErro" class="alert alert-danger">{{ mensagemErro }}</div>

    <div v-if="carregando" class="text-center my-4">
      <div class="spinner-border text-success" role="status"></div>
    </div>

    <div v-else-if="planos.length === 0" class="text-muted text-center my-4">
      Nenhum plano cadastrado.
    </div>

    <div v-else class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Código</th>
            <th>Nome</th>
            <th>Slug</th>
            <th class="text-end">Valor</th>
            <th>Descrição</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in planos" :key="p.uuid">
            <td>{{ p.codigo }}</td>
            <td>{{ p.nome }}</td>
            <td><code>{{ p.slug }}</code></td>
            <td class="text-end">{{ formatarValor(p.valor_em_centavos) }}</td>
            <td>{{ p.descricao || '—' }}</td>
            <td class="text-end text-nowrap">
              <button class="btn btn-sm btn-outline-primary me-2" @click="abrirModal(p)">Editar</button>
              <button
                class="btn btn-sm btn-outline-danger"
                :disabled="excluindo === p.uuid"
                @click="excluir(p)"
              >
                {{ excluindo === p.uuid ? 'Excluindo...' : 'Excluir' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="modalAberto" class="modal-backdrop-custom">
      <div class="modal-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">{{ editandoUuid ? 'Editar plano' : 'Novo plano' }}</h5>
          <button class="btn-close" @click="fecharModal"></button>
        </div>

        <div v-if="erroForm" class="alert alert-danger">{{ erroForm }}</div>

        <form @submit.prevent="salvar">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="plano-codigo">Código *</label>
              <input id="plano-codigo" v-model.number="form.codigo" type="number" min="0" class="form-control" required />
            </div>
            <div class="col-md-8">
              <label class="form-label" for="plano-nome">Nome *</label>
              <input id="plano-nome" v-model="form.nome" type="text" class="form-control" required />
            </div>
            <div class="col-md-8">
              <label class="form-label" for="plano-slug">Slug *</label>
              <input id="plano-slug" v-model="form.slug" type="text" class="form-control" placeholder="plano_bronze" required />
            </div>
            <div class="col-md-4">
              <label class="form-label" for="plano-valor">Valor (R$) *</label>
              <input id="plano-valor" v-model.number="form.valor" type="number" min="0.01" step="0.01" class="form-control" required />
            </div>
            <div class="col-12">
              <label class="form-label" for="plano-descricao">Descrição</label>
              <textarea id="plano-descricao" v-model="form.descricao" rows="3" class="form-control"></textarea>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-outline-secondary" @click="fecharModal">Cancelar</button>
            <button type="submit" class="btn btn-success" :disabled="salvando">
              {{ salvando ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import planosService from '@/services/planos.service'

// Junta a mensagem principal da API com os detalhes de validação (422)
const mensagemDaApi = (err, padrao) => {
  const data = err.response?.data
  if (!data) return padrao
  const detalhes = data.details ? Object.values(data.details).join(' ') : ''
  return [data.error, detalhes].filter(Boolean).join(': ') || padrao
}

export default {
  name: 'PlanosList',
  setup() {
    const planos = ref([])
    const carregando = ref(false)
    const mensagemErro = ref('')
    const excluindo = ref(null)

    const modalAberto = ref(false)
    const editandoUuid = ref(null)
    const salvando = ref(false)
    const erroForm = ref('')
    const form = reactive({ codigo: null, nome: '', slug: '', valor: null, descricao: '' })

    const formatarValor = centavos =>
      (Number(centavos || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

    const carregar = async () => {
      carregando.value = true
      mensagemErro.value = ''
      try {
        const res = await planosService.getAll()
        planos.value = res.data?.data || res.data || []
      } catch (err) {
        mensagemErro.value = mensagemDaApi(err, 'Erro ao carregar planos')
      } finally {
        carregando.value = false
      }
    }

    const abrirModal = (plano = null) => {
      editandoUuid.value = plano?.uuid || null
      form.codigo = plano ? Number(plano.codigo) : null
      form.nome = plano?.nome || ''
      form.slug = plano?.slug || ''
      form.valor = plano ? Number(plano.valor_em_centavos) / 100 : null
      form.descricao = plano?.descricao || ''
      erroForm.value = ''
      modalAberto.value = true
    }

    const fecharModal = () => {
      modalAberto.value = false
    }

    const salvar = async () => {
      salvando.value = true
      erroForm.value = ''
      const payload = {
        codigo: form.codigo,
        nome: form.nome.trim(),
        slug: form.slug.trim(),
        valor_em_centavos: Math.round(Number(form.valor) * 100),
        descricao: form.descricao.trim() || null
      }
      try {
        if (editandoUuid.value) {
          const res = await planosService.update(editandoUuid.value, payload)
          const i = planos.value.findIndex(p => p.uuid === editandoUuid.value)
          if (i !== -1) planos.value.splice(i, 1, res.data)
        } else {
          const res = await planosService.create(payload)
          planos.value.push(res.data)
        }
        modalAberto.value = false
      } catch (err) {
        erroForm.value = mensagemDaApi(err, 'Erro ao salvar plano')
      } finally {
        salvando.value = false
      }
    }

    const excluir = async plano => {
      if (!confirm(`Excluir o plano "${plano.nome}"?`)) return
      excluindo.value = plano.uuid
      mensagemErro.value = ''
      try {
        await planosService.delete(plano.uuid)
        planos.value = planos.value.filter(p => p.uuid !== plano.uuid)
      } catch (err) {
        mensagemErro.value = mensagemDaApi(err, 'Erro ao excluir plano')
      } finally {
        excluindo.value = null
      }
    }

    onMounted(carregar)

    return {
      planos,
      carregando,
      mensagemErro,
      excluindo,
      modalAberto,
      editandoUuid,
      salvando,
      erroForm,
      form,
      formatarValor,
      abrirModal,
      fecharModal,
      salvar,
      excluir
    }
  }
}
</script>

<style scoped>
.planos-list {
  padding: 20px;
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
