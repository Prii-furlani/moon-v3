// src/lib/api.ts
const BASE_URL = import.meta.env.VITE_API_URL || '/backend/api';

interface RequestOptions extends RequestInit {
    body?: any;
}

export async function fetchApi<T>(endpoint: string, options: RequestOptions = {}): Promise<T> {
    const { body, headers, ...rest } = options;
    
    const config: RequestInit = {
        ...rest,
        credentials: 'include', // Necessário para enviar cookies PHPSESSID
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...headers,
        },
    };
    
    if (body) {
        config.body = JSON.stringify(body);
    }
    
    const response = await fetch(`${BASE_URL}${endpoint}`, config);
    
    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        if (response.status === 401) {
            // Disparar evento global para o AuthContext interceptar
            window.dispatchEvent(new CustomEvent('auth-error', { detail: errorData }));
        }
        throw new Error(errorData.message || 'Erro na requisição da API');
    }
    
    return response.json();
}
