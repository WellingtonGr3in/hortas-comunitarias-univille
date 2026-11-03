<template>
  <div class="cadastro-root d-flex min-vh-100 m-0">
    <div class="left-panel d-none d-md-flex align-items-center justify-content-center">
      <div class="d-flex flex-column align-items-center text-center text-white px-4">
        <h2 class="mb-3">Hortas Comunitárias Univille</h2>
        <p class="lead w-75">Cadastre sua associação e comece a gerenciar hortas, canteiros e canteiristas</p>
      </div>
    </div>

    <div class="right-panel d-flex align-items-center justify-content-center w-100">
      <div class="card cadastro-card shadow rounded-4">
        <div class="card-body">
          <h3 class="text-center mb-1">Criar conta</h3>
          <p class="text-center text-muted mb-4">Cadastre a associação e o administrador responsável</p>

          <div v-if="errorMessage" class="alert alert-danger" role="alert">{{ errorMessage }}</div>

          <form novalidate @submit.prevent="handleCadastro">
            <fieldset :disabled="loading">
              <legend class="secao">Associação</legend>
              <FormInput id="razao_social" v-model="form.razao_social" label="Razão social" :required="true" :error="errors.razao_social" />
              <FormInput id="nome_fantasia" v-model="form.nome_fantasia" label="Nome fantasia" hint="Opcional. Se vazio, usamos a razão social." />
              <FormInput
                id="cnpj"
                :model-value="form.cnpj"
                @update:model-value="v => (form.cnpj = mascaraCnpj(v))"
                label="CNPJ"
                placeholder="00.000.000/0000-00"
                :required="true"
                :error="errors.cnpj"
              />

              <legend class="secao mt-4">Administrador</legend>
              <FormInput id="nome_completo" v-model="form.nome_completo" label="Nome completo" :required="true" :error="errors.nome_completo" />
              <FormInput id="apelido" v-model="form.apelido" label="Como prefere ser chamado" :required="true" :error="errors.apelido" />
              <FormInput
                id="cpf"
                :model-value="form.cpf"
                @update:model-value="v => (form.cpf = mascaraCpf(v))"
                label="CPF"
                placeholder="000.000.000-00"
                :required="true"
                :error="errors.cpf"
              />
              <FormInput id="data_de_nascimento" v-model="form.data_de_nascimento" label="Data de nascimento" type="date" :required="true" :error="errors.data_de_nascimento" />
              <FormInput id="email" v-model="form.email" label="E-mail" type="email" placeholder="seu@email.com" :required="true" :error="errors.email" />
              <FormInput id="senha" v-model="form.senha" label="Senha" type="password" hint="Mínimo de 6 caracteres" :required="true" :error="errors.senha" />
              <FormInput id="confirmar_senha" v-model="form.confirmar_senha" label="Confirme a senha" type="password" :required="true" :error="errors.confirmar_senha" />

              <button type="submit" class="w-100 mt-3 cadastro-cta">
                {{ loading ? 'Criando conta...' : 'Criar conta' }}
              </button>
            </fieldset>
          </form>

          <div class="mt-3 text-center small">
            Já tem conta? <router-link to="/login" class="text-success">Entrar</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import FormInput from '@/components/FormInput.vue'
import { validarCpf, validarCnpj, mascaraCpf, mascaraCnpj } from '@/utils/documentos'

const CAMPOS = ['razao_social', 'cnpj', 'nome_completo', 'apelido', 'cpf', 'data_de_nascimento', 'email', 'senha', 'confirmar_senha']

export default {
  name: 'Cadastro',
  components: { FormInput },
  setup() {
    const store = useStore()
    const router = useRouter()

    const form = reactive({
      razao_social: '',
      nome_fantasia: '',
      cnpj: '',
      nome_completo: '',
      apelido: '',
      cpf: '',
      data_de_nascimento: '',
      email: '',
      senha: '',
      confirmar_senha: ''
    })
    const errors = reactive(Object.fromEntries(CAMPOS.map(c => [c, ''])))
    const loading = ref(false)
    const errorMessage = ref('')

    const limparErros = () => {
      CAMPOS.forEach(c => { errors[c] = '' })
      errorMessage.value = ''
    }

    // Data local em YYYY-MM-DD (toISOString usaria UTC e viraria o dia às 21h no Brasil)
    const hojeISO = () => {
      const d = new Date()
      const pad = n => String(n).padStart(2, '0')
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
    }

    const validateForm = () => {
      limparErros()
      const obrigatorio = 'Campo obrigatório'

      if (!form.razao_social.trim()) errors.razao_social = obrigatorio
      if (!form.cnpj) errors.cnpj = obrigatorio
      else if (!validarCnpj(form.cnpj)) errors.cnpj = 'CNPJ inválido'

      if (!form.nome_completo.trim()) errors.nome_completo = obrigatorio
      if (!form.apelido.trim()) errors.apelido = obrigatorio
      if (!form.cpf) errors.cpf = obrigatorio
      else if (!validarCpf(form.cpf)) errors.cpf = 'CPF inválido'

      if (!form.data_de_nascimento) errors.data_de_nascimento = obrigatorio
      else if (form.data_de_nascimento > hojeISO()) errors.data_de_nascimento = 'Data no futuro'

      if (!form.email.trim()) errors.email = obrigatorio
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) errors.email = 'E-mail inválido'

      if (form.senha.length < 6) errors.senha = 'A senha precisa ter pelo menos 6 caracteres'
      if (form.confirmar_senha !== form.senha) errors.confirmar_senha = 'As senhas não conferem'

      return CAMPOS.every(c => !errors[c])
    }

    // Leva o erro da API para o campo certo: 409 diz qual documento já existe; 422 traz details por campo
    const mostrarErroDaApi = result => {
      if (result.status === 409) {
        const msg = result.message || ''
        if (/e-mail/i.test(msg)) errors.email = msg
        else if (/CPF/.test(msg)) errors.cpf = msg
        else if (/CNPJ/.test(msg)) errors.cnpj = msg
        else errorMessage.value = msg
        return
      }
      if (result.status === 422 && result.details) {
        let algumCampo = false
        Object.keys(result.details).forEach(campo => {
          if (campo in errors) {
            errors[campo] = 'Valor inválido'
            algumCampo = true
          }
        })
        errorMessage.value = algumCampo ? 'Confira os campos destacados.' : result.message
        return
      }
      errorMessage.value = result.message || 'Não foi possível criar a conta'
    }

    const handleCadastro = async () => {
      if (!validateForm()) return

      loading.value = true
      const email = form.email.trim()
      const result = await store.dispatch('auth/cadastro', {
        associacao: {
          cnpj: form.cnpj,
          razao_social: form.razao_social.trim(),
          nome_fantasia: form.nome_fantasia.trim() || form.razao_social.trim()
        },
        usuario: {
          nome_completo: form.nome_completo.trim(),
          apelido: form.apelido.trim(),
          cpf: form.cpf,
          data_de_nascimento: form.data_de_nascimento,
          email,
          senha: form.senha
        }
      })

      if (!result.success) {
        loading.value = false
        mostrarErroDaApi(result)
        return
      }

      // Conta criada: entra direto com as credenciais que acabou de digitar
      const login = await store.dispatch('auth/login', { email, senha: form.senha })
      loading.value = false
      router.push(login.success ? '/' : { path: '/login', query: { email } })
    }

    return { form, errors, loading, errorMessage, handleCadastro, mascaraCpf, mascaraCnpj }
  }
}
</script>

<style scoped>
.left-panel { flex: 0 0 40%; background: linear-gradient(135deg, #00c853 0%, #00a152 100%); }
.right-panel { flex: 1; padding: 32px 16px; }
.cadastro-card { width: 100%; max-width: 560px; border-radius: 16px; }
.cadastro-card .card-body { padding: 32px; }
.cadastro-card h3 { font-size: 20px; font-weight: 700; }
.secao { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #0f9d58; margin-bottom: 12px; }

.cadastro-cta {
  height: 52px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 16px;
  color: #fff;
  background-color: #0f9d58;
  border: none;
}
.cadastro-cta:hover { background-color: #0e8f4e; }
.cadastro-cta:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
