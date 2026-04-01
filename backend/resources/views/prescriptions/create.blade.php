@extends('layouts.app')

@section('title', 'Create Prescription')
@section('breadcrumb', 'Create Prescription')

@section('content')
<div class="p-6 space-y-6" x-data="prescriptionForm()">

    {{-- ═══ Patient Info Header ═══ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">New Prescription</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Visit #{{ $visit->id }} &middot; {{ $visit->diagnosis_text ?? 'No diagnosis recorded' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('prescriptions.index') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-5 pt-5 border-t border-gray-100">
            <div>
                <p class="text-xs text-gray-400 font-medium">Patient Name</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $patient->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium">Age / Gender</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                    {{ $patient->age_years ?? '-' }} yrs / {{ ucfirst($patient->sex ?? '-') }}
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium">ABHA ID</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $patient->abha_id ?? 'Not linked' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium">Allergies</p>
                <div class="flex flex-wrap gap-1 mt-0.5">
                    @if($patient->known_allergies && count($patient->known_allergies) > 0)
                        @foreach($patient->known_allergies as $allergy)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                {{ $allergy }}
                            </span>
                        @endforeach
                    @else
                        <span class="text-sm text-gray-500">NKDA</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Allergy Alert Banner ═══ --}}
    <template x-if="allergyAlert">
        <div class="bg-red-50 border border-red-300 rounded-xl p-4 flex items-start gap-3">
            <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <div>
                <p class="text-sm font-bold text-red-800">ALLERGY ALERT</p>
                <p class="text-sm text-red-700 mt-0.5" x-text="allergyAlert"></p>
            </div>
        </div>
    </template>

    {{-- ═══ Drug Interaction Warning ═══ --}}
    <template x-if="interactionWarnings.length > 0">
        <div class="bg-amber-50 border border-amber-300 rounded-xl p-4">
            <p class="text-sm font-bold text-amber-800 mb-2">Drug Interaction Warnings</p>
            <template x-for="(warning, wi) in interactionWarnings" :key="wi">
                <div class="flex items-start gap-2 mb-1.5">
                    <span class="px-1.5 py-0.5 rounded text-xs font-bold uppercase"
                          :class="warning.severity === 'major' ? 'bg-red-100 text-red-700' : (warning.severity === 'moderate' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600')"
                          x-text="warning.severity"></span>
                    <p class="text-sm text-amber-800">
                        <span class="font-semibold" x-text="warning.drug_a"></span> +
                        <span class="font-semibold" x-text="warning.drug_b"></span> &mdash;
                        <span x-text="warning.description"></span>
                    </p>
                </div>
            </template>
        </div>
    </template>

    {{-- ═══ Prescription Templates ═══ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-900">Quick Templates</h2>
            <button @click="showTemplateModal = true" type="button"
                    class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                Load Template
            </button>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach(['Acne Vulgaris','Eczema','Psoriasis','Fungal Infection','URI','UTI','Hypertension','Diabetes'] as $tmpl)
            <button type="button" @click="applyTemplate('{{ strtolower(str_replace(' ', '-', $tmpl)) }}')"
                    class="px-3 py-1.5 text-xs font-medium bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition">
                {{ $tmpl }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- ═══ Drug Search ═══ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Add Drug</h2>
        <div class="relative">
            <input type="text"
                   x-model="drugQuery"
                   @input.debounce.300ms="searchDrugs()"
                   @keydown.escape="drugResults = []; drugQuery = ''"
                   placeholder="Search drug by brand name, generic name, or class..."
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            <svg class="absolute right-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>

            {{-- Search Results Dropdown --}}
            <div x-show="drugResults.length > 0" x-cloak
                 @click.outside="drugResults = []"
                 class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-72 overflow-y-auto">
                <template x-for="(drug, di) in drugResults" :key="di">
                    <button type="button" @click="addDrugFromSearch(drug)"
                            class="w-full px-4 py-3 text-left hover:bg-blue-50 border-b border-gray-100 last:border-0 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-semibold text-gray-900" x-text="drug.brand_names ? (Array.isArray(drug.brand_names) ? drug.brand_names[0] : drug.brand_names) : drug.generic_name"></span>
                                <span class="text-xs text-gray-500 ml-2" x-text="drug.generic_name"></span>
                            </div>
                            <span class="text-xs text-gray-400" x-text="drug.form + ' | ' + drug.strength"></span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <span x-text="drug.manufacturer || ''"></span>
                            <span x-if="drug.schedule" class="ml-2 px-1 py-0.5 bg-yellow-50 text-yellow-700 rounded text-xs" x-text="drug.schedule"></span>
                        </p>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- ═══ Prescription Drug Table ═══ --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">
                    Prescription Drugs
                    <span class="text-gray-400 font-normal" x-text="'(' + drugs.length + ')'"></span>
                </h2>
                <button type="button" @click="addEmptyRow()"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Row
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 w-8">#</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 min-w-[200px]">Drug Name</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 w-24">Dose</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 w-24">Route</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 w-28">Frequency</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 w-32">Duration</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 w-16">Qty</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 min-w-[180px]">Instructions</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(drug, index) in drugs" :key="index">
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="px-3 py-2 text-gray-400 text-xs font-mono" x-text="index + 1"></td>
                            <td class="px-3 py-2">
                                <input type="text" x-model="drug.drug_name"
                                       class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-blue-400"
                                       placeholder="Drug name">
                                <p class="text-xs text-gray-400 mt-0.5" x-text="drug.generic_name || ''"></p>
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" x-model="drug.dose"
                                       class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-blue-400"
                                       placeholder="e.g. 500mg">
                            </td>
                            <td class="px-3 py-2">
                                <select x-model="drug.route"
                                        class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-blue-400 bg-white">
                                    <option value="oral">Oral</option>
                                    <option value="topical">Topical</option>
                                    <option value="iv">IV</option>
                                    <option value="im">IM</option>
                                    <option value="sc">SC</option>
                                    <option value="sublingual">Sublingual</option>
                                    <option value="inhalation">Inhalation</option>
                                    <option value="rectal">Rectal</option>
                                    <option value="ophthalmic">Ophthalmic</option>
                                    <option value="otic">Otic</option>
                                    <option value="nasal">Nasal</option>
                                    <option value="vaginal">Vaginal</option>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <select x-model="drug.frequency" @change="calculateQty(index)"
                                        class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-blue-400 bg-white">
                                    <option value="">Select</option>
                                    <option value="OD">OD (Once daily)</option>
                                    <option value="BD">BD (Twice daily)</option>
                                    <option value="TDS">TDS (Thrice daily)</option>
                                    <option value="QID">QID (Four times)</option>
                                    <option value="SOS">SOS (As needed)</option>
                                    <option value="HS">HS (At bedtime)</option>
                                    <option value="AC">AC (Before food)</option>
                                    <option value="PC">PC (After food)</option>
                                    <option value="Stat">Stat (Immediately)</option>
                                    <option value="Once weekly">Once weekly</option>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex gap-1">
                                    <input type="number" x-model.number="drug.duration_number" @change="calculateQty(index)"
                                           class="w-14 px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-blue-400"
                                           min="1" placeholder="#">
                                    <select x-model="drug.duration_unit" @change="calculateQty(index)"
                                            class="w-20 px-1 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-blue-400 bg-white">
                                        <option value="Days">Days</option>
                                        <option value="Weeks">Weeks</option>
                                        <option value="Months">Months</option>
                                    </select>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" x-model.number="drug.quantity"
                                       class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm bg-gray-50 focus:ring-1 focus:ring-blue-400"
                                       min="0" readonly>
                            </td>
                            <td class="px-3 py-2">
                                <div class="space-y-1">
                                    <input type="text" x-model="drug.instructions"
                                           class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-1 focus:ring-blue-400"
                                           placeholder="Instructions">
                                    <div class="flex flex-wrap gap-1">
                                        <button type="button" @click="drug.instructions = 'Before food'"
                                                class="px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Before food</button>
                                        <button type="button" @click="drug.instructions = 'After food'"
                                                class="px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-600 rounded hover:bg-gray-200">After food</button>
                                        <button type="button" @click="drug.instructions = 'With water'"
                                                class="px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-600 rounded hover:bg-gray-200">With water</button>
                                        <button type="button" @click="drug.instructions = 'Empty stomach'"
                                                class="px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Empty stomach</button>
                                        <button type="button" @click="drug.instructions = 'Avoid sunlight'"
                                                class="px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Avoid sunlight</button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <button type="button" @click="removeDrug(index)"
                                        class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <template x-if="drugs.length === 0">
            <div class="p-8 text-center text-gray-400 text-sm">
                No drugs added yet. Search above or click "Add Row".
            </div>
        </template>
    </div>

    {{-- ═══ Submit Buttons ═══ --}}
    <div class="flex items-center justify-end gap-3">
        <button type="button" @click="savePrescription('draft')"
                :disabled="saving"
                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition disabled:opacity-50">
            Save Draft
        </button>
        <button type="button" @click="savePrescription('finalise')"
                :disabled="saving || drugs.length === 0"
                class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition disabled:opacity-50">
            <span x-show="!saving">Finalise & Print</span>
            <span x-show="saving" x-cloak>Saving...</span>
        </button>
        <button type="button" @click="savePrescription('whatsapp')"
                :disabled="saving || drugs.length === 0"
                class="px-5 py-2.5 text-sm font-semibold text-white bg-green-600 rounded-xl hover:bg-green-700 transition disabled:opacity-50">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                Send via WhatsApp
            </span>
        </button>
    </div>

    {{-- ═══ Template Modal ═══ --}}
    <div x-show="showTemplateModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
         @click.self="showTemplateModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Load Prescription Template</h3>
                <button @click="showTemplateModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @foreach(['Acne Vulgaris','Eczema','Psoriasis','Fungal Infection','URI','UTI','Hypertension','Diabetes'] as $tmpl)
                <button type="button"
                        @click="applyTemplate('{{ strtolower(str_replace(' ', '-', $tmpl)) }}'); showTemplateModal = false"
                        class="w-full text-left px-4 py-3 bg-gray-50 hover:bg-blue-50 rounded-lg transition">
                    <p class="text-sm font-semibold text-gray-900">{{ $tmpl }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Pre-configured drugs for {{ $tmpl }}</p>
                </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Success/Error flash --}}
    <template x-if="flashMessage">
        <div class="fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl shadow-xl text-sm font-medium"
             :class="flashType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
             x-text="flashMessage"
             x-init="setTimeout(() => { flashMessage = ''; }, 4000)">
        </div>
    </template>
</div>

@push('scripts')
<script>
function prescriptionForm() {
    return {
        // State
        drugs: [],
        drugQuery: '',
        drugResults: [],
        saving: false,
        showTemplateModal: false,
        allergyAlert: '',
        interactionWarnings: [],
        flashMessage: '',
        flashType: 'success',

        // Patient allergies from server
        patientAllergies: @json($patient->known_allergies ?? []),

        // Drug interaction data from server
        knownInteractions: @json($interactions ?? []),

        // Search drugs via API
        async searchDrugs() {
            if (this.drugQuery.length < 2) {
                this.drugResults = [];
                return;
            }
            try {
                const response = await fetch(`/api/drugs/search?q=${encodeURIComponent(this.drugQuery)}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                this.drugResults = data.drugs || [];
            } catch (e) {
                console.error('Drug search failed:', e);
                this.drugResults = [];
            }
        },

        // Add drug from search result
        addDrugFromSearch(drug) {
            const brandName = drug.brand_names
                ? (Array.isArray(drug.brand_names) ? drug.brand_names[0] : drug.brand_names)
                : drug.generic_name;

            const newDrug = {
                drug_db_id: drug.id || null,
                drug_name: brandName + (drug.strength ? ' ' + drug.strength : ''),
                generic_name: drug.generic_name || '',
                strength: drug.strength || '',
                form: drug.form || '',
                dose: drug.strength || '',
                route: this.guessRoute(drug.form),
                frequency: '',
                duration_number: '',
                duration_unit: 'Days',
                quantity: 0,
                instructions: '',
            };

            this.drugs.push(newDrug);
            this.drugQuery = '';
            this.drugResults = [];

            // Check allergies
            this.checkAllergy(newDrug);

            // Check interactions
            this.checkInteractions();

            // Apply dosage suggestion
            this.suggestDosage(this.drugs.length - 1);
        },

        // Add empty row
        addEmptyRow() {
            this.drugs.push({
                drug_db_id: null,
                drug_name: '',
                generic_name: '',
                strength: '',
                form: '',
                dose: '',
                route: 'oral',
                frequency: '',
                duration_number: '',
                duration_unit: 'Days',
                quantity: 0,
                instructions: '',
            });
        },

        // Remove drug row
        removeDrug(index) {
            this.drugs.splice(index, 1);
            this.checkInteractions();
            this.allergyAlert = '';
        },

        // Auto-calculate quantity
        calculateQty(index) {
            const drug = this.drugs[index];
            const freq = drug.frequency;
            const dur = drug.duration_number;
            const unit = drug.duration_unit;

            if (!freq || !dur) {
                drug.quantity = 0;
                return;
            }

            const freqMap = {
                'OD': 1, 'BD': 2, 'TDS': 3, 'QID': 4,
                'SOS': 1, 'HS': 1, 'AC': 1, 'PC': 1, 'Stat': 1,
                'Once weekly': 0.143,
            };

            const perDay = freqMap[freq] || 1;
            let days = dur;

            if (unit === 'Weeks') days = dur * 7;
            if (unit === 'Months') days = dur * 30;

            drug.quantity = Math.ceil(perDay * days);
        },

        // Guess route from form
        guessRoute(form) {
            if (!form) return 'oral';
            const f = form.toLowerCase();
            if (['cream', 'ointment', 'gel', 'lotion', 'solution'].some(t => f.includes(t))) return 'topical';
            if (f.includes('eye') || f.includes('ophthalmic')) return 'ophthalmic';
            if (f.includes('ear') || f.includes('otic')) return 'otic';
            if (f.includes('inhaler') || f.includes('nebul')) return 'inhalation';
            if (f.includes('mouthwash')) return 'oral';
            if (f.includes('injection') || f.includes('vial')) return 'iv';
            return 'oral';
        },

        // Check drug against patient allergies
        checkAllergy(drug) {
            if (!this.patientAllergies || this.patientAllergies.length === 0) return;

            const drugNameLower = (drug.drug_name + ' ' + drug.generic_name).toLowerCase();
            for (const allergy of this.patientAllergies) {
                if (drugNameLower.includes(allergy.toLowerCase())) {
                    this.allergyAlert = `Patient is allergic to "${allergy}". The drug "${drug.drug_name}" may trigger an allergic reaction.`;
                    return;
                }
            }
        },

        // Check all drugs for interactions
        checkInteractions() {
            this.interactionWarnings = [];
            const names = this.drugs.map(d => d.generic_name || d.drug_name).filter(n => n);

            if (names.length < 2) return;

            for (let i = 0; i < names.length; i++) {
                for (let j = i + 1; j < names.length; j++) {
                    for (const ix of this.knownInteractions) {
                        const a = ix.drug_a.toLowerCase();
                        const b = ix.drug_b.toLowerCase();
                        const ni = names[i].toLowerCase();
                        const nj = names[j].toLowerCase();

                        if ((ni.includes(a) && nj.includes(b)) || (ni.includes(b) && nj.includes(a))) {
                            this.interactionWarnings.push({
                                drug_a: names[i],
                                drug_b: names[j],
                                severity: ix.severity,
                                description: ix.description,
                            });
                        }
                    }
                }
            }
        },

        // Dosage auto-suggestion based on common dosing
        suggestDosage(index) {
            const drug = this.drugs[index];
            const generic = (drug.generic_name || '').toLowerCase();
            const form = (drug.form || '').toLowerCase();

            const suggestions = {
                'paracetamol': { dose: '500mg', frequency: 'TDS', duration_number: 3, duration_unit: 'Days', instructions: 'After food, SOS for fever' },
                'cetirizine': { dose: '10mg', frequency: 'OD', duration_number: 7, duration_unit: 'Days', instructions: 'At bedtime' },
                'pantoprazole': { dose: '40mg', frequency: 'OD', duration_number: 14, duration_unit: 'Days', instructions: 'Before breakfast' },
                'amoxicillin': { dose: '500mg', frequency: 'TDS', duration_number: 5, duration_unit: 'Days', instructions: 'After food, complete full course' },
                'azithromycin': { dose: '500mg', frequency: 'OD', duration_number: 3, duration_unit: 'Days', instructions: 'After food' },
                'metformin': { dose: '500mg', frequency: 'BD', duration_number: 30, duration_unit: 'Days', instructions: 'After food' },
                'amlodipine': { dose: '5mg', frequency: 'OD', duration_number: 30, duration_unit: 'Days', instructions: 'Morning' },
                'doxycycline': { dose: '100mg', frequency: 'BD', duration_number: 14, duration_unit: 'Days', instructions: 'After food with water, avoid sun' },
                'isotretinoin': { dose: '20mg', frequency: 'OD', duration_number: 30, duration_unit: 'Days', instructions: 'After fatty meal' },
                'terbinafine': { dose: '250mg', frequency: 'OD', duration_number: 28, duration_unit: 'Days', instructions: 'After food' },
                'ibuprofen': { dose: '400mg', frequency: 'TDS', duration_number: 5, duration_unit: 'Days', instructions: 'After food' },
                'ciprofloxacin': { dose: '500mg', frequency: 'BD', duration_number: 5, duration_unit: 'Days', instructions: 'After food' },
            };

            for (const [key, suggestion] of Object.entries(suggestions)) {
                if (generic.includes(key)) {
                    if (!drug.frequency) drug.frequency = suggestion.frequency;
                    if (!drug.duration_number) drug.duration_number = suggestion.duration_number;
                    if (!drug.duration_unit) drug.duration_unit = suggestion.duration_unit;
                    if (!drug.instructions) drug.instructions = suggestion.instructions;
                    this.calculateQty(index);
                    return;
                }
            }
        },

        // Apply template
        async applyTemplate(slug) {
            try {
                const response = await fetch(`/api/v1/prescriptions/template/${slug}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    // Fallback: use local templates
                    this.applyLocalTemplate(slug);
                    return;
                }

                const data = await response.json();
                if (data.drugs && data.drugs.length > 0) {
                    this.drugs = data.drugs.map(d => ({
                        drug_db_id: null,
                        drug_name: d.drug_name || '',
                        generic_name: d.generic_name || '',
                        strength: '',
                        form: '',
                        dose: d.dose || '',
                        route: d.route || 'oral',
                        frequency: d.frequency || '',
                        duration_number: parseInt(d.duration) || '',
                        duration_unit: 'Days',
                        quantity: 0,
                        instructions: d.instructions || '',
                    }));

                    // Recalculate quantities
                    this.drugs.forEach((_, i) => this.calculateQty(i));
                    this.checkInteractions();
                }
            } catch (e) {
                this.applyLocalTemplate(slug);
            }
        },

        // Local fallback templates
        applyLocalTemplate(slug) {
            const templates = {
                'acne-vulgaris': [
                    { drug_name: 'Adapalene Gel 0.1%', generic_name: 'Adapalene', dose: 'Pea-sized', route: 'topical', frequency: 'HS', duration_number: 84, duration_unit: 'Days', instructions: 'Apply at night on dry face' },
                    { drug_name: 'Clindamycin Gel 1%', generic_name: 'Clindamycin', dose: 'Thin layer', route: 'topical', frequency: 'BD', duration_number: 56, duration_unit: 'Days', instructions: 'Morning and evening on affected area' },
                    { drug_name: 'Doxycycline 100mg', generic_name: 'Doxycycline', dose: '100mg', route: 'oral', frequency: 'BD', duration_number: 42, duration_unit: 'Days', instructions: 'After food with water' },
                ],
                'eczema': [
                    { drug_name: 'Mometasone Cream 0.1%', generic_name: 'Mometasone Furoate', dose: 'Thin layer', route: 'topical', frequency: 'BD', duration_number: 14, duration_unit: 'Days', instructions: 'Apply on affected areas' },
                    { drug_name: 'Cetirizine 10mg', generic_name: 'Cetirizine', dose: '10mg', route: 'oral', frequency: 'OD', duration_number: 14, duration_unit: 'Days', instructions: 'At bedtime' },
                ],
                'uri': [
                    { drug_name: 'Paracetamol 500mg', generic_name: 'Paracetamol', dose: '500mg', route: 'oral', frequency: 'TDS', duration_number: 3, duration_unit: 'Days', instructions: 'After food, SOS for fever' },
                    { drug_name: 'Cetirizine 10mg', generic_name: 'Cetirizine', dose: '10mg', route: 'oral', frequency: 'OD', duration_number: 5, duration_unit: 'Days', instructions: 'At bedtime' },
                ],
            };

            const drugs = templates[slug];
            if (drugs) {
                this.drugs = drugs.map(d => ({ ...d, drug_db_id: null, strength: '', form: '', quantity: 0 }));
                this.drugs.forEach((_, i) => this.calculateQty(i));
                this.checkInteractions();
            }
        },

        // Save prescription
        async savePrescription(action) {
            if (this.drugs.length === 0 && action !== 'draft') {
                this.flashMessage = 'Please add at least one drug';
                this.flashType = 'error';
                return;
            }

            this.saving = true;

            try {
                const payload = {
                    visit_id: {{ $visit->id }},
                    patient_id: {{ $patient->id }},
                    valid_days: 30,
                    drugs: this.drugs.map(d => ({
                        drug_name: d.drug_name,
                        generic_name: d.generic_name,
                        strength: d.strength,
                        form: d.form,
                        dose: d.dose,
                        frequency: d.frequency,
                        route: d.route,
                        duration: d.duration_number ? (d.duration_number + ' ' + d.duration_unit) : '',
                        instructions: d.instructions,
                        drug_db_id: d.drug_db_id,
                    })),
                };

                const response = await fetch('/api/v1/prescriptions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to save');
                }

                const prescriptionId = data.prescription?.id;

                if (action === 'finalise' && prescriptionId) {
                    // Open PDF in new tab
                    window.open(`/api/v1/prescriptions/${prescriptionId}/pdf`, '_blank');
                }

                if (action === 'whatsapp' && prescriptionId) {
                    // Send via WhatsApp
                    await fetch(`/api/v1/prescriptions/${prescriptionId}/send`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });
                }

                this.flashMessage = 'Prescription saved successfully!';
                this.flashType = 'success';

                setTimeout(() => {
                    window.location.href = '{{ route("prescriptions.index") }}';
                }, 1500);

            } catch (e) {
                this.flashMessage = 'Error: ' + e.message;
                this.flashType = 'error';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
@endsection
