<template>
  <div class="pagamentos-list">
    <div class="header">
      <h2>Pagamentos</h2>
      <button @click="handleCreate" class="btn btn-primary">
        + Novo Pagamento
      </button>
    </div>

    <div class="summary-cards">
      <div class="summary-card">
        <h3>Total Pago</h3>
        <p class="value">R$ {{ totalPaid.toFixed(2) }}</p>
      </div>
      <div class="summary-card">
        <h3>Total de Pagamentos</h3>
        <p class="value">{{ pagamentos.length }}</p>
      </div>
    </div>

    <div v-if="loading" class="loading">Carregando...</div>

    <div v-else-if="pagamentos.length === 0" class="empty-state">
      <p>Nenhum pagamento cadastrado.</p>
      <button @click="handleCreate" class="btn btn-primary">
        Cadastrar Primeiro Pagamento
      </button>
    </div>

    <div v-else class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>Pagador</th>
            <th>Telefone</th>
            <th>Mensalidade</th>
            <th>Valor</th>
            <th>Forma</th>
            <th>Data</th>
            <th>Observação</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="pagamento in pagamentos" :key="pagamento.id">
            <td>{{ pagamento.mensalidade_pagador || pagamento.carteirista_nome || '—' }}</td>
            <td>{{ pagamento.carteirista_telefone || '—' }}</td>
            <td>
              <template v-if="pagamento.mensalidade_uuid">
                Venc. {{ formatDate(pagamento.mensalidade_vencimento) }}
                <div class="small text-muted">
                  {{ pagamento.mensalidade_tipo === 'plataforma' ? 'Mensalidade da plataforma' : 'Mensalidade da associação' }}
                </div>
              </template>
              <span v-else class="text-muted">Avulso</span>
            </td>
            <td class="valor">R$ {{ parseFloat(pagamento.valor).toFixed(2) }}</td>
            <td>
              <span class="badge" :class="getBadgeClass(pagamento.forma_pagamento)">
                {{ formatFormaPagamento(pagamento.forma_pagamento) }}
              </span>
            </td>
            <td>{{ formatDate(pagamento.data_pagamento) }}</td>
            <td>{{ pagamento.observacao || '—' }}</td>
            <td class="actions">
              <!-- Pagamento de mensalidade não se edita: estorna e lança de novo -->
              <button v-if="!pagamento.mensalidade_uuid" @click="handleEdit(pagamento.id)" class="btn-icon" title="Editar">
                ✏️
              </button>
              <button @click="handleDelete(pagamento)" class="btn-icon" title="Estornar">
                ↩️
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'

export default {
  name: 'PagamentosList',
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(true)

    const pagamentos = computed(() => store.getters['pagamentos/allPagamentos'])

    const totalPaid = computed(() => {
      return pagamentos.value.reduce((sum, p) => sum + (parseFloat(p.valor) || 0), 0)
    })

    onMounted(async () => {
      try {
        await store.dispatch('pagamentos/fetchPagamentos')
      } catch (error) {
        console.error('Erro ao carregar pagamentos:', error)
      } finally {
        loading.value = false
      }
    })

    const handleCreate = () => {
      router.push('/pagamentos/criar')
    }

    const handleEdit = (id) => {
      router.push(`/pagamentos/${id}/editar`)
    }

    // Estorno = exclusão lógica; se o pagamento quitava uma mensalidade, ela volta para em aberto
    const handleDelete = async (pagamento) => {
      const aviso = pagamento.mensalidade_uuid
        ? 'Estornar este pagamento? A mensalidade voltará para "em aberto".'
        : 'Estornar este pagamento?'
      if (!confirm(aviso)) return

      try {
        await store.dispatch('pagamentos/deletePagamento', pagamento.id)
        alert('Pagamento estornado.')
      } catch (error) {
        alert('Erro ao estornar pagamento: ' + (error.message || 'Erro desconhecido'))
      }
    }

    const formatFormaPagamento = (forma) => {
      const map = {
        'dinheiro': 'Dinheiro',
        'pix': 'PIX'
      }
      return map[forma] || forma
    }

    const getBadgeClass = (forma) => {
      return forma === 'pix' ? 'badge-pix' : 'badge-dinheiro'
    }

    // Datas chegam como YYYY-MM-DD; new Date() leria como UTC e mostraria o dia anterior no Brasil
    const formatDate = (date) => {
      if (!date) return '—'
      const [ano, mes, dia] = String(date).substring(0, 10).split('-')
      return `${dia}/${mes}/${ano}`
    }

    return {
      loading,
      pagamentos,
      totalPaid,
      handleCreate,
      handleEdit,
      handleDelete,
      formatFormaPagamento,
      getBadgeClass,
      formatDate
    }
  }
}
</script>

<style scoped>
.pagamentos-list {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header h2 {
  margin: 0;
  color: #333;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.summary-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.summary-card h3 {
  margin: 0 0 12px 0;
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.summary-card .value {
  margin: 0;
  font-size: 28px;
  font-weight: 600;
  color: #4CAF50;
}

.loading, .empty-state {
  text-align: center;
  padding: 40px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.empty-state p {
  margin-bottom: 16px;
  color: #666;
}

.table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table thead {
  background-color: #f8f9fa;
}

.table th {
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  color: #555;
  border-bottom: 2px solid #dee2e6;
}

.table td {
  padding: 12px 16px;
  border-bottom: 1px solid #dee2e6;
}

.table tbody tr:hover {
  background-color: #f8f9fa;
}

.valor {
  font-weight: 600;
  color: #4CAF50;
}

.badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.badge-pix {
  background-color: #e3f2fd;
  color: #1976d2;
}

.badge-dinheiro {
  background-color: #f1f8e9;
  color: #558b2f;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #4CAF50;
  color: white;
}

.btn-primary:hover {
  background-color: #45a049;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  padding: 4px 8px;
  transition: transform 0.2s;
}

.btn-icon:hover {
  transform: scale(1.2);
}
</style>
