import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Plus, Search, Filter, Download, Send, IndianRupee, CheckCircle, Clock, XCircle } from 'lucide-react'
import { cn } from '@/utils/cn'

console.log('[ClinicOS] Loading BillingPage')

const invoices = [
  { id: 1, number: 'SSHC-202603-0001', patient: 'Priya Mehta', date: '2026-03-26', amount: 4130, paid: 4130, status: 'paid', method: 'UPI' },
  { id: 2, number: 'SSHC-202603-0002', patient: 'Ananya Patil', date: '2026-03-26', amount: 8500, paid: 0, status: 'pending', method: null },
  { id: 3, number: 'SSHC-202603-0003', patient: 'Vikram Shah', date: '2026-03-25', amount: 800, paid: 800, status: 'paid', method: 'Cash' },
  { id: 4, number: 'SSHC-202603-0004', patient: 'Sunita Desai', date: '2026-03-25', amount: 5300, paid: 5300, status: 'paid', method: 'Card' },
  { id: 5, number: 'SSHC-202603-0005', patient: 'Rohit Kulkarni', date: '2026-03-24', amount: 3500, paid: 1500, status: 'partial', method: 'UPI' },
]

const statusConfig = {
  paid: { label: 'Paid', color: 'bg-green-100 text-green-700', icon: CheckCircle },
  pending: { label: 'Pending', color: 'bg-yellow-100 text-yellow-700', icon: Clock },
  partial: { label: 'Partial', color: 'bg-blue-100 text-blue-700', icon: Clock },
  cancelled: { label: 'Cancelled', color: 'bg-red-100 text-red-700', icon: XCircle },
}

export default function BillingPage() {
  const [search, setSearch] = useState('')
  
  console.log('[ClinicOS] BillingPage render')

  const totalRevenue = invoices.reduce((sum, inv) => sum + inv.paid, 0)
  const totalPending = invoices.reduce((sum, inv) => sum + (inv.amount - inv.paid), 0)

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Billing</h1>
          <p className="text-gray-500">Manage invoices and payments</p>
        </div>
        <button className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
          <Plus className="w-5 h-5" />
          New Invoice
        </button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-white rounded-xl p-6 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Today's Collection</p>
              <p className="text-2xl font-bold text-gray-900">₹{totalRevenue.toLocaleString('en-IN')}</p>
            </div>
            <div className="p-3 bg-green-100 rounded-lg">
              <IndianRupee className="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Pending Amount</p>
              <p className="text-2xl font-bold text-yellow-600">₹{totalPending.toLocaleString('en-IN')}</p>
            </div>
            <div className="p-3 bg-yellow-100 rounded-lg">
              <Clock className="w-6 h-6 text-yellow-600" />
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl p-6 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">Invoices Today</p>
              <p className="text-2xl font-bold text-gray-900">{invoices.length}</p>
            </div>
            <div className="p-3 bg-blue-100 rounded-lg">
              <CheckCircle className="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>
      </div>

      {/* Search and Filters */}
      <div className="flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search by invoice number or patient..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
          />
        </div>
        <button className="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50">
          <Filter className="w-5 h-5 text-gray-500" />
          Filters
        </button>
        <button className="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50">
          <Download className="w-5 h-5 text-gray-500" />
          Export
        </button>
      </div>

      {/* Invoices Table */}
      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {invoices.map((invoice) => {
                const status = statusConfig[invoice.status as keyof typeof statusConfig]
                const StatusIcon = status.icon
                
                return (
                  <tr key={invoice.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <Link to={`/billing/${invoice.id}`} className="font-medium text-primary hover:underline">
                        {invoice.number}
                      </Link>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm font-medium">
                          {invoice.patient.split(' ').map(n => n[0]).join('')}
                        </div>
                        <span className="text-gray-900">{invoice.patient}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-600">
                      {new Date(invoice.date).toLocaleDateString('en-IN')}
                    </td>
                    <td className="px-6 py-4 font-medium text-gray-900">
                      ₹{invoice.amount.toLocaleString('en-IN')}
                    </td>
                    <td className="px-6 py-4 text-gray-600">
                      ₹{invoice.paid.toLocaleString('en-IN')}
                      {invoice.method && <span className="text-xs text-gray-400 ml-1">({invoice.method})</span>}
                    </td>
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
                      <div className="flex items-center justify-end gap-2">
                        {invoice.status === 'pending' && (
                          <button className="p-2 hover:bg-gray-100 rounded-lg text-gray-500" title="Send WhatsApp">
                            <Send className="w-4 h-4" />
                          </button>
                        )}
                        <button className="p-2 hover:bg-gray-100 rounded-lg text-gray-500" title="Download PDF">
                          <Download className="w-4 h-4" />
                        </button>
                      </div>
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
