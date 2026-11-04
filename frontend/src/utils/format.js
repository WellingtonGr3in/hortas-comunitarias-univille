export const formatarCpf = (cpf) => {
    if (!cpf) return '-'
    const s = cpf.replace(/\D/g, '')
    if (s.length !== 11) return cpf
    return s.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}

export const cpfEstaMascarado = (cpf) => String(cpf ?? '').includes('*')

// Nas listagens o CPF vem mascarado (***.456.789-**): a busca compara só os dígitos visíveis
export const cpfCorresponde = (cpf, termo) => {
    const texto = String(termo ?? '').trim()
    if (!/^[\d.\-\s]+$/.test(texto)) return false
    const busca = texto.replace(/\D/g, '')
    const digitos = String(cpf ?? '').replace(/\D/g, '')
    if (!busca || !digitos) return false
    if (cpfEstaMascarado(cpf) && busca.length === 11) return busca.slice(3, 9) === digitos
    return digitos.includes(busca)
}
