import { useState } from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '@/utils/cn'

console.log('[ClinicOS] Loading CalendarPage')

export default function CalendarPage() {
  const [currentDate, setCurrentDate] = useState(new Date())
  
  console.log('[ClinicOS] CalendarPage render', { currentDate })

  const daysInMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate()
  const firstDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).getDay()
  const monthName = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' })

  const prevMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1))
  }

  const nextMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1))
  }

  const days = []
  for (let i = 0; i < firstDayOfMonth; i++) {
    days.push(null)
  }
  for (let i = 1; i <= daysInMonth; i++) {
    days.push(i)
  }

  // Mock appointments per day
  const appointmentCounts: Record<number, number> = {
    5: 4, 6: 6, 7: 3, 8: 5, 12: 8, 13: 4, 14: 6, 15: 2,
    19: 7, 20: 5, 21: 4, 22: 3, 26: 6, 27: 4, 28: 5
  }

  const today = new Date()
  const isToday = (day: number) => 
    day === today.getDate() && 
    currentDate.getMonth() === today.getMonth() && 
    currentDate.getFullYear() === today.getFullYear()

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Calendar</h1>
          <p className="text-gray-500">Appointment schedule overview</p>
        </div>
      </div>

      {/* Calendar */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        {/* Month Navigation */}
        <div className="flex items-center justify-between mb-6">
          <button 
            onClick={prevMonth}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <ChevronLeft className="w-5 h-5" />
          </button>
          <h2 className="text-lg font-semibold text-gray-900">{monthName}</h2>
          <button 
            onClick={nextMonth}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <ChevronRight className="w-5 h-5" />
          </button>
        </div>

        {/* Days of Week */}
        <div className="grid grid-cols-7 gap-2 mb-2">
          {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((day) => (
            <div key={day} className="text-center text-sm font-medium text-gray-500 py-2">
              {day}
            </div>
          ))}
        </div>

        {/* Calendar Grid */}
        <div className="grid grid-cols-7 gap-2">
          {days.map((day, index) => (
            <div
              key={index}
              className={cn(
                "min-h-[100px] p-2 rounded-lg border transition-colors cursor-pointer",
                day ? "hover:border-primary" : "",
                isToday(day!) ? "bg-primary/5 border-primary" : "border-gray-200",
              )}
            >
              {day && (
                <>
                  <p className={cn(
                    "text-sm font-medium",
                    isToday(day) ? "text-primary" : "text-gray-900"
                  )}>
                    {day}
                  </p>
                  {appointmentCounts[day] && (
                    <div className="mt-2">
                      <span className={cn(
                        "inline-block px-2 py-0.5 rounded text-xs font-medium",
                        appointmentCounts[day] > 5 ? "bg-red-100 text-red-700" :
                        appointmentCounts[day] > 3 ? "bg-yellow-100 text-yellow-700" :
                        "bg-green-100 text-green-700"
                      )}>
                        {appointmentCounts[day]} appts
                      </span>
                    </div>
                  )}
                </>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Legend */}
      <div className="flex items-center gap-6 text-sm">
        <div className="flex items-center gap-2">
          <span className="w-3 h-3 rounded bg-green-100"></span>
          <span className="text-gray-600">Light (1-3)</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="w-3 h-3 rounded bg-yellow-100"></span>
          <span className="text-gray-600">Moderate (4-5)</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="w-3 h-3 rounded bg-red-100"></span>
          <span className="text-gray-600">Busy (6+)</span>
        </div>
      </div>
    </div>
  )
}
