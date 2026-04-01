{{-- ENT EMR Template --}}
{{-- Variables: $visit, $patient --}}

<style>
.ent-section { margin-bottom: 20px; }
.ent-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.ent-header { padding: 12px 16px; background: linear-gradient(135deg, #fce7f3, #fbcfe8); border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px; cursor: pointer; }
.ent-header h3 { font-size: 14px; font-weight: 600; color: #9d174d; margin: 0; }
.ent-body { padding: 16px; }
.ent-grid { display: grid; gap: 12px; }
.ent-grid-2 { grid-template-columns: repeat(2, 1fr); }
.ent-grid-3 { grid-template-columns: repeat(3, 1fr); }
.ent-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.ent-input { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; }
.ent-input:focus { outline: none; border-color: #ec4899; box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1); }
.side-badge { display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.side-badge-r { background: #dbeafe; color: #1e40af; }
.side-badge-l { background: #dcfce7; color: #166534; }
.finding-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f1f5f9; border-radius: 8px; font-size: 12px; margin: 3px; cursor: pointer; transition: all 0.15s; }
.finding-chip:hover { background: #e2e8f0; }
.finding-chip.selected { background: #ec4899; color: white; }
.hearing-bar { height: 8px; border-radius: 4px; background: linear-gradient(90deg, #22c55e 0%, #eab308 50%, #dc2626 100%); }
.audiogram-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; font-size: 11px; text-align: center; }
.audiogram-cell { padding: 6px; background: #f8fafc; border-radius: 4px; }
.audiogram-cell input { width: 100%; padding: 4px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; text-align: center; }
</style>

<div x-data="entEMR()" class="ent-section">
    {{-- Ear Examination --}}
    <div class="ent-card">
        <div class="ent-header" @click="sections.ear = !sections.ear">
            <span style="font-size: 18px;">👂</span>
            <h3>Ear Examination</h3>
            <span style="margin-left: auto; color: #9d174d;" x-text="sections.ear ? '▼' : '▶'"></span>
        </div>
        <div class="ent-body" x-show="sections.ear" x-collapse>
            <div class="ent-grid ent-grid-2" style="gap: 24px;">
                {{-- Right Ear --}}
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <span class="side-badge side-badge-r">Right</span>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">External Ear / Pinna</div>
                        <select class="ent-input" x-model="earData.right.pinna" @change="updateEar()">
                            <option value="Normal">Normal</option>
                            <option value="Tender">Tender</option>
                            <option value="Swelling">Swelling</option>
                            <option value="Preauricular sinus">Preauricular sinus</option>
                            <option value="Keloid">Keloid</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">External Canal</div>
                        <select class="ent-input" x-model="earData.right.canal" @change="updateEar()">
                            <option value="Normal">Normal</option>
                            <option value="Wax impaction">Wax impaction</option>
                            <option value="Otitis externa">Otitis externa</option>
                            <option value="Furunculosis">Furunculosis</option>
                            <option value="Foreign body">Foreign body</option>
                            <option value="Stenosis">Stenosis</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">Tympanic Membrane</div>
                        <select class="ent-input" x-model="earData.right.tm" @change="updateEar()">
                            <option value="Intact, pearly white">Intact, pearly white</option>
                            <option value="Dull">Dull</option>
                            <option value="Retracted">Retracted</option>
                            <option value="Bulging">Bulging</option>
                            <option value="Perforation - Central">Perforation - Central</option>
                            <option value="Perforation - Marginal">Perforation - Marginal</option>
                            <option value="Perforation - Attic">Perforation - Attic</option>
                            <option value="Cholesteatoma">Cholesteatoma</option>
                            <option value="Myringosclerosis">Myringosclerosis</option>
                        </select>
                    </div>

                    <div>
                        <div class="ent-label">Middle Ear (if visible)</div>
                        <textarea class="ent-input" x-model="earData.right.middleEar" @change="updateEar()" placeholder="Notes..."></textarea>
                    </div>
                </div>

                {{-- Left Ear --}}
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <span class="side-badge side-badge-l">Left</span>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">External Ear / Pinna</div>
                        <select class="ent-input" x-model="earData.left.pinna" @change="updateEar()">
                            <option value="Normal">Normal</option>
                            <option value="Tender">Tender</option>
                            <option value="Swelling">Swelling</option>
                            <option value="Preauricular sinus">Preauricular sinus</option>
                            <option value="Keloid">Keloid</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">External Canal</div>
                        <select class="ent-input" x-model="earData.left.canal" @change="updateEar()">
                            <option value="Normal">Normal</option>
                            <option value="Wax impaction">Wax impaction</option>
                            <option value="Otitis externa">Otitis externa</option>
                            <option value="Furunculosis">Furunculosis</option>
                            <option value="Foreign body">Foreign body</option>
                            <option value="Stenosis">Stenosis</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">Tympanic Membrane</div>
                        <select class="ent-input" x-model="earData.left.tm" @change="updateEar()">
                            <option value="Intact, pearly white">Intact, pearly white</option>
                            <option value="Dull">Dull</option>
                            <option value="Retracted">Retracted</option>
                            <option value="Bulging">Bulging</option>
                            <option value="Perforation - Central">Perforation - Central</option>
                            <option value="Perforation - Marginal">Perforation - Marginal</option>
                            <option value="Perforation - Attic">Perforation - Attic</option>
                            <option value="Cholesteatoma">Cholesteatoma</option>
                            <option value="Myringosclerosis">Myringosclerosis</option>
                        </select>
                    </div>

                    <div>
                        <div class="ent-label">Middle Ear (if visible)</div>
                        <textarea class="ent-input" x-model="earData.left.middleEar" @change="updateEar()" placeholder="Notes..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Hearing Tests --}}
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                <div class="ent-label">Tuning Fork Tests</div>
                <div class="ent-grid ent-grid-2" style="margin-top: 8px;">
                    <div>
                        <div class="ent-label" style="font-size: 10px;">Rinne Test</div>
                        <div class="ent-grid ent-grid-2">
                            <select class="ent-input" x-model="earData.right.rinne" @change="updateEar()">
                                <option value="">R: Select</option>
                                <option value="Positive">Positive (AC > BC)</option>
                                <option value="Negative">Negative (BC > AC)</option>
                            </select>
                            <select class="ent-input" x-model="earData.left.rinne" @change="updateEar()">
                                <option value="">L: Select</option>
                                <option value="Positive">Positive (AC > BC)</option>
                                <option value="Negative">Negative (BC > AC)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="ent-label" style="font-size: 10px;">Weber Test</div>
                        <select class="ent-input" x-model="earData.weber" @change="updateEar()">
                            <option value="">Select</option>
                            <option value="Central">Central (Normal)</option>
                            <option value="Lateralized Right">Lateralized Right</option>
                            <option value="Lateralized Left">Lateralized Left</option>
                        </select>
                    </div>
                </div>
            </div>
            <input type="hidden" name="ent_ear" :value="JSON.stringify(earData)">
        </div>
    </div>

    {{-- Nose Examination --}}
    <div class="ent-card" style="margin-top: 16px;">
        <div class="ent-header" @click="sections.nose = !sections.nose">
            <span style="font-size: 18px;">👃</span>
            <h3>Nose Examination</h3>
            <span style="margin-left: auto; color: #9d174d;" x-text="sections.nose ? '▼' : '▶'"></span>
        </div>
        <div class="ent-body" x-show="sections.nose" x-collapse>
            <div class="ent-grid ent-grid-2" style="gap: 24px;">
                {{-- Right --}}
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <span class="side-badge side-badge-r">Right</span>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">Turbinates</div>
                        <select class="ent-input" x-model="noseData.right.turbinates" @change="updateNose()">
                            <option value="Normal">Normal</option>
                            <option value="Congested">Congested</option>
                            <option value="Hypertrophied">Hypertrophied</option>
                            <option value="Atrophied">Atrophied</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">Septum</div>
                        <select class="ent-input" x-model="noseData.septum" @change="updateNose()">
                            <option value="Central">Central</option>
                            <option value="DNS to Right">DNS to Right</option>
                            <option value="DNS to Left">DNS to Left</option>
                            <option value="S-shaped">S-shaped</option>
                            <option value="Spur">Spur</option>
                            <option value="Perforation">Perforation</option>
                        </select>
                    </div>

                    <div>
                        <div class="ent-label">Discharge</div>
                        <select class="ent-input" x-model="noseData.right.discharge" @change="updateNose()">
                            <option value="Nil">Nil</option>
                            <option value="Clear mucoid">Clear mucoid</option>
                            <option value="Mucopurulent">Mucopurulent</option>
                            <option value="Purulent">Purulent</option>
                            <option value="Blood-tinged">Blood-tinged</option>
                            <option value="Crusting">Crusting</option>
                        </select>
                    </div>
                </div>

                {{-- Left --}}
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <span class="side-badge side-badge-l">Left</span>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">Turbinates</div>
                        <select class="ent-input" x-model="noseData.left.turbinates" @change="updateNose()">
                            <option value="Normal">Normal</option>
                            <option value="Congested">Congested</option>
                            <option value="Hypertrophied">Hypertrophied</option>
                            <option value="Atrophied">Atrophied</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <div class="ent-label">Polyps</div>
                        <select class="ent-input" x-model="noseData.left.polyps" @change="updateNose()">
                            <option value="Absent">Absent</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                        </select>
                    </div>

                    <div>
                        <div class="ent-label">Discharge</div>
                        <select class="ent-input" x-model="noseData.left.discharge" @change="updateNose()">
                            <option value="Nil">Nil</option>
                            <option value="Clear mucoid">Clear mucoid</option>
                            <option value="Mucopurulent">Mucopurulent</option>
                            <option value="Purulent">Purulent</option>
                            <option value="Blood-tinged">Blood-tinged</option>
                            <option value="Crusting">Crusting</option>
                        </select>
                    </div>
                </div>
            </div>

            <div style="margin-top: 12px;">
                <div class="ent-label">Paranasal Sinus Tenderness</div>
                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                    <template x-for="sinus in ['Frontal R', 'Frontal L', 'Maxillary R', 'Maxillary L', 'Ethmoid', 'Sphenoid']" :key="sinus">
                        <button type="button" class="finding-chip" :class="{'selected': noseData.sinusTenderness.includes(sinus)}" @click="toggleSinusTenderness(sinus)" x-text="sinus"></button>
                    </template>
                </div>
            </div>
            <input type="hidden" name="ent_nose" :value="JSON.stringify(noseData)">
        </div>
    </div>

    {{-- Throat Examination --}}
    <div class="ent-card" style="margin-top: 16px;">
        <div class="ent-header" @click="sections.throat = !sections.throat">
            <span style="font-size: 18px;">🫁</span>
            <h3>Throat / Oropharynx</h3>
            <span style="margin-left: auto; color: #9d174d;" x-text="sections.throat ? '▼' : '▶'"></span>
        </div>
        <div class="ent-body" x-show="sections.throat" x-collapse>
            <div class="ent-grid ent-grid-2">
                <div>
                    <div class="ent-label">Oral Cavity</div>
                    <select class="ent-input" x-model="throatData.oral" @change="updateThroat()">
                        <option value="Normal">Normal</option>
                        <option value="Ulcers">Ulcers</option>
                        <option value="White patches">White patches</option>
                        <option value="Candidiasis">Candidiasis</option>
                        <option value="Leukoplakia">Leukoplakia</option>
                    </select>
                </div>
                <div>
                    <div class="ent-label">Tongue</div>
                    <select class="ent-input" x-model="throatData.tongue" @change="updateThroat()">
                        <option value="Normal">Normal</option>
                        <option value="Coated">Coated</option>
                        <option value="Geographic">Geographic</option>
                        <option value="Fissured">Fissured</option>
                        <option value="Atrophic">Atrophic</option>
                        <option value="Deviation">Deviation</option>
                    </select>
                </div>
            </div>

            <div class="ent-grid ent-grid-2" style="margin-top: 12px;">
                <div>
                    <div class="ent-label">Tonsils</div>
                    <select class="ent-input" x-model="throatData.tonsils" @change="updateThroat()">
                        <option value="Grade 0 (Absent/Tonsilectomy)">Grade 0 (Absent)</option>
                        <option value="Grade 1">Grade 1</option>
                        <option value="Grade 2">Grade 2</option>
                        <option value="Grade 3">Grade 3</option>
                        <option value="Grade 4 (Kissing)">Grade 4 (Kissing)</option>
                    </select>
                </div>
                <div>
                    <div class="ent-label">Tonsil Appearance</div>
                    <select class="ent-input" x-model="throatData.tonsilAppearance" @change="updateThroat()">
                        <option value="Normal">Normal</option>
                        <option value="Congested">Congested</option>
                        <option value="Exudate">Exudate</option>
                        <option value="Cryptic">Cryptic</option>
                        <option value="Asymmetric">Asymmetric</option>
                    </select>
                </div>
            </div>

            <div class="ent-grid ent-grid-2" style="margin-top: 12px;">
                <div>
                    <div class="ent-label">Posterior Pharyngeal Wall</div>
                    <select class="ent-input" x-model="throatData.ppw" @change="updateThroat()">
                        <option value="Normal">Normal</option>
                        <option value="Granular">Granular</option>
                        <option value="Congested">Congested</option>
                        <option value="Post-nasal drip">Post-nasal drip</option>
                    </select>
                </div>
                <div>
                    <div class="ent-label">Uvula</div>
                    <select class="ent-input" x-model="throatData.uvula" @change="updateThroat()">
                        <option value="Central">Central</option>
                        <option value="Deviated">Deviated</option>
                        <option value="Elongated">Elongated</option>
                        <option value="Bifid">Bifid</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 12px;">
                <div class="ent-label">Palate</div>
                <select class="ent-input" x-model="throatData.palate" @change="updateThroat()">
                    <option value="Normal">Normal</option>
                    <option value="High arched">High arched</option>
                    <option value="Cleft">Cleft</option>
                    <option value="Submucosal cleft">Submucosal cleft</option>
                </select>
            </div>
            <input type="hidden" name="ent_throat" :value="JSON.stringify(throatData)">
        </div>
    </div>

    {{-- Larynx --}}
    <div class="ent-card" style="margin-top: 16px;">
        <div class="ent-header" @click="sections.larynx = !sections.larynx">
            <span style="font-size: 18px;">🎤</span>
            <h3>Larynx (Indirect Laryngoscopy / VLS)</h3>
            <span style="margin-left: auto; color: #9d174d;" x-text="sections.larynx ? '▼' : '▶'"></span>
        </div>
        <div class="ent-body" x-show="sections.larynx" x-collapse>
            <div class="ent-grid ent-grid-2">
                <div>
                    <div class="ent-label">Epiglottis</div>
                    <select class="ent-input" x-model="larynxData.epiglottis" @change="updateLarynx()">
                        <option value="Normal">Normal</option>
                        <option value="Omega-shaped">Omega-shaped</option>
                        <option value="Edematous">Edematous</option>
                        <option value="Mass">Mass</option>
                    </select>
                </div>
                <div>
                    <div class="ent-label">Arytenoids</div>
                    <select class="ent-input" x-model="larynxData.arytenoids" @change="updateLarynx()">
                        <option value="Normal">Normal</option>
                        <option value="Edematous">Edematous</option>
                        <option value="Granuloma">Granuloma</option>
                    </select>
                </div>
            </div>

            <div class="ent-grid ent-grid-2" style="margin-top: 12px;">
                <div>
                    <div class="ent-label">Vocal Cords</div>
                    <select class="ent-input" x-model="larynxData.vocalCords" @change="updateLarynx()">
                        <option value="Mobile, normal">Mobile, normal</option>
                        <option value="Congested">Congested</option>
                        <option value="Nodule">Nodule</option>
                        <option value="Polyp">Polyp</option>
                        <option value="Cyst">Cyst</option>
                        <option value="Leukoplakia">Leukoplakia</option>
                        <option value="Mass/Tumor">Mass/Tumor</option>
                        <option value="Paralysis - Right">Paralysis - Right</option>
                        <option value="Paralysis - Left">Paralysis - Left</option>
                        <option value="Paralysis - Bilateral">Paralysis - Bilateral</option>
                    </select>
                </div>
                <div>
                    <div class="ent-label">Cord Mobility</div>
                    <select class="ent-input" x-model="larynxData.cordMobility" @change="updateLarynx()">
                        <option value="Normal bilateral">Normal bilateral</option>
                        <option value="Reduced Right">Reduced Right</option>
                        <option value="Reduced Left">Reduced Left</option>
                        <option value="Fixed Right">Fixed Right</option>
                        <option value="Fixed Left">Fixed Left</option>
                        <option value="Fixed Bilateral">Fixed Bilateral</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 12px;">
                <div class="ent-label">Subglottis / Pyriform Fossa</div>
                <textarea class="ent-input" x-model="larynxData.subglottis" @change="updateLarynx()" placeholder="Notes..."></textarea>
            </div>
            <input type="hidden" name="ent_larynx" :value="JSON.stringify(larynxData)">
        </div>
    </div>

    {{-- Common ENT Diagnoses --}}
    <div class="ent-card" style="margin-top: 16px;">
        <div class="ent-header" @click="sections.diagnosis = !sections.diagnosis">
            <span style="font-size: 18px;">📋</span>
            <h3>Quick Diagnoses</h3>
            <span style="margin-left: auto; color: #9d174d;" x-text="sections.diagnosis ? '▼' : '▶'"></span>
        </div>
        <div class="ent-body" x-show="sections.diagnosis" x-collapse>
            <div class="ent-label">Common ENT Diagnoses</div>
            <div style="display: flex; flex-wrap: wrap; margin-top: 8px;">
                <template x-for="dx in commonDiagnoses" :key="dx.code">
                    <button type="button" class="finding-chip" :class="{'selected': selectedDiagnoses.includes(dx.code)}" @click="toggleDiagnosis(dx)">
                        <span x-text="dx.name"></span>
                        <span style="font-size: 10px; opacity: 0.7;" x-text="dx.code"></span>
                    </button>
                </template>
            </div>
            <input type="hidden" name="ent_diagnoses" :value="JSON.stringify(selectedDiagnoses)">
        </div>
    </div>
</div>

<script>
console.log('ENT EMR template loaded');

function entEMR() {
    return {
        sections: {
            ear: true,
            nose: true,
            throat: true,
            larynx: false,
            diagnosis: true
        },
        
        earData: @json($visit->getStructuredField('ent.ear') ?? [
            'right' => ['pinna' => 'Normal', 'canal' => 'Normal', 'tm' => 'Intact, pearly white', 'middleEar' => '', 'rinne' => ''],
            'left' => ['pinna' => 'Normal', 'canal' => 'Normal', 'tm' => 'Intact, pearly white', 'middleEar' => '', 'rinne' => ''],
            'weber' => ''
        ]),
        
        noseData: @json($visit->getStructuredField('ent.nose') ?? [
            'right' => ['turbinates' => 'Normal', 'discharge' => 'Nil', 'polyps' => 'Absent'],
            'left' => ['turbinates' => 'Normal', 'discharge' => 'Nil', 'polyps' => 'Absent'],
            'septum' => 'Central',
            'sinusTenderness' => []
        ]),
        
        throatData: @json($visit->getStructuredField('ent.throat') ?? [
            'oral' => 'Normal',
            'tongue' => 'Normal',
            'tonsils' => 'Grade 1',
            'tonsilAppearance' => 'Normal',
            'ppw' => 'Normal',
            'uvula' => 'Central',
            'palate' => 'Normal'
        ]),
        
        larynxData: @json($visit->getStructuredField('ent.larynx') ?? [
            'epiglottis' => 'Normal',
            'arytenoids' => 'Normal',
            'vocalCords' => 'Mobile, normal',
            'cordMobility' => 'Normal bilateral',
            'subglottis' => ''
        ]),
        
        commonDiagnoses: [
            { code: 'H66.9', name: 'CSOM' },
            { code: 'H65.9', name: 'OME' },
            { code: 'H60.9', name: 'Otitis Externa' },
            { code: 'H72.9', name: 'TM Perforation' },
            { code: 'J30.4', name: 'Allergic Rhinitis' },
            { code: 'J32.9', name: 'Chronic Sinusitis' },
            { code: 'J34.2', name: 'DNS' },
            { code: 'J33.0', name: 'Nasal Polyps' },
            { code: 'J03.9', name: 'Acute Tonsillitis' },
            { code: 'J35.0', name: 'Chronic Tonsillitis' },
            { code: 'J02.9', name: 'Pharyngitis' },
            { code: 'J06.9', name: 'URTI' },
            { code: 'J37.0', name: 'Chronic Laryngitis' },
            { code: 'J38.1', name: 'Vocal Cord Polyp' },
            { code: 'J38.2', name: 'Vocal Cord Nodules' },
            { code: 'R49.0', name: 'Dysphonia' },
            { code: 'H91.9', name: 'Hearing Loss' },
            { code: 'H81.0', name: 'Meniere\'s Disease' },
            { code: 'H81.1', name: 'BPPV' },
            { code: 'R42', name: 'Vertigo' }
        ],
        
        selectedDiagnoses: @json($visit->getStructuredField('ent.diagnoses') ?? []),
        
        init() {
            console.log('ENT EMR initialized');
        },
        
        updateEar() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateNose() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        toggleSinusTenderness(sinus) {
            const idx = this.noseData.sinusTenderness.indexOf(sinus);
            if (idx > -1) {
                this.noseData.sinusTenderness.splice(idx, 1);
            } else {
                this.noseData.sinusTenderness.push(sinus);
            }
            this.updateNose();
        },
        
        updateThroat() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateLarynx() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        toggleDiagnosis(dx) {
            const idx = this.selectedDiagnoses.indexOf(dx.code);
            if (idx > -1) {
                this.selectedDiagnoses.splice(idx, 1);
            } else {
                this.selectedDiagnoses.push(dx.code);
            }
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}
</script>
