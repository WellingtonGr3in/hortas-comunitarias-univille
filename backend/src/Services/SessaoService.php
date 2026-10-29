<?php
namespace App\Services;

use App\Exceptions\AcessoNegadoException;
use App\Exceptions\CredenciaisInvalidasException;
use App\Exceptions\RecursoNaoEncontradoException;
use App\Models\CargoModel;
use App\Services\UsuarioService;
use App\Services\AssociacaoService;
use App\Services\MensalidadeDaPlataformaService;
use App\Services\PlanoService;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Capsule\Manager as Capsule;

class SessaoService
{
    // Hash bcrypt (custo 12, como as senhas reais) de um valor aleatório. Usado quando o e-mail
    // não existe, para o login levar o mesmo tempo e não revelar quais e-mails estão cadastrados.
    private const HASH_PARA_EMAIL_INEXISTENTE = '$2y$12$3p1NHxC0HRkGcRKqI4QRQeXHx6xulZak2IRNXeHl7iEZEDanpCW2.';

    public function __construct(
        private UsuarioService $usuarioService,
        private AssociacaoService $associacaoService,
        private CargoModel $cargoModel,
        private MensalidadeDaPlataformaService $mensalidadeService,
        private PlanoService $planoService,
        private Capsule $capsule
    ) {}

    public function signIn(string $email, string $senha, array $payloadUsuarioLogado): string
    {
        try {
            $usuario = $this->usuarioService->findByEmail($email);
        } catch (RecursoNaoEncontradoException) {
            $usuario = null;
        }

        // E-mail inexistente e senha errada respondem igual, com a mesma mensagem e o mesmo custo
        $senhaConfere = password_verify($senha, $usuario->senha ?? self::HASH_PARA_EMAIL_INEXISTENTE);
        if (!$usuario || !$senhaConfere) {
            throw new CredenciaisInvalidasException();
        }

        // Só chega aqui quem acertou e-mail e senha, então a mensagem própria não vaza nada
        if (in_array((string) $usuario->status_de_acesso, ['inativo', 'bloqueado', '0'], true)) {
            throw new AcessoNegadoException('Acesso do usuário bloqueado');
        }

        $payload = [
            'usuario_uuid' => $usuario->uuid,
            'cargo_uuid' => $usuario->cargo_uuid,
            'cargo_slug' => $this->cargoModel->find($usuario->cargo_uuid)?->slug,
            'associacao_uuid' => $usuario->associacao_uuid,
            'horta_uuid' => $usuario->horta_uuid,
            'exp' => time() + 7200,
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }
    public function signUp(array $data): array
    {
        $uuidSistema = "NEW_ACCOUNT";



        return $this->capsule->connection()->transaction(function () use ($data, $uuidSistema) {
            $payloadMinimo = [
                'usuario_uuid' => null,
                'cargo_uuid' => null,
                'associacao_uuid' => null,
                'horta_uuid' => null
            ];


            $associacaoData = $data['associacao'] ?? [];
            $usuarioData = $data['usuario'] ?? [];

            $payloadMinimo["usuario_uuid"] = $uuidSistema;

            $cargo = $this->cargoModel->firstOrCreate(['slug' => 'admin_associacao_geral']);
            $usuarioData['cargo_uuid'] = $cargo->uuid;

            $associacao = $this->associacaoService->create($associacaoData, $payloadMinimo);
            $usuarioData['associacao_uuid'] = $associacao->uuid;

            $payloadMinimo['cargo_uuid'] =$cargo->uuid;
            $payloadMinimo['associacao_uuid'] = $associacao->uuid;

            $usuario = $this->usuarioService->create($usuarioData, $uuidSistema, $payloadMinimo);

            $plano = $this->planoService->findBySlug('plano_bronze', $payloadMinimo);

            $hoje = new \DateTime();
            $dataVencimento = $hoje->modify('+30 days')->format('Y-m-d');

            $mensalidadeData = [
                'valor_em_centavos' => $plano->valor_em_centavos,
                'usuario_uuid' => $usuario->uuid,
                'plano_uuid' => $plano->uuid,
                'data_vencimento' => $dataVencimento,
                'data_pagamento' => null,
                'status' => 1,
                'dias_atraso' => 0,
                'usuario_criador_uuid' => $usuario->uuid,
                'usuario_alterador_uuid' => $usuario->uuid,
            ];

            $mensalidade = $this->mensalidadeService->create($mensalidadeData, $payloadMinimo);

            $payload = [
                'usuario_uuid' => $usuario->uuid,
                'cargo_uuid' => $usuario->cargo_uuid,
                'cargo_slug' => $cargo->slug,
                'associacao_uuid' => $usuario->associacao_uuid,
                'horta_uuid' => $usuario->horta_uuid,
                'exp' => time() + 7200,
            ];

            $token = \Firebase\JWT\JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

            return [
                'associacao' => $associacao,
                'usuario' => $usuario,
                'mensalidade' => $mensalidade,
                'token' => $token
            ];
        });
    }
}
