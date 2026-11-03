export const formatarCpf = (cpf) => {
    if (!cpf) return '-'
    const s = cpf.replace(/\D/g, '')
    if (s.length !== 11) return cpf
    return s.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}