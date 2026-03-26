import { useState } from 'react'
import { BarChart3, TrendingUp, Users, Calendar, IndianRupee, Download } from 'lucide-react'
import { cn } from '@/utils/cn'

console.log('[ClinicOS] Loading ReportsPage')

export default function ReportsPage() {
  const [period, setPeriod] = useState('month')
  
  console.log('[ClinicOS] ReportsPage render', { period })

  const stats = [
    { label: 'Total Revenue', value: '₹4,87,000', change: '+12.5%', positive: true },
    { label: 'Total Patients', value: '156', change: '+8.3%', positive: true },
    { label: 'Appointments', value: '423', change: '+15.2%', positive: true },
    { label: 'Avg. per Visit', value: '₹1,150', change: '-2.1%', positive: false },
  ]

  const topServices = [
    { name: 'Consultation', count: 145, revenue: 116000 },
    { name: 'Chemical Peel', count: 52, revenue: 182000 },
    { name: 'Q-Switch Laser', count: 38, revenue: 171000 },
    { name: 'PRP Hair', count: 24, revenue: 192000 },
    { name: 'Follow-up', count: 89, revenue: 44500 },
  ]

  const revenueByMonth = [
    { month: 'Jan', revenue: 385000 },
    { month: 'Feb', revenue: 420000 },
    { month: 'Mar', revenue: 487000 },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Reports</h1>
          <p className="text-gray-500">Analytics and insights</p>
        </div>
        <div className="flex gap-3">
          <select
            value={period}
            onChange={(e) => setPeriod(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
          >
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year">This Year</option>
          </select>
          <button className="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            <Download className="w-5 h-5" />
            Export
          </button>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat, index) => (
          <div key={index} className="bg-white rounded-xl p-6 shadow-sm">
            <p className="text-sm text-gray-500 mb-1">{stat.label}</p>
            <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
            <p className={cn(
              "text-sm mt-1",
              stat.positive ? "text-green-600" : "text-red-600"
            )}>
              {stat.change} vs last {period}
            </p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Revenue Chart */}
        <div className="bg-white rounded-xl shadow-sm p-6">
          <h3 className="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <TrendingUp className="w-5 h-5 text-primary" />
            Revenue Trend
          </h3>
          <div className="h-64 flex items-end justify-around gap-4">
            {revenueByMonth.map((item, index) => (
              <div key={index} className="flex flex-col items-center gap-2">
                <div 
                  className="w-16 bg-primary/20 rounded-t-lg transition-all hover:bg-primary/30"
                  style={{ height: `${(item.revenue / 500000) * 100}%` }}
                >
                  <div 
                    className="w-full bg-primary rounded-t-lg"
                    style={{ height: `${(item.revenue / 500000) * 80}%` }}
                  />
                </div>
                <p className="text-sm font-medium text-gray-600">{item.month}</p>
                <p className="text-xs text-gray-500">₹{(item.revenue / 1000).toFixed(0)}K</p>
              </div>
            ))}
          </div>
        </div>

        {/* Top Services */}
        <div className="bg-white rounded-xl shadow-sm p-6">
          <h3 className="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <BarChart3 className="w-5 h-5 text-primary" />
            Top Services
          </h3>
          <div className="space-y-4">
            {topServices.map((service, index) => (
              <div key={index} className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <span className="w-8 h-8 flex items-center justify-center bg-primary/10 text-primary rounded-lg text-sm font-medium">
                    {index + 1}
                  </span>
                  <div>
                    <p className="font-medium text-gray-900">{service.name}</p>
                    <p className="text-sm text-gray-500">{service.count} appointments</p>
                  </div>
                </div>
                <p className="font-semibold text-gray-900">
                  ₹{service.revenue.toLocaleString('en-IN')}
                </p>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* GST Summary */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h3 className="font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <IndianRupee className="w-5 h-5 text-primary" />
          GST Summary (This Month)
        </h3>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">SAC Code</th>
                <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Description</th>
                <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">Taxable Value</th>
                <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">CGST</th>
                <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">SGST</th>
                <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">Total Tax</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              <tr>
                <td className="px-4 py-3 text-gray-900">999311</td>
                <td className="px-4 py-3 text-gray-600">OPD Consultation (Exempt)</td>
                <td className="px-4 py-3 text-right text-gray-900">₹1,16,000</td>
                <td className="px-4 py-3 text-right text-gray-600">₹0</td>
                <td className="px-4 py-3 text-right text-gray-600">₹0</td>
                <td className="px-4 py-3 text-right font-medium text-gray-900">₹0</td>
              </tr>
              <tr>
                <td className="px-4 py-3 text-gray-900">999312</td>
                <td className="px-4 py-3 text-gray-600">Cosmetic Procedures (18%)</td>
                <td className="px-4 py-3 text-right text-gray-900">₹3,71,000</td>
                <td className="px-4 py-3 text-right text-gray-600">₹33,390</td>
                <td className="px-4 py-3 text-right text-gray-600">₹33,390</td>
                <td className="px-4 py-3 text-right font-medium text-gray-900">₹66,780</td>
              </tr>
              <tr className="bg-gray-50 font-semibold">
                <td className="px-4 py-3 text-gray-900" colSpan={2}>Total</td>
                <td className="px-4 py-3 text-right text-gray-900">₹4,87,000</td>
                <td className="px-4 py-3 text-right text-gray-900">₹33,390</td>
                <td className="px-4 py-3 text-right text-gray-900">₹33,390</td>
                <td className="px-4 py-3 text-right text-gray-900">₹66,780</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
