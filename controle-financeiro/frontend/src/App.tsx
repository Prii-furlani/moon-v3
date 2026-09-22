import { AuthProvider, useAuth } from "@/contexts/AuthContext"
import { Button } from "@/components/ui/button"

function AppContent() {
  const { user, logout } = useAuth()
  
  // Como a AuthView é carregada caso !user no AuthProvider, 
  // este trecho só renderiza se o user estiver logado.
  return (
    <div className="min-h-screen bg-background flex flex-col items-center justify-center p-6 text-foreground">
      <div className="max-w-md w-full bg-card border border-border shadow-lg rounded-xl p-8 text-center space-y-4">
        <h2 className="text-2xl font-bold">Olá, {user?.nome}!</h2>
        <p className="text-muted-foreground">Bem-vindo(a) ao seu Painel de Controle.</p>
        <Button onClick={() => logout(false)} className="w-full mt-4 bg-destructive hover:bg-destructive/90 text-destructive-foreground">
          Sair da Conta
        </Button>
      </div>
    </div>
  )
}

import { Toaster } from "@/components/ui/toast"

function App() {
  return (
    <AuthProvider>
      <AppContent />
      <Toaster />
    </AuthProvider>
  )
}

export default App
