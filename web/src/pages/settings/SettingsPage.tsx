import { useState } from 'react'
import { Building2, Users, Bell, Shield, CreditCard, Save } from 'lucide-react'
import { cn } from '@/utils/cn'

console.log('[ClinicOS] Loading SettingsPage')

const tabs = [
  { id: 'clinic', label: 'Clinic', icon: Building2 },
  { id: 'users', label: 'Users', icon: Users },
  { id: 'notifications', label: 'Notifications', icon: Bell },
  { id: 'billing', label: 'Billing', icon: CreditCard },
  { id: 'security', label: 'Security', icon: Shield },
]

export default function SettingsPage() {
  const [activeTab, setActiveTab] = useState('clinic')
  
  console.log('[ClinicOS] SettingsPage render', { activeTab })

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
        <p className="text-gray-500">Manage your clinic settings</p>
      </div>

      <div className="flex flex-col lg:flex-row gap-6">
        {/* Tabs */}
        <div className="lg:w-64">
          <nav className="space-y-1">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={cn(
                  "w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors",
                  activeTab === tab.id
                    ? "bg-primary/10 text-primary"
                    : "text-gray-600 hover:bg-gray-100"
                )}
              >
                <tab.icon className="w-5 h-5" />
                {tab.label}
              </button>
            ))}
          </nav>
        </div>

        {/* Content */}
        <div className="flex-1">
          {activeTab === 'clinic' && (
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-6">Clinic Information</h2>
              
              <form className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">
                      Clinic Name
                    </label>
                    <input
                      type="text"
                      defaultValue="Sharma Skin & Hair Clinic"
                      className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">
                      GSTIN
                    </label>
                    <input
                      type="text"
                      defaultValue="27AADCS1234A1Z5"
                      className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">
                      Phone
                    </label>
                    <input
                      type="tel"
                      defaultValue="+91-20-25678901"
                      className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">
                      Email
                    </label>
                    <input
                      type="email"
                      defaultValue="info@sharmaskin.in"
                      className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1.5">
                    Address
                  </label>
                  <textarea
                    rows={3}
                    defaultValue="302, Aditya Heights, FC Road, Shivajinagar, Pune - 411005"
                    className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                  />
                </div>

                <div className="pt-4 border-t">
                  <h3 className="text-md font-medium text-gray-900 mb-4">Bank Details (for invoices)</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1.5">
                        Bank Name
                      </label>
                      <input
                        type="text"
                        defaultValue="HDFC Bank"
                        className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1.5">
                        Account Number
                      </label>
                      <input
                        type="text"
                        defaultValue="50100123456789"
                        className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1.5">
                        IFSC Code
                      </label>
                      <input
                        type="text"
                        defaultValue="HDFC0001234"
                        className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1.5">
                        Account Holder Name
                      </label>
                      <input
                        type="text"
                        defaultValue="Sharma Skin & Hair Clinic"
                        className="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary"
                      />
                    </div>
                  </div>
                </div>

                <div className="flex justify-end">
                  <button
                    type="submit"
                    className="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors"
                  >
                    <Save className="w-5 h-5" />
                    Save Changes
                  </button>
                </div>
              </form>
            </div>
          )}

          {activeTab === 'users' && (
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-6">Team Members</h2>
              <p className="text-gray-500">Manage users and their permissions.</p>
              {/* User management UI would go here */}
            </div>
          )}

          {activeTab === 'notifications' && (
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-6">Notification Settings</h2>
              <p className="text-gray-500">Configure WhatsApp and email notifications.</p>
              {/* Notification settings UI would go here */}
            </div>
          )}

          {activeTab === 'billing' && (
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-6">Billing & Subscription</h2>
              <p className="text-gray-500">Manage your ClinicOS subscription and payment methods.</p>
              {/* Billing UI would go here */}
            </div>
          )}

          {activeTab === 'security' && (
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-6">Security Settings</h2>
              <p className="text-gray-500">Manage password, 2FA, and session settings.</p>
              {/* Security settings UI would go here */}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
