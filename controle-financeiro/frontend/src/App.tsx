import { AuthProvider } from "@/contexts/AuthContext"
import { Toaster } from "@/components/ui/toast"
import { DashboardView } from "@/views/dashboard/DashboardView"

function App() {
  return (
    <>
      <AuthProvider>
        <DashboardView />
      </AuthProvider>
      <Toaster />
    </>
  )
}

export default App
