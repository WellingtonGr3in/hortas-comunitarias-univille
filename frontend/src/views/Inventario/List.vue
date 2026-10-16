<template>
  <div class="inventario-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Inventário</h2>
      <button class="btn btn-success" @click="abrirFormulario(null)">+ Novo item</button>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
      <input v-model="busca" type="search" class="form-control filtro-busca" placeholder="Buscar por nome..." />
      <select v-model="filtroTipo" class="form-select filtro">
        <option value="">Todos os tipos</option>
        <option v-for="(rotulo, valor) in TIPOS_DE_ITEM" :key="valor" :value="valor">{{ rotulo }}</option>
      </select>
      <div class="form-check align-self-center ms-2">
        <input id="so-baixo" v-model="soEstoqueBaixo" type="checkbox" class="form-check-input" />
        <label for="so-baixo" class="form-check-label">Só estoque baixo</label>
      </div>
    </div>

    <div v-if="mensagemErro" class="alert alert-danger">{{ mensagemErro }}</div>

    <div v-if="isLoading" class="text-center my-4">
      <div class="spinner-border text-success" role="status"></div>
    </div>

    <div v-else-if="itensFiltrados.length === 0" class="text-muted text-center my-4">
      Nenhum item encontrado.
    </div>

    <div v-else class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Item</th>
            <th>Tipo</th>
            <th>Local</th>
            <th class="text-end">Estoque</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in itensFiltrados" :key="item.uuid">
            <td>
              <router-link :to="`/inventario/${item.uuid}`">{{ item.nome }}</router-link>
              <div v-if="item.descricao" class="small text-muted">{{ item.descricao }}</div>
            </td>
            <td>{{ TIPOS_DE_ITEM[item.tipo] }}</td>
            <td>{{ local(item) }}</td>
            <td class="text-end">
              {{ formatarQuantidade(item.quantidade) }} {{ item.unidade_de_medida }}
              <div v-if="estoqueBaixo(item)">
                <span class="badge bg-warning text-dark">Estoque baixo</span>
              </div>
            </td>
            <td class="text-end text-nowrap">
              <button class="btn btn-sm btn-success me-1" @click="itemMovimentando = item">Movimentar</button>
              <button class="btn btn-sm btn-outline-secondary me-1" @click="abrirFormulario(item)">Editar</button>
              <button class="btn btn-sm btn-outline-danger" @click="itemExcluindo = item">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <MovimentacaoModal :item="itemMovimentando" @fechar="itemMovimentando = null" @salvo="itemMovimentando = null" />

    <ItemDoInventarioModal
      :aberto="formularioAberto"
      :item="itemEditando"
      :associacoes="associacoes"
      :hortas="hortas"
      :nome-associacao="nomeAssociacao"
      :local="local"
      @fechar="formularioAberto = false"
      @salvo="formularioAberto = false"
    />

    <div v-if="itemExcluindo">
      <div class="modal d-block" tabindex="-1" @click.self="itemExcluindo = null">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Excluir item</h5>
              <button type="button" class="btn-close" @click="itemExcluindo = null"></button>
            </div>
            <div class="modal-body">
              <div v-if="erroExclusao" class="alert alert-danger">{{ erroExclusao }}</div>
              Excluir <strong>{{ itemExcluindo.nome }}</strong> do inventário? O histórico de movimentações é mantido.
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="itemExcluindo = null">Cancelar</button>
              <button type="button" class="btn btn-danger" :disabled="excluindo" @click="confirmarExclusao">
                {{ excluindo ? 'Excluindo...' : 'Excluir' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-backdrop show"></div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import MovimentacaoModal from '@/components/MovimentacaoModal.vue'
import ItemDoInventarioModal from '@/components/ItemDoInventarioModal.vue'
import { TIPOS_DE_ITEM, formatarQuantidade, estoqueBaixo } from '@/store/modules/inventario'
import { useLocaisDoInventario } from './locais'

export default {
  name: 'InventarioList',
  components: { MovimentacaoModal, ItemDoInventarioModal },
  setup() {
    const store = useStore()
    const busca = ref('')
    const filtroTipo = ref('')
    const soEstoqueBaixo = ref(false)
    const itemMovimentando = ref(null)
    const formularioAberto = ref(false)
    const itemEditando = ref(null)
    const itemExcluindo = ref(null)
    const excluindo = ref(false)
    const erroExclusao = ref('')
    const { associacoes, hortas, carregarLocais, nomeAssociacao, local } = useLocaisDoInventario()

    const itens = computed(() => store.getters['inventario/allItens'])
    const isLoading = computed(() => store.getters['inventario/isLoading'])
    const mensagemErro = computed(() => store.state.inventario.error)

    const itensFiltrados = computed(() => {
      const termo = busca.value.trim().toLowerCase()
      return itens.value.filter(item =>
        (!termo || item.nome.toLowerCase().includes(termo)) &&
        (!filtroTipo.value || item.tipo === filtroTipo.value) &&
        (!soEstoqueBaixo.value || estoqueBaixo(item))
      )
    })

    const abrirFormulario = item => {
      itemEditando.value = item
      formularioAberto.value = true
    }

    const confirmarExclusao = async () => {
      erroExclusao.value = ''
      excluindo.value = true
      const result = await store.dispatch('inventario/deleteItem', itemExcluindo.value.uuid)
      excluindo.value = false
      if (!result.success) {
        erroExclusao.value = result.message
        return
      }
      itemExcluindo.value = null
    }

    onMounted(() => {
      store.dispatch('inventario/fetchItens')
      carregarLocais()
    })

    return {
      busca,
      filtroTipo,
      soEstoqueBaixo,
      itemMovimentando,
      formularioAberto,
      itemEditando,
      itemExcluindo,
      excluindo,
      erroExclusao,
      associacoes,
      hortas,
      nomeAssociacao,
      isLoading,
      mensagemErro,
      itensFiltrados,
      TIPOS_DE_ITEM,
      formatarQuantidade,
      estoqueBaixo,
      local,
      abrirFormulario,
      confirmarExclusao
    }
  }
}
</script>

<style scoped>
.inventario-list {
  padding: 20px;
}

.filtro-busca {
  max-width: 280px;
}

.filtro {
  width: auto;
}
</style>
