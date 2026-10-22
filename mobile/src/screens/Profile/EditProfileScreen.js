import React, { useState, useEffect } from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Alert,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useAuth } from '../../contexts/AuthContext';
import { Button, Input } from '../../components/common';
import { COLORS } from '../../constants/colors';
import { validateEmail, validatePhone } from '../../utils/validators';
import { updateUsuario } from '../../services/usuarios.service';

const EditProfileScreen = ({ navigation }) => {
  const { user, updateUser } = useAuth();
  // O canteirista só pode alterar o próprio e-mail e telefone (Manual do Usuário)
  const [formData, setFormData] = useState({
    email: '',
    telefone: '',
  });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (user) {
      setFormData({
        email: user.email || '',
        telefone: user.telefone || '',
      });
    }
  }, [user]);

  const updateField = (field, value) => {
    setFormData({ ...formData, [field]: value });
    setErrors({ ...errors, [field]: '' });
  };

  const validate = () => {
    const newErrors = {};

    if (!formData.email.trim()) {
      newErrors.email = 'E-mail é obrigatório';
    } else if (!validateEmail(formData.email)) {
      newErrors.email = 'E-mail inválido';
    }

    if (formData.telefone.trim() && !validatePhone(formData.telefone)) {
      newErrors.telefone = 'Telefone inválido';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async () => {
    if (!validate()) return;

    setLoading(true);
    try {
      const dados = { email: formData.email.trim() };
      if (formData.telefone.trim()) {
        dados.telefone = formData.telefone.trim();
      }
      await updateUsuario(user.uuid, dados);

      const result = await updateUser({ ...user, ...dados });
      
      if (result.success) {
        Alert.alert('Sucesso', 'Perfil atualizado com sucesso', [
          { text: 'OK', onPress: () => navigation.goBack() }
        ]);
      } else {
        Alert.alert('Erro', result.message);
      }
    } catch (error) {
      Alert.alert('Erro', error.message || 'Ocorreu um erro ao atualizar o perfil');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.content}
        keyboardShouldPersistTaps="handled"
      >
        <Input
          label="E-mail"
          value={formData.email}
          onChangeText={(text) => updateField('email', text)}
          placeholder="seu@email.com"
          keyboardType="email-address"
          autoCapitalize="none"
          error={errors.email}
          leftIcon="mail-outline"
        />

        <Input
          label="Telefone"
          value={formData.telefone}
          onChangeText={(text) => updateField('telefone', text)}
          placeholder="(47) 99999-9999"
          keyboardType="phone-pad"
          error={errors.telefone}
          leftIcon="call-outline"
        />

        <Button
          title="Salvar Alterações"
          onPress={handleSubmit}
          loading={loading}
          style={styles.submitButton}
        />

        <Button
          title="Cancelar"
          onPress={() => navigation.goBack()}
          variant="outline"
          disabled={loading}
        />
      </ScrollView>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollView: {
    flex: 1,
  },
  content: {
    padding: 16,
  },
  submitButton: {
    marginTop: 8,
    marginBottom: 12,
  },
});

export default EditProfileScreen;
