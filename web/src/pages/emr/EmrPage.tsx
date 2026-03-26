import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { cn } from '@/utils/cn'
import { 
  ArrowLeft, 
  Bell, 
  MessageCircle, 
  Receipt, 
  Check,
  Mic,
  Sparkles,
  ChevronDown,
  Plus,
  X,
  Printer
} from 'lucide-react'

console.log('[ClinicOS] Loading EmrPage')

// Mock data matching the HTML design
const mockPatient = {
  id: 1,
  name: 'Priya Mehta',
  initials: 'P',
  age: 28,
  gender: 'F',
  dob: '12 Mar 1998',
  phone: '+91 98201 47382',
  bloodGroup: 'A+',
  visitNumber: 4,
  abha: '91-2847-3910-4562',
  status: 'in-consultation',
}

const mockVisits = [
  {
    id: 1,
    date: 'Today · 27 Mar 2026',
    type: 'Follow-up #4 · Chemical Peel',
    summary: 'PASI 8.4 · IGA Grade 3 · Chem Peel 30% SA done',
    isCurrent: true,
    chips: [{ label: 'Current', color: 'bg-[#ecfdf5] text-[#059669]' }],
  },
  {
    id: 2,
    date: '14 Feb 2026',
    type: 'Follow-up #3 · Laser Session',
    summary: 'PASI 11.2 → 8.6 · Q-switch 532nm · 3J/cm²',
    chips: [
      { label: 'LASER', color: 'bg-[#eff3ff] text-[#1447e6]' },
      { label: 'Rx sent', color: 'bg-[#f1f5f9] text-[#64748b]' },
    ],
  },
  {
    id: 3,
    date: '10 Jan 2026',
    type: 'Follow-up #2 · Review',
    summary: 'Adapalene 0.1% response moderate · added doxycycline',
    chips: [{ label: 'Rx changed', color: 'bg-[#f1f5f9] text-[#64748b]' }],
  },
  {
    id: 4,
    date: '28 Nov 2025',
    type: 'Initial Consultation',
    summary: 'New patient · Acne Grade 3 · Psoriasis patches bilateral',
    chips: [{ label: 'New Patient', color: 'bg-[#fef9c3] text-[#a16207]' }],
  },
]

const mockAlerts = [
  { icon: '⚠️', text: 'Doxycycline prescribed — check for allergy (none on record)', color: 'text-[var(--amber)]' },
  { icon: '💊', text: 'Last Rx: 14 Feb — Adapalene 0.1% + Doxy 100mg OD', color: 'text-[var(--text2)]' },
  { icon: '📸', text: '12 photos on file · 3 body regions', color: 'text-[var(--blue)]' },
]

const mockLesions = [
  { id: 1, color: '#ef4444', location: 'Left Cheek', type: 'Plaque (raised) · 2.0 cm · Red · Well-defined', details: 'Distribution: Localised · Surface: Rough, scaling' },
  { id: 2, color: '#ef4444', location: 'Forehead', type: 'Papule · Multiple · 0.3–0.5 cm · Erythematous', details: 'Distribution: Scattered · Closed comedones +' },
  { id: 3, color: '#f59e0b', location: 'Chin / Jaw', type: 'Macule · Post-inflammatory · Hyperpigmented', details: 'Residual PIH from previous lesions' },
  { id: 4, color: '#6366f1', location: 'Left Upper Arm', type: 'Plaque · 3.0 × 4.0 cm · Silvery scale', details: 'Psoriasis patch — stable vs last visit' },
]

const mockDrugs = [
  { id: 1, name: 'Adapalene 0.1% gel', generic: 'Differin · Topical retinoid', dose: 'Pea size', frequency: 'Once nocte', duration: 'Continue', instructions: 'Affected areas only. Avoid eye area.' },
  { id: 2, name: 'Clindamycin 1% lotion', generic: 'Clindac A · Antibiotic topical', dose: 'Thin layer', frequency: 'BD (morning+night)', duration: '6 weeks', instructions: 'Before moisturiser. Avoid mixing with Adapalene.' },
  { id: 3, name: 'Doxycycline 100mg', generic: 'Doxt-SL · Oral antibiotic', dose: '1 tablet', frequency: 'OD (morning)', duration: '4 weeks', instructions: 'After food. Avoid dairy 1 hour before/after.' },
  { id: 4, name: 'Sunscreen SPF 50+', generic: 'Broad spectrum · UVA+UVB', dose: 'Adequate amount', frequency: 'Every 3 hours outdoors', duration: 'Daily ongoing', instructions: 'Apply before stepping out. Reapply after sweating.' },
]

const procedures = ['Chemical Peel', 'LASER', 'PRP', 'Botox', 'Fillers', 'Microneedling']

const emrTabs = ['📋 Visit Note', '💊 Prescription', '📷 Photos (12)', '📈 Progress', '🔬 Investigations', '🧾 Billing']

export default function EmrPage() {
  const { patientId } = useParams()
  const [activeTab, setActiveTab] = useState(0)
  const [selectedVisit, setSelectedVisit] = useState(1)
  const [selectedProcedure, setSelectedProcedure] = useState('Chemical Peel')
  const [expandedSections, setExpandedSections] = useState<Record<string, boolean>>({
    chief: true,
    lesion: true,
    scales: true,
    procedure: true,
    prescription: true,
    plan: true,
  })

  console.log('[ClinicOS] EmrPage render', { patientId })

  const toggleSection = (section: string) => {
    setExpandedSections(prev => ({ ...prev, [section]: !prev[section] }))
  }

  return (
    <div className="h-[calc(100vh-60px)] flex flex-col -m-7 -mb-7">
      {/* Top Bar with patient info */}
      <div 
        className="flex items-center gap-3 px-7 py-3 flex-shrink-0"
        style={{ background: 'white', borderBottom: '1px solid var(--border)' }}
      >
        <span className="text-[13px] text-[var(--text3)]">Patients /</span>
        <span className="text-sm font-bold text-[var(--dark)]">{mockPatient.name}</span>
        <div className="flex items-center gap-1.5 ml-1">
          <div className="w-2 h-2 rounded-full bg-[var(--green)] pulse-dot" />
          <span className="text-xs font-semibold text-[var(--green)]">In Consultation</span>
        </div>
        <div className="flex items-center gap-2 ml-auto">
          <button className="btn btn-ghost text-sm py-1.5">
            <Bell className="w-4 h-4" /> Remind
          </button>
          <button className="btn btn-ghost text-sm py-1.5">
            <MessageCircle className="w-4 h-4" /> WhatsApp
          </button>
          <button className="btn btn-ghost text-sm py-1.5">
            <Receipt className="w-4 h-4" /> Invoice
          </button>
          <button className="btn-green px-4 py-1.5 text-sm rounded-lg font-semibold flex items-center gap-1.5">
            <Check className="w-4 h-4" /> Complete Visit
          </button>
        </div>
      </div>

      {/* Patient Header Bar */}
      <div 
        className="flex items-center gap-5 px-7 py-4 flex-shrink-0"
        style={{ background: 'white', borderBottom: '1px solid var(--border)' }}
      >
        <div 
          className="w-[52px] h-[52px] rounded-full flex items-center justify-center text-white font-extrabold text-xl flex-shrink-0"
          style={{ background: 'linear-gradient(135deg, #f59e0b, #ef4444)' }}
        >
          {mockPatient.initials}
        </div>
        <div>
          <div className="font-display text-lg font-bold text-[var(--dark)]">{mockPatient.name}</div>
          <div className="flex items-center gap-3 mt-1 flex-wrap">
            <MetaChip label="Age:" value={`${mockPatient.age}${mockPatient.gender}`} />
            <MetaChip label="DOB:" value={mockPatient.dob} />
            <MetaChip label="📞" value={mockPatient.phone} />
            <MetaChip label="Blood:" value={mockPatient.bloodGroup} />
            <MetaChip label="Visit #:" value={mockPatient.visitNumber.toString()} />
            <div 
              className="flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold text-white"
              style={{ background: 'linear-gradient(135deg, #f97316, #ef4444)' }}
            >
              🛡️ ABHA: {mockPatient.abha}
            </div>
          </div>
        </div>
        <div className="ml-auto flex gap-2">
          <button className="btn btn-ghost text-xs py-1.5">Edit Profile</button>
          <button className="btn btn-ghost text-xs py-1.5">Medical History</button>
        </div>
      </div>

      {/* EMR Tabs */}
      <div 
        className="flex gap-0.5 px-7 flex-shrink-0"
        style={{ background: 'white', borderBottom: '1px solid var(--border)' }}
      >
        {emrTabs.map((tab, i) => (
          <button
            key={tab}
            onClick={() => setActiveTab(i)}
            className={cn(
              "px-5 py-3.5 text-[13px] font-semibold cursor-pointer border-b-2 transition-colors",
              activeTab === i 
                ? "text-[var(--blue)] border-[var(--blue)]" 
                : "text-[var(--text3)] border-transparent hover:text-[var(--text2)]"
            )}
          >
            {tab}
          </button>
        ))}
      </div>

      {/* EMR Body */}
      <div className="flex flex-1 min-h-0">
        {/* Visit Timeline Sidebar */}
        <div 
          className="w-[280px] flex-shrink-0 overflow-y-auto p-4"
          style={{ background: 'white', borderRight: '1px solid var(--border)' }}
        >
          <div className="text-xs font-bold text-[var(--text3)] tracking-wider uppercase mb-3">
            Visit History
          </div>
          
          {mockVisits.map((visit) => (
            <div
              key={visit.id}
              onClick={() => setSelectedVisit(visit.id)}
              className={cn(
                "border rounded-[10px] p-3 mb-2 cursor-pointer transition-all",
                selectedVisit === visit.id 
                  ? "border-[var(--blue)] bg-[var(--blue-light)]" 
                  : "border-[var(--border)] hover:border-[var(--blue)] hover:bg-[var(--blue-light)]"
              )}
            >
              <div className="text-[11px] font-bold text-[var(--blue)]">{visit.date}</div>
              <div className="text-[13px] font-semibold text-[var(--dark)] mt-0.5">{visit.type}</div>
              <div className="text-[11px] text-[var(--text3)] mt-1 leading-relaxed">{visit.summary}</div>
              <div className="flex gap-1 mt-1.5 flex-wrap">
                {visit.chips.map((chip, i) => (
                  <span key={i} className={cn("px-2 py-0.5 rounded text-[10px] font-semibold", chip.color)}>
                    {chip.label}
                  </span>
                ))}
              </div>
            </div>
          ))}

          {/* Alerts */}
          <div className="mt-4 p-3 bg-[var(--bg)] rounded-[10px]">
            <div className="text-[11px] font-bold text-[var(--text3)] tracking-wider uppercase mb-2.5">
              Alerts
            </div>
            <div className="space-y-1.5">
              {mockAlerts.map((alert, i) => (
                <div key={i} className="flex gap-1.5 items-start text-[11px]">
                  <span>{alert.icon}</span>
                  <span className={cn("font-medium", alert.color)}>{alert.text}</span>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Main EMR Form */}
        <div className="flex-1 overflow-y-auto p-6" style={{ background: 'var(--bg)' }}>
          {/* AI Dictation Strip */}
          <div 
            className="rounded-[10px] p-3.5 px-4 flex items-center gap-3 mb-4"
            style={{ 
              background: 'linear-gradient(135deg, rgba(20,71,230,0.05), rgba(8,145,178,0.05))',
              border: '1.5px solid rgba(20,71,230,0.15)'
            }}
          >
            <div className="w-9 h-9 rounded-[9px] bg-[var(--blue)] flex items-center justify-center text-white flex-shrink-0">
              <Mic className="w-4 h-4" />
            </div>
            <div>
              <h4 className="text-[13px] font-bold text-[var(--dark)]">AI Dictation Mode</h4>
              <p className="text-xs text-[var(--text3)] mt-0.5">
                Tap to dictate findings — AI will auto-fill the fields below in your specialty template
              </p>
            </div>
            <div className="ml-auto flex gap-2">
              <button className="btn-primary text-xs py-1.5">
                <span className="w-2 h-2 rounded-full bg-red-400 animate-pulse" /> Start Dictation
              </button>
              <button className="btn btn-ghost text-xs py-1.5">
                <Sparkles className="w-3.5 h-3.5" /> AI Summarise
              </button>
            </div>
          </div>

          {/* Chief Complaint Section */}
          <FormSection 
            title="Chief Complaint & History" 
            expanded={expandedSections.chief}
            onToggle={() => toggleSection('chief')}
          >
            <div className="grid grid-cols-2 gap-3 mb-3">
              <FieldGroup label="Chief Complaint">
                <select className="field-select" defaultValue="acne">
                  <option value="acne">Acne — Worsening</option>
                  <option value="rash">Rash</option>
                  <option value="pigmentation">Pigmentation</option>
                  <option value="hairloss">Hair Loss</option>
                  <option value="itch">Itch</option>
                </select>
              </FieldGroup>
              <FieldGroup label="Duration">
                <input type="text" className="field-input" defaultValue="8 months" />
              </FieldGroup>
            </div>
            <div className="grid grid-cols-3 gap-3 mb-3">
              <FieldGroup label="Onset">
                <select className="field-select" defaultValue="gradual">
                  <option value="gradual">Gradual</option>
                  <option value="sudden">Sudden</option>
                  <option value="recurrent">Recurrent</option>
                </select>
              </FieldGroup>
              <FieldGroup label="Progression">
                <select className="field-select" defaultValue="worsening">
                  <option value="worsening">Worsening</option>
                  <option value="improving">Improving</option>
                  <option value="static">Static</option>
                  <option value="fluctuating">Fluctuating</option>
                </select>
              </FieldGroup>
              <FieldGroup label="Previous Treatment">
                <select className="field-select" defaultValue="yes-on">
                  <option value="yes-on">Yes — On treatment</option>
                  <option value="yes-stopped">Yes — Stopped</option>
                  <option value="no">No</option>
                </select>
              </FieldGroup>
            </div>
            <FieldGroup label="History Notes">
              <textarea 
                className="field-textarea" 
                defaultValue="Patient reports worsening acne over past 8 months. Currently on adapalene 0.1% nocte and doxycycline 100mg OD started on 14 Feb. Partial response noted. No known drug allergies. Family history of psoriasis (father). Triggers: stress, dairy."
              />
            </FieldGroup>
          </FormSection>

          {/* Body Map + Lesion Section */}
          <FormSection 
            title="Lesion Map & Skin Findings" 
            expanded={expandedSections.lesion}
            onToggle={() => toggleSection('lesion')}
          >
            <div className="flex gap-5">
              {/* Body Diagram */}
              <div className="text-center">
                <div 
                  className="bg-[var(--bg)] rounded-xl p-5 cursor-crosshair relative min-w-[160px]"
                  style={{ border: '1.5px solid var(--border)' }}
                >
                  <div className="text-[11px] font-semibold text-[var(--text3)] mb-2.5">Tap to annotate</div>
                  <svg viewBox="0 0 80 160" fill="none" className="w-[100px] mx-auto">
                    <ellipse cx="40" cy="16" rx="14" ry="15" fill="#d1d5db"/>
                    <rect x="22" y="32" width="36" height="48" rx="8" fill="#d1d5db"/>
                    <rect x="7" y="33" width="14" height="38" rx="7" fill="#d1d5db"/>
                    <rect x="59" y="33" width="14" height="38" rx="7" fill="#d1d5db"/>
                    <rect x="23" y="82" width="15" height="50" rx="7" fill="#d1d5db"/>
                    <rect x="42" y="82" width="15" height="50" rx="7" fill="#d1d5db"/>
                    {/* Lesion dots */}
                    <circle cx="36" cy="18" r="5" fill="#ef4444" opacity="0.8"/>
                    <circle cx="46" cy="14" r="4" fill="#ef4444" opacity="0.6"/>
                    <circle cx="33" cy="28" r="3.5" fill="#f59e0b" opacity="0.8"/>
                    <circle cx="50" cy="42" r="4" fill="#ef4444" opacity="0.5"/>
                  </svg>
                  <div className="text-[10px] text-[var(--text3)] mt-2">Front View</div>
                </div>
              </div>

              {/* Lesion Annotations */}
              <div className="flex-1">
                <div className="text-[11px] font-bold text-[var(--text3)] tracking-wider uppercase mb-2.5">
                  Recorded Lesions
                </div>
                {mockLesions.map((lesion) => (
                  <div key={lesion.id} className="flex items-center gap-2 p-2 px-2.5 bg-[var(--bg)] rounded-lg mb-1.5">
                    <div 
                      className="w-3 h-3 rounded-full flex-shrink-0"
                      style={{ background: lesion.color }}
                    />
                    <div className="flex-1 text-xs text-[var(--text2)]">
                      <div>
                        <strong className="text-[var(--dark)]">{lesion.location}</strong> · {lesion.type}
                      </div>
                      <div className="text-[11px] text-[var(--text3)]">{lesion.details}</div>
                    </div>
                    <button className="text-[var(--text3)] hover:text-[var(--red)] text-sm">
                      <X className="w-4 h-4" />
                    </button>
                  </div>
                ))}
                <button 
                  className="flex items-center gap-1.5 mt-2 px-3.5 py-2 rounded-lg text-xs text-[var(--text3)]"
                  style={{ border: '1.5px dashed var(--border)' }}
                >
                  <Plus className="w-3.5 h-3.5" /> Add lesion annotation
                </button>
              </div>
            </div>
          </FormSection>

          {/* Grading Scales Section */}
          <FormSection 
            title="Dermatological Grading Scales" 
            expanded={expandedSections.scales}
            onToggle={() => toggleSection('scales')}
          >
            <div className="space-y-2">
              <ScaleRow label="PASI Score" value={8.4} range="(0–72)" result="Moderate" resultType="mod" />
              <ScaleRow label="IGA Grade" value={3} range="(0–4)" result="Moderate" resultType="mod" />
              <ScaleRow label="DLQI Score" value={14} range="(0–30)" result="Large effect on QoL" resultType="sev" />
            </div>
            <div className="mt-3 p-3 bg-[var(--blue-light)] rounded-lg text-xs text-[var(--blue)]">
              <strong>vs Last Visit (14 Feb):</strong> PASI 11.2 → 8.4 (↓2.8 · 25% improvement) · IGA 3 → 3 (unchanged) · DLQI 18 → 14 (↓4)
            </div>
          </FormSection>

          {/* Procedure Section */}
          <FormSection 
            title="Procedure Performed Today" 
            expanded={expandedSections.procedure}
            onToggle={() => toggleSection('procedure')}
          >
            <div className="mb-3">
              <div className="text-[11px] font-semibold text-[var(--text3)] uppercase tracking-wider mb-2">
                Select Procedure
              </div>
              <div className="grid grid-cols-3 gap-2">
                {procedures.map((proc) => (
                  <button
                    key={proc}
                    onClick={() => setSelectedProcedure(proc)}
                    className={cn(
                      "p-2 px-3 rounded-lg text-xs font-semibold text-center transition-all",
                      selectedProcedure === proc 
                        ? "bg-[var(--blue)] text-white border-[var(--blue)]"
                        : "text-[var(--text2)] hover:border-[var(--blue)] hover:text-[var(--blue)]"
                    )}
                    style={{ border: selectedProcedure === proc ? 'none' : '1.5px solid var(--border)' }}
                  >
                    {proc}
                  </button>
                ))}
              </div>
            </div>
            <div className="grid grid-cols-3 gap-3 mb-3">
              <FieldGroup label="Agent">
                <select className="field-select" defaultValue="sa30">
                  <option value="sa30">Salicylic Acid 30%</option>
                  <option value="ga40">Glycolic Acid 40%</option>
                  <option value="tca25">TCA 25%</option>
                  <option value="jessner">Jessner's Solution</option>
                </select>
              </FieldGroup>
              <FieldGroup label="Areas Treated">
                <input type="text" className="field-input" defaultValue="Full face, T-zone" />
              </FieldGroup>
              <FieldGroup label="Session No.">
                <input type="text" className="field-input" defaultValue="3 of 6" />
              </FieldGroup>
            </div>
            <FieldGroup label="Procedure Notes">
              <textarea 
                className="field-textarea min-h-[60px]" 
                defaultValue="30% SA peel applied for 3 minutes. Patient tolerated well. Mild erythema post-procedure. Pre-cooled with ice pack. SPF 50 applied. Patient advised sun avoidance for 48 hours."
              />
            </FieldGroup>
          </FormSection>

          {/* Prescription Section */}
          <FormSection 
            title="Prescription" 
            expanded={expandedSections.prescription}
            onToggle={() => toggleSection('prescription')}
          >
            <table className="w-full">
              <thead>
                <tr className="bg-[var(--bg)]">
                  <th className="text-[11px] font-semibold text-[var(--text3)] text-left p-2 px-2.5 uppercase tracking-wider rounded-l-lg">Drug</th>
                  <th className="text-[11px] font-semibold text-[var(--text3)] text-left p-2 px-2.5 uppercase tracking-wider">Dose</th>
                  <th className="text-[11px] font-semibold text-[var(--text3)] text-left p-2 px-2.5 uppercase tracking-wider">Frequency</th>
                  <th className="text-[11px] font-semibold text-[var(--text3)] text-left p-2 px-2.5 uppercase tracking-wider">Duration</th>
                  <th className="text-[11px] font-semibold text-[var(--text3)] text-left p-2 px-2.5 uppercase tracking-wider">Instructions</th>
                  <th className="text-[11px] font-semibold text-[var(--text3)] text-left p-2 px-2.5 uppercase tracking-wider rounded-r-lg"></th>
                </tr>
              </thead>
              <tbody>
                {mockDrugs.map((drug) => (
                  <tr key={drug.id} className="border-b border-[var(--border)] hover:bg-[var(--bg)]">
                    <td className="p-2.5 px-2.5">
                      <div className="font-semibold text-[13px] text-[var(--dark)]">{drug.name}</div>
                      <div className="text-[11px] text-[var(--text3)] mt-0.5">{drug.generic}</div>
                    </td>
                    <td className="p-2.5 px-2.5 text-[13px] text-[var(--text2)]">{drug.dose}</td>
                    <td className="p-2.5 px-2.5 text-[13px] text-[var(--text2)]">{drug.frequency}</td>
                    <td className="p-2.5 px-2.5 text-[13px] text-[var(--text2)]">{drug.duration}</td>
                    <td className="p-2.5 px-2.5 text-[13px] text-[var(--text2)]">{drug.instructions}</td>
                    <td className="p-2.5 px-2.5">
                      <button className="text-[var(--text3)] hover:text-[var(--red)]">
                        <X className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            <button 
              className="flex items-center gap-2 mt-2 px-3.5 py-2.5 rounded-lg text-[13px] text-[var(--text3)] font-medium w-full"
              style={{ border: '1.5px dashed var(--border)' }}
            >
              <Plus className="w-4 h-4" /> Add drug from 40,000+ Indian drug database
            </button>
            <div className="mt-3 p-2.5 px-3.5 rounded-lg text-xs text-[var(--amber)]" style={{ background: '#fff7ed', border: '1px solid #fed7aa' }}>
              ⚠️ Drug interaction check: Adapalene + Clindamycin — caution: may cause dryness. Advise moisturiser buffer.
            </div>
          </FormSection>

          {/* Plan Section */}
          <FormSection 
            title="Plan & Follow-up" 
            expanded={expandedSections.plan}
            onToggle={() => toggleSection('plan')}
          >
            <div className="grid grid-cols-2 gap-3 mb-3">
              <FieldGroup label="Follow-up In">
                <select className="field-select" defaultValue="6w">
                  <option value="6w">6 weeks</option>
                  <option value="4w">4 weeks</option>
                  <option value="2w">2 weeks</option>
                  <option value="3m">3 months</option>
                  <option value="asneeded">As needed</option>
                </select>
              </FieldGroup>
              <FieldGroup label="WhatsApp Reminder">
                <select className="field-select" defaultValue="5w">
                  <option value="5w">Send at 5 weeks (auto)</option>
                  <option value="4w">Send at 4 weeks</option>
                  <option value="none">Do not send</option>
                </select>
              </FieldGroup>
            </div>
            <FieldGroup label="Plan Notes">
              <textarea 
                className="field-textarea min-h-[60px]" 
                defaultValue="Continue SA peel series — session 4 in 6 weeks. Review PASI score. If IGA remains Grade 3 at next visit, consider adding topical calcipotriol for psoriasis patches. Patient counselled on photoprotection."
              />
            </FieldGroup>
          </FormSection>
        </div>
      </div>

      {/* Bottom Bar */}
      <div 
        className="px-7 py-3.5 flex items-center gap-3 flex-shrink-0"
        style={{ background: 'white', borderTop: '1px solid var(--border)' }}
      >
        <div className="text-xs text-[var(--text3)]">
          <strong className="text-[var(--green)] font-semibold">Auto-saved</strong> · 10:47 AM
        </div>
        <div className="flex gap-2.5 ml-auto">
          <button className="btn btn-ghost text-sm py-1.5">Save Draft</button>
          <button className="btn btn-ghost text-sm py-1.5">
            <Printer className="w-4 h-4" /> Print Note
          </button>
          <button className="btn-primary text-sm py-1.5">
            <Check className="w-4 h-4" /> Finalise & Send Prescription via WhatsApp
          </button>
        </div>
      </div>
    </div>
  )
}

// Helper Components
function MetaChip({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-center gap-1 text-xs text-[var(--text2)]">
      {label} <span className="font-semibold text-[var(--text)]">{value}</span>
    </div>
  )
}

function FormSection({ 
  title, 
  expanded, 
  onToggle, 
  children 
}: { 
  title: string
  expanded: boolean
  onToggle: () => void
  children: React.ReactNode 
}) {
  return (
    <div className="card mb-4 overflow-hidden">
      <div 
        className="px-5 py-3.5 flex items-center gap-2 cursor-pointer"
        style={{ background: 'var(--bg)', borderBottom: '1px solid var(--border)' }}
        onClick={onToggle}
      >
        <h3 className="text-sm font-bold text-[var(--dark)] flex-1">{title}</h3>
        <span className="text-[var(--text3)] text-lg">{expanded ? '−' : '+'}</span>
      </div>
      {expanded && <div className="p-5">{children}</div>}
    </div>
  )
}

function FieldGroup({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="flex flex-col gap-1">
      <div className="text-[11px] font-semibold text-[var(--text3)] uppercase tracking-wider">{label}</div>
      {children}
    </div>
  )
}

function ScaleRow({ 
  label, 
  value, 
  range, 
  result, 
  resultType 
}: { 
  label: string
  value: number
  range: string
  result: string
  resultType: 'mild' | 'mod' | 'sev'
}) {
  const resultStyles = {
    mild: 'bg-[var(--green-light)] text-[var(--green)]',
    mod: 'bg-[#fffbeb] text-[var(--amber)]',
    sev: 'bg-[var(--red-light)] text-[var(--red)]',
  }
  
  return (
    <div className="flex items-center gap-2 mb-2">
      <div className="text-xs text-[var(--text2)] w-[120px] flex-shrink-0">{label}</div>
      <input 
        type="number" 
        className="w-[80px] p-1.5 px-2.5 text-center text-[13px] font-bold rounded-[7px] outline-none"
        style={{ border: '1.5px solid var(--border)' }}
        defaultValue={value}
      />
      <div className="text-[11px] text-[var(--text3)]">{range}</div>
      <span className={cn("text-xs font-bold px-2.5 py-0.5 rounded-full", resultStyles[resultType])}>
        {result}
      </span>
    </div>
  )
}
