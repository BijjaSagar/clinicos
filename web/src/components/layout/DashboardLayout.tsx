import { Outlet, Link, useLocation } from 'react-router-dom'
import { useState } from 'react'
import { cn } from '@/utils/cn'
import { useAuthStore } from '@/store/auth-store'
import { 
  LayoutDashboard, 
  Calendar, 
  Users, 
  FileText,
  MessageCircle,
  Receipt,
  CreditCard,
  PieChart,
  Camera,
  Pill,
  FlaskConical,
  Shield,
  BarChart3,
  Settings,
  Menu,
  Search,
  Bell,
  HelpCircle,
  Plus,
  ChevronDown,
  MoreHorizontal
} from 'lucide-react'

console.log('[ClinicOS] Loading DashboardLayout')

const clinicNavItems = [
  { icon: LayoutDashboard, label: 'Dashboard', href: '/dashboard' },
  { icon: Calendar, label: 'Schedule', href: '/appointments', badge: 14 },
  { icon: Users, label: 'Patients', href: '/patients' },
  { icon: FileText, label: 'EMR / Notes', href: '/emr' },
  { icon: MessageCircle, label: 'WhatsApp', href: '/whatsapp', badge: 3, badgeRed: true },
]

const billingNavItems = [
  { icon: Receipt, label: 'Invoices', href: '/billing' },
  { icon: CreditCard, label: 'Payments', href: '/payments' },
  { icon: PieChart, label: 'GST Reports', href: '/gst-reports' },
]

const clinicalNavItems = [
  { icon: Camera, label: 'Photo Vault', href: '/photo-vault' },
  { icon: Pill, label: 'Prescriptions', href: '/prescriptions' },
  { icon: FlaskConical, label: 'Lab Orders', href: '/lab-orders' },
]

const adminNavItems = [
  { icon: Shield, label: 'ABDM Centre', href: '/abdm' },
  { icon: BarChart3, label: 'Analytics', href: '/reports' },
  { icon: Settings, label: 'Settings', href: '/settings' },
]

interface NavItemProps {
  item: {
    icon: React.ElementType
    label: string
    href: string
    badge?: number
    badgeRed?: boolean
  }
  isActive: boolean
  onClick?: () => void
}

function NavItem({ item, isActive, onClick }: NavItemProps) {
  return (
    <Link
      to={item.href}
      onClick={onClick}
      className={cn(
        "nav-item",
        isActive && "active"
      )}
    >
      <item.icon className="w-[18px] h-[18px]" />
      <span>{item.label}</span>
      {item.badge && (
        <span className={cn(
          "ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full",
          item.badgeRed 
            ? "bg-[var(--red)] text-white" 
            : "bg-[var(--blue)] text-white"
        )}>
          {item.badge}
        </span>
      )}
    </Link>
  )
}

export default function DashboardLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const location = useLocation()
  const { user, logout } = useAuthStore()

  console.log('[ClinicOS] DashboardLayout render', { pathname: location.pathname })

  const isActive = (href: string) => {
    return location.pathname === href || location.pathname.startsWith(href + '/')
  }

  const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  }

  const todayDate = new Date().toLocaleDateString('en-US', { 
    weekday: 'long', 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  })

  return (
    <div className="flex h-screen overflow-hidden">
      {/* Mobile sidebar backdrop */}
      {sidebarOpen && (
        <div 
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-50 w-60 transform transition-transform lg:static lg:translate-x-0",
          "flex flex-col overflow-y-auto",
          sidebarOpen ? "translate-x-0" : "-translate-x-full"
        )}
        style={{ background: 'var(--sidebar)' }}
      >
        {/* Logo */}
        <div className="px-5 py-5 border-b border-[#1e2535]">
          <div className="flex items-center gap-2.5">
            <div 
              className="w-8 h-8 rounded-lg flex items-center justify-center text-white font-extrabold text-[13px]"
              style={{ background: 'linear-gradient(135deg, var(--blue) 0%, var(--teal) 100%)' }}
            >
              C
            </div>
            <div>
              <div className="text-white font-display font-bold text-[15px]">ClinicOS</div>
              <div className="text-[#475569] text-[10px]">क्लिनिक ओएस</div>
            </div>
          </div>
        </div>

        {/* Clinic Switcher */}
        <div className="mx-4 my-3 p-2.5 px-3 bg-[#1e2535] rounded-lg flex items-center gap-2 cursor-pointer hover:bg-[#2a3548] transition-colors">
          <div 
            className="w-7 h-7 rounded-md flex items-center justify-center text-white text-[11px] font-bold"
            style={{ background: 'linear-gradient(135deg, #667eea, #764ba2)' }}
          >
            {getInitials(user?.clinic?.name || 'SK')}
          </div>
          <div className="flex-1 min-w-0">
            <div className="text-[#e2e8f0] text-xs font-semibold truncate">
              {user?.clinic?.name || 'Sharma Skin Clinic'}
            </div>
            <div className="text-[#64748b] text-[10px]">
              Small Clinic · Dermatology
            </div>
          </div>
          <ChevronDown className="w-3 h-3 text-[#475569]" />
        </div>

        {/* Navigation */}
        <div className="flex-1 overflow-y-auto">
          {/* Clinic Section */}
          <div className="px-3 pt-4 pb-2">
            <div className="text-[#374151] text-[10px] font-semibold tracking-wider uppercase px-2.5 mb-1.5">
              Clinic
            </div>
            {clinicNavItems.map((item) => (
              <NavItem 
                key={item.href} 
                item={item} 
                isActive={isActive(item.href)}
                onClick={() => setSidebarOpen(false)}
              />
            ))}
          </div>

          {/* Billing Section */}
          <div className="px-3 pt-4 pb-2">
            <div className="text-[#374151] text-[10px] font-semibold tracking-wider uppercase px-2.5 mb-1.5">
              Billing
            </div>
            {billingNavItems.map((item) => (
              <NavItem 
                key={item.href} 
                item={item} 
                isActive={isActive(item.href)}
                onClick={() => setSidebarOpen(false)}
              />
            ))}
          </div>

          {/* Clinical Section */}
          <div className="px-3 pt-4 pb-2">
            <div className="text-[#374151] text-[10px] font-semibold tracking-wider uppercase px-2.5 mb-1.5">
              Clinical
            </div>
            {clinicalNavItems.map((item) => (
              <NavItem 
                key={item.href} 
                item={item} 
                isActive={isActive(item.href)}
                onClick={() => setSidebarOpen(false)}
              />
            ))}
          </div>

          {/* Admin Section */}
          <div className="px-3 pt-4 pb-2">
            <div className="text-[#374151] text-[10px] font-semibold tracking-wider uppercase px-2.5 mb-1.5">
              Admin
            </div>
            {adminNavItems.map((item) => (
              <NavItem 
                key={item.href} 
                item={item} 
                isActive={isActive(item.href)}
                onClick={() => setSidebarOpen(false)}
              />
            ))}
          </div>
        </div>

        {/* User Profile */}
        <div className="mt-auto p-4 border-t border-[#1e2535]">
          <div className="flex items-center gap-2.5">
            <div 
              className="w-[34px] h-[34px] rounded-full flex items-center justify-center text-white font-bold text-[13px]"
              style={{ background: 'linear-gradient(135deg, #0891b2, #059669)' }}
            >
              {getInitials(user?.name || 'PS')}
            </div>
            <div className="flex-1 min-w-0">
              <div className="text-[#e2e8f0] text-xs font-semibold truncate">
                {user?.name || 'Dr. Priya Sharma'}
              </div>
              <div className="text-[#64748b] text-[10px]">
                {user?.specialty || 'Dermatologist'} · {user?.role || 'MD'}
              </div>
            </div>
            <button 
              onClick={() => logout()}
              className="text-[#475569] hover:text-[#e2e8f0] transition-colors"
            >
              <MoreHorizontal className="w-4 h-4" />
            </button>
          </div>
        </div>
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Top Bar */}
        <header 
          className="h-[60px] flex items-center gap-4 px-7 flex-shrink-0 sticky top-0 z-10"
          style={{ background: 'white', borderBottom: '1px solid var(--border)' }}
        >
          <button
            onClick={() => setSidebarOpen(true)}
            className="p-2 rounded-lg text-[var(--text2)] hover:bg-[var(--bg)] lg:hidden"
          >
            <Menu className="w-6 h-6" />
          </button>

          <div>
            <span className="font-display text-base font-bold text-[var(--dark)]">
              Good morning, {user?.name?.split(' ')[0] || 'Doctor'} 👋
            </span>
            <span className="text-[var(--text3)] text-[13px] ml-1">
              · {todayDate}
            </span>
          </div>

          <div className="flex items-center gap-3 ml-auto">
            {/* Search */}
            <div 
              className="flex items-center gap-2 px-3.5 py-[7px] rounded-lg w-[220px]"
              style={{ background: 'var(--bg)', border: '1px solid var(--border)' }}
            >
              <Search className="w-4 h-4 text-[var(--text3)]" />
              <input 
                type="text" 
                placeholder="Search patients..." 
                className="bg-transparent border-none outline-none text-[13px] text-[var(--text)] w-full placeholder:text-[var(--text3)]"
              />
            </div>

            {/* Notifications */}
            <button 
              className="w-9 h-9 rounded-lg flex items-center justify-center relative"
              style={{ background: 'var(--bg)', border: '1px solid var(--border)' }}
            >
              <Bell className="w-[15px] h-[15px] text-[var(--text2)]" />
              <div className="absolute top-1.5 right-1.5 w-[7px] h-[7px] rounded-full bg-[var(--red)] border-[1.5px] border-white" />
            </button>

            {/* Help */}
            <button 
              className="w-9 h-9 rounded-lg flex items-center justify-center"
              style={{ background: 'var(--bg)', border: '1px solid var(--border)' }}
            >
              <HelpCircle className="w-[15px] h-[15px] text-[var(--text2)]" />
            </button>

            {/* New Patient */}
            <button className="btn-primary flex items-center gap-1.5">
              <Plus className="w-4 h-4" />
              New Patient
            </button>
          </div>
        </header>

        {/* Page Content */}
        <main className="flex-1 overflow-auto p-7" style={{ background: 'var(--bg)' }}>
          <Outlet />
        </main>
      </div>
    </div>
  )
}
