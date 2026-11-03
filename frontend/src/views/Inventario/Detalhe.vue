<template>
  <div class="inventario-detalhe">
    <router-link to="/inventario" class="d-inline-block mb-3">&larr; Voltar ao inventário</router-link>

    <div v-if="erro" class="alert alert-danger">{{ erro }}</div>

    <div v-if="carregando" class="text-center my-4">
      <div class="spinner-border text-success" role="status"></div>
    </div>

    <template v-else-if="item">
      <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
          <h2 class="mb-1">{{ item.nome }}</h2>
          <div class="text-muted">{{ TIPOS_DE_ITEM[item.tipo] }} · {{ local(item) }}</div>
          <div v-if="item.descricao" class="mt-1">{{ item.descricao }}</div>
        </div>
        <div class="text-end">
          <div class="fs-3 fw-bold">{{ formatarQuantidade(item.quantidade) }} {{ item.unidade_de_medida }}</div>
          <div v-if="item.quantidade_minima !== null" class="small text-muted">
            mínimo: {{ formatarQuantidade(item.quantidade_minima) }} {{ item.unidade_de_medida }}
          </div>
          <span v-if="estoqueBaixo(item)" class="badge bg-warning text-dark">Estoque baixo</span>
          <div class="mt-2">
            <button class="btn btn-success btn-sm" @click="movimentando = true">Movimentar</button>
          </div>
        </div>
      </div>

      <h5>Histórico de movimentações</h5>
      <div v-if="movimentacoes.length === 0" class="text-muted my-3">Nenhuma movimentação registrada.</div>
      <div v-else class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>Data</th>
              <th>Tipo</th>
              <th class="text-end">Quantidade</th>
              <th class="text-end">Saldo</th>
              <th>Observação</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in movimentacoes" :key="m.uuid">
              <td class="text-nowrap">{{ formatarDataHora(m.data_de_criacao) }}</td>
              <td><span class="badge" :class="classeTipo[m.tipo]">{{ TIPOS_DE_MOVIMENTACAO[m.tipo] }}</span></td>
              <td class="text-end">{{ sinal(m) }}{{ formatarQuantidade(m.quantidade) }}</td>
              <td class="text-end text-nowrap">
                {{ formatarQuantidade(m.quantidade_anterior) }} &rarr; {{ formatarQuantidade(m.quantidade_posterior) }}
              </td>
              <td>{{ m.observacao || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <MovimentacaoModal :item="movimentando ? item : null" @fechar="movimentando = false" @salvo="aposMovimentar" />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import inventarioService from '@/services/inventario.service'
import MovimentacaoModal from '@/components/MovimentacaoModal.vue'
import { TIPOS_DE_ITEM, TIPOS_DE_MOVIMENTACAO, formatarQuantidade, estoqueBaixo, mensagemDeErro } from '@/store/modules/inventario'
import { useLocaisDoInventario } from './locais'

export default {
  name: 'InventarioDetalhe',
  components: { MovimentacaoModal },
  setup() {
    const route = useRoute()
    const { carregarLocais, local } = useLocaisDoInventario()
    const item = ref(null)
    const movimentacoes = ref([])
    const carregando = ref(true)
    const movimentando = ref(false)
    const erro = ref('')

    const classeTipo = { entrada: 'bg-success', saida: 'bg-danger', ajuste: 'bg-secondary' }
    const sinal = m => (m.tipo === 'entrada' ? '+' : m.tipo === 'saida' ? '−' : '=')

    const formatarDataHora = data => (data ? new Date(data).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '—')

    const carregar = async () => {
      try {
        const [resItem, resMovimentacoes] = await Promise.all([
          inventarioService.getItem(route.params.id),
          inventarioService.getMovimentacoesDoItem(route.params.id)
        ])
        item.value = resItem.data
        movimentacoes.value = resMovimentacoes.data || []
      } catch (err) {
        erro.value = mensagemDeErro(err, 'Item não encontrado')
      }
    }

    const aposMovimentar = async () => {
      movimentando.value = false
      await carregar()
    }

    onMounted(async () => {
      await Promise.all([carregar(), carregarLocais()])
      carregando.value = false
    })

    return {
      item,
      movimentacoes,
      carregando,
      movimentando,
      erro,
      classeTipo,
      sinal,
      formatarDataHora,
      aposMovimentar,
      local,
      TIPOS_DE_ITEM,
      TIPOS_DE_MOVIMENTACAO,
      formatarQuantidade,
      estoqueBaixo
    }
  }
}
</script>

<style scoped>
.inventario-detalhe {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
}
</style>
