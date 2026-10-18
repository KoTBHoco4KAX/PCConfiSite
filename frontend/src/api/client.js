import axios from 'axios';

export const API_BASE_URL = '/api';
export const TOKEN_KEY = 'pc_configurator_token';
export const USER_KEY = 'pc_configurator_user';

const client = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 20000,
});

// Автоподстановка Bearer-токена
client.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Автоматический logout при 401
client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(USER_KEY);
      window.dispatchEvent(new Event('pc-configurator:unauthorized'));
    }
    return Promise.reject(error);
  }
);

/**
 * Универсальный экстрактор сообщений об ошибках Laravel.
 */
export function extractErrorMessages(error) {
  const data = error?.response?.data;
  if (!data) return ['Не удалось подключиться к серверу.'];

  if (typeof data === 'string') return [data];

  if (data.message && !data.errors) return [data.message];

  if (data.errors) {
    const out = [];
    Object.values(data.errors).forEach((v) => {
      if (Array.isArray(v)) out.push(...v);
      else out.push(String(v));
    });
    return out.length ? out : [data.message || 'Ошибка валидации.'];
  }

  return ['Неизвестная ошибка.'];
}

export default client;