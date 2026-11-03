<template>
  <div v-if="aberto">
    <div class="modal d-block" tabindex="-1" @click.self="fechar">
      <div class="modal-dialog modal-lg">
        <form class="modal-content" @submit.prevent="salvar">
          <div class="modal-header">
            <h5 class="modal-title">{{ item ? 'Editar item' : 'Novo item do inventário' }}</h5>
            <button type="button" class="btn-close" @click="fechar"></button>
          </div>

          <div class="modal-body">
            <div v-if="erro" class="alert alert-danger">{{ erro }}</div>

            <div class="mb-3">
              <label for="item-nome" class="form-label">Nome <span class="text-danger">*</span></label>
              <input id="item-nome" v-model="form.nome" type="text" maxlength="100" class="form-control" required />
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="item-tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                <select id="item-tipo" v-model="form.tipo" class="form-select" required>
                  <option v-for="(rotulo, valor) in TIPOS_DE_ITEM" :key="valor" :value="valor">{{ rotulo }}</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label for="item-unidade" class="form-label">Unidade de medida <span class="text-danger">*</span></label>
                <input
                  id="item-unidade"
                  v-model="form.unidade_de_medida"
                  type="text"
                  maxlength="20"
                  list="unidades-sugeridas"
                  class="form-control"
                  placeholder="kg, litro, unidade..."
                  required
                />
                <datalist id="unidades-sugeridas">
                  <option v-for="u in UNIDADES_SUGERIDAS" :key="u" :value="u" />
                </datalist>
              </div>
            </div>

            <div class="mb-3">
              <label for="item-local" class="form-label">Pertence a <span class="text-danger">*</span></label>
              <input v-if="item" id="item-local" :value="local(item)" class="form-control" disabled />
              <select v-else id="item-local" v-model="form.local" class="form-select" required>
                <option value="" disabled>Selecione</option>
                <option v-for="opcao in opcoesDeLocal" :key="opcao.valor" :value="opcao.valor">{{ opcao.rotulo }}</option>
              </select>
              <div v-if="item" class="form-text">O local de um item não muda depois de cadastrado.</div>
            </div>

            <div class="row">
              <div v-if="!item" class="col-md-6 mb-3">
                <label for="item-quantidade" class="form-label">Quantidade inicial</label>
                <input id="item-quantidade" v-model="form.quantidade" type="number" step="0.001" min="0" class="form-control" />
                <div class="form-text">Depois do cadastro, o estoque só muda por movimentação.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="item-minima" class="form-label">Quantidade mínima</label>
                <input id="item-minima" v-model="form.quantidade_minima" type="number" step="0.001" min="0" class="form-control" />
                <div class="form-text">Abaixo disso o item aparece como "estoque baixo" (opcional).</div>
              </div>
            </div>

            <div class="mb-3">
              <label for="item-descricao" class="form-label">Descrição</label>
              <textarea id="item-descricao" v-model="form.descricao" rows="2" class="form-control"></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="fechar">Cancelar</button>
            <button type="submit" class="btn btn-success" :disabled="salvando">
              {{ salvando ? 'Salvando...' : 'Salvar' }}
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
import { TIPOS_DE_ITEM } from '@/store/modules/inventario'

const UNIDADES_SUGERIDAS = ['kg', 'g', 'litro', 'ml', 'unidade', 'pacote', 'saco', 'metro']

// Abre para criar (item = null) ou editar (item = objeto). As listas de associações e hortas
// vêm da tela que abre o modal, para não buscar de novo a cada abertura.
export default {
  name: 'ItemDoInventarioModal',
  props: {
    aberto: { type: Boolean, default: false },
    item: { type: Object, default: null },
    associacoes: { type: Array, default: () => [] },
    hortas: { type: Array, default: () => [] },
    nomeAssociacao: { type: Function, required: true },
    local: { type: Function, required: true }
  },
  emits: ['fechar', 'salvo'],
  setup(props, { emit }) {
    const store = useStore()
    const salvando = ref(false)
    const erro = ref('')

    const form = reactive({
      nome: '',
      tipo: 'insumo',
      unidade_de_medida: '',
      local: '',
      quantidade: '',
      quantidade_minima: '',
      descricao: ''
    })

    const cargo = computed(() => store.getters['auth/currentUser']?.cargo_slug)

    // Admin de horta só cadastra na própria horta; os demais também podem cadastrar na associação.
    const opcoesDeLocal = computed(() => {
      const opcoesHorta = props.hortas.map(h => ({ valor: `horta:${h.uuid}`, rotulo: `Horta: ${h.nome_da_horta}` }))
      if (cargo.value === 'admin_horta_geral') return opcoesHorta
      const opcoesAssociacao = props.associacoes.map(a => ({ valor: `associacao:${a.uuid}`, rotulo: `Associação: ${props.nomeAssociacao(a)}` }))
      return [...opcoesAssociacao, ...opcoesHorta]
    })

    // Preenche o formulário sempre que o modal abre.
    watch(() => props.aberto, aberto => {
      if (!aberto) return
      erro.value = ''
      const i = props.item
      Object.assign(form, {
        nome: i?.nome || '',
        tipo: i?.tipo || 'insumo',
        unidade_de_medida: i?.unidade_de_medida || '',
        local: !i && opcoesDeLocal.value.length === 1 ? opcoesDeLocal.value[0].valor : '',
        quantidade: '',
        quantidade_minima: i?.quantidade_minima ?? '',
        descricao: i?.descricao || ''
      })
    })

    const numeroOuNulo = v => (v === '' || v === null ? null : Number(v))

    const fechar = () => emit('fechar')

    const salvar = async () => {
      erro.value = ''
      salvando.value = true

      const dados = {
        nome: form.nome,
        tipo: form.tipo,
        unidade_de_medida: form.unidade_de_medida,
        quantidade_minima: numeroOuNulo(form.quantidade_minima),
        descricao: form.descricao || null
      }

      let result
      if (props.item) {
        result = await store.dispatch('inventario/updateItem', { id: props.item.uuid, data: dados })
      } else {
        const [tipoLocal, uuidLocal] = form.local.split(':')
        dados[`${tipoLocal}_uuid`] = uuidLocal
        dados.quantidade = numeroOuNulo(form.quantidade) ?? 0
        result = await store.dispatch('inventario/createItem', dados)
      }

      salvando.value = false
      if (!result.success) {
        erro.value = result.message
        return
      }
      emit('salvo', result.data)
    }

    return { form, salvando, erro, opcoesDeLocal, fechar, salvar, TIPOS_DE_ITEM, UNIDADES_SUGERIDAS }
  }
}
</script>
