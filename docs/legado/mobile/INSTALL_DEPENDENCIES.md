> [!NOTE]
> **Documento histórico.** Arquivado em 15/10/2026 (estava em `mobile/INSTALL_DEPENDENCIES.md`). Pode conter credenciais, URLs e instruções que não valem mais. A organização atual da documentação está no [DOCUMENTACAO.md](../../../DOCUMENTACAO.md).

# 📦 Dependências Adicionais - Mobile

## Instalação Necessária

Para que as novas features funcionem (Pagamentos, Dependentes), é necessário instalar:

```bash
cd mobile

# Picker para selects (iOS/Android nativo)
npx expo install @react-native-picker/picker

# DateTimePicker para seleção de datas (opcional, para futuras melhorias)
npx expo install @react-native-community/datetimepicker

# Restart do Expo
npx expo start --clear
```

## Dependências Já Instaladas

- `@react-native-async-storage/async-storage` ✅
- `@react-navigation/bottom-tabs` ✅
- `@react-navigation/native` ✅
- `@react-navigation/stack` ✅
- `axios` ✅
- `expo` ✅
- `react-native-gesture-handler` ✅
- `react-native-safe-area-context` ✅
- `react-native-screens` ✅

## Após Instalação

1. Limpar cache: `npx expo start --clear`
2. Se necessário, rebuild: `npx expo run:android` ou `npx expo run:ios`
