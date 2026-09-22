import { useAuth } from "@/contexts/AuthContext"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import {
  ArrowUpCircle,
  ArrowDownCircle,
  Wallet,
  PlusCircle,
  MinusCircle,
  FileText,
  LogOut,
  Moon,
  TrendingUp,
} from "lucide-react"
import "@/styles/dashboard.css"

export function DashboardView() {
  const { user, logout } = useAuth()

  // Iniciais do avatar
  const initials = user
    ? `${user.nome.charAt(0)}${user.sobrenome?.charAt(0) || ""}`.toUpperCase()
    : "?"

  // Dados mockados (serão substituídos por dados reais do backend)
  const saldo = 0
  const receitasMes = 0
  const despesasMes = 0
  const movimentacoes: Array<{
    id: number
    descricao: string
    valor: number
    tipo: "receita" | "despesa"
    data: string
  }> = []

  const formatCurrency = (value: number) =>
    value.toLocaleString("pt-BR", { style: "currency", currency: "BRL" })

  return (
    <div className="min-h-screen bg-background text-foreground">
      {/* ===== HEADER ===== */}
      <header className="dashboard-header-animated border-b border-border bg-card/80 backdrop-blur-sm sticky top-0 z-40">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
          {/* Logo + Saudação */}
          <div className="flex items-center gap-3">
            <div className="dashboard-avatar h-10 w-10 bg-primary text-primary-foreground text-sm">
              {initials}
            </div>
            <div>
              <p className="text-sm text-muted-foreground leading-tight">Olá,</p>
              <h2 className="text-base font-semibold text-foreground leading-tight">
                {user?.nome} {user?.sobrenome}
              </h2>
            </div>
          </div>

          {/* Ações do Header */}
          <div className="flex items-center gap-2">
            <div className="flex items-center gap-1 text-muted-foreground">
              <Moon className="h-4 w-4" />
              <span className="text-xs font-medium hidden sm:inline">MoonFinance</span>
            </div>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => logout(false)}
              className="text-muted-foreground hover:text-destructive"
            >
              <LogOut className="h-4 w-4" />
              <span className="hidden sm:inline ml-1">Sair</span>
            </Button>
          </div>
        </div>
      </header>

      {/* ===== CONTEÚDO PRINCIPAL ===== */}
      <main className="max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-6">
        {/* --- Cards de Métricas --- */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {/* Saldo Total */}
          <Card className="dashboard-card-animated metric-card-hover bg-card border-border">
            <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Saldo Total
              </CardTitle>
              <Wallet className="h-5 w-5 text-primary" />
            </CardHeader>
            <CardContent>
              <p
                className={`text-2xl sm:text-3xl font-bold tracking-tight ${
                  saldo >= 0 ? "text-secondary" : "text-primary"
                }`}
              >
                {formatCurrency(saldo)}
              </p>
              <p className="text-xs text-muted-foreground mt-1">Atualizado agora</p>
            </CardContent>
          </Card>

          {/* Receitas do Mês */}
          <Card className="dashboard-card-animated metric-card-hover bg-card border-border">
            <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Receitas do Mês
              </CardTitle>
              <ArrowUpCircle className="h-5 w-5 text-secondary" />
            </CardHeader>
            <CardContent>
              <p className="text-2xl sm:text-3xl font-bold tracking-tight text-secondary">
                {formatCurrency(receitasMes)}
              </p>
              <p className="text-xs text-muted-foreground mt-1">
                <TrendingUp className="inline h-3 w-3 mr-1" />
                Entradas do mês
              </p>
            </CardContent>
          </Card>

          {/* Despesas do Mês */}
          <Card className="dashboard-card-animated metric-card-hover bg-card border-border">
            <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Despesas do Mês
              </CardTitle>
              <ArrowDownCircle className="h-5 w-5 text-primary" />
            </CardHeader>
            <CardContent>
              <p className="text-2xl sm:text-3xl font-bold tracking-tight text-primary">
                {formatCurrency(despesasMes)}
              </p>
              <p className="text-xs text-muted-foreground mt-1">
                <ArrowDownCircle className="inline h-3 w-3 mr-1" />
                Saídas do mês
              </p>
            </CardContent>
          </Card>
        </div>

        {/* --- Ações Rápidas --- */}
        <div className="dashboard-actions-animated">
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3">
            Ações Rápidas
          </h3>
          <div className="grid grid-cols-3 gap-3">
            <Button
              variant="outline"
              className="quick-action-btn flex flex-col items-center gap-1.5 h-auto py-4 border-border hover:border-secondary hover:text-secondary"
            >
              <PlusCircle className="h-5 w-5" />
              <span className="text-xs font-medium">Nova Receita</span>
            </Button>
            <Button
              variant="outline"
              className="quick-action-btn flex flex-col items-center gap-1.5 h-auto py-4 border-border hover:border-primary hover:text-primary"
            >
              <MinusCircle className="h-5 w-5" />
              <span className="text-xs font-medium">Nova Despesa</span>
            </Button>
            <Button
              variant="outline"
              className="quick-action-btn flex flex-col items-center gap-1.5 h-auto py-4 border-border hover:border-foreground"
            >
              <FileText className="h-5 w-5" />
              <span className="text-xs font-medium">Ver Extrato</span>
            </Button>
          </div>
        </div>

        {/* --- Extrato Recente --- */}
        <div className="dashboard-section-animated">
          <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3">
            Extrato Recente
          </h3>
          <Card className="bg-card border-border">
            <CardContent className="p-0">
              {movimentacoes.length === 0 ? (
                /* Estado Vazio Elegante */
                <div className="flex flex-col items-center justify-center py-12 px-6 text-center">
                  <div className="empty-state-icon mb-4">
                    <FileText className="h-16 w-16 text-muted-foreground" />
                  </div>
                  <h4 className="text-base font-semibold text-foreground mb-1">
                    Nenhuma movimentação registrada
                  </h4>
                  <p className="text-sm text-muted-foreground max-w-xs">
                    Comece adicionando sua primeira receita ou despesa para
                    acompanhar suas finanças aqui!
                  </p>
                  <Button
                    variant="outline"
                    className="mt-6 border-primary text-primary hover:bg-primary hover:text-primary-foreground"
                  >
                    <PlusCircle className="h-4 w-4 mr-2" />
                    Adicionar Movimentação
                  </Button>
                </div>
              ) : (
                /* Lista de Movimentações */
                <ul className="divide-y divide-border">
                  {movimentacoes.map((mov) => (
                    <li
                      key={mov.id}
                      className="extrato-item flex items-center justify-between px-4 py-3"
                    >
                      <div className="flex items-center gap-3">
                        {mov.tipo === "receita" ? (
                          <ArrowUpCircle className="h-4 w-4 text-secondary flex-shrink-0" />
                        ) : (
                          <ArrowDownCircle className="h-4 w-4 text-primary flex-shrink-0" />
                        )}
                        <div>
                          <p className="text-sm font-medium text-foreground">{mov.descricao}</p>
                          <p className="text-xs text-muted-foreground">{mov.data}</p>
                        </div>
                      </div>
                      <span
                        className={`text-sm font-semibold ${
                          mov.tipo === "receita" ? "text-secondary" : "text-primary"
                        }`}
                      >
                        {mov.tipo === "receita" ? "+" : "-"} {formatCurrency(Math.abs(mov.valor))}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  )
}
