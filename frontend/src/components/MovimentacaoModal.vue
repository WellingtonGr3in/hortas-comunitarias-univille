<template>
  <div v-if="item">
    <div class="modal d-block" tabindex="-1" @click.self="fechar">
      <div class="modal-dialog">
        <form class="modal-content" @submit.prevent="salvar">
          <div class="modal-header">
            <h5 class="modal-title">Movimentar: {{ item.nome }}</h5>
            <button type="button" class="btn-close" @click="fechar"></button>
          </div>

          <div class="modal-body">
            <p class="text-muted">
              Estoque atual: <strong>{{ formatarQuantidade(item.quantidade) }} {{ item.unidade_de_medida }}</strong>
            </p>

            <div v-if="erro" class="alert alert-danger">{{ erro }}</div>

            <div class="mb-3">
              <label class="form-label d-block">Tipo</label>
              <div class="btn-group" role="group">
                <template v-for="(rotulo, valor) in TIPOS_DE_MOVIMENTACAO" :key="valor">
                  <input :id="`tipo-${valor}`" v-model="form.tipo" type="radio" class="btn-check" :value="valor" />
                  <label class="btn btn-outline-success" :for="`tipo-${valor}`">{{ rotulo }}</label>
                </template>
              </div>
            </div>

            <div class="mb-3">
              <label for="mov-quantidade" class="form-label">
                {{ form.tipo === 'ajuste' ? 'Novo saldo (contagem)' : 'Quantidade' }} ({{ item.unidade_de_medida }})
              </label>
              <input
                id="mov-quantidade"
                v-model="form.quantidade"
                type="number"
                step="0.001"
                min="0"
                class="form-control"
                required
              />
              <div v-if="previsao !== null" class="form-text">
                Saldo depois: {{ formatarQuantidade(previsao) }} {{ item.unidade_de_medida }}
              </div>
            </div>

            <div class="mb-3">
              <label for="mov-observacao" class="form-label">Observação</label>
              <input
                id="mov-observacao"
                v-model="form.observacao"
                type="text"
                class="form-control"
                placeholder="Ex.: compra, uso no canteiro 3, contagem mensal (opcional)"
              />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="fechar">Cancelar</button>
            <button type="submit" class="btn btn-success" :disabled="salvando">
              {{ salvando ? 'Salvando...' : 'Registrar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    <div class="modal-backdrop show"></div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch } from 'vue'
import { useStore } from 'vuex'
import { TIPOS_DE_MOVIMENTACAO, formatarQuantidade } from '@/store/modules/inventario'

export default {
  name: 'MovimentacaoModal',
  props: {
    item: { type: Object, default: null }
  },
  emits: ['fechar', 'salvo'],
  setup(props, { emit }) {
    const store = useStore()
    const form = reactive({ tipo: 'entrada', quantidade: '', observacao: '' })
    const erro = ref('')
    const salvando = ref(false)

    // Limpa o formulário sempre que o modal abre para outro item.
    watch(() => props.item, () => {
      Object.assign(form, { tipo: 'entrada', quantidade: '', observacao: '' })
      erro.value = ''
    })

    const previsao = computed(() => {
      if (form.quantidade === '' || !props.item) return null
      const atual = Number(props.item.quantidade)
      const q = Number(form.quantidade)
      if (form.tipo === 'entrada') return atual + q
      if (form.tipo === 'saida') return atual - q
      return q
    })

    const fechar = () => emit('fechar')

    const salvar = async () => {
      erro.value = ''
      salvando.value = true
      const result = await store.dispatch('inventario/registrarMovimentacao', {
        item_uuid: props.item.uuid,
        tipo: form.tipo,
        quantidade: Number(form.quantidade),
        observacao: form.observacao || null
      })
      salvando.value = false
      if (!result.success) {
        erro.value = result.message
        return
      }
      emit('salvo', result.data)
    }

    return { form, erro, salvando, previsao, TIPOS_DE_MOVIMENTACAO, formatarQuantidade, fechar, salvar }
  }
}
</script>
