import { Routes, Route, Navigate } from 'react-router-dom'
import { Toaster } from '@/components/ui/toaster'
import { useAuthStore } from '@/store/auth-store'
import DashboardLayout from '@/components/layout/DashboardLayout'
import LoginPage from '@/pages/auth/LoginPage'
import DashboardPage from '@/pages/dashboard/DashboardPage'
import PatientsPage from '@/pages/patients/PatientsPage'
import PatientDetailPage from '@/pages/patients/PatientDetailPage'
import AppointmentsPage from '@/pages/appointments/AppointmentsPage'
import CalendarPage from '@/pages/appointments/CalendarPage'
import BillingPage from '@/pages/billing/BillingPage'
import InvoiceDetailPage from '@/pages/billing/InvoiceDetailPage'
import ReportsPage from '@/pages/reports/ReportsPage'
import SettingsPage from '@/pages/settings/SettingsPage'
import EmrPage from '@/pages/emr/EmrPage'
import VendorPage from '@/pages/vendor/VendorPage'

console.log('[ClinicOS] App component loaded')

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading } = useAuthStore()
  
  console.log('[ClinicOS] ProtectedRoute check:', { isAuthenticated, isLoading })
  
  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    )
  }
  
  if (!isAuthenticated) {
    console.log('[ClinicOS] User not authenticated, redirecting to login')
    return <Navigate to="/login" replace />
  }
  
  return <>{children}</>
}

function App() {
  console.log('[ClinicOS] App rendering')
  
  return (
    <>
      <Routes>
        {/* Public routes */}
        <Route path="/login" element={<LoginPage />} />
        
        {/* Protected routes */}
        <Route
          path="/"
          element={
            <ProtectedRoute>
              <DashboardLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<Navigate to="/dashboard" replace />} />
          <Route path="dashboard" element={<DashboardPage />} />
          
          {/* Patients */}
          <Route path="patients" element={<PatientsPage />} />
          <Route path="patients/:id" element={<PatientDetailPage />} />
          
          {/* Appointments */}
          <Route path="appointments" element={<AppointmentsPage />} />
          <Route path="calendar" element={<CalendarPage />} />
          
          {/* Billing */}
          <Route path="billing" element={<BillingPage />} />
          <Route path="billing/:id" element={<InvoiceDetailPage />} />
          
          {/* Reports */}
          <Route path="reports" element={<ReportsPage />} />
          
          {/* Settings */}
          <Route path="settings" element={<SettingsPage />} />
          
          {/* EMR */}
          <Route path="emr" element={<EmrPage />} />
          <Route path="emr/:patientId" element={<EmrPage />} />
          
          {/* Vendor Portal */}
          <Route path="lab-orders" element={<VendorPage />} />
        </Route>
        
        {/* Catch all */}
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
      
      <Toaster />
    </>
  )
}

export default App
