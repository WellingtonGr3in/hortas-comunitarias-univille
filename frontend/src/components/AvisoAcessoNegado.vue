<template>
  <div v-if="mensagem" class="aviso-acesso-negado alert alert-warning alert-dismissible shadow" role="alert">
    <strong>Sem permissão.</strong> {{ mensagem }}
    <button type="button" class="btn-close" aria-label="Fechar" @click="fechar"></button>
  </div>
</template>

<script>
// Mostra um aviso quando a API responde 403 ou quando o router bloqueia uma página.
const PADRAO = 'Você não tem permissão para isso. Se precisar, fale com o administrador da sua horta.'

export default {
  name: 'AvisoAcessoNegado',
  data() {
    return { mensagem: '', timer: null }
  },
  mounted() {
    window.addEventListener('acesso-negado', this.mostrar)
  },
  beforeUnmount() {
    window.removeEventListener('acesso-negado', this.mostrar)
    clearTimeout(this.timer)
  },
  methods: {
    mostrar(evento) {
      this.mensagem = evento.detail?.mensagem || PADRAO
      clearTimeout(this.timer)
      this.timer = setTimeout(this.fechar, 6000)
    },
    fechar() {
      this.mensagem = ''
    }
  }
}
</script>

<style scoped>
.aviso-acesso-negado {
  position: fixed;
  top: 1rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2000;
  width: min(560px, calc(100% - 2rem));
}
</style>
