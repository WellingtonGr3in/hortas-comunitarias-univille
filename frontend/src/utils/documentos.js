// Validação e máscara de CPF e CNPJ (dígitos verificadores da Receita Federal)

export const apenasDigitos = valor => String(valor || '').replace(/\D/g, '')

// Dígito verificador: soma ponderada módulo 11; resto < 2 vira 0
const digitoVerificador = (digitos, pesos) => {
  const soma = pesos.reduce((acc, peso, i) => acc + Number(digitos[i]) * peso, 0)
  const resto = soma % 11
  return resto < 2 ? 0 : 11 - resto
}

// Sequências repetidas (000.000.000-00, 11.111.111/1111-11...) passam na conta, mas são inválidas
const todosIguais = digitos => /^(\d)\1+$/.test(digitos)

export const validarCpf = valor => {
  const cpf = apenasDigitos(valor)
  if (cpf.length !== 11 || todosIguais(cpf)) return false
  const d1 = digitoVerificador(cpf, [10, 9, 8, 7, 6, 5, 4, 3, 2])
  const d2 = digitoVerificador(cpf, [11, 10, 9, 8, 7, 6, 5, 4, 3, 2])
  return d1 === Number(cpf[9]) && d2 === Number(cpf[10])
}

export const validarCnpj = valor => {
  const cnpj = apenasDigitos(valor)
  if (cnpj.length !== 14 || todosIguais(cnpj)) return false
  const d1 = digitoVerificador(cnpj, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2])
  const d2 = digitoVerificador(cnpj, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2])
  return d1 === Number(cnpj[12]) && d2 === Number(cnpj[13])
}

// Máscaras aplicadas enquanto o usuário digita (mesmo formato gravado no banco pelo seed)
export const mascaraCpf = valor =>
  apenasDigitos(valor)
    .slice(0, 11)
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d{1,2})$/, '$1-$2')

export const mascaraCnpj = valor =>
  apenasDigitos(valor)
    .slice(0, 14)
    .replace(/(\d{2})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1/$2')
    .replace(/(\d{4})(\d{1,2})$/, '$1-$2')
