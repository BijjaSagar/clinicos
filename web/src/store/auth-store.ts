import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import api from '@/services/api'

console.log('[ClinicOS] Loading auth-store')

interface User {
  id: number
  name: string
  email: string
  role: string
  specialty?: string
  clinic_id: number
  clinic?: {
    id: number
    name: string
    slug: string
    plan: string
    specialties: string[]
  }
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
  login: (email: string, password: string) => Promise<boolean>
  logout: () => Promise<void>
  checkAuth: () => Promise<void>
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: true,
      error: null,

      login: async (email: string, password: string) => {
        console.log('[ClinicOS] auth-store.login: Attempting login', { email })
        set({ isLoading: true, error: null })

        try {
          const response = await api.post('/auth/login', { email, password })
          const { user, token } = response.data.data
          
          console.log('[ClinicOS] auth-store.login: Login successful', { userId: user.id })
          
          localStorage.setItem('token', token)
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`
          
          set({
            user,
            token,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          })
          
          return true
        } catch (error: any) {
          console.error('[ClinicOS] auth-store.login: Login failed', error)
          const message = error.response?.data?.message || 'Login failed'
          set({ isLoading: false, error: message })
          return false
        }
      },

      logout: async () => {
        console.log('[ClinicOS] auth-store.logout: Logging out')
        
        try {
          await api.post('/auth/logout')
        } catch (error) {
          console.error('[ClinicOS] auth-store.logout: API logout failed', error)
        }
        
        localStorage.removeItem('token')
        delete api.defaults.headers.common['Authorization']
        
        set({
          user: null,
          token: null,
          isAuthenticated: false,
          isLoading: false,
          error: null,
        })
      },

      checkAuth: async () => {
        console.log('[ClinicOS] auth-store.checkAuth: Checking authentication')
        const token = localStorage.getItem('token')
        
        if (!token) {
          console.log('[ClinicOS] auth-store.checkAuth: No token found')
          set({ isLoading: false, isAuthenticated: false })
          return
        }

        api.defaults.headers.common['Authorization'] = `Bearer ${token}`
        
        try {
          const response = await api.get('/auth/me')
          const user = response.data.data
          
          console.log('[ClinicOS] auth-store.checkAuth: User verified', { userId: user.id })
          
          set({
            user,
            token,
            isAuthenticated: true,
            isLoading: false,
          })
        } catch (error) {
          console.error('[ClinicOS] auth-store.checkAuth: Token invalid', error)
          localStorage.removeItem('token')
          delete api.defaults.headers.common['Authorization']
          
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
          })
        }
      },
    }),
    {
      name: 'clinicos-auth',
      partialize: (state) => ({ token: state.token }),
    }
  )
)

// Check auth on app load
console.log('[ClinicOS] auth-store: Initializing auth check')
useAuthStore.getState().checkAuth()
