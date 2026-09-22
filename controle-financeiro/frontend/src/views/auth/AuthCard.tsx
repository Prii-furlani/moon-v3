import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Mail, Lock, Eye, EyeOff, ArrowRight, User } from "lucide-react"
import { toast } from "@/components/ui/toast"
import { fetchApi } from "@/lib/api"
import { useAuth } from "@/contexts/AuthContext"
import "@/styles/auth.css"

export function AuthCard() {
  const [isLogin, setIsLogin] = useState(true)
  const [showPassword, setShowPassword] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { setUser } = useAuth()

  // Login states
  const [identificador, setIdentificador] = useState("")
  const [loginSenha, setLoginSenha] = useState("")
  
  // Register states
  const [nome, setNome] = useState("")
  const [sobrenome, setSobrenome] = useState("")
  const [cpf, setCpf] = useState("")
  const [dataNascimento, setDataNascimento] = useState("")
  const [email, setEmail] = useState("")
  const [registerSenha, setRegisterSenha] = useState("")
  const [confirmSenha, setConfirmSenha] = useState("")

  // Limpar os formulários ao alternar de aba
  const handleTabSwitch = () => {
    setIsLogin(!isLogin)
    setIdentificador("")
    setLoginSenha("")
    setNome("")
    setSobrenome("")
    setCpf("")
    setDataNascimento("")
    setEmail("")
    setRegisterSenha("")
    setConfirmSenha("")
    setShowPassword(false)
  }

  const handleCpfChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    let value = e.target.value.replace(/\D/g, '')
    if (value.length > 11) value = value.slice(0, 11)
    
    // Mask 000.000.000-00
    let masked = value
    if (value.length > 9) {
      masked = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4")
    } else if (value.length > 6) {
      masked = value.replace(/(\d{3})(\d{3})(\d{3})/, "$1.$2.$3")
    } else if (value.length > 3) {
      masked = value.replace(/(\d{3})(\d{3})/, "$1.$2")
    }
    setCpf(masked)
  }

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsLoading(true)
    try {
      const res = await fetchApi<any>('/auth/login.php', {
        method: 'POST',
        body: { identificador, senha: loginSenha }
      })
      if (res.status === 'success') {
        setUser(res.user)
      }
    } catch (error: any) {
      toast.add({
        type: "error",
        title: "Erro de Autenticação",
        description: error.message || "Credenciais inválidas."
      })
    } finally {
      setIsLoading(false)
    }
  }

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault()
    if (registerSenha !== confirmSenha) {
      toast.add({
        type: "error",
        title: "As senhas não coincidem",
        description: "Verifique a senha e a confirmação."
      })
      return
    }
    if (registerSenha.length < 8) {
      toast.add({
        type: "error",
        title: "Senha muito curta",
        description: "A senha deve ter no mínimo 8 caracteres."
      })
      return
    }

    setIsLoading(true)
    try {
      const res = await fetchApi<any>('/auth/register.php', {
        method: 'POST',
        body: { 
          nome, 
          sobrenome, 
          cpf, 
          data_nascimento: dataNascimento, 
          email, 
          senha: registerSenha 
        }
      })
      if (res.status === 'success') {
        toast.add({
          type: "success",
          title: "Cadastro realizado!",
          description: "Conta criada com sucesso. Você já pode fazer login."
        })
        handleTabSwitch() // Volta pro login limpo
      }
    } catch (error: any) {
      toast.add({
        type: "error",
        title: "Erro no cadastro",
        description: error.message || "Verifique os dados e tente novamente."
      })
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-background flex flex-col items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
      {/* Branding Header */}
      <div className="w-full max-w-md text-center mb-8">
        <h1 className="text-4xl font-serif text-foreground tracking-wide font-bold">MoonFinance</h1>
        <p className="mt-2 text-sm text-muted-foreground font-medium uppercase tracking-widest">
          Gestão Financeira Inteligente
        </p>
      </div>

      {/* Main Card */}
      <Card className="w-full sm:max-w-md auth-card-top-border rounded-2xl sm:rounded-3xl shadow-xl sm:shadow-2xl bg-card border-border border">
        <CardHeader className="space-y-2 pb-6 pt-8 px-6 sm:px-8">
          <CardTitle className="text-2xl text-center font-semibold text-foreground">
            {isLogin ? 'Acesse sua Conta' : 'Criar Nova Conta'}
          </CardTitle>
          <CardDescription className="text-center text-muted-foreground">
            {isLogin ? 'Seja bem-vindo de volta.' : 'Preencha os dados abaixo.'}
          </CardDescription>
        </CardHeader>
        <CardContent className="px-6 sm:px-8">
          {isLogin ? (
            <form onSubmit={handleLogin} className="space-y-5">
              <div className="space-y-2">
                <Label htmlFor="identificador">E-mail ou CPF</Label>
                <div className="relative input-focus-ring rounded-md">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Mail className="h-5 w-5 text-muted-foreground" />
                  </div>
                  <Input 
                    id="identificador" 
                    type="text" 
                    placeholder="seu@email.com ou 000.000.000-00" 
                    className="pl-10 h-12 border-border bg-background" 
                    value={identificador}
                    onChange={(e) => setIdentificador(e.target.value)}
                    required 
                    autoComplete="username"
                    disabled={isLoading}
                  />
                </div>
              </div>
              
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <Label htmlFor="senha">Senha</Label>
                  <a href="#" className="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
                    Esqueceu a senha?
                  </a>
                </div>
                <div className="relative input-focus-ring rounded-md">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Lock className="h-5 w-5 text-muted-foreground" />
                  </div>
                  <Input 
                    id="senha" 
                    type={showPassword ? "text" : "password"} 
                    placeholder="••••••••" 
                    className="pl-10 pr-10 h-12 border-border bg-background" 
                    value={loginSenha}
                    onChange={(e) => setLoginSenha(e.target.value)}
                    required 
                    autoComplete="current-password"
                    disabled={isLoading}
                  />
                  <button 
                    type="button" 
                    className="absolute inset-y-0 right-0 pr-3 flex items-center"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? (
                      <EyeOff className="h-5 w-5 text-muted-foreground hover:text-foreground transition-colors" />
                    ) : (
                      <Eye className="h-5 w-5 text-muted-foreground hover:text-foreground transition-colors" />
                    )}
                  </button>
                </div>
              </div>
              
              <Button 
                type="submit"
                disabled={isLoading}
                className="w-full h-12 mt-2 bg-gradient-primary hover:bg-gradient-primary-hover text-white text-base font-medium rounded-xl group overflow-hidden relative shadow-lg"
              >
                <span className="flex items-center justify-center">
                  {isLoading ? "Processando..." : "Entrar"}
                  {!isLoading && <ArrowRight className="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform" />}
                </span>
              </Button>
            </form>
          ) : (
            <form onSubmit={handleRegister} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="nome">Nome</Label>
                  <div className="relative input-focus-ring rounded-md">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <User className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <Input id="nome" type="text" placeholder="Ex: Maria" autoComplete="off" className="pl-9 h-11 bg-background" value={nome} onChange={e=>setNome(e.target.value)} required disabled={isLoading} />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="sobrenome">Sobrenome</Label>
                  <Input id="sobrenome" type="text" placeholder="Ex: da Silva" autoComplete="off" className="h-11 bg-background px-3" value={sobrenome} onChange={e=>setSobrenome(e.target.value)} required disabled={isLoading} />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="cpf">CPF</Label>
                  <Input id="cpf" type="text" placeholder="000.000.000-00" autoComplete="off" className="h-11 bg-background px-3" value={cpf} onChange={handleCpfChange} required disabled={isLoading} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="data_nascimento">Data de Nascimento</Label>
                  <Input id="data_nascimento" type="date" autoComplete="off" className="h-11 bg-background px-3" value={dataNascimento} onChange={e=>setDataNascimento(e.target.value)} required disabled={isLoading} />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="email_cadastro">E-mail</Label>
                <div className="relative input-focus-ring rounded-md">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Mail className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <Input id="email_cadastro" type="email" placeholder="seu@email.com" autoComplete="off" className="pl-9 h-11 bg-background" value={email} onChange={e=>setEmail(e.target.value)} required disabled={isLoading} />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="senha_cadastro">Senha</Label>
                  <div className="relative input-focus-ring rounded-md">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <Lock className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <Input id="senha_cadastro" type="password" placeholder="••••••••" autoComplete="new-password" className="pl-9 h-11 bg-background" value={registerSenha} onChange={e=>setRegisterSenha(e.target.value)} required disabled={isLoading} />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="confirm_senha">Confirmar Senha</Label>
                  <Input id="confirm_senha" type="password" placeholder="••••••••" autoComplete="new-password" className="h-11 bg-background px-3" value={confirmSenha} onChange={e=>setConfirmSenha(e.target.value)} required disabled={isLoading} />
                </div>
              </div>
              
              <Button 
                type="submit"
                disabled={isLoading}
                className="w-full h-12 mt-4 bg-gradient-primary hover:bg-gradient-primary-hover text-white text-base font-medium rounded-xl shadow-lg"
              >
                {isLoading ? "Processando..." : "Finalizar Cadastro"}
              </Button>
            </form>
          )}
        </CardContent>
        <CardFooter className="flex flex-col justify-center border-t border-border/50 py-6 px-6 sm:px-8 bg-muted/20 rounded-b-2xl sm:rounded-b-3xl">
          <p className="text-sm text-center text-muted-foreground">
            {isLogin ? 'Ainda não tem conta? ' : 'Já tem uma conta? '}
            <button
              type="button"
              onClick={handleTabSwitch}
              className="font-semibold text-primary hover:text-primary/80 transition-colors focus:outline-none focus:underline hover:underline"
            >
              {isLogin ? 'Criar Conta' : 'Fazer Login'}
            </button>
          </p>
        </CardFooter>
      </Card>
    </div>
  )
}
