import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { 
  Search, 
  Plus, 
  Filter, 
  MoreVertical,
  Phone,
  Mail,
  Calendar as CalendarIcon
} from 'lucide-react'
import { apiService } from '@/services/api'
import { cn } from '@/utils/cn'

console.log('[ClinicOS] Loading PatientsPage')

interface Patient {
  id: number
  name: string
  phone: string
  email?: string
  dob: string
  sex: string
  blood_group?: string
  abha_id?: string
  visit_count: number
  last_visit_date?: string
  created_at: string
}

export default function PatientsPage() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  
  console.log('[ClinicOS] PatientsPage render', { search, page })

  const { data, isLoading } = useQuery({
    queryKey: ['patients', { search, page }],
    queryFn: async () => {
      console.log('[ClinicOS] PatientsPage: Fetching patients')
      const response = await apiService.getPatients({ search, page, limit: 20 })
      return response.data
    },
  })

  // Mock data for demo
  const patients: Patient[] = data?.data || [
    { id: 1, name: 'Priya Mehta', phone: '+919823456780', email: 'priya.mehta91@gmail.com', dob: '1991-04-15', sex: 'F', blood_group: 'B+', abha_id: '423112345601', visit_count: 6, last_visit_date: '2026-02-10', created_at: '2025-06-15' },
    { id: 2, name: 'Ananya Patil', phone: '+919765432109', email: 'ananya.patil98@gmail.com', dob: '1998-09-22', sex: 'F', blood_group: 'O+', visit_count: 4, last_visit_date: '2026-01-25', created_at: '2025-08-20' },
    { id: 3, name: 'Vikram Shah', phone: '+919654321098', email: 'vikram.shah74@yahoo.com', dob: '1974-03-08', sex: 'M', blood_group: 'A+', abha_id: '514223456702', visit_count: 8, last_visit_date: '2026-03-01', created_at: '2024-12-10' },
    { id: 4, name: 'Sunita Desai', phone: '+919543210987', email: 'sunita.desai@hotmail.com', dob: '1985-11-30', sex: 'F', blood_group: 'AB+', abha_id: '625334567803', visit_count: 5, last_visit_date: '2026-02-18', created_at: '2025-03-22' },
    { id: 5, name: 'Rohit Kulkarni', phone: '+919432109876', email: 'rohit.kulkarni95@gmail.com', dob: '1995-06-17', sex: 'M', blood_group: 'O-', visit_count: 2, last_visit_date: '2026-01-10', created_at: '2025-11-05' },
  ]

  const getAge = (dob: string) => {
    const today = new Date()
    const birthDate = new Date(dob)
    let age = today.getFullYear() - birthDate.getFullYear()
    const m = today.getMonth() - birthDate.getMonth()
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      age--
    }
    return age
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Patients</h1>
          <p className="text-gray-500">Manage your patient records</p>
        </div>
        <button className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
          <Plus className="w-5 h-5" />
          Add Patient
        </button>
      </div>

      {/* Search and Filters */}
      <div className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search by name, phone, or ABHA ID..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
          />
        </div>
        <button className="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
          <Filter className="w-5 h-5 text-gray-500" />
          Filters
        </button>
      </div>

      {/* Patients Table */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age / Gender</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visits</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ABHA</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {isLoading ? (
                Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i} className="animate-pulse">
                    <td className="px-6 py-4"><div className="h-4 bg-gray-200 rounded w-32"></div></td>
                    <td className="px-6 py-4"><div className="h-4 bg-gray-200 rounded w-28"></div></td>
                    <td className="px-6 py-4"><div className="h-4 bg-gray-200 rounded w-16"></div></td>
                    <td className="px-6 py-4"><div className="h-4 bg-gray-200 rounded w-12"></div></td>
                    <td className="px-6 py-4"><div className="h-4 bg-gray-200 rounded w-20"></div></td>
                    <td className="px-6 py-4"><div className="h-4 bg-gray-200 rounded w-16"></div></td>
                    <td className="px-6 py-4"><div className="h-4 bg-gray-200 rounded w-8 ml-auto"></div></td>
                  </tr>
                ))
              ) : (
                patients.map((patient) => (
                  <tr key={patient.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <Link to={`/patients/${patient.id}`} className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-medium">
                          {patient.name.split(' ').map(n => n[0]).join('')}
                        </div>
                        <div>
                          <p className="font-medium text-gray-900 hover:text-primary">{patient.name}</p>
                          <p className="text-sm text-gray-500">{patient.blood_group || 'N/A'}</p>
                        </div>
                      </Link>
                    </td>
                    <td className="px-6 py-4">
                      <div className="space-y-1">
                        <p className="text-sm text-gray-900 flex items-center gap-1">
                          <Phone className="w-3.5 h-3.5 text-gray-400" />
                          {patient.phone}
                        </p>
                        {patient.email && (
                          <p className="text-sm text-gray-500 flex items-center gap-1">
                            <Mail className="w-3.5 h-3.5 text-gray-400" />
                            {patient.email}
                          </p>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <p className="text-sm text-gray-900">
                        {getAge(patient.dob)} yrs, {patient.sex === 'F' ? 'Female' : 'Male'}
                      </p>
                    </td>
                    <td className="px-6 py-4">
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                        {patient.visit_count} visits
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <p className="text-sm text-gray-900 flex items-center gap-1">
                        <CalendarIcon className="w-3.5 h-3.5 text-gray-400" />
                        {patient.last_visit_date ? new Date(patient.last_visit_date).toLocaleDateString('en-IN') : 'Never'}
                      </p>
                    </td>
                    <td className="px-6 py-4">
                      {patient.abha_id ? (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                          Linked
                        </span>
                      ) : (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                          Not linked
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <button className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <MoreVertical className="w-5 h-5 text-gray-400" />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
          <p className="text-sm text-gray-500">
            Showing {patients.length} of {data?.meta?.total || patients.length} patients
          </p>
          <div className="flex gap-2">
            <button 
              onClick={() => setPage(p => Math.max(1, p - 1))}
              disabled={page === 1}
              className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button 
              onClick={() => setPage(p => p + 1)}
              className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
