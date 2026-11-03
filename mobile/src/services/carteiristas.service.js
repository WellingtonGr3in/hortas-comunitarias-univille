import api from './api';

/**
 * Service para gerenciar carteiristas
 * (usado principalmente para selects em Pagamentos e Dependentes)
 */

export const getAll = async () => {
  try {
    const response = await api.get('/canteiristas');
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getById = async (id) => {
  try {
    const response = await api.get(`/canteiristas/${id}`);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const create = async (carteiristaData) => {
  try {
    const response = await api.post('/canteiristas', carteiristaData);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const update = async (id, carteiristaData) => {
  try {
    const response = await api.put(`/canteiristas/${id}`, carteiristaData);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const remove = async (id) => {
  try {
    const response = await api.delete(`/canteiristas/${id}`);
    return response.data;
  } catch (error) {
    throw error;
  }
};
