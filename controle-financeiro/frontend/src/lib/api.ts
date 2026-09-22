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
    const responseText = await response.text();
    
    let data;
    try {
        data = JSON.parse(responseText);
    } catch (e) {
        console.error(`-> O servidor retornou texto/HTML em vez de JSON no endpoint ${endpoint}. Prévia:`, responseText.substring(0, 300));
        throw new Error(`O servidor retornou erro HTTP ${response.status}. Verifique se o caminho da API está correto.`);
    }
    
    if (!response.ok) {
        if (response.status === 401) {
            // Disparar evento global para o AuthContext interceptar
            window.dispatchEvent(new CustomEvent('auth-error', { detail: data }));
        }
        throw new Error(data.message || 'Erro na requisição da API');
    }
    
    return data;
}
