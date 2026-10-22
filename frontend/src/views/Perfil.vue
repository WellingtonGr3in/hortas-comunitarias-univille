<template>
  <div class="container mt-4" style="max-width: 640px">
    <h2 class="fw-bold mb-1">Meu perfil</h2>
    <p class="text-muted mb-4">
      {{ ehCanteirista
        ? 'Você pode corrigir seu e-mail e telefone. Para mudar outros dados, fale com o administrador da sua horta.'
        : 'Seus dados de acesso ao sistema.' }}
    </p>

    <div class="card shadow-sm">
      <div class="card-body">
        <dl class="row mb-4">
          <dt class="col-sm-4">Nome</dt>
          <dd class="col-sm-8">{{ usuario?.nome || '—' }}</dd>
          <dt class="col-sm-4">Perfil</dt>
          <dd class="col-sm-8">{{ nomeDoCargo }}</dd>
        </dl>

        <form @submit.prevent="salvar" novalidate>
          <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input id="email" v-model.trim="form.email" type="email" class="form-control" :class="{ 'is-invalid': erros.email }" />
            <div v-if="erros.email" class="invalid-feedback">{{ erros.email }}</div>
          </div>

          <div v-if="ehCanteirista" class="mb-3">
            <label for="telefone" class="form-label">Telefone</label>
            <input id="telefone" v-model.trim="form.telefone" type="tel" class="form-control" placeholder="(47) 99999-9999" :class="{ 'is-invalid': erros.telefone }" />
            <div v-if="erros.telefone" class="invalid-feedback">{{ erros.telefone }}</div>
          </div>

          <div v-if="mensagem" class="alert" :class="sucesso ? 'alert-success' : 'alert-danger'">{{ mensagem }}</div>

          <button type="submit" class="btn btn-success" :disabled="salvando">
            {{ salvando ? 'Salvando...' : 'Salvar alterações' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/services/api'
import { NOMES_DOS_CARGOS } from '@/utils/permissoes'

export default {
  name: 'Perfil',
  data() {
    return {
      form: { email: '', telefone: '' },
      erros: {},
      mensagem: '',
      sucesso: false,
      salvando: false
    }
  },
  computed: {
    usuario() {
      return this.$store.getters['auth/currentUser']
    },
    nomeDoCargo() {
      return NOMES_DOS_CARGOS[this.$store.getters['auth/cargoSlug']] || '—'
    },
    ehCanteirista() {
      return this.$store.getters['auth/cargoSlug'] === 'canteirista'
    }
  },
  async mounted() {
    this.form.email = this.usuario?.email || ''
    if (this.ehCanteirista) {
      // O telefone fica no cadastro de canteirista; a API devolve só o do próprio usuário
      try {
        const { data } = await api.get('/canteiristas')
        this.form.telefone = data?.[0]?.telefone || ''
      } catch {
        this.form.telefone = ''
      }
    }
  },
  methods: {
    validar() {
      this.erros = {}
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
        this.erros.email = 'Informe um e-mail válido'
      }
      if (this.ehCanteirista && this.form.telefone) {
        const digitos = this.form.telefone.replace(/\D/g, '')
        if (digitos.length < 10 || digitos.length > 11) {
          this.erros.telefone = 'Informe um telefone com DDD'
        }
      }
      return Object.keys(this.erros).length === 0
    },
    async salvar() {
      this.mensagem = ''
      if (!this.validar()) return

      const dados = { email: this.form.email }
      if (this.ehCanteirista && this.form.telefone) {
        dados.telefone = this.form.telefone
      }

      this.salvando = true
      try {
        await api.put(`/usuarios/${this.usuario.id}`, dados)
        this.$store.commit('auth/SET_USER', { ...this.usuario, email: dados.email })
        this.sucesso = true
        this.mensagem = 'Perfil atualizado.'
      } catch (error) {
        const data = error.response?.data || {}
        this.sucesso = false
        this.mensagem = data.error || data.message || 'Não foi possível salvar. Tente de novo.'
      } finally {
        this.salvando = false
      }
    }
  }
}
</script>
