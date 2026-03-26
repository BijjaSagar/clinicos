import { useState } from 'react'
import { Plus, Clock, CheckCircle, XCircle, User } from 'lucide-react'
import { cn } from '@/utils/cn'

console.log('[ClinicOS] Loading AppointmentsPage')

const appointments = [
  { id: 1, patient: 'Priya Mehta', time: '09:00 AM', service: 'Consultation', doctor: 'Dr. Priya Sharma', status: 'completed', duration: 20 },
  { id: 2, patient: 'Ananya Patil', time: '09:30 AM', service: 'Chemical Peel', doctor: 'Dr. Priya Sharma', status: 'in_progress', duration: 45 },
  { id: 3, patient: 'Vikram Shah', time: '10:30 AM', service: 'Follow-up', doctor: 'Dr. Priya Sharma', status: 'checked_in', duration: 15 },
  { id: 4, patient: 'Sunita Desai', time: '11:00 AM', service: 'Q-Switch Laser', doctor: 'Dr. Priya Sharma', status: 'booked', duration: 30 },
  { id: 5, patient: 'Rohit Kulkarni', time: '11:30 AM', service: 'Consultation', doctor: 'Dr. Priya Sharma', status: 'booked', duration: 20 },
  { id: 6, patient: 'Kaveri Iyer', time: '12:00 PM', service: 'PRP Hair', doctor: 'Dr. Priya Sharma', status: 'booked', duration: 60 },
]

const statusConfig = {
  booked: { label: 'Booked', color: 'bg-blue-100 text-blue-700', icon: Clock },
  checked_in: { label: 'Checked In', color: 'bg-yellow-100 text-yellow-700', icon: User },
  in_progress: { label: 'In Progress', color: 'bg-purple-100 text-purple-700', icon: Clock },
  completed: { label: 'Completed', color: 'bg-green-100 text-green-700', icon: CheckCircle },
  cancelled: { label: 'Cancelled', color: 'bg-red-100 text-red-700', icon: XCircle },
  no_show: { label: 'No Show', color: 'bg-gray-100 text-gray-700', icon: XCircle },
}

export default function AppointmentsPage() {
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0])
  
  console.log('[ClinicOS] AppointmentsPage render', { selectedDate })

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Appointments</h1>
          <p className="text-gray-500">Today's schedule and queue</p>
        </div>
        <div className="flex gap-3">
          <input
            type="date"
            value={selectedDate}
            onChange={(e) => setSelectedDate(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
          />
          <button className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <Plus className="w-5 h-5" />
            New Appointment
          </button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <p className="text-sm text-gray-500">Total</p>
          <p className="text-2xl font-bold text-gray-900">{appointments.length}</p>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <p className="text-sm text-gray-500">Completed</p>
          <p className="text-2xl font-bold text-green-600">{appointments.filter(a => a.status === 'completed').length}</p>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <p className="text-sm text-gray-500">In Progress</p>
          <p className="text-2xl font-bold text-purple-600">{appointments.filter(a => a.status === 'in_progress').length}</p>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <p className="text-sm text-gray-500">Pending</p>
          <p className="text-2xl font-bold text-blue-600">{appointments.filter(a => ['booked', 'checked_in'].includes(a.status)).length}</p>
        </div>
      </div>

      {/* Appointments List */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {appointments.map((apt) => {
                const status = statusConfig[apt.status as keyof typeof statusConfig]
                const StatusIcon = status.icon
                
                return (
                  <tr key={apt.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <p className="font-medium text-gray-900">{apt.time}</p>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm font-medium">
                          {apt.patient.split(' ').map(n => n[0]).join('')}
                        </div>
                        <span className="font-medium text-gray-900">{apt.patient}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-600">{apt.service}</td>
                    <td className="px-6 py-4 text-gray-600">{apt.doctor}</td>
                    <td className="px-6 py-4 text-gray-600">{apt.duration} min</td>
                    <td className="px-6 py-4">
                      <span className={cn(
                        "inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium",
                        status.color
                      )}>
                        <StatusIcon className="w-3.5 h-3.5" />
                        {status.label}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right">
                      {apt.status === 'booked' && (
                        <button className="text-sm text-primary hover:underline">Check In</button>
                      )}
                      {apt.status === 'checked_in' && (
                        <button className="text-sm text-primary hover:underline">Start Visit</button>
                      )}
                      {apt.status === 'in_progress' && (
                        <button className="text-sm text-primary hover:underline">View EMR</button>
                      )}
                      {apt.status === 'completed' && (
                        <button className="text-sm text-primary hover:underline">View</button>
                      )}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
