import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import { fetchApi } from '@/lib/api';
import { AuthView } from '@/views/auth/AuthView';
import { useIdleTimer } from '@/hooks/useIdleTimer';
import { toast } from '@/components/ui/toast';

interface User {
  id: number;
  nome: string;
  sobrenome: string;
  email: string;
  avatar: string | null;
  onboarding_completo: boolean;
}

interface AuthContextData {
  user: User | null;
  setUser: (user: User | null) => void;
  logout: (silent?: boolean) => void;
  isLoading: boolean;
}

const AuthContext = createContext<AuthContextData>({} as AuthContextData);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const logout = async (silent = false) => {
    try {
      await fetchApi('/auth/logout.php', { method: 'POST' });
    } catch (e) {
      // Ignora erro no logout
    }
    setUser(null);
    if (!silent) {
      toast.add({
        title: 'Sessão encerrada',
        description: 'Você saiu da sua conta.',
      });
    }
  };

  useIdleTimer({
    timeout: 3600000, // 1 hora
    onIdle: () => {
      if (user) {
        logout(true);
        toast.add({
          type: 'error',
          title: 'Sessão expirada',
          description: 'Sessão expirada por inatividade. Por favor, faça login novamente.',
        });
      }
    }
  });

  useEffect(() => {
    const checkSession = async () => {
      try {
        const data = await fetchApi<any>('/auth/verify_session.php');
        if (data.status === 'success') {
          // Precisamos buscar os dados completos do usuário ou usar do verify_session
          // O ideal era o verify_session retornar o usuário todo. Por hora, apenas evitamos o loop de login.
          // Para este mock, setamos um usuário vazio com o ID.
          setUser({
            id: data.user_id,
            nome: 'Usuário',
            sobrenome: '',
            email: '',
            avatar: null,
            onboarding_completo: true
          });
        }
      } catch (error: any) {
        // Sessão inválida ou expirada
      } finally {
        setIsLoading(false);
      }
    };

    checkSession();

    const handleAuthError = (e: any) => {
      if (e.detail?.status === 'expired') {
        logout(true);
        toast.add({
          type: 'error',
          title: 'Sessão expirada',
          description: 'Sessão expirada. Por favor, faça login novamente.',
        });
      } else {
        setUser(null);
      }
    };

    window.addEventListener('auth-error', handleAuthError);
    return () => window.removeEventListener('auth-error', handleAuthError);
  }, []);

  if (isLoading) {
    return <div className="min-h-screen flex items-center justify-center bg-background text-foreground">Carregando...</div>;
  }

  return (
    <AuthContext.Provider value={{ user, setUser, logout, isLoading }}>
      {user ? children : <AuthView />}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
