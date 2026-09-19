<template>
  <div class="container mt-4">
    <div class="row">
      <div class="col-md-8 mx-auto">
        <div class="card shadow">
          <div class="card-body">
            <h2 class="mb-4">Novo Carteirista</h2>
            
            <div v-if="errorMessage" class="alert alert-danger">
              {{ errorMessage }}
            </div>
            
            <form @submit.prevent="handleSubmit">
              <div class="mb-3">
                <label for="horta_uuid" class="form-label">Horta *</label>
                <select id="horta_uuid" v-model="form.horta_uuid" class="form-select" required>
                  <option value="">Selecione uma horta</option>
                  <option v-for="h in hortas" :key="h.uuid" :value="h.uuid">{{ h.nome_da_horta || h.nome }}</option>
                </select>
              </div>
              <FormInput id="cpf" v-model="form.cpf" label="CPF" :required="true" />
              <FormInput id="email" v-model="form.email" label="E-mail" type="email" :required="true" />
              <FormInput id="senha" v-model="form.senha" label="Senha (mínimo 6 caracteres)" type="password" :required="true" />
              <FormInput id="data_de_nascimento" v-model="form.data_de_nascimento" label="Data de nascimento" type="date" :required="true" />
              <FormInput id="apelido" v-model="form.apelido" label="Apelido" :required="true" />
              <FormInput
                id="nome"
                v-model="form.nome"
                label="Nome"
                placeholder="Nome completo"
                :required="true"
                :error="errors.nome"
              />
              
              <div class="mb-3">
                <label for="telefone" class="form-label">Telefone <span class="text-danger">*</span></label>
                <input
                  id="telefone"
                  v-model="form.telefone"
                  type="tel"
                  class="form-control"
                  :class="{ 'is-invalid': errors.telefone }"
                  placeholder="(00) 00000-0000"
                  @input="formatTelefone"
                  maxlength="15"
                  required
                />
                <div v-if="errors.telefone" class="invalid-feedback">
                  {{ errors.telefone }}
                </div>
              </div>
              
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success" :disabled="loading">
                  <span v-if="loading">Salvando...</span>
                  <span v-else>Salvar</span>
                </button>
                <router-link to="/carteiristas" class="btn btn-secondary">
                  Cancelar
                </router-link>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { reactive, ref, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import FormInput from '@/components/FormInput.vue'

export default {
  name: 'CarteiristasCreate',
  components: { FormInput },
  setup() {
    const store = useStore()
    const router = useRouter()
    
    const form = reactive({
      nome: '',
      telefone: '', cpf: '', email: '', senha: '', data_de_nascimento: '', apelido: '', horta_uuid: ''
    })
    
    const hortas = ref([])
    onMounted(async () => {
      try { hortas.value = (await api.get('/hortas')).data }
      catch { errorMessage.value = 'Não foi possível carregar as hortas.' }
    })
    const errors = reactive({
      nome: '',
      telefone: ''
    })
    
    const loading = ref(false)
    const errorMessage = ref('')
    
    const validateForm = () => {
      let isValid = true
      errors.nome = ''
      errors.telefone = ''
      
      if (!form.nome || form.nome.trim() === '') {
        errors.nome = 'Nome é obrigatório'
        isValid = false
      } else if (form.nome.length < 3) {
        errors.nome = 'Nome deve ter no mínimo 3 caracteres'
        isValid = false
      }
      
      if (!form.telefone || form.telefone.trim() === '') {
        errors.telefone = 'Telefone é obrigatório'
        isValid = false
      } else {
        const phoneNumbers = form.telefone.replace(/\D/g, '')
        if (phoneNumbers.length !== 11) {
          errors.telefone = 'Telefone deve ter DDD (2 dígitos) + 9 dígitos'
          isValid = false
        } else if (phoneNumbers[0] === '0' || phoneNumbers[2] !== '9') {
          errors.telefone = 'DDD inválido ou número não é celular'
          isValid = false
        }
      }
      
      return isValid
    }
    
    const formatTelefone = (event) => {
      let value = event.target.value.replace(/\D/g, '').substring(0, 11)
      if (value.length > 0) {
        if (value.length <= 2) {
          value = `(${value}`
        } else if (value.length <= 7) {
          value = `(${value.substring(0, 2)}) ${value.substring(2)}`
        } else {
          value = `(${value.substring(0, 2)}) ${value.substring(2, 7)}-${value.substring(7)}`
        }
      }
      form.telefone = value
    }
    
    const handleSubmit = async () => {
      if (!validateForm()) {
        errorMessage.value = 'Por favor, corrija os erros no formulário'
        return
      }
      
      loading.value = true
      try {
        const res = await store.dispatch('carteiristas/createCarteirista', { ...form, nome_completo: form.nome })
        loading.value = false
        
        if (res.success) {
          router.push('/carteiristas')
        } else {
          errorMessage.value = res.message || 'Erro ao criar carteirista. Verifique se o backend está configurado corretamente.'
        }
      } catch (error) {
        loading.value = false
        errorMessage.value = 'Não foi possível cadastrar o canteirista. Verifique os dados e tente novamente.'
      }
    }
    
    return {
      form,
      hortas,
      errors,
      loading,
      errorMessage,
      handleSubmit,
      formatTelefone
    }
  }
}
</script>

