import axios from 'axios'

console.log('[ClinicOS] Initializing API service')

// API Base URL - change this to your production API URL
const API_BASE_URL = import.meta.env.VITE_API_URL || '/api/v1'

console.log('[ClinicOS] API Base URL:', API_BASE_URL)

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    console.log('[ClinicOS] API Request:', config.method?.toUpperCase(), config.url)
    return config
  },
  (error) => {
    console.error('[ClinicOS] API Request Error:', error)
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    console.log('[ClinicOS] API Response:', response.status, response.config.url)
    return response
  },
  (error) => {
    console.error('[ClinicOS] API Error:', {
      status: error.response?.status,
      url: error.config?.url,
      message: error.response?.data?.message,
    })
    
    // Handle 401 - Unauthorized
    if (error.response?.status === 401) {
      console.log('[ClinicOS] Unauthorized - clearing auth state')
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    
    return Promise.reject(error)
  }
)

export default api

// API helper functions
export const apiService = {
  // Dashboard
  getDashboard: () => api.get('/analytics/dashboard'),
  
  // Patients
  getPatients: (params?: Record<string, any>) => api.get('/patients', { params }),
  getPatient: (id: number) => api.get(`/patients/${id}`),
  createPatient: (data: any) => api.post('/patients', data),
  updatePatient: (id: number, data: any) => api.put(`/patients/${id}`, data),
  getPatientTimeline: (id: number) => api.get(`/patients/${id}/timeline`),
  getPatientVisits: (id: number) => api.get(`/patients/${id}/visits`),
  
  // Appointments
  getAppointments: (params?: Record<string, any>) => api.get('/appointments', { params }),
  getAppointment: (id: number) => api.get(`/appointments/${id}`),
  createAppointment: (data: any) => api.post('/appointments', data),
  updateAppointment: (id: number, data: any) => api.put(`/appointments/${id}`, data),
  cancelAppointment: (id: number) => api.delete(`/appointments/${id}`),
  checkInAppointment: (id: number) => api.post(`/appointments/${id}/check-in`),
  completeAppointment: (id: number) => api.post(`/appointments/${id}/complete`),
  getAvailableSlots: (params: Record<string, any>) => api.get('/appointments/slots', { params }),
  getQueue: () => api.get('/appointments/queue'),
  
  // Invoices
  getInvoices: (params?: Record<string, any>) => api.get('/invoices', { params }),
  getInvoice: (id: number) => api.get(`/invoices/${id}`),
  createInvoice: (data: any) => api.post('/invoices', data),
  updateInvoice: (id: number, data: any) => api.put(`/invoices/${id}`, data),
  sendInvoiceLink: (id: number) => api.post(`/invoices/${id}/send`),
  getInvoicePdf: (id: number) => api.get(`/invoices/${id}/pdf`, { responseType: 'blob' }),
  
  // Payments
  getPayments: (params?: Record<string, any>) => api.get('/payments', { params }),
  createRazorpayOrder: (data: any) => api.post('/payments/razorpay/order', data),
  verifyPayment: (data: any) => api.post('/payments/razorpay/verify', data),
  getOutstanding: () => api.get('/payments/outstanding'),
  
  // Analytics
  getRevenue: (params?: Record<string, any>) => api.get('/analytics/revenue', { params }),
  getAppointmentStats: (params?: Record<string, any>) => api.get('/analytics/appointments', { params }),
  getPatientStats: (params?: Record<string, any>) => api.get('/analytics/patients', { params }),
  getDoctorStats: (params?: Record<string, any>) => api.get('/analytics/doctors', { params }),
  
  // GST Reports
  getGstReport: (params: { month: number; year: number }) => api.get('/gst/report', { params }),
  
  // Clinic
  getClinic: () => api.get('/clinic'),
  updateClinic: (data: any) => api.put('/clinic', data),
  getStaff: () => api.get('/clinic/staff'),
  getDoctors: () => api.get('/clinic/doctors'),
  getRooms: () => api.get('/clinic/rooms'),
}
