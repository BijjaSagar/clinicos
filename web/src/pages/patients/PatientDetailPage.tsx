import { useParams, Link } from 'react-router-dom'
import { ArrowLeft, Phone, Mail, Calendar, Activity, FileText, Camera } from 'lucide-react'

console.log('[ClinicOS] Loading PatientDetailPage')

export default function PatientDetailPage() {
  const { id } = useParams()
  console.log('[ClinicOS] PatientDetailPage render', { id })

  // Mock patient data
  const patient = {
    id: Number(id),
    name: 'Priya Mehta',
    phone: '+919823456780',
    email: 'priya.mehta91@gmail.com',
    dob: '1991-04-15',
    sex: 'F',
    blood_group: 'B+',
    abha_id: '423112345601',
    abha_address: 'priyamehta@abdm',
    visit_count: 6,
    last_visit_date: '2026-02-10',
    medical_history: {
      allergies: ['Penicillin'],
      chronic_conditions: ['Psoriasis'],
      current_medications: ['Topical steroids'],
    },
    visits: [
      { id: 1, date: '2026-02-10', diagnosis: 'Psoriasis vulgaris', doctor: 'Dr. Priya Sharma' },
      { id: 2, date: '2026-01-15', diagnosis: 'Follow-up', doctor: 'Dr. Priya Sharma' },
      { id: 3, date: '2025-12-10', diagnosis: 'Psoriasis vulgaris', doctor: 'Dr. Priya Sharma' },
    ],
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link to="/patients" className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <ArrowLeft className="w-5 h-5" />
        </Link>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{patient.name}</h1>
          <p className="text-gray-500">Patient ID: #{patient.id}</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Patient Info */}
        <div className="bg-white rounded-xl shadow-sm p-6">
          <div className="flex items-center gap-4 mb-6">
            <div className="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xl font-medium">
              {patient.name.split(' ').map(n => n[0]).join('')}
            </div>
            <div>
              <h2 className="text-lg font-semibold">{patient.name}</h2>
              <p className="text-gray-500">35 yrs, Female • {patient.blood_group}</p>
            </div>
          </div>

          <div className="space-y-4">
            <div className="flex items-center gap-3">
              <Phone className="w-5 h-5 text-gray-400" />
              <span>{patient.phone}</span>
            </div>
            <div className="flex items-center gap-3">
              <Mail className="w-5 h-5 text-gray-400" />
              <span>{patient.email}</span>
            </div>
            <div className="flex items-center gap-3">
              <Calendar className="w-5 h-5 text-gray-400" />
              <span>DOB: {new Date(patient.dob).toLocaleDateString('en-IN')}</span>
            </div>
          </div>

          {patient.abha_id && (
            <div className="mt-6 p-4 bg-green-50 rounded-lg">
              <p className="text-sm font-medium text-green-800">ABHA Linked</p>
              <p className="text-xs text-green-600">{patient.abha_address}</p>
            </div>
          )}
        </div>

        {/* Medical History */}
        <div className="bg-white rounded-xl shadow-sm p-6">
          <h3 className="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Activity className="w-5 h-5 text-primary" />
            Medical History
          </h3>

          <div className="space-y-4">
            <div>
              <p className="text-sm font-medium text-gray-500 mb-2">Allergies</p>
              <div className="flex flex-wrap gap-2">
                {patient.medical_history.allergies.map((allergy, i) => (
                  <span key={i} className="px-2 py-1 bg-red-100 text-red-700 rounded-full text-sm">
                    {allergy}
                  </span>
                ))}
              </div>
            </div>

            <div>
              <p className="text-sm font-medium text-gray-500 mb-2">Chronic Conditions</p>
              <div className="flex flex-wrap gap-2">
                {patient.medical_history.chronic_conditions.map((condition, i) => (
                  <span key={i} className="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-sm">
                    {condition}
                  </span>
                ))}
              </div>
            </div>

            <div>
              <p className="text-sm font-medium text-gray-500 mb-2">Current Medications</p>
              <div className="flex flex-wrap gap-2">
                {patient.medical_history.current_medications.map((med, i) => (
                  <span key={i} className="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                    {med}
                  </span>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="bg-white rounded-xl shadow-sm p-6">
          <h3 className="font-semibold text-gray-900 mb-4">Quick Actions</h3>
          <div className="space-y-3">
            <button className="w-full flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <Calendar className="w-5 h-5 text-primary" />
              <span>Book Appointment</span>
            </button>
            <button className="w-full flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <FileText className="w-5 h-5 text-primary" />
              <span>View Records</span>
            </button>
            <button className="w-full flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <Camera className="w-5 h-5 text-primary" />
              <span>Photo Vault</span>
            </button>
          </div>
        </div>
      </div>

      {/* Visit History */}
      <div className="bg-white rounded-xl shadow-sm">
        <div className="px-6 py-4 border-b">
          <h3 className="font-semibold text-gray-900">Visit History ({patient.visit_count} visits)</h3>
        </div>
        <div className="divide-y">
          {patient.visits.map((visit) => (
            <div key={visit.id} className="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
              <div>
                <p className="font-medium text-gray-900">{visit.diagnosis}</p>
                <p className="text-sm text-gray-500">{visit.doctor}</p>
              </div>
              <div className="text-right">
                <p className="text-sm text-gray-900">{new Date(visit.date).toLocaleDateString('en-IN')}</p>
                <Link to={`/emr/${visit.id}`} className="text-sm text-primary hover:underline">
                  View Details
                </Link>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
