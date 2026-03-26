import { useState } from 'react'
import { Link } from 'react-router-dom'
import { cn } from '@/utils/cn'
import { Shield, Sparkles, ArrowRight } from 'lucide-react'

console.log('[ClinicOS] Loading DashboardPage')

// Mock data matching the HTML design
const mockData = {
  stats: {
    todaysPatients: 14,
    patientsDelta: '+3',
    todaysRevenue: 18400,
    revenueDelta: '+12%',
    pendingCollections: 9200,
    pendingInvoices: 3,
    noShowRate: 8,
    noShowDelta: '+2%',
  },
  schedule: [
    { id: 1, time: '10:30', name: 'Priya Mehta', initials: 'PM', type: 'Dermatology · Follow-up #4 · ABHA linked', status: 'in-consultation', gradient: 'from-amber-500 to-red-500', isCurrent: true },
    { id: 2, time: '10:50', name: 'Rajesh Kumar', initials: 'RK', type: 'Dermatology · LASER Session #2', status: 'waiting', token: 7, gradient: 'from-cyan-600 to-indigo-500' },
    { id: 3, time: '11:15', name: 'Ananya Patil', initials: 'AP', type: 'Dermatology · New Patient · Psoriasis', status: 'confirmed', gradient: 'from-violet-500 to-pink-500' },
    { id: 4, time: '11:40', name: 'Vikram Shah', initials: 'VS', type: 'Dermatology · PRP Session · Hair Loss', status: 'confirmed', gradient: 'from-emerald-600 to-cyan-600' },
    { id: 5, time: '12:00', name: 'Neha Joshi', initials: 'NJ', type: 'Dermatology · Chemical Peel Follow-up', status: 'waiting', token: 8, gradient: 'from-orange-500 to-yellow-500' },
    { id: 6, time: '09:00', name: 'Meera Kapoor', initials: 'MK', type: 'Dermatology · Initial Consultation', status: 'noshow', gradient: 'from-gray-400 to-gray-500', faded: true },
    { id: 7, time: '09:30', name: 'Suresh Deshpande', initials: 'SD', type: 'Dermatology · Acne Review', status: 'done', gradient: 'from-indigo-500 to-violet-500', faded: true },
  ],
  queue: {
    current: { number: 6, name: 'Priya Mehta', eta: 'Est. 8 mins remaining' },
    waiting: [
      { number: 7, name: 'Rajesh Kumar', type: 'LASER Session', wait: '~12 min' },
      { number: 8, name: 'Neha Joshi', type: 'Chemical Peel', wait: '~28 min' },
      { number: 9, name: 'Ananya Patil', type: 'Not arrived yet', wait: '11:15', faded: true },
    ],
  },
  whatsapp: [
    { id: 1, type: 'reminder', title: 'Reminder sent — Ananya Patil', message: '"Your appointment is at 11:15 AM today..."', status: 'delivered', time: '09:15' },
    { id: 2, type: 'prescription', title: 'Prescription — Suresh Deshpande', message: 'e-Prescription + AI consultation summary sent', status: 'delivered', time: '09:51' },
    { id: 3, type: 'reply', title: 'Reply — Meera Kapoor', message: '"Can I reschedule to tomorrow morning?"', status: 'unread', time: '10:02' },
  ],
  weeklyRevenue: {
    total: '₹1.04L',
    collected: 94800,
    pending: 9200,
    gst: 3240,
    days: [
      { label: 'Mon', value: 52, color: 'bg-blue-200' },
      { label: 'Tue', value: 78, color: 'bg-blue-300' },
      { label: 'Wed', value: 64, color: 'bg-blue-300' },
      { label: 'Thu', value: 96, color: 'bg-[var(--blue)]', active: true },
      { label: 'Fri', value: 32, color: 'bg-gray-200' },
      { label: 'Sat', value: 16, color: 'bg-gray-200' },
    ],
  },
  recentInvoices: [
    { id: 1, name: 'Suresh Deshpande', initials: 'SD', service: 'Consultation + Topical Rx', amount: 1800, status: 'paid', method: 'UPI', gradient: 'from-indigo-500 to-violet-500' },
    { id: 2, name: 'Priya Mehta', initials: 'PM', service: 'Chem Peel + Consultation', amount: 4200, status: 'paid', method: 'Card', gradient: 'from-amber-500 to-red-500' },
    { id: 3, name: 'Rajesh Kumar', initials: 'RK', service: 'LASER Session #2', amount: 5500, status: 'due', method: 'Link sent', gradient: 'from-cyan-600 to-emerald-600' },
    { id: 4, name: 'Ananya Patil', initials: 'AP', service: 'New Patient + Assessment', amount: 2200, status: 'partial', method: 'Advance: ₹500', gradient: 'from-violet-500 to-pink-500' },
    { id: 5, name: 'Vikram Shah', initials: 'VS', service: 'PRP Session #1', amount: 8000, status: 'paid', method: 'UPI', gradient: 'from-emerald-600 to-cyan-600' },
  ],
  aiSuggestions: [
    { icon: '📋', title: "Start Priya Mehta's note", description: "Patient is in consultation now. Tap to open Dermatology EMR with last visit pre-filled." },
    { icon: '💊', title: 'Prescription template ready', description: 'Based on Acne Grade 3 — suggested: Adapalene 0.1% + Clindamycin 1%. Review and send.' },
    { icon: '📆', title: 'Recall due — 6 patients', description: 'Psoriasis patients due for 6-week review. Send WhatsApp recall batch?' },
  ],
  visitTypes: [
    { label: 'Consultation', value: 65, color: 'var(--blue)' },
    { label: 'LASER', value: 18, color: 'var(--teal)' },
    { label: 'PRP', value: 10, color: '#8b5cf6' },
    { label: 'Chem Peel', value: 7, color: 'var(--amber)' },
  ],
}

const statusStyles: Record<string, { pill: string; text: string }> = {
  'in-consultation': { pill: 'status-in-consultation', text: 'In Consultation' },
  'waiting': { pill: 'status-waiting', text: 'Waiting' },
  'confirmed': { pill: 'status-confirmed', text: 'Confirmed' },
  'done': { pill: 'status-done', text: 'Done' },
  'noshow': { pill: 'status-noshow', text: 'No-show' },
}

export default function DashboardPage() {
  const [scheduleFilter, setScheduleFilter] = useState('all')

  console.log('[ClinicOS] DashboardPage render')

  return (
    <div className="space-y-6">
      {/* ABDM Strip */}
      <div 
        className="rounded-xl p-5 flex items-center gap-4"
        style={{ background: 'linear-gradient(135deg, #0d1117 0%, #0d1f3c 100%)' }}
      >
        <div 
          className="w-11 h-11 rounded-[10px] flex items-center justify-center text-xl"
          style={{ background: 'rgba(20,71,230,0.2)', border: '1px solid rgba(20,71,230,0.3)' }}
        >
          <Shield className="w-5 h-5 text-blue-400" />
        </div>
        <div className="flex-1">
          <h4 className="text-white text-sm font-bold">ABDM Compliance Active</h4>
          <p className="text-[#64748b] text-xs mt-0.5">
            ABHA creation live · HFR registered · FHIR R4 records syncing · 38 records shared this month
          </p>
        </div>
        <div className="flex gap-2">
          <span className="px-3 py-1 rounded-full text-[11px] font-semibold bg-[rgba(5,150,105,0.15)] text-[#6ee7b7]">
            M1 ✓ Live
          </span>
          <span className="px-3 py-1 rounded-full text-[11px] font-semibold bg-[rgba(5,150,105,0.15)] text-[#6ee7b7]">
            HFR ✓
          </span>
          <span className="px-3 py-1 rounded-full text-[11px] font-semibold bg-[#1e2535] text-[#94a3b8]">
            M2 In Progress
          </span>
        </div>
      </div>

      {/* Stats Row */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard 
          label="Today's Patients" 
          value={mockData.stats.todaysPatients} 
          delta={mockData.stats.patientsDelta}
          deltaType="up"
          subtext="vs yesterday"
        />
        <StatCard 
          label="Today's Revenue" 
          value={`₹${mockData.stats.todaysRevenue.toLocaleString('en-IN')}`} 
          delta={mockData.stats.revenueDelta}
          deltaType="up"
          subtext="vs last Thursday"
        />
        <StatCard 
          label="Pending Collections" 
          value={`₹${mockData.stats.pendingCollections.toLocaleString('en-IN')}`} 
          delta={`${mockData.stats.pendingInvoices} invoices`}
          deltaType="neutral"
          subtext="outstanding"
        />
        <StatCard 
          label="No-show Rate" 
          value={`${mockData.stats.noShowRate}%`} 
          delta={mockData.stats.noShowDelta}
          deltaType="down"
          subtext="vs last week"
        />
      </div>

      {/* Schedule + Queue Row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {/* Today's Schedule */}
        <div className="lg:col-span-2 card">
          <div className="card-header">
            <h3>Today's Schedule</h3>
            <div className="flex items-center gap-2">
              <div className="flex gap-0.5 bg-[#f8fafc] p-1 rounded-lg">
                {['All (14)', 'Waiting (4)', 'Done (7)'].map((tab, i) => (
                  <button 
                    key={tab}
                    onClick={() => setScheduleFilter(['all', 'waiting', 'done'][i])}
                    className={cn(
                      "tab-btn px-2.5 py-1 text-[11px]",
                      scheduleFilter === ['all', 'waiting', 'done'][i] && 'active'
                    )}
                  >
                    {tab}
                  </button>
                ))}
              </div>
              <Link to="/appointments" className="card-link">Full calendar →</Link>
            </div>
          </div>
          <div className="p-3 space-y-0.5">
            {mockData.schedule.map((apt) => (
              <div 
                key={apt.id}
                className={cn(
                  "flex items-center gap-3 p-2.5 px-3 rounded-lg cursor-pointer transition-colors hover:bg-[var(--bg)]",
                  apt.isCurrent && "bg-[#f0fdf4]",
                  apt.faded && "opacity-50"
                )}
              >
                <div className="text-xs font-semibold text-[var(--text3)] w-[52px] text-right flex-shrink-0">
                  {apt.time}
                </div>
                <div 
                  className={cn("w-[34px] h-[34px] rounded-full flex items-center justify-center text-white font-bold text-[13px] flex-shrink-0 bg-gradient-to-br", apt.gradient)}
                >
                  {apt.initials}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="text-[13px] font-semibold text-[var(--dark)]">{apt.name}</div>
                  <div className="text-[11px] text-[var(--text3)] mt-0.5">{apt.type}</div>
                </div>
                <div className="flex-shrink-0">
                  <span className={cn("status-pill", statusStyles[apt.status]?.pill)}>
                    {apt.status === 'waiting' ? `Waiting · Token ${apt.token}` : statusStyles[apt.status]?.text}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Queue + WhatsApp */}
        <div className="space-y-4">
          {/* Live Queue */}
          <div className="card">
            <div className="card-header">
              <h3>Live Queue</h3>
              <Link to="/queue" className="card-link">Manage →</Link>
            </div>
            <div className="p-4">
              <div 
                className="rounded-[10px] p-4 text-center mb-3"
                style={{ background: 'linear-gradient(135deg, var(--blue) 0%, var(--teal) 100%)' }}
              >
                <div className="text-white/70 text-[11px] font-semibold tracking-wider uppercase">Now Serving</div>
                <div className="font-display text-5xl font-extrabold text-white leading-none">{mockData.queue.current.number}</div>
                <div className="text-white/80 text-[13px] mt-1">{mockData.queue.current.name} · {mockData.queue.current.eta}</div>
              </div>
              <div className="space-y-1.5">
                {mockData.queue.waiting.map((item) => (
                  <div 
                    key={item.number}
                    className={cn(
                      "flex items-center gap-2.5 p-2 px-2.5 rounded-lg bg-[var(--bg)]",
                      item.faded && "opacity-50"
                    )}
                  >
                    <div className="w-7 h-7 rounded-md bg-white border border-[var(--border)] flex items-center justify-center text-xs font-bold text-[var(--text2)]">
                      {item.number}
                    </div>
                    <div className="flex-1">
                      <h5 className="text-xs font-semibold text-[var(--dark)]">{item.name}</h5>
                      <p className="text-[11px] text-[var(--text3)]">{item.type}</p>
                    </div>
                    <div className="text-[11px] text-[var(--text3)]">{item.wait}</div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* WhatsApp Activity */}
          <div className="card">
            <div className="card-header">
              <h3>WhatsApp Activity</h3>
              <Link to="/whatsapp" className="card-link">View all →</Link>
            </div>
            <div className="p-3 space-y-2">
              {mockData.whatsapp.map((item) => (
                <div 
                  key={item.id}
                  className={cn(
                    "flex items-start gap-2.5 p-2.5 px-3 rounded-lg",
                    item.status === 'unread' ? "bg-[#fff7ed]" : "bg-[var(--bg)]"
                  )}
                >
                  <div className="w-7 h-7 rounded-[7px] bg-[#25D366] flex items-center justify-center flex-shrink-0 text-xs">
                    💬
                  </div>
                  <div className="flex-1 min-w-0">
                    <h5 className="text-xs font-semibold text-[var(--dark)]">{item.title}</h5>
                    <p className="text-[11px] text-[var(--text3)] mt-0.5">{item.message}</p>
                    {item.status === 'delivered' && (
                      <div className="text-[#25D366] text-[10px] mt-0.5">✓✓ Delivered</div>
                    )}
                    {item.status === 'unread' && (
                      <div className="text-[var(--amber)] text-[10px] font-semibold mt-0.5">● Unread · Needs reply</div>
                    )}
                  </div>
                  <div className="text-[10px] text-[var(--text3)] flex-shrink-0">{item.time}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Revenue + Billing + AI Row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {/* Revenue Chart */}
        <div className="card">
          <div className="card-header">
            <h3>Revenue — This Week</h3>
            <div className="font-display text-lg font-extrabold text-[var(--dark)]">{mockData.weeklyRevenue.total}</div>
          </div>
          <div className="card-body">
            {/* Chart */}
            <div className="relative h-[140px]">
              {/* Grid lines */}
              <div className="absolute inset-0 flex flex-col justify-between pointer-events-none pb-2">
                {[0, 1, 2, 3].map((i) => (
                  <div key={i} className="border-t border-dashed border-[#f1f5f9]" />
                ))}
              </div>
              {/* Bars */}
              <div className="relative h-full flex items-end gap-2 pb-2">
                {mockData.weeklyRevenue.days.map((day) => (
                  <div key={day.label} className="flex-1 flex flex-col items-center">
                    <div 
                      className={cn("w-full rounded-t transition-opacity hover:opacity-80 cursor-pointer", day.color)}
                      style={{ height: `${day.value}px` }}
                    />
                    <div className={cn(
                      "text-[10px] text-[var(--text3)] mt-1.5 text-center",
                      day.active && "font-bold text-[var(--blue)]"
                    )}>
                      {day.label} {day.active && '●'}
                    </div>
                  </div>
                ))}
              </div>
            </div>
            {/* Summary */}
            <div className="flex gap-4 mt-3 pt-3 border-t border-[var(--border)]">
              <div>
                <div className="text-[10px] text-[var(--text3)]">Collected</div>
                <div className="text-sm font-bold text-[var(--green)]">₹{mockData.weeklyRevenue.collected.toLocaleString('en-IN')}</div>
              </div>
              <div>
                <div className="text-[10px] text-[var(--text3)]">Pending</div>
                <div className="text-sm font-bold text-[var(--amber)]">₹{mockData.weeklyRevenue.pending.toLocaleString('en-IN')}</div>
              </div>
              <div>
                <div className="text-[10px] text-[var(--text3)]">GST</div>
                <div className="text-sm font-bold text-[var(--text2)]">₹{mockData.weeklyRevenue.gst.toLocaleString('en-IN')}</div>
              </div>
            </div>
          </div>
        </div>

        {/* Recent Invoices */}
        <div className="card">
          <div className="card-header">
            <h3>Recent Invoices</h3>
            <Link to="/billing" className="card-link">All invoices →</Link>
          </div>
          <div className="p-2 space-y-0.5">
            {mockData.recentInvoices.map((inv) => (
              <div key={inv.id} className="flex items-center gap-2.5 p-2 px-2.5 rounded-lg hover:bg-[var(--bg)] cursor-pointer">
                <div className={cn("w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-xs bg-gradient-to-br", inv.gradient)}>
                  {inv.initials}
                </div>
                <div className="flex-1 min-w-0">
                  <h5 className="text-xs font-semibold text-[var(--dark)]">{inv.name}</h5>
                  <p className="text-[11px] text-[var(--text3)]">{inv.service}</p>
                </div>
                <div className="text-right">
                  <div className="text-[13px] font-bold text-[var(--dark)]">₹{inv.amount.toLocaleString('en-IN')}</div>
                  <div className={cn(
                    "text-[10px] font-semibold mt-0.5",
                    inv.status === 'paid' ? "text-[var(--green)]" : "text-[var(--amber)]"
                  )}>
                    {inv.status === 'paid' ? `Paid · ${inv.method}` : inv.method}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* AI Panel + Visit Types */}
        <div className="space-y-4">
          {/* AI Suggestions */}
          <div 
            className="rounded-xl p-4"
            style={{ 
              background: 'linear-gradient(135deg, rgba(20,71,230,0.04) 0%, rgba(8,145,178,0.04) 100%)',
              border: '1px solid rgba(20,71,230,0.12)'
            }}
          >
            <div className="flex items-center gap-2 mb-3">
              <div className="w-7 h-7 rounded-[7px] bg-[var(--blue)] flex items-center justify-center text-white text-xs">
                <Sparkles className="w-3.5 h-3.5" />
              </div>
              <h4 className="text-[13px] font-bold text-[var(--dark)]">AI Suggestions</h4>
              <span className="ml-auto text-[11px] text-[var(--blue)] font-semibold cursor-pointer hover:underline">Dismiss all</span>
            </div>
            <div className="space-y-2">
              {mockData.aiSuggestions.map((sug, i) => (
                <div 
                  key={i}
                  className="p-2.5 px-3 bg-white rounded-lg border border-[var(--border)] cursor-pointer hover:border-[var(--blue)] transition-colors"
                >
                  <h5 className="text-xs font-semibold text-[var(--dark)] mb-0.5">{sug.icon} {sug.title}</h5>
                  <p className="text-[11px] text-[var(--text3)] leading-relaxed">{sug.description}</p>
                </div>
              ))}
            </div>
          </div>

          {/* Visits by Type */}
          <div className="card">
            <div className="card-header"><h3>Visits by Type</h3></div>
            <div className="card-body space-y-2">
              {mockData.visitTypes.map((vt) => (
                <div key={vt.label} className="flex items-center gap-2.5">
                  <div className="text-[11px] text-[var(--text2)] w-[90px] flex-shrink-0">{vt.label}</div>
                  <div className="flex-1 h-1.5 rounded-full bg-[var(--bg)] overflow-hidden">
                    <div 
                      className="h-full rounded-full"
                      style={{ width: `${vt.value}%`, background: vt.color }}
                    />
                  </div>
                  <div className="text-xs font-semibold text-[var(--dark)] w-[30px] text-right">{vt.value}%</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

// Stat Card Component
function StatCard({ 
  label, 
  value, 
  delta, 
  deltaType, 
  subtext 
}: { 
  label: string
  value: string | number
  delta: string
  deltaType: 'up' | 'down' | 'neutral'
  subtext: string
}) {
  return (
    <div className="card p-5">
      <div className="text-xs text-[var(--text3)] font-medium mb-2">{label}</div>
      <div className="font-display text-[28px] font-extrabold text-[var(--dark)] leading-none">{value}</div>
      <div className="flex items-center gap-1.5 mt-2">
        <span className={cn(
          "delta",
          deltaType === 'up' && "delta-up",
          deltaType === 'down' && "delta-down",
          deltaType === 'neutral' && "delta-neutral"
        )}>
          {delta}
        </span>
        <span className="text-xs text-[var(--text3)]">{subtext}</span>
      </div>
    </div>
  )
}
