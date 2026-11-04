<?php

namespace App\Utils\Privacidade;

use App\Models\UsuarioModel;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Princípio do menor privilégio: quem vê qual dado pessoal (ver docs/MANUAL_USUARIO.md).
 *
 * - Titular: vê tudo o que é dele.
 * - Admin de horta e de associação: cuidam das pessoas, então veem o dado completo ao abrir um
 *   registro específico. Em listagens o CPF vem mascarado e a data de nascimento não vem.
 * - Admin da plataforma: cuida das associações, não das pessoas. CPF sempre mascarado.
 *
 * O escopo (horta/associação) continua sendo garantido pelos Services, que respondem 403 antes de
 * o registro chegar aqui. Cargo desconhecido cai no caso mais restrito.
 */
final class PoliticaDeDadosSensiveis
{
    private const CARGOS_GESTORES = ['admin_associacao_geral', 'admin_horta_geral'];

    // Único conjunto de campos de um usuário que sai para quem não pode ver o cadastro completo
    private const CAMPOS_DE_USUARIO_EM_LISTAS = [
        'uuid',
        'nome_completo',
        'apelido',
        'email',
        'cargo_uuid',
        'associacao_uuid',
        'horta_uuid',
        'status_de_acesso',
    ];

    public function __construct(
        private ?string $usuarioUuid,
        private ?string $cargoSlug
    ) {}

    public static function daRequisicao(Request $request): self
    {
        return new self($request->getAttribute('usuario_uuid'), $request->getAttribute('cargo_slug'));
    }

    // Mantém só os 6 dígitos centrais: ***.456.789-**
    public static function mascararCpf(?string $cpf): ?string
    {
        if ($cpf === null || $cpf === '') {
            return $cpf;
        }
        $digitos = preg_replace('/\D/', '', $cpf);
        if (strlen($digitos) !== 11) {
            return '***';
        }
        return '***.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-**';
    }

    public static function cpfEstaMascarado(mixed $cpf): bool
    {
        return is_string($cpf) && str_contains($cpf, '*');
    }

    public function podeVerRegistroCompleto(?string $titularUuid): bool
    {
        return $this->ehTitular($titularUuid) || in_array($this->cargoSlug, self::CARGOS_GESTORES, true);
    }

    public function cpf(?string $cpf, ?string $titularUuid, bool $detalhe): ?string
    {
        return $this->podeVerDadosPessoais($titularUuid, $detalhe) ? $cpf : self::mascararCpf($cpf);
    }

    // Data de nascimento, endereço e afins: completos ou ausentes, sem meio-termo
    public function dadoPessoal(mixed $valor, ?string $titularUuid, bool $detalhe): mixed
    {
        return $this->podeVerDadosPessoais($titularUuid, $detalhe) ? $valor : null;
    }

    public function usuario(UsuarioModel $usuario, bool $detalhe): array
    {
        $dados = $usuario->toArray();
        if ($this->podeVerDadosPessoais($usuario->uuid, $detalhe)) {
            return $dados;
        }

        $visiveis = array_intersect_key($dados, array_flip(self::CAMPOS_DE_USUARIO_EM_LISTAS));
        $visiveis['cpf'] = self::mascararCpf($usuario->cpf);
        return $visiveis;
    }

    private function podeVerDadosPessoais(?string $titularUuid, bool $detalhe): bool
    {
        return $this->ehTitular($titularUuid) || ($detalhe && $this->podeVerRegistroCompleto($titularUuid));
    }

    private function ehTitular(?string $titularUuid): bool
    {
        return $titularUuid !== null && $titularUuid === $this->usuarioUuid;
    }
}
