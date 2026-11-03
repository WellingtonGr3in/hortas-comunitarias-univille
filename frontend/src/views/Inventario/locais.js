import { ref } from 'vue'
import api from '@/services/api'

// A API do inventário devolve só os UUIDs da associação/horta; os nomes vêm de /associacoes e /hortas.
// Cada cargo só recebe o que pode ver, então as listas também servem de opções no formulário.
export function useLocaisDoInventario() {
  const associacoes = ref([])
  const hortas = ref([])

  const carregarLocais = async () => {
    const [resAssociacoes, resHortas] = await Promise.allSettled([api.get('/associacoes'), api.get('/hortas')])
    // /associacoes já devolve formatado ({ id, nome }), diferente das outras rotas.
    if (resAssociacoes.status === 'fulfilled') {
      associacoes.value = (resAssociacoes.value.data || []).map(a => ({ ...a, uuid: a.uuid || a.id }))
    }
    if (resHortas.status === 'fulfilled') hortas.value = resHortas.value.data || []
  }

  const nomeAssociacao = a => a.nome || a.nome_fantasia || a.razao_social || 'Associação'

  const local = item => {
    if (item.horta_uuid) {
      const horta = hortas.value.find(h => h.uuid === item.horta_uuid)
      return horta ? `Horta: ${horta.nome_da_horta}` : 'Horta'
    }
    const associacao = associacoes.value.find(a => a.uuid === item.associacao_uuid)
    return associacao ? `Associação: ${nomeAssociacao(associacao)}` : 'Associação'
  }

  return { associacoes, hortas, carregarLocais, nomeAssociacao, local }
}
