<?php

namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Models\AssociacaoModel;
use App\Repositories\AssociacaoRepository;
use App\Repositories\HortaRepository;
use Respect\Validation\Validator as v;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use Ramsey\Uuid\Uuid;

/**
 * Quem pode o quê:
 * - admin_plataforma: cadastra, corrige, aprova e exclui qualquer associação.
 * - demais cargos: só veem a própria associação (a do token ou a da sua horta).
 * Criar também é liberado no cadastro público (signUp), que roda como NEW_ACCOUNT.
 */
class AssociacaoService
{
    protected AssociacaoRepository $associacaoRepository;
    protected CargoService $cargoService;
    protected HortaRepository $hortaRepository;

    public function __construct(
        AssociacaoRepository $associacaoRepository,
        CargoService $cargoService,
        HortaRepository $hortaRepository
    ) {
        $this->associacaoRepository = $associacaoRepository;
        $this->cargoService = $cargoService;
        $this->hortaRepository = $hortaRepository;
    }

    public function findAllWhere(array $payloadUsuarioLogado): Collection
    {
        $associacoes = $this->associacaoRepository->findAllWhere(['excluido' => 0]);
        if ($this->isCargoAdminPlataforma($payloadUsuarioLogado)) {
            return $associacoes;
        }

        $permitidas = $this->associacoesDoEscopo($payloadUsuarioLogado);
        return $associacoes->filter(fn($a) => in_array($a->uuid, $permitidas, true))->values();
    }

    public function findByUuid(string $uuid, array $payloadUsuarioLogado): ?AssociacaoModel
    {
        $associacao = $this->associacaoRepository->findByUuid($uuid);
        if (!$associacao || $associacao->excluido) {
            throw new Exception('Associação não encontrada');
        }

        if (!$this->isCargoAdminPlataforma($payloadUsuarioLogado)
            && !in_array($associacao->uuid, $this->associacoesDoEscopo($payloadUsuarioLogado), true)) {
            throw new AcessoNegadoException('Você não tem permissão para acessar esta associação');
        }
        return $associacao;
    }

    public function create(array $data, array $payloadUsuarioLogado): AssociacaoModel
    {
        $cadastroPublico = ($payloadUsuarioLogado['usuario_uuid'] ?? null) === 'NEW_ACCOUNT';
        if (!$cadastroPublico && !$this->isCargoAdminPlataforma($payloadUsuarioLogado)) {
            throw new AcessoNegadoException('Somente o administrador da plataforma pode cadastrar associações');
        }

        $guarded = ['uuid', 'usuario_criador_uuid', 'data_de_criacao', 'data_de_ultima_alteracao'];
        foreach ($guarded as $g) unset($data[$g]);

        // Mapear campo 'nome' para 'razao_social' se necessário
        if (isset($data['nome']) && !isset($data['razao_social'])) {
            $data['razao_social'] = $data['nome'];
            $data['nome_fantasia'] = $data['nome'];
            unset($data['nome']);
        }

        // Mapear campo 'telefone' para 'telefone_de_contato' se necessário
        if (isset($data['telefone']) && !isset($data['telefone_de_contato'])) {
            $data['telefone_de_contato'] = $data['telefone'];
            unset($data['telefone']);
        }

        // Validação mínima - apenas nome é obrigatório (após o mapeamento)
        if (empty($data['razao_social'])) {
            throw new Exception("Nome da associação é obrigatório");
        }

        // Gerar CNPJ temporário se não fornecido (para não violar constraint UNIQUE)
        if (!isset($data['cnpj']) || empty($data['cnpj'])) {
            // $data['cnpj'] = 'TEMP-' . time() . '-' . rand(1000, 9999); // corrigido, essa geração quebrava o tamanho definido
            $data['cnpj'] = substr(date('ymdHis') . rand(10, 99), 0, 14);
        }

        // Definir valores padrão para campos opcionais
        if (!isset($data['status_aprovacao'])) {
            $data['status_aprovacao'] = 1;
        }

        $data['uuid'] = Uuid::uuid1()->toString();
        $data['usuario_criador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];
        if (array_key_exists('endereco', $data)) {
            $data['endereco_texto'] = $data['endereco'];
            unset($data['endereco']);
        }
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        return $this->associacaoRepository->create($data);
    }

    public function update(string $uuid, array $data, array $payloadUsuarioLogado): AssociacaoModel
    {
        if (!$this->isCargoAdminPlataforma($payloadUsuarioLogado)) {
            throw new AcessoNegadoException('Somente o administrador da plataforma pode alterar ou aprovar associações');
        }

        $associacao = $this->associacaoRepository->findByUuid($uuid);
        if (!$associacao || $associacao->excluido) {
            throw new Exception('Associação não encontrada');
        }

        $guarded = ['uuid', 'usuario_criador_uuid', 'data_de_criacao', 'data_de_ultima_alteracao'];
        foreach ($guarded as $g) unset($data[$g]);

        // Mapear campo 'nome' para 'razao_social' se necessário
        if (isset($data['nome']) && !isset($data['razao_social'])) {
            $data['razao_social'] = $data['nome'];
            $data['nome_fantasia'] = $data['nome'];
            unset($data['nome']);
        }

        // Mapear campo 'telefone' para 'telefone_de_contato' se necessário
        if (isset($data['telefone']) && !isset($data['telefone_de_contato'])) {
            $data['telefone_de_contato'] = $data['telefone'];
            unset($data['telefone']);
        }

        if (array_key_exists('endereco', $data)) {
            $data['endereco_texto'] = $data['endereco'];
            unset($data['endereco']);
        }
        $data['usuario_alterador_uuid'] = $payloadUsuarioLogado['usuario_uuid'];

        return $this->associacaoRepository->update($associacao, $data);
    }

    public function delete(string $uuid, array $payloadUsuarioLogado)
    {
        if (!$this->isCargoAdminPlataforma($payloadUsuarioLogado)) {
            throw new AcessoNegadoException('Somente o administrador da plataforma pode excluir associações');
        }

        $associacao = $this->associacaoRepository->findByUuid($uuid);
        if (!$associacao || $associacao->excluido) {
            throw new Exception('Associação não encontrada');
        }

        $data = [
            'excluido' => 1,
            'usuario_alterador_uuid' => $payloadUsuarioLogado['usuario_uuid'],
        ];

        return $this->associacaoRepository->delete($associacao, $data) ? true : false;
    }

    private function isCargoAdminPlataforma(array $payloadUsuarioLogado): bool
    {
        if (empty($payloadUsuarioLogado['cargo_uuid'])) {
            return false;
        }
        return $this->cargoService->findByUuidInternal($payloadUsuarioLogado['cargo_uuid'])->slug === "admin_plataforma";
    }

    // A associação do token e a associação da horta do token
    private function associacoesDoEscopo(array $payloadUsuarioLogado): array
    {
        $associacoes = [];
        if (!empty($payloadUsuarioLogado['associacao_uuid'])) {
            $associacoes[] = $payloadUsuarioLogado['associacao_uuid'];
        }
        if (!empty($payloadUsuarioLogado['horta_uuid'])) {
            $horta = $this->hortaRepository->findByUuid($payloadUsuarioLogado['horta_uuid']);
            if ($horta && !$horta->excluido && $horta->associacao_vinculada_uuid) {
                $associacoes[] = $horta->associacao_vinculada_uuid;
            }
        }
        return array_values(array_unique($associacoes));
    }
}
