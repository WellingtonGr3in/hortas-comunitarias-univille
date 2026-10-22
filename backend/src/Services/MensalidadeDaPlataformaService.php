<?php
namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Models\MensalidadeDaPlataformaModel;
use App\Repositories\MensalidadeDaPlataformaRepository;
use Respect\Validation\Validator as v;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\Uuid;

/**
 * Códigos de status (coluna `status` de mensalidades_da_plataforma e mensalidades_da_associacao),
 * conforme docs/db/README.md e frontend/src/store/modules/mensalidades.js:
 *   0 = aguardando pagamento (em aberto)
 *   1 = pago
 *   2 = compensado/concluído
 *   3 = cancelado
 *   4 = em atraso
 */
class MensalidadeDaPlataformaService
{
    public const STATUS_AGUARDANDO_PAGAMENTO = 0;
    public const STATUS_PAGO = 1;
    public const STATUS_COMPENSADO = 2;
    public const STATUS_CANCELADO = 3;
    public const STATUS_EM_ATRASO = 4;

    protected MensalidadeDaPlataformaRepository $mensalidadeDaPlataformaRepository;
    protected PlanoService $planoService;
    protected UsuarioService $usuarioService;
    protected CargoService $cargoService;

    public function __construct(
        MensalidadeDaPlataformaRepository $mensalidadeDaPlataformaRepository,
        PlanoService $planoService,
        UsuarioService $usuarioService,
        CargoService $cargoService
        
    ){
        $this->mensalidadeDaPlataformaRepository = $mensalidadeDaPlataformaRepository;
        $this->planoService = $planoService;
        $this->usuarioService = $usuarioService;
        $this->cargoService = $cargoService;
    }

    public function findAllWhere(array $payloadUsuarioLogado): Collection {
        if (!$this->isCargoAdminPlataforma($payloadUsuarioLogado) &&
            !$this->isCargoAdminASsociacao($payloadUsuarioLogado)) {
            throw new AcessoNegadoException("Permissão de cargo 0 necessária | findAllWhere");
        } else if ($this->isCargoAdminPlataforma($payloadUsuarioLogado)) {
            return $this->mensalidadeDaPlataformaRepository->findAllWhere(['excluido' => 0]);
        } else {
            return $this->mensalidadeDaPlataformaRepository->findByAssociacaoUuid($payloadUsuarioLogado['associacao_uuid']);
        }
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado): ?MensalidadeDaPlataformaModel {
        if (!$this->isCargoAdminPlataforma($payloadUsuarioLogado) &&
            !$this->isCargoAdminASsociacao($payloadUsuarioLogado)) {

                
                throw new AcessoNegadoException("Permissão de cargo 0 necessária | findByUuid");
        } else { 
            $mensalidadeDaPlataforma = $this->mensalidadeDaPlataformaRepository->findByUuid($uuid);
            if(!$mensalidadeDaPlataforma || $mensalidadeDaPlataforma->excluido){
                throw new Exception('Mensalidade da Plataforma não encontrado');
            }
            $this->checkEscopoAssociacao($mensalidadeDaPlataforma, $payloadUsuarioLogado);
            return $mensalidadeDaPlataforma;
        }
    }

    public function findByUsuarioUuid(string $usuarioUuid, array $payloadUsuarioLogado): Collection
    {
        if (!$this->isCargoAdminPlataforma($payloadUsuarioLogado)) {
            throw new AcessoNegadoException("Permissão de cargo 0 necessária | findByUsuarioUuid");
        } else { 
            $usuario = $this->usuarioService->findByUuid($usuarioUuid, $payloadUsuarioLogado);
            if (!$usuario || $usuario->excluido) {
                throw new Exception('Usuário não encontrado');
            }
    
            $mensalidades = $this->mensalidadeDaPlataformaRepository->findByUsuarioUuid($usuarioUuid);
            if ($mensalidades->isEmpty()) {
                throw new Exception('Mensalidades de plataforma do usuário não encontradas');
            }
            return $mensalidades;
        }
    }

    public function create(array $data, array $payloadUsuarioLogado): MensalidadeDaPlataformaModel {
        if($payloadUsuarioLogado['usuario_uuid'] == "NEW_ACCOUNT"){
            v::key('valor_em_centavos', v::intType()->positive())
            ->key('usuario_uuid', v::uuid())
            ->key('plano_uuid', v::uuid()) 
            ->check($data);

            $data['url_anexo'] = null;
            $data['url_recibo'] = null; 

            if (!empty($data['plano_uuid'])){
                $this->planoService->findByUuid($data['plano_uuid'], $payloadUsuarioLogado);
            }

            if (!empty($data['usuario_uuid'])){
                $this->usuarioService->findByUuid($data['usuario_uuid'], $payloadUsuarioLogado);
            }


            $guarded = ['uuid', 'data_de_criacao', 'data_de_ultima_alteracao'];
            foreach ($guarded as $g) unset($data[$g]);

            $data['uuid'] = Uuid::uuid1()->toString(); 

            return $this->mensalidadeDaPlataformaRepository->create($data);
        } else if (!$this->isCargoAdminPlataforma($payloadUsuarioLogado)) {
            throw new AcessoNegadoException("Permissão de cargo 0 necessária | create");
        } else { 
            $data['status'] = $data['status'] ?? self::STATUS_AGUARDANDO_PAGAMENTO;
            $data['dias_atraso'] = $data['dias_atraso'] ?? 0;
            foreach (['plano_uuid', 'data_pagamento', 'url_anexo', 'url_recibo'] as $campo) {
                if (array_key_exists($campo, $data) && $data[$campo] === '') $data[$campo] = null;
            }

            v::key('valor_em_centavos', v::intType()->positive())
            ->key('usuario_uuid', v::uuid())
            ->key('plano_uuid', v::optional(v::uuid()), false)
            ->key('data_vencimento',  v::date('Y-m-d'))
            ->key('data_pagamento', v::optional(v::date('Y-m-d')), false)
            ->key('status', v::intType()->between(0, 4))
            ->key('dias_atraso', v::intType()->min(0))
            ->key('url_recibo', v::optional(v::url()), false)
            ->key('url_anexo', v::optional(v::url()), false)
            ->check($data);

            if (!empty($data['plano_uuid'])){
                $this->planoService->findByUuid($data['plano_uuid'], $payloadUsuarioLogado);
            }

            if (!empty($data['usuario_uuid'])){
                $this->usuarioService->findByUuid($data['usuario_uuid'], $payloadUsuarioLogado);
            }


            $guarded = ['uuid', 'usuario_criador_uuid', 'data_de_criacao', 'data_de_ultima_alteracao'];
            foreach ($guarded as $g) unset($data[$g]);

            $data['uuid'] = Uuid::uuid1()->toString();
            $data['usuario_criador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];
            $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

            return $this->mensalidadeDaPlataformaRepository->create($data);
        }
    }

    public function update(string $uuid, array $data, array $payloadUsuarioLogado): MensalidadeDaPlataformaModel {

        if (!$this->isCargoAdminPlataforma($payloadUsuarioLogado) &&
            !$this->isCargoAdminASsociacao($payloadUsuarioLogado)) {
            throw new AcessoNegadoException("Permissão de cargo 0,1 necessária | update");
        } else {

            $mensalidadeDaPlataforma = $this->mensalidadeDaPlataformaRepository->findByUuid($uuid);
            if(!$mensalidadeDaPlataforma || $mensalidadeDaPlataforma->excluido){
                throw new Exception('Mensalidade da Plataforma não encontrado');
            }

            if ($this->isCargoAdminASsociacao($payloadUsuarioLogado)) {
                // Admin da associação só registra o pagamento das mensalidades da própria associação
                $this->checkEscopoAssociacao($mensalidadeDaPlataforma, $payloadUsuarioLogado);
                $data = array_intersect_key($data, array_flip(['status', 'data_pagamento', 'url_recibo']));
            }

            v::key('valor_em_centavos', v::intType()->positive(), false)
            ->key('usuario_uuid', v::uuid(), false)
            ->key('plano_uuid', v::optional(v::uuid()), false)
            ->key('data_vencimento',  v::date('Y-m-d'), false)
            ->key('data_pagamento', v::optional(v::date('Y-m-d')), false)
            ->key('status', v::intType()->between(0, 4), false)
            ->key('dias_atraso', v::intType()->min(0), false)
            ->key('url_anexo', v::optional(v::url()), false)
            ->key('url_recibo', v::optional(v::url()), false)
              ->check($data);
    
            if (!empty($data['plano_uuid'])){
                $this->planoService->findByUuid($data['plano_uuid'], $payloadUsuarioLogado);
            }
    
            if (!empty($data['usuario_uuid'])){
                $this->usuarioService->findByUuid($data['usuario_uuid'], $payloadUsuarioLogado);
            }
            $guarded = ['uuid', 'usuario_criador_uuid', 'data_de_criacao', 'data_de_ultima_alteracao', 'excluido'];
            foreach ($guarded as $g) unset($data[$g]);

            $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];
    
            return $this->mensalidadeDaPlataformaRepository->update($mensalidadeDaPlataforma, $data);
        }
    }

    public function delete(string $uuid, array $payloadUsuarioLogado): bool {
        if (!$this->isCargoAdminPlataforma($payloadUsuarioLogado)) {
            throw new AcessoNegadoException("Permissão de cargo 0 necessária | delete");
        } else {
            $mensalidadeDaPlataforma = $this->mensalidadeDaPlataformaRepository->findByUuid($uuid);
            if (!$mensalidadeDaPlataforma || $mensalidadeDaPlataforma->excluido) {
                throw new Exception('Mensalidade da Plataforma não encontrado');
            }
    
            $data = [
                'excluido' => 1,
                'usuario_alterador_uuid' => $payloadUsuarioLogado['usuario_uuid'],
            ];
    
            return $this->mensalidadeDaPlataformaRepository->delete($mensalidadeDaPlataforma, $data);
        }
    }

    private function checkEscopoAssociacao(MensalidadeDaPlataformaModel $mensalidade, array $payloadUsuarioLogado): void
    {
        if ($this->isCargoAdminPlataforma($payloadUsuarioLogado)) {
            return;
        }
        $usuario = $mensalidade->usuario;
        if (!$usuario || $usuario->associacao_uuid !== $payloadUsuarioLogado['associacao_uuid']) {
            throw new Exception('Mensalidade da Plataforma não encontrado');
        }
    }

    private function isCargoAdminPlataforma(array $payloadUsuarioLogado): bool
    {
        return $this->cargoService->findByUuidInternal($payloadUsuarioLogado['cargo_uuid'])->slug === "admin_plataforma";
    }

    private function isCargoAdminASsociacao(array $payloadUsuarioLogado): bool
    {
        return $this->cargoService->findByUuidInternal($payloadUsuarioLogado['cargo_uuid'])->slug === "admin_associacao_geral";
    }
}
