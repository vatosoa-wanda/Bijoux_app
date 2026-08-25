import axios from 'axios';

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { 'Content-Type': 'application/json' },
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    const message = error.response?.data?.message ?? 'Une erreur est survenue.';
    const validationErrors = error.response?.data?.errors ?? null;

    return Promise.reject({ status, message, validationErrors, raw: error });
  }
);

export default apiClient;