// Quem pode o quê no site, conforme o Manual do Usuário (docs/MANUAL_USUARIO.md).
// A API é quem garante a permissão; aqui só escondemos o que o perfil não pode usar.
import { jwtDecode } from 'jwt-decode'

export const PLATAFORMA = ['admin_plataforma']
export const ADMIN_ASSOCIACAO = ['admin_plataforma', 'admin_associacao_geral']
export const ADMINS = ['admin_plataforma', 'admin_associacao_geral', 'admin_horta_geral']
export const TODOS = [...ADMINS, 'canteirista']

export const NOMES_DOS_CARGOS = {
  admin_plataforma: 'Administrador da plataforma',
  admin_associacao_geral: 'Administrador de associação',
  admin_horta_geral: 'Administrador de horta',
  canteirista: 'Canteirista'
}

export function ehAdmin(cargoSlug) {
  return ADMINS.includes(cargoSlug)
}

// Rota sem meta.roles fica aberta para qualquer pessoa logada
export function podeAcessar(rota, cargoSlug) {
  const roles = rota?.meta?.roles
  return !roles || roles.includes(cargoSlug)
}

// Mostra o aviso de acesso negado (componente AvisoAcessoNegado, no App.vue)
export function avisarAcessoNegado(mensagem) {
  window.dispatchEvent(new CustomEvent('acesso-negado', { detail: { mensagem } }))
}

// Cargo do usuário logado, lido do token (mesma fonte que o guard do router usa)
export function cargoDoToken() {
  try {
    const token = localStorage.getItem('token')
    return token ? jwtDecode(token).cargo_slug || null : null
  } catch {
    return null
  }
}
