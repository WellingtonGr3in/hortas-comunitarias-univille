<template>
  <div class="container-fluid px-4 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div>
        <h2 class="fw-bold mb-1">Gestão de Usuários</h2>
        <p class="text-muted mb-0">Consulte os usuários, seus vínculos e status de acesso.</p>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-success" @click="abrirModalCriar">
          <i class="fas fa-plus me-2" aria-hidden="true"></i>Novo usuário
        </button>
        <button type="button" class="btn btn-outline-success" :disabled="isLoading" @click="carregarDados">
          <i class="fas fa-rotate-right me-2" aria-hidden="true"></i>Atualizar
        </button>
      </div>
    </div>

    <div class="row g-3 mb-4" :aria-busy="isLoading">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <p class="text-muted small mb-1">Total de usuários</p>
            <h3 class="fw-bold text-success mb-0">{{ isLoading ? '—' : total }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <p class="text-muted small mb-2">Distribuição por cargo</p>
            <span v-if="isLoading" class="text-muted">—</span>
            <ul v-else-if="distribuicaoCargos.length" class="list-unstyled small mb-0">
              <li v-for="cargo in distribuicaoCargos" :key="cargo.uuid" class="d-flex justify-content-between gap-2">
                <span>{{ cargo.nome }}</span><strong>{{ cargo.quantidade }}</strong>
              </li>
            </ul>
            <span v-else class="text-muted small">Nenhum usuário cadastrado.</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <p class="text-muted small mb-2">Ativos vs. inativos</p>
            <span v-if="isLoading" class="text-muted">—</span>
            <div v-else class="d-flex flex-wrap gap-3">
              <span class="text-success"><strong>{{ totalAtivos }}</strong> ativos</span>
              <span class="text-secondary"><strong>{{ total - totalAtivos }}</strong> inativos</span>
            </div>
            <p class="text-muted small mt-2 mb-0">Inativos incluem bloqueados, suspensos e pendentes.</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">
      {{ error }}
      <button type="button" class="btn btn-sm btn-outline-danger ms-2" :disabled="isLoading" @click="carregarDados">
        Tentar novamente
      </button>
    </div>
    <output v-if="avisoReferencias" class="alert alert-warning">{{ avisoReferencias }}</output>

    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <div class="row align-items-center g-3">
          <div class="col-md-9">
            <FormInput
              id="usuarios-busca"
              v-model="busca"
              label="Buscar usuários"
              type="search"
              placeholder="Nome, e-mail ou CPF"
              :disabled="isLoading"
            />
          </div>
          <div class="col-md-3">
            <button
              type="button"
              class="btn btn-outline-secondary w-100"
              aria-controls="usuarios-filtros"
              :aria-expanded="mostrarFiltros"
              @click="mostrarFiltros = !mostrarFiltros"
            >
              <i class="fas fa-filter me-2" aria-hidden="true"></i>Filtros
            </button>
          </div>
        </div>
        <div v-if="mostrarFiltros" id="usuarios-filtros" class="row g-3 border-top pt-3">
          <div class="col-md-4">
            <label for="usuarios-cargo" class="form-label">Cargo</label>
            <select id="usuarios-cargo" v-model="filtros.cargo" class="form-select" :disabled="isLoading">
              <option value="">Todos os cargos</option>
              <option v-for="item in opcoesCargos" :key="item.uuid" :value="item.uuid">{{ item.nome }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="usuarios-associacao" class="form-label">Associação</label>
            <select id="usuarios-associacao" v-model="filtros.associacao" class="form-select" :disabled="isLoading">
              <option value="">Todas as associações</option>
              <option v-for="item in opcoesAssociacoes" :key="item.uuid" :value="item.uuid">{{ item.nome }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="usuarios-horta" class="form-label">Horta</label>
            <select id="usuarios-horta" v-model="filtros.horta" class="form-select" :disabled="isLoading">
              <option value="">Todas as hortas</option>
              <option v-for="item in opcoesHortas" :key="item.uuid" :value="item.uuid">{{ item.nome }}</option>
            </select>
          </div>
          <div class="col-12 text-end">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="limparFiltros">Limpar busca e filtros</button>
          </div>
        </div>
      </div>
    </div>

    <output v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-success" aria-hidden="true"></div>
      <p class="mt-2">Carregando usuários...</p>
    </output>
    <div v-else-if="!error" class="card border-0 shadow-sm">
      <div class="px-4 py-3 border-bottom">
        <h6 class="mb-0 fw-semibold">Lista de usuários — {{ listaFiltrada.length }} de {{ total }}</h6>
      </div>
      <output v-if="!listaFiltrada.length" class="text-center py-5 text-muted">
        <i class="fas fa-users fa-2x mb-3 d-block opacity-25" aria-hidden="true"></i>
        <p>{{ total ? 'Nenhum usuário corresponde à busca e aos filtros.' : 'Nenhum usuário cadastrado.' }}</p>
        <button v-if="total" type="button" class="btn btn-outline-secondary btn-sm" @click="limparFiltros">Limpar busca e filtros</button>
      </output>
      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <caption class="visually-hidden">Usuários cadastrados, seus vínculos e status de acesso</caption>
          <thead class="table-light">
            <tr>
              <th scope="col" class="px-4">Nome</th>
              <th scope="col">E-mail</th>
              <th scope="col">CPF</th>
              <th scope="col">Cargo</th>
              <th scope="col">Associação</th>
              <th scope="col">Horta</th>
              <th scope="col">Status</th>
              <th scope="col" class="text-end px-4">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="usuario in listaFiltrada" :key="usuario.uuid">
              <td class="px-4 fw-semibold">{{ usuario.nome_completo || usuario.apelido || '—' }}</td>
              <td>{{ usuario.email || '—' }}</td>
              <td class="text-nowrap">{{ formatarCpf(usuario.cpf) }}</td>
              <td>{{ nomeVinculo(usuario, 'cargo') }}</td>
              <td>{{ nomeVinculo(usuario, 'associacao') }}</td>
              <td>{{ nomeVinculo(usuario, 'horta') }}</td>
              <td>
                <span class="badge" :class="estaAtivo(usuario) ? 'bg-success' : 'bg-secondary'">{{ nomeStatus(usuario) }}</span>
              </td>
              <td class="text-end px-4 text-nowrap">
                <button type="button" class="btn btn-sm btn-link text-secondary p-1 me-1" title="Visualizar" @click="abrirModalVisualizar(usuario)">
                  <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
                <button type="button" class="btn btn-sm btn-link text-secondary p-1 me-1" title="Editar" @click="abrirModalEditar(usuario)">
                  <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                </button>
                <button type="button" class="btn btn-sm btn-link text-danger p-1" title="Excluir" @click="abrirModalExcluir(usuario)">
                  <i class="fas fa-trash" aria-hidden="true"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="modalCriar" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:680px">
        <div class="modal-content border-0 shadow">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Cadastrar usuário</h5>
            <button type="button" class="btn-close" aria-label="Fechar" @click="modalCriar = false"></button>
          </div>
          <div class="modal-body">
            <div v-if="erroCriar" class="alert alert-danger py-2 small" role="alert">{{ erroCriar }}</div>
            <div class="row g-3">
              <div class="col-md-6">
                <FormInput id="usuario-criar-cpf" :model-value="form.cpf" label="CPF" placeholder="000.000.000-00" :required="true" :error="errosCriar.cpf" @update:model-value="form.cpf = mascararCpf($event)" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-criar-nome" v-model="form.nome_completo" label="Nome completo" :required="true" :error="errosCriar.nome_completo" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-criar-email" v-model="form.email" label="E-mail" type="email" :required="true" :error="errosCriar.email" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-criar-senha" v-model="form.senha" label="Senha (mínimo 6 caracteres)" type="password" :required="true" :error="errosCriar.senha" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-criar-data" v-model="form.data_de_nascimento" label="Data de nascimento" type="date" :required="true" :error="errosCriar.data_de_nascimento" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-criar-apelido" v-model="form.apelido" label="Apelido" :required="true" :error="errosCriar.apelido" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-criar-ausencia" v-model="form.dias_ausente" label="Dias de ausência" type="number" :required="true" :error="errosCriar.dias_ausente" />
              </div>
              <div class="col-md-6">
                <label for="usuario-criar-cargo" class="form-label">Cargo</label>
                <select id="usuario-criar-cargo" v-model="form.cargo_uuid" class="form-select" :class="{ 'is-invalid': errosCriar.cargo_uuid }">
                  <option value="">Sem cargo</option>
                  <option v-for="item in referencias.cargo" :key="item.uuid" :value="item.uuid">{{ item.nome || item.slug }}</option>
                </select>
                <div v-if="errosCriar.cargo_uuid" class="invalid-feedback">{{ errosCriar.cargo_uuid }}</div>
              </div>
              <div class="col-md-6">
                <label for="usuario-criar-associacao" class="form-label">Associação</label>
                <select id="usuario-criar-associacao" v-model="form.associacao_uuid" class="form-select" :class="{ 'is-invalid': errosCriar.associacao_uuid }">
                  <option value="">Sem associação</option>
                  <option v-for="item in referencias.associacao" :key="item.uuid" :value="item.uuid">{{ nomeOpcao(item, 'associacao') }}</option>
                </select>
                <div v-if="errosCriar.associacao_uuid" class="invalid-feedback">{{ errosCriar.associacao_uuid }}</div>
              </div>
              <div class="col-md-6">
                <label for="usuario-criar-horta" class="form-label">Horta</label>
                <select id="usuario-criar-horta" v-model="form.horta_uuid" class="form-select" :class="{ 'is-invalid': errosCriar.horta_uuid }">
                  <option value="">Sem horta</option>
                  <option v-for="item in referencias.horta" :key="item.uuid" :value="item.uuid">{{ nomeOpcao(item, 'horta') }}</option>
                </select>
                <div v-if="errosCriar.horta_uuid" class="invalid-feedback">{{ errosCriar.horta_uuid }}</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" :disabled="salvando" @click="modalCriar = false">Cancelar</button>
            <button type="button" class="btn btn-success" :disabled="salvando" @click="salvarCriacao">
              <span v-if="salvando" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Cadastrar
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="modalVisualizar" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:560px">
        <div class="modal-content border-0 shadow">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Detalhes do usuário</h5>
            <button type="button" class="btn-close" aria-label="Fechar" @click="modalVisualizar = false"></button>
          </div>
          <div v-if="usuarioSelecionado" class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><p class="text-muted small mb-1">Nome completo</p><p class="fw-semibold mb-0">{{ usuarioSelecionado.nome_completo || '—' }}</p></div>
              <div class="col-md-6"><p class="text-muted small mb-1">Apelido</p><p class="fw-semibold mb-0">{{ usuarioSelecionado.apelido || '—' }}</p></div>
              <div class="col-md-6"><p class="text-muted small mb-1">CPF</p><p class="fw-semibold mb-0">{{ formatarCpf(usuarioSelecionado.cpf) }}</p></div>
              <div class="col-md-6"><p class="text-muted small mb-1">E-mail</p><p class="fw-semibold mb-0">{{ usuarioSelecionado.email || '—' }}</p></div>
              <div class="col-md-6"><p class="text-muted small mb-1">Data de nascimento</p><p class="fw-semibold mb-0">{{ formatarData(usuarioSelecionado.data_de_nascimento) }}</p></div>
              <div class="col-md-6"><p class="text-muted small mb-1">Dias de ausência</p><p class="fw-semibold mb-0">{{ usuarioSelecionado.dias_ausente ?? '—' }}</p></div>
              <div class="col-md-6"><p class="text-muted small mb-1">Cargo</p><p class="fw-semibold mb-0">{{ nomeVinculo(usuarioSelecionado, 'cargo') }}</p></div>
              <div class="col-md-6"><p class="text-muted small mb-1">Status</p><span class="badge" :class="estaAtivo(usuarioSelecionado) ? 'bg-success' : 'bg-secondary'">{{ nomeStatus(usuarioSelecionado) }}</span></div>
              <div class="col-md-6"><p class="text-muted small mb-1">Associação</p><p class="fw-semibold mb-0">{{ nomeVinculo(usuarioSelecionado, 'associacao') }}</p></div>
              <div class="col-md-6"><p class="text-muted small mb-1">Horta</p><p class="fw-semibold mb-0">{{ nomeVinculo(usuarioSelecionado, 'horta') }}</p></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="modalVisualizar = false">Fechar</button>
            <button type="button" class="btn btn-success" @click="abrirEdicaoSelecionado"><i class="fas fa-pen-to-square me-2" aria-hidden="true"></i>Editar</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="modalEditar" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:680px">
        <div class="modal-content border-0 shadow">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Editar usuário</h5>
            <button type="button" class="btn-close" aria-label="Fechar" @click="modalEditar = false"></button>
          </div>
          <div class="modal-body">
            <div v-if="erroEditar" class="alert alert-danger py-2 small" role="alert">{{ erroEditar }}</div>
            <div class="row g-3">
              <div class="col-md-6">
                <FormInput id="usuario-editar-cpf" :model-value="formEditar.cpf" label="CPF" placeholder="000.000.000-00" :required="true" :error="errosEditar.cpf" @update:model-value="formEditar.cpf = mascararCpf($event)" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-editar-nome" v-model="formEditar.nome_completo" label="Nome completo" :required="true" :error="errosEditar.nome_completo" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-editar-email" v-model="formEditar.email" label="E-mail" type="email" :required="true" :error="errosEditar.email" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-editar-senha" v-model="formEditar.senha" label="Nova senha (opcional)" type="password" :error="errosEditar.senha" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-editar-data" v-model="formEditar.data_de_nascimento" label="Data de nascimento" type="date" :required="true" :error="errosEditar.data_de_nascimento" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-editar-apelido" v-model="formEditar.apelido" label="Apelido" :required="true" :error="errosEditar.apelido" />
              </div>
              <div class="col-md-6">
                <FormInput id="usuario-editar-ausencia" v-model="formEditar.dias_ausente" label="Dias de ausência" type="number" :required="true" :error="errosEditar.dias_ausente" />
              </div>
              <div class="col-md-6">
                <label for="usuario-editar-cargo" class="form-label">Cargo</label>
                <select id="usuario-editar-cargo" v-model="formEditar.cargo_uuid" class="form-select" :class="{ 'is-invalid': errosEditar.cargo_uuid }">
                  <option value="">Sem alteração de cargo</option>
                  <option v-for="item in referencias.cargo" :key="item.uuid" :value="item.uuid">{{ item.nome || item.slug }}</option>
                </select>
                <div v-if="errosEditar.cargo_uuid" class="invalid-feedback">{{ errosEditar.cargo_uuid }}</div>
              </div>
              <div class="col-md-6">
                <label for="usuario-editar-associacao" class="form-label">Associação</label>
                <select id="usuario-editar-associacao" v-model="formEditar.associacao_uuid" class="form-select" :class="{ 'is-invalid': errosEditar.associacao_uuid }">
                  <option value="">Sem alteração de associação</option>
                  <option v-for="item in referencias.associacao" :key="item.uuid" :value="item.uuid">{{ nomeOpcao(item, 'associacao') }}</option>
                </select>
                <div v-if="errosEditar.associacao_uuid" class="invalid-feedback">{{ errosEditar.associacao_uuid }}</div>
              </div>
              <div class="col-md-6">
                <label for="usuario-editar-horta" class="form-label">Horta</label>
                <select id="usuario-editar-horta" v-model="formEditar.horta_uuid" class="form-select" :class="{ 'is-invalid': errosEditar.horta_uuid }">
                  <option value="">Sem alteração de horta</option>
                  <option v-for="item in referencias.horta" :key="item.uuid" :value="item.uuid">{{ nomeOpcao(item, 'horta') }}</option>
                </select>
                <div v-if="errosEditar.horta_uuid" class="invalid-feedback">{{ errosEditar.horta_uuid }}</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" :disabled="salvando" @click="modalEditar = false">Cancelar</button>
            <button type="button" class="btn btn-success" :disabled="salvando" @click="salvarEdicao">
              <span v-if="salvando" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Salvar
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="modalExcluir" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
      <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content border-0 shadow">
          <div class="modal-body text-center py-4 px-4">
            <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px">
              <i class="fas fa-trash-alt text-danger fs-4" aria-hidden="true"></i>
            </div>
            <h5 class="fw-bold mb-2">Excluir usuário</h5>
            <p class="text-muted mb-0">Tem certeza que deseja excluir <strong>{{ usuarioSelecionado?.nome_completo || usuarioSelecionado?.apelido }}</strong>?</p>
            <p class="text-muted small">Esta ação não pode ser desfeita.</p>
            <div v-if="erroExcluir" class="alert alert-danger py-2 small" role="alert">{{ erroExcluir }}</div>
          </div>
          <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
            <button type="button" class="btn btn-outline-secondary px-4" :disabled="salvando" @click="modalExcluir = false">Cancelar</button>
            <button type="button" class="btn btn-danger px-4" :disabled="salvando" @click="confirmarExclusao">
              <span v-if="salvando" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Excluir
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'
import FormInput from '@/components/FormInput.vue'
import associacoesService from '@/services/associacoes.service'
import cargosService from '@/services/cargos.service'
import hortasService from '@/services/hortas.service'
import { formatarCpf } from '@/utils/format'

export default {
  name: 'UsuariosList',
  components: { FormInput },
  setup() {
    const store = useStore()
    const busca = ref('')
    const mostrarFiltros = ref(false)
    const filtros = reactive({ cargo: '', associacao: '', horta: '' })
    const referencias = reactive({ cargo: [], associacao: [], horta: [] })
    const carregandoReferencias = ref(false)
    const avisoReferencias = ref('')
    const modalCriar = ref(false)
    const modalVisualizar = ref(false)
    const modalEditar = ref(false)
    const modalExcluir = ref(false)
    const usuarioSelecionado = ref(null)
    const salvando = ref(false)
    const erroCriar = ref('')
    const erroEditar = ref('')
    const erroExcluir = ref('')
    const errosCriar = ref({})
    const errosEditar = ref({})
    const formInicial = () => ({
      cpf: '', nome_completo: '', email: '', senha: '', data_de_nascimento: '',
      apelido: '', dias_ausente: '', cargo_uuid: '', associacao_uuid: '', horta_uuid: ''
    })
    const form = ref(formInicial())
    const formEditar = ref(formInicial())
    const usuarios = computed(() => store.getters['usuarios/allUsuarios'])
    const isLoading = computed(() => store.getters['usuarios/isLoading'] || carregandoReferencias.value)
    const error = computed(() => store.state.usuarios.error)
    const total = computed(() => usuarios.value.length)
    const normalizar = value => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim()
    const status = usuario => normalizar(usuario.status_de_acesso)
    const estaAtivo = usuario => ['1', 'ativo', 'true'].includes(status(usuario))
    const totalAtivos = computed(() => usuarios.value.filter(estaAtivo).length)
    const semVinculo = '__sem_vinculo__'
    const uuidVinculo = (usuario, tipo) => usuario[`${tipo}_uuid`] || usuario[tipo]?.uuid || semVinculo
    const camposNome = { cargo: ['nome', 'slug'], associacao: ['nome_fantasia', 'razao_social'], horta: ['nome_da_horta'] }

    function nomeVinculo(usuario, tipo) {
      const uuid = uuidVinculo(usuario, tipo)
      if (uuid === semVinculo) return `Sem ${tipo === 'associacao' ? 'associação' : tipo}`
      const vinculo = usuario[tipo] || referencias[tipo].find(item => item.uuid === uuid)
      return camposNome[tipo].map(campo => vinculo?.[campo]).find(Boolean) || uuid
    }

    function nomeOpcao(item, tipo) {
      if (tipo === 'associacao') return item.nome_fantasia || item.razao_social || item.nome || item.uuid
      if (tipo === 'horta') return item.nome_da_horta || item.nome || item.uuid
      return item.nome || item.slug || item.uuid
    }

    function mascararCpf(valor) {
      let digitos = String(valor ?? '').replace(/\D/g, '').slice(0, 11)
      digitos = digitos.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')
      return digitos
    }

    function formatarData(data) {
      if (!data) return '—'
      const valor = String(data).slice(0, 10)
      const dataLocal = new Date(`${valor}T00:00:00`)
      return Number.isNaN(dataLocal.getTime()) ? valor : dataLocal.toLocaleDateString('pt-BR')
    }

    function abrirModalCriar() {
      form.value = formInicial()
      errosCriar.value = {}
      erroCriar.value = ''
      modalCriar.value = true
    }

    function abrirModalVisualizar(usuario) {
      usuarioSelecionado.value = usuario
      modalVisualizar.value = true
    }

    function abrirModalEditar(usuario) {
      usuarioSelecionado.value = usuario
      formEditar.value = {
        cpf: mascararCpf(usuario.cpf),
        nome_completo: usuario.nome_completo || '',
        email: usuario.email || '',
        senha: '',
        data_de_nascimento: String(usuario.data_de_nascimento || '').slice(0, 10),
        apelido: usuario.apelido || '',
        dias_ausente: usuario.dias_ausente ?? '',
        cargo_uuid: usuario.cargo_uuid || usuario.cargo?.uuid || '',
        associacao_uuid: usuario.associacao_uuid || usuario.associacao?.uuid || '',
        horta_uuid: usuario.horta_uuid || usuario.horta?.uuid || ''
      }
      errosEditar.value = {}
      erroEditar.value = ''
      modalEditar.value = true
    }

    function abrirEdicaoSelecionado() {
      modalVisualizar.value = false
      if (usuarioSelecionado.value) abrirModalEditar(usuarioSelecionado.value)
    }

    function abrirModalExcluir(usuario) {
      usuarioSelecionado.value = usuario
      erroExcluir.value = ''
      modalExcluir.value = true
    }

    function validarFormulario(dados, edicao = false) {
      const erros = {}
      if (String(dados.cpf || '').replace(/\D/g, '').length !== 11) erros.cpf = 'Informe um CPF com 11 dígitos.'
      if (!dados.nome_completo.trim()) erros.nome_completo = 'Nome completo é obrigatório.'
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(dados.email)) erros.email = 'Informe um e-mail válido.'
      if (!edicao && String(dados.senha).length < 6) erros.senha = 'A senha deve ter pelo menos 6 caracteres.'
      if (edicao && dados.senha && dados.senha.length < 6) erros.senha = 'A senha deve ter pelo menos 6 caracteres.'
      if (!dados.data_de_nascimento) erros.data_de_nascimento = 'Data de nascimento é obrigatória.'
      if (!dados.apelido.trim()) erros.apelido = 'Apelido é obrigatório.'
      if (!Number.isInteger(Number(dados.dias_ausente)) || Number(dados.dias_ausente) < 1) erros.dias_ausente = 'Informe um número inteiro maior que zero.'
      return erros
    }

    function payloadUsuario(dados) {
      const payload = {
        cpf: String(dados.cpf).replace(/\D/g, ''),
        nome_completo: dados.nome_completo.trim(),
        email: dados.email.trim(),
        data_de_nascimento: dados.data_de_nascimento,
        apelido: dados.apelido.trim(),
        dias_ausente: Number(dados.dias_ausente)
      }
      if (dados.senha) payload.senha = dados.senha
      for (const campo of ['cargo_uuid', 'associacao_uuid', 'horta_uuid']) {
        if (dados[campo]) payload[campo] = dados[campo]
      }
      return payload
    }

    function aplicarErros(campoErros, details) {
      if (!details || typeof details !== 'object') return
      const normalizados = {}
      Object.entries(details).forEach(([campo, mensagem]) => {
        normalizados[campo] = Array.isArray(mensagem) ? mensagem.join('; ') : String(mensagem)
      })
      campoErros.value = normalizados
    }

    async function salvarCriacao() {
      errosCriar.value = validarFormulario(form.value)
      if (Object.keys(errosCriar.value).length) return
      salvando.value = true
      erroCriar.value = ''
      try {
        const resultado = await store.dispatch('usuarios/createUsuario', payloadUsuario(form.value))
        if (resultado.success) {
          modalCriar.value = false
        } else {
          erroCriar.value = resultado.message
          aplicarErros(errosCriar, resultado.details)
        }
      } finally {
        salvando.value = false
      }
    }

    async function salvarEdicao() {
      if (!usuarioSelecionado.value) return
      errosEditar.value = validarFormulario(formEditar.value, true)
      if (Object.keys(errosEditar.value).length) return
      salvando.value = true
      erroEditar.value = ''
      try {
        const resultado = await store.dispatch('usuarios/updateUsuario', {
          uuid: usuarioSelecionado.value.uuid,
          data: payloadUsuario(formEditar.value)
        })
        if (resultado.success) {
          modalEditar.value = false
        } else {
          erroEditar.value = resultado.message
          aplicarErros(errosEditar, resultado.details)
        }
      } finally {
        salvando.value = false
      }
    }

    async function confirmarExclusao() {
      if (!usuarioSelecionado.value) return
      salvando.value = true
      erroExcluir.value = ''
      try {
        const resultado = await store.dispatch('usuarios/deleteUsuario', usuarioSelecionado.value.uuid)
        if (resultado.success) {
          modalExcluir.value = false
          usuarioSelecionado.value = null
        } else {
          erroExcluir.value = resultado.message
        }
      } finally {
        salvando.value = false
      }
    }

    function opcoes(tipo) {
      const itens = new Map()
      usuarios.value.forEach(usuario => {
        const uuid = uuidVinculo(usuario, tipo)
        itens.set(uuid, { uuid, nome: nomeVinculo(usuario, tipo) })
      })
      return [...itens.values()].sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'))
    }

    const opcoesCargos = computed(() => opcoes('cargo'))
    const opcoesAssociacoes = computed(() => opcoes('associacao'))
    const opcoesHortas = computed(() => opcoes('horta'))
    const distribuicaoCargos = computed(() => opcoesCargos.value.map(cargo => ({
      ...cargo,
      quantidade: usuarios.value.filter(usuario => uuidVinculo(usuario, 'cargo') === cargo.uuid).length
    })))

    const listaFiltrada = computed(() => {
      const termo = normalizar(busca.value)
      const cpfBusca = /^[\d.\-\s]+$/.test(termo) ? termo.replace(/\D/g, '') : ''
      return usuarios.value.filter(usuario => {
        const correspondeBusca = !termo || [usuario.nome_completo, usuario.apelido, usuario.email, usuario.cpf]
          .some(value => normalizar(value).includes(termo)) ||
          (cpfBusca && String(usuario.cpf ?? '').replace(/\D/g, '').includes(cpfBusca))
        return correspondeBusca && Object.entries(filtros)
          .every(([tipo, uuid]) => !uuid || uuidVinculo(usuario, tipo) === uuid)
      })
    })

    function limparFiltros() {
      busca.value = ''
      Object.assign(filtros, { cargo: '', associacao: '', horta: '' })
    }

    function nomeStatus(usuario) {
      if (estaAtivo(usuario)) return 'Ativo'
      const nomes = { '0': 'Bloqueado', '2': 'Suspenso', '3': 'Pendente de aprovação', inativo: 'Inativo', bloqueado: 'Bloqueado', suspenso: 'Suspenso', pendente: 'Pendente de aprovação', false: 'Inativo' }
      return nomes[status(usuario)] || 'Não informado'
    }

    async function carregarReferencias() {
      carregandoReferencias.value = true
      avisoReferencias.value = ''
      try {
        const tipos = ['cargo', 'associacao', 'horta']
        const resultados = await Promise.allSettled([
          cargosService.getAll(), associacoesService.getAll(), hortasService.getAll()
        ])
        resultados.forEach((resultado, index) => {
          referencias[tipos[index]] = resultado.status === 'fulfilled' && Array.isArray(resultado.value.data)
            ? resultado.value.data : []
        })
        if (resultados.some(resultado => resultado.status === 'rejected' || !Array.isArray(resultado.value.data))) {
          avisoReferencias.value = 'Alguns nomes de cargos, associações ou hortas não puderam ser carregados. Seus identificadores serão exibidos quando necessário.'
        }
      } finally {
        carregandoReferencias.value = false
      }
    }

    async function carregarDados() {
      await Promise.all([store.dispatch('usuarios/fetchUsuarios'), carregarReferencias()])
    }

    onMounted(carregarDados)

    return {
      busca, mostrarFiltros, filtros, isLoading, error, total, totalAtivos,
      distribuicaoCargos, opcoesCargos, opcoesAssociacoes, opcoesHortas,
      listaFiltrada, avisoReferencias, nomeVinculo, estaAtivo, nomeStatus,
      referencias, form, formEditar, errosCriar, errosEditar,
      modalCriar, modalVisualizar, modalEditar, modalExcluir, usuarioSelecionado,
      salvando, erroCriar, erroEditar, erroExcluir,
      limparFiltros, carregarDados, formatarCpf, formatarData, nomeOpcao, mascararCpf,
      abrirModalCriar, abrirModalVisualizar, abrirModalEditar, abrirModalExcluir, abrirEdicaoSelecionado,
      salvarCriacao, salvarEdicao, confirmarExclusao
    }
  }
}
</script>

<style scoped>
.modal { overflow-y: auto; }
.btn-link { text-decoration: none; }
</style>
