import { useParams, Link } from 'react-router-dom'
import { ArrowLeft, Download, Send, Printer, IndianRupee } from 'lucide-react'

console.log('[ClinicOS] Loading InvoiceDetailPage')

export default function InvoiceDetailPage() {
  const { id } = useParams()
  console.log('[ClinicOS] InvoiceDetailPage render', { id })

  // Mock invoice data
  const invoice = {
    id: Number(id),
    number: 'SSHC-202603-0001',
    date: '2026-03-26',
    due_date: '2026-04-02',
    status: 'paid',
    patient: {
      name: 'Priya Mehta',
      phone: '+919823456780',
      email: 'priya.mehta91@gmail.com',
    },
    clinic: {
      name: 'Sharma Skin & Hair Clinic',
      gstin: '27AADCS1234A1Z5',
      address: '302, Aditya Heights, FC Road, Shivajinagar, Pune - 411005',
    },
    items: [
      { description: 'Chemical Peel - Session 2', sac: '999312', qty: 1, rate: 3500, cgst: 315, sgst: 315, total: 4130 },
    ],
    subtotal: 3500,
    cgst_total: 315,
    sgst_total: 315,
    total: 4130,
    amount_paid: 4130,
    payment_method: 'UPI',
    payment_date: '2026-03-26',
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link to="/billing" className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <ArrowLeft className="w-5 h-5" />
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Invoice {invoice.number}</h1>
            <p className="text-gray-500">Created on {new Date(invoice.date).toLocaleDateString('en-IN')}</p>
          </div>
        </div>
        <div className="flex gap-2">
          <button className="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            <Printer className="w-5 h-5" />
            Print
          </button>
          <button className="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            <Download className="w-5 h-5" />
            PDF
          </button>
          <button className="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <Send className="w-5 h-5" />
            WhatsApp
          </button>
        </div>
      </div>

      {/* Invoice */}
      <div className="bg-white rounded-xl shadow-sm p-8 max-w-4xl mx-auto">
        {/* Header */}
        <div className="flex justify-between items-start mb-8 pb-8 border-b">
          <div>
            <h2 className="text-2xl font-bold text-gray-900">{invoice.clinic.name}</h2>
            <p className="text-gray-600 mt-1">{invoice.clinic.address}</p>
            <p className="text-gray-600">GSTIN: {invoice.clinic.gstin}</p>
          </div>
          <div className="text-right">
            <h3 className="text-3xl font-bold text-gray-900">INVOICE</h3>
            <p className="text-lg text-gray-600 mt-1">{invoice.number}</p>
            <span className="inline-block mt-2 px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
              PAID
            </span>
          </div>
        </div>

        {/* Bill To */}
        <div className="grid grid-cols-2 gap-8 mb-8">
          <div>
            <p className="text-sm font-medium text-gray-500 mb-2">Bill To</p>
            <p className="font-medium text-gray-900">{invoice.patient.name}</p>
            <p className="text-gray-600">{invoice.patient.phone}</p>
            <p className="text-gray-600">{invoice.patient.email}</p>
          </div>
          <div className="text-right">
            <div className="space-y-1">
              <p className="text-sm text-gray-500">Invoice Date: <span className="text-gray-900">{new Date(invoice.date).toLocaleDateString('en-IN')}</span></p>
              <p className="text-sm text-gray-500">Due Date: <span className="text-gray-900">{new Date(invoice.due_date).toLocaleDateString('en-IN')}</span></p>
            </div>
          </div>
        </div>

        {/* Items Table */}
        <table className="w-full mb-8">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Description</th>
              <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">SAC</th>
              <th className="px-4 py-3 text-center text-sm font-medium text-gray-500">Qty</th>
              <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">Rate</th>
              <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">CGST (9%)</th>
              <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">SGST (9%)</th>
              <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">Total</th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {invoice.items.map((item, index) => (
              <tr key={index}>
                <td className="px-4 py-4 text-gray-900">{item.description}</td>
                <td className="px-4 py-4 text-gray-600">{item.sac}</td>
                <td className="px-4 py-4 text-center text-gray-600">{item.qty}</td>
                <td className="px-4 py-4 text-right text-gray-600">₹{item.rate.toLocaleString('en-IN')}</td>
                <td className="px-4 py-4 text-right text-gray-600">₹{item.cgst.toLocaleString('en-IN')}</td>
                <td className="px-4 py-4 text-right text-gray-600">₹{item.sgst.toLocaleString('en-IN')}</td>
                <td className="px-4 py-4 text-right font-medium text-gray-900">₹{item.total.toLocaleString('en-IN')}</td>
              </tr>
            ))}
          </tbody>
        </table>

        {/* Totals */}
        <div className="flex justify-end">
          <div className="w-72">
            <div className="flex justify-between py-2">
              <span className="text-gray-600">Subtotal</span>
              <span className="text-gray-900">₹{invoice.subtotal.toLocaleString('en-IN')}</span>
            </div>
            <div className="flex justify-between py-2">
              <span className="text-gray-600">CGST (9%)</span>
              <span className="text-gray-900">₹{invoice.cgst_total.toLocaleString('en-IN')}</span>
            </div>
            <div className="flex justify-between py-2">
              <span className="text-gray-600">SGST (9%)</span>
              <span className="text-gray-900">₹{invoice.sgst_total.toLocaleString('en-IN')}</span>
            </div>
            <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
              <span className="text-gray-900">Total</span>
              <span className="text-gray-900">₹{invoice.total.toLocaleString('en-IN')}</span>
            </div>
            <div className="flex justify-between py-2 text-green-600">
              <span>Amount Paid ({invoice.payment_method})</span>
              <span>₹{invoice.amount_paid.toLocaleString('en-IN')}</span>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="mt-8 pt-8 border-t text-center text-sm text-gray-500">
          <p>Thank you for your visit!</p>
          <p className="mt-1">For queries, contact: +91-20-25678901 | info@sharmaskin.in</p>
        </div>
      </div>
    </div>
  )
}
