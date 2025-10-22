import { useState } from 'react'
import { Route, Switch } from 'wouter'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import Dashboard from './pages/Dashboard'
import Login from './pages/Login'

const queryClient = new QueryClient()

function App() {
  const [isAuthenticated, setIsAuthenticated] = useState(false)

  if (!isAuthenticated) {
    return <Login onLogin={() => setIsAuthenticated(true)} />
  }

  return (
    <QueryClientProvider client={queryClient}>
      <div className="min-h-screen bg-background">
        <Switch>
          <Route path="/" component={Dashboard} />
          <Route path="/vehicles" component={Dashboard} />
          <Route>404 Page Not Found</Route>
        </Switch>
      </div>
    </QueryClientProvider>
  )
}

export default App
