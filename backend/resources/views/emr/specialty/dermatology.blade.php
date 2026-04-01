{{-- 
    Dermatology EMR Template
    Includes: Body diagram, lesion mapping, scales (PASI, IGA, DLQI), procedures
--}}

{{-- LESION MAPPING SECTION --}}
<div class="form-section" x-data="{ open: true }">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--blue)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <h3>Lesion Mapping</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div class="body-map-container" x-data="lesionMapper()">
            {{-- Body Diagram --}}
            <div class="body-diagram-wrapper" style="display:flex;gap:12px">
                {{-- Front View --}}
                <div class="body-diagram" @click="addLesion($event, 'front')" style="flex:1">
                    <div style="font-size:10px;font-weight:600;color:var(--text3);margin-bottom:8px">FRONT</div>
                    <svg viewBox="0 0 100 200" style="width:80px;height:160px">
                        {{-- Head --}}
                        <circle cx="50" cy="20" r="15" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        {{-- Torso --}}
                        <path d="M35 35 L35 90 L65 90 L65 35 Z" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        {{-- Arms --}}
                        <path d="M35 40 L15 75" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        <path d="M65 40 L85 75" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        {{-- Hands --}}
                        <circle cx="12" cy="78" r="5" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                        <circle cx="88" cy="78" r="5" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                        {{-- Legs --}}
                        <path d="M40 90 L38 150" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        <path d="M60 90 L62 150" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        {{-- Feet --}}
                        <ellipse cx="35" cy="155" rx="8" ry="5" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                        <ellipse cx="65" cy="155" rx="8" ry="5" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                    </svg>
                    {{-- Lesion markers --}}
                    <template x-for="(lesion, idx) in lesions.filter(l => l.view === 'front')" :key="idx">
                        <div class="lesion-marker" 
                             :style="`position:absolute; left:${lesion.x}%; top:${lesion.y}%; transform:translate(-50%,-50%); width:12px; height:12px; border-radius:50%; background:${lesion.color}; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.2); cursor:pointer`"
                             @click.stop="selectLesion(idx)"
                             :title="lesion.type">
                        </div>
                    </template>
                </div>
                
                {{-- Back View --}}
                <div class="body-diagram" @click="addLesion($event, 'back')" style="flex:1">
                    <div style="font-size:10px;font-weight:600;color:var(--text3);margin-bottom:8px">BACK</div>
                    <svg viewBox="0 0 100 200" style="width:80px;height:160px">
                        {{-- Head --}}
                        <circle cx="50" cy="20" r="15" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        {{-- Torso --}}
                        <path d="M35 35 L35 90 L65 90 L65 35 Z" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        {{-- Spine indication --}}
                        <path d="M50 35 L50 90" fill="none" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3"/>
                        {{-- Arms --}}
                        <path d="M35 40 L15 75" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        <path d="M65 40 L85 75" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        {{-- Hands --}}
                        <circle cx="12" cy="78" r="5" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                        <circle cx="88" cy="78" r="5" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                        {{-- Legs --}}
                        <path d="M40 90 L38 150" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        <path d="M60 90 L62 150" fill="none" stroke="#cbd5e1" stroke-width="1.5"/>
                        {{-- Feet --}}
                        <ellipse cx="35" cy="155" rx="8" ry="5" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                        <ellipse cx="65" cy="155" rx="8" ry="5" fill="none" stroke="#cbd5e1" stroke-width="1"/>
                    </svg>
                    {{-- Lesion markers --}}
                    <template x-for="(lesion, idx) in lesions.filter(l => l.view === 'back')" :key="idx">
                        <div class="lesion-marker" 
                             :style="`position:absolute; left:${lesion.x}%; top:${lesion.y}%; transform:translate(-50%,-50%); width:12px; height:12px; border-radius:50%; background:${lesion.color}; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.2); cursor:pointer`"
                             @click.stop="selectLesion(idx)">
                        </div>
                    </template>
                </div>
            </div>

            {{-- Lesion List --}}
            <div class="lesion-annotations" style="flex:1;min-width:280px">
                <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:10px">
                    Lesions (<span x-text="lesions.length">0</span>)
                </div>
                
                <template x-if="lesions.length === 0">
                    <div style="padding:24px;text-align:center;background:var(--bg);border-radius:8px;color:var(--text3);font-size:12px">
                        Click on the body diagram to mark lesion locations
                    </div>
                </template>

                <template x-for="(lesion, idx) in lesions" :key="idx">
                    <div class="lesion-row" :class="selectedLesion === idx ? 'selected' : ''" @click="selectLesion(idx)"
                         style="cursor:pointer;transition:all .15s" :style="selectedLesion === idx ? 'border:2px solid var(--blue);background:var(--blue-light)' : ''">
                        <div class="lesion-color" :style="`background:${lesion.color}`"></div>
                        <div class="lesion-desc" style="flex:1">
                            <div style="font-weight:600;color:var(--dark);font-size:12px" x-text="lesion.type || 'Unnamed'"></div>
                            <div style="font-size:11px;color:var(--text3)" x-text="`${lesion.region} (${lesion.view}) ${lesion.size ? '• ' + lesion.size + 'cm' : ''}`"></div>
                        </div>
                        <button type="button" class="lesion-remove" @click.stop="removeLesion(idx)" title="Remove">×</button>
                    </div>
                </template>

                {{-- Lesion Detail Form (shown when lesion selected) --}}
                <template x-if="selectedLesion !== null && lesions[selectedLesion]">
                    <div style="margin-top:12px;padding:14px;background:white;border:1.5px solid var(--border);border-radius:10px">
                        <div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:10px;text-transform:uppercase">Lesion Details</div>
                        
                        <div class="form-row form-row-2" style="gap:8px;margin-bottom:8px">
                            <div class="field-group">
                                <label class="field-label">Type</label>
                                <select class="field-select" x-model="lesions[selectedLesion].type" @change="updateLesion()">
                                    <option value="">Select...</option>
                                    <option value="Macule">Macule</option>
                                    <option value="Papule">Papule</option>
                                    <option value="Plaque">Plaque</option>
                                    <option value="Vesicle">Vesicle</option>
                                    <option value="Bulla">Bulla</option>
                                    <option value="Pustule">Pustule</option>
                                    <option value="Nodule">Nodule</option>
                                    <option value="Cyst">Cyst</option>
                                    <option value="Wheal">Wheal</option>
                                    <option value="Patch">Patch</option>
                                    <option value="Erosion">Erosion</option>
                                    <option value="Ulcer">Ulcer</option>
                                    <option value="Scar">Scar</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Size (cm)</label>
                                <input type="number" step="0.1" class="field-input" x-model="lesions[selectedLesion].size" @change="updateLesion()" placeholder="e.g. 2.5">
                            </div>
                        </div>
                        
                        <div class="form-row form-row-2" style="gap:8px;margin-bottom:8px">
                            <div class="field-group">
                                <label class="field-label">Colour</label>
                                <select class="field-select" x-model="lesions[selectedLesion].colour" @change="updateLesion()">
                                    <option value="">Select...</option>
                                    <option value="Erythematous">Erythematous (Red)</option>
                                    <option value="Hyperpigmented">Hyperpigmented</option>
                                    <option value="Hypopigmented">Hypopigmented</option>
                                    <option value="Violaceous">Violaceous</option>
                                    <option value="Yellow">Yellow</option>
                                    <option value="Brown">Brown</option>
                                    <option value="Black">Black</option>
                                    <option value="White">White</option>
                                    <option value="Skin-colored">Skin-colored</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Border</label>
                                <select class="field-select" x-model="lesions[selectedLesion].border" @change="updateLesion()">
                                    <option value="">Select...</option>
                                    <option value="Well-defined">Well-defined</option>
                                    <option value="Ill-defined">Ill-defined</option>
                                    <option value="Irregular">Irregular</option>
                                    <option value="Rolled">Rolled</option>
                                    <option value="Undermined">Undermined</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row form-row-2" style="gap:8px;margin-bottom:8px">
                            <div class="field-group">
                                <label class="field-label">Surface</label>
                                <select class="field-select" x-model="lesions[selectedLesion].surface" @change="updateLesion()">
                                    <option value="">Select...</option>
                                    <option value="Smooth">Smooth</option>
                                    <option value="Rough">Rough</option>
                                    <option value="Scaly">Scaly</option>
                                    <option value="Crusted">Crusted</option>
                                    <option value="Verrucous">Verrucous</option>
                                    <option value="Umbilicated">Umbilicated</option>
                                    <option value="Ulcerated">Ulcerated</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Distribution</label>
                                <select class="field-select" x-model="lesions[selectedLesion].distribution" @change="updateLesion()">
                                    <option value="">Select...</option>
                                    <option value="Localized">Localized</option>
                                    <option value="Generalized">Generalized</option>
                                    <option value="Bilateral">Bilateral</option>
                                    <option value="Symmetric">Symmetric</option>
                                    <option value="Dermatomal">Dermatomal</option>
                                    <option value="Acral">Acral</option>
                                    <option value="Photodistributed">Photodistributed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="field-group">
                            <label class="field-label">Notes</label>
                            <textarea class="field-textarea" x-model="lesions[selectedLesion].notes" @change="updateLesion()" rows="2" placeholder="Additional observations..."></textarea>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        {{-- Hidden input to store lesions JSON --}}
        <input type="hidden" name="lesions_json" :value="JSON.stringify(lesions)">
    </div>
</div>

{{-- DERMATOLOGICAL SCALES SECTION --}}
<div class="form-section" x-data="{ open: true }">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--teal)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
        </svg>
        <h3>Dermatological Scales</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse x-data="dermScales()">
        {{-- PASI Score (Psoriasis) --}}
        <div style="margin-bottom:20px;padding:16px;background:var(--bg);border-radius:10px">
            <div style="display:flex;align-items:center;justify-content:between;margin-bottom:12px">
                <div style="font-size:13px;font-weight:700;color:var(--dark)">PASI Score</div>
                <div style="margin-left:auto;padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700"
                     :class="pasiTotal < 5 ? 'sr-mild' : (pasiTotal < 10 ? 'sr-mod' : 'sr-sev')">
                    <span x-text="pasiTotal.toFixed(1)"></span>
                    <span x-text="pasiTotal < 5 ? '(Mild)' : (pasiTotal < 10 ? '(Moderate)' : '(Severe)')"></span>
                </div>
            </div>
            <div style="font-size:11px;color:var(--text3);margin-bottom:12px">Psoriasis Area and Severity Index</div>
            
            <div style="display:grid;grid-template-columns:100px repeat(4, 1fr);gap:8px;font-size:11px;margin-bottom:8px">
                <div style="font-weight:600;color:var(--text3)">Region</div>
                <div style="font-weight:600;color:var(--text3);text-align:center">Erythema</div>
                <div style="font-weight:600;color:var(--text3);text-align:center">Thickness</div>
                <div style="font-weight:600;color:var(--text3);text-align:center">Scale</div>
                <div style="font-weight:600;color:var(--text3);text-align:center">Area %</div>
            </div>
            
            <template x-for="region in ['Head', 'Trunk', 'Upper', 'Lower']" :key="region">
                <div style="display:grid;grid-template-columns:100px repeat(4, 1fr);gap:8px;margin-bottom:6px;align-items:center">
                    <div style="font-size:12px;color:var(--text2)" x-text="region === 'Upper' ? 'Upper Limbs' : (region === 'Lower' ? 'Lower Limbs' : region)"></div>
                    <select class="field-select" style="font-size:11px;padding:6px 8px" x-model="pasi[region].erythema" @change="calcPasi()">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                    <select class="field-select" style="font-size:11px;padding:6px 8px" x-model="pasi[region].thickness" @change="calcPasi()">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                    <select class="field-select" style="font-size:11px;padding:6px 8px" x-model="pasi[region].scale" @change="calcPasi()">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                    <select class="field-select" style="font-size:11px;padding:6px 8px" x-model="pasi[region].area" @change="calcPasi()">
                        <option value="0">0 (0%)</option>
                        <option value="1">1 (<10%)</option>
                        <option value="2">2 (10-29%)</option>
                        <option value="3">3 (30-49%)</option>
                        <option value="4">4 (50-69%)</option>
                        <option value="5">5 (70-89%)</option>
                        <option value="6">6 (90-100%)</option>
                    </select>
                </div>
            </template>
            <input type="hidden" name="pasi_score" :value="pasiTotal">
            <input type="hidden" name="pasi_data" :value="JSON.stringify(pasi)">
        </div>

        {{-- IGA Score --}}
        <div style="margin-bottom:20px;padding:16px;background:var(--bg);border-radius:10px">
            <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:8px">IGA Score</div>
            <div style="font-size:11px;color:var(--text3);margin-bottom:12px">Investigator's Global Assessment (0-4)</div>
            
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <template x-for="score in [0, 1, 2, 3, 4]" :key="score">
                    <button type="button" 
                            style="padding:10px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;border:1.5px solid var(--border)"
                            :class="iga === score ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 hover:border-blue-400'"
                            :style="iga === score ? 'background:var(--blue);color:white;border-color:var(--blue)' : ''"
                            @click="iga = score">
                        <span x-text="score"></span> - 
                        <span x-text="['Clear', 'Almost Clear', 'Mild', 'Moderate', 'Severe'][score]"></span>
                    </button>
                </template>
            </div>
            <input type="hidden" name="iga_score" :value="iga">
        </div>

        {{-- DLQI Score --}}
        <div style="padding:16px;background:var(--bg);border-radius:10px">
            <div style="display:flex;align-items:center;justify-content:between;margin-bottom:12px">
                <div style="font-size:13px;font-weight:700;color:var(--dark)">DLQI Score</div>
                <div style="margin-left:auto;padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700"
                     :class="dlqiTotal <= 5 ? 'sr-mild' : (dlqiTotal <= 10 ? 'sr-mod' : 'sr-sev')">
                    <span x-text="dlqiTotal"></span>/30
                    <span x-text="dlqiTotal <= 1 ? '(No effect)' : (dlqiTotal <= 5 ? '(Small)' : (dlqiTotal <= 10 ? '(Moderate)' : (dlqiTotal <= 20 ? '(Large)' : '(Extreme)')))"></span>
                </div>
            </div>
            <div style="font-size:11px;color:var(--text3);margin-bottom:12px">Dermatology Life Quality Index (last week)</div>
            
            <div style="display:grid;gap:8px">
                <template x-for="(q, idx) in dlqiQuestions" :key="idx">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div style="font-size:12px;color:var(--text2);flex:1" x-text="q"></div>
                        <select class="field-select" style="width:120px;font-size:11px;padding:6px 8px" x-model="dlqi[idx]" @change="calcDlqi()">
                            <option value="0">Not at all</option>
                            <option value="1">A little</option>
                            <option value="2">A lot</option>
                            <option value="3">Very much</option>
                        </select>
                    </div>
                </template>
            </div>
            <input type="hidden" name="dlqi_score" :value="dlqiTotal">
            <input type="hidden" name="dlqi_data" :value="JSON.stringify(dlqi)">
        </div>
    </div>
</div>

{{-- PROCEDURES SECTION --}}
<div class="form-section" x-data="{ open: true }">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--green)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
        </svg>
        <h3>Procedures Performed</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse x-data="dermProcedures()">
        <div style="font-size:11px;color:var(--text3);margin-bottom:12px">Select procedures performed during this visit</div>
        
        <div class="proc-grid">
            <template x-for="proc in availableProcedures" :key="proc.code">
                <div class="proc-chip" 
                     :class="selectedProcedures.includes(proc.code) ? 'selected' : ''"
                     @click="toggleProcedure(proc.code)">
                    <span x-text="proc.name"></span>
                </div>
            </template>
        </div>

        {{-- Procedure Details --}}
        <template x-for="procCode in selectedProcedures" :key="procCode">
            <div style="margin-top:12px;padding:14px;background:var(--bg);border-radius:10px">
                <div style="font-size:12px;font-weight:700;color:var(--dark);margin-bottom:10px" x-text="getProcName(procCode)"></div>
                
                <div class="form-row form-row-3" style="gap:8px">
                    <div class="field-group">
                        <label class="field-label">Body Region</label>
                        <input type="text" class="field-input" x-model="procedureDetails[procCode].region" placeholder="e.g. Face, Arms">
                    </div>
                    <div class="field-group" x-show="procCode === 'LASER'">
                        <label class="field-label">LASER Type</label>
                        <select class="field-select" x-model="procedureDetails[procCode].laserType">
                            <option value="">Select...</option>
                            <option value="CO2">CO2 Laser</option>
                            <option value="Nd:YAG">Nd:YAG</option>
                            <option value="Alexandrite">Alexandrite</option>
                            <option value="Diode">Diode</option>
                            <option value="IPL">IPL</option>
                            <option value="Q-Switched">Q-Switched</option>
                            <option value="Fractional">Fractional</option>
                        </select>
                    </div>
                    <div class="field-group" x-show="procCode === 'LASER'">
                        <label class="field-label">Settings</label>
                        <input type="text" class="field-input" x-model="procedureDetails[procCode].settings" placeholder="e.g. 20J, 10ms">
                    </div>
                    <div class="field-group" x-show="procCode === 'PEEL'">
                        <label class="field-label">Peel Agent</label>
                        <select class="field-select" x-model="procedureDetails[procCode].agent">
                            <option value="">Select...</option>
                            <option value="Glycolic">Glycolic Acid</option>
                            <option value="Salicylic">Salicylic Acid</option>
                            <option value="TCA">TCA</option>
                            <option value="Jessner">Jessner's</option>
                            <option value="Lactic">Lactic Acid</option>
                            <option value="Mandelic">Mandelic Acid</option>
                        </select>
                    </div>
                    <div class="field-group" x-show="procCode === 'PEEL'">
                        <label class="field-label">Concentration</label>
                        <input type="text" class="field-input" x-model="procedureDetails[procCode].concentration" placeholder="e.g. 35%">
                    </div>
                    <div class="field-group" x-show="procCode === 'PRP'">
                        <label class="field-label">Session #</label>
                        <input type="number" class="field-input" x-model="procedureDetails[procCode].session" placeholder="1">
                    </div>
                    <div class="field-group" x-show="procCode === 'BOTOX' || procCode === 'FILLER'">
                        <label class="field-label">Units/Volume</label>
                        <input type="text" class="field-input" x-model="procedureDetails[procCode].units" placeholder="e.g. 20 units">
                    </div>
                    <div class="field-group" x-show="procCode === 'BOTOX' || procCode === 'FILLER'">
                        <label class="field-label">Sites</label>
                        <input type="text" class="field-input" x-model="procedureDetails[procCode].sites" placeholder="e.g. Glabella, Forehead">
                    </div>
                </div>
                
                <div class="field-group" style="margin-top:8px">
                    <label class="field-label">Notes</label>
                    <textarea class="field-textarea" x-model="procedureDetails[procCode].notes" rows="2" placeholder="Procedure notes..."></textarea>
                </div>
            </div>
        </template>
        
        <input type="hidden" name="procedures_json" :value="JSON.stringify(getSelectedProceduresData())">
    </div>
</div>

{{-- BEFORE/AFTER PHOTOS --}}
<div class="form-section" x-data="{ open: true }">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--amber)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
        </svg>
        <h3>Before/After Photos</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div class="photo-grid">
            {{-- Existing photos --}}
            @foreach($visit->photos ?? [] as $photo)
            <div class="photo-thumb">
                <img src="{{ route('patients.view-photo', [$patient, $photo->id]) }}" alt="Visit photo" style="width:100%;height:100%;object-fit:cover">
                <div style="position:absolute;top:4px;left:4px;padding:2px 6px;background:rgba(0,0,0,0.6);color:white;font-size:9px;border-radius:4px">
                    {{ ucfirst($photo->photo_type) }}
                </div>
            </div>
            @endforeach
            
            {{-- Upload placeholder --}}
            <label class="photo-thumb" style="cursor:pointer">
                <input type="file" name="visit_photos[]" accept="image/*" multiple style="display:none" @change="window.triggerAutoSave && window.triggerAutoSave()">
                <div class="photo-placeholder">
                    <svg style="width:24px;height:24px;color:var(--text3)" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    <span class="photo-label">Add Photo</span>
                </div>
            </label>
        </div>
        
        <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
            <select name="photo_type" class="field-select" style="width:150px">
                <option value="before">Before</option>
                <option value="after">After</option>
                <option value="progress">Progress</option>
                <option value="clinical">Clinical</option>
            </select>
            <input type="text" name="photo_body_region" class="field-input" style="flex:1" placeholder="Body region (e.g. Face, Left Arm)">
        </div>
    </div>
</div>

@push('scripts')
<script>
console.log('Dermatology EMR specialty template loaded');

// Lesion Mapper Component
function lesionMapper() {
    return {
        lesions: @json($visit->lesions ?? []),
        selectedLesion: null,
        
        init() {
            console.log('lesionMapper initialized', this.lesions.length, 'lesions');
            // Convert existing lesions from DB format
            if (this.lesions.length > 0 && this.lesions[0].body_region) {
                this.lesions = this.lesions.map(l => ({
                    x: parseFloat(l.x_pct) || 50,
                    y: parseFloat(l.y_pct) || 50,
                    view: l.view || 'front',
                    region: l.body_region || '',
                    type: l.lesion_type || '',
                    size: l.size_cm || '',
                    colour: l.colour || '',
                    border: l.border || '',
                    surface: l.surface || '',
                    distribution: l.distribution || '',
                    notes: l.notes || '',
                    color: this.getColorForType(l.lesion_type)
                }));
            }
        },
        
        getColorForType(type) {
            const colors = {
                'Macule': '#ef4444',
                'Papule': '#f97316',
                'Plaque': '#eab308',
                'Vesicle': '#22c55e',
                'Bulla': '#14b8a6',
                'Pustule': '#06b6d4',
                'Nodule': '#3b82f6',
                'Cyst': '#8b5cf6',
                'Wheal': '#ec4899',
                'Patch': '#f43f5e',
                'Erosion': '#dc2626',
                'Ulcer': '#991b1b',
                'Scar': '#78716c'
            };
            return colors[type] || '#6b7280';
        },
        
        addLesion(event, view) {
            const rect = event.target.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;
            
            const region = this.getBodyRegion(x, y, view);
            
            this.lesions.push({
                x: x.toFixed(1),
                y: y.toFixed(1),
                view: view,
                region: region,
                type: '',
                size: '',
                colour: '',
                border: '',
                surface: '',
                distribution: '',
                notes: '',
                color: '#6b7280'
            });
            
            this.selectedLesion = this.lesions.length - 1;
            console.log('Added lesion at', x.toFixed(1), y.toFixed(1), 'view:', view, 'region:', region);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        getBodyRegion(x, y, view) {
            // Simple region detection based on coordinates
            if (y < 25) return 'Head/Scalp';
            if (y < 35) return 'Neck';
            if (y >= 35 && y < 60 && x > 30 && x < 70) return view === 'front' ? 'Chest/Abdomen' : 'Back';
            if (y >= 35 && y < 50 && (x < 30 || x > 70)) return 'Upper Arms';
            if (y >= 50 && y < 55 && (x < 25 || x > 75)) return 'Forearms';
            if (y >= 55 && y < 60 && (x < 20 || x > 80)) return 'Hands';
            if (y >= 60 && y < 75) return 'Thighs';
            if (y >= 75 && y < 90) return 'Lower Legs';
            if (y >= 90) return 'Feet';
            return 'Trunk';
        },
        
        selectLesion(idx) {
            this.selectedLesion = idx;
            console.log('Selected lesion', idx);
        },
        
        removeLesion(idx) {
            this.lesions.splice(idx, 1);
            if (this.selectedLesion === idx) this.selectedLesion = null;
            if (this.selectedLesion > idx) this.selectedLesion--;
            console.log('Removed lesion', idx);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateLesion() {
            if (this.selectedLesion !== null && this.lesions[this.selectedLesion]) {
                // Update color based on type
                this.lesions[this.selectedLesion].color = this.getColorForType(this.lesions[this.selectedLesion].type);
            }
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}

// Dermatological Scales Component
function dermScales() {
    return {
        pasi: {
            Head: { erythema: 0, thickness: 0, scale: 0, area: 0 },
            Trunk: { erythema: 0, thickness: 0, scale: 0, area: 0 },
            Upper: { erythema: 0, thickness: 0, scale: 0, area: 0 },
            Lower: { erythema: 0, thickness: 0, scale: 0, area: 0 }
        },
        pasiTotal: 0,
        iga: null,
        dlqi: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        dlqiTotal: 0,
        dlqiQuestions: [
            '1. Itchy, sore, painful or stinging skin?',
            '2. Embarrassed or self-conscious?',
            '3. Interfered with shopping/home/gardening?',
            '4. Influenced clothes you wear?',
            '5. Affected social/leisure activities?',
            '6. Made it difficult to do sport?',
            '7. Prevented you from working/studying?',
            '8. Problems with partner/friends/relatives?',
            '9. Sexual difficulties?',
            '10. Treatment problems?'
        ],
        
        init() {
            console.log('dermScales initialized');
            // Load existing scale data if available
            @if($visit->scales && $visit->scales->isNotEmpty())
                @foreach($visit->scales as $scale)
                    @if($scale->scale_name === 'PASI')
                        this.pasi = @json($scale->components ?? []);
                        this.pasiTotal = {{ $scale->score ?? 0 }};
                    @elseif($scale->scale_name === 'IGA')
                        this.iga = {{ $scale->score ?? 'null' }};
                    @elseif($scale->scale_name === 'DLQI')
                        this.dlqi = @json($scale->components ?? [0,0,0,0,0,0,0,0,0,0]);
                        this.dlqiTotal = {{ $scale->score ?? 0 }};
                    @endif
                @endforeach
            @endif
        },
        
        calcPasi() {
            const weights = { Head: 0.1, Trunk: 0.3, Upper: 0.2, Lower: 0.4 };
            let total = 0;
            for (let region in this.pasi) {
                const r = this.pasi[region];
                const sum = parseInt(r.erythema) + parseInt(r.thickness) + parseInt(r.scale);
                total += sum * parseInt(r.area) * weights[region];
            }
            this.pasiTotal = total;
            console.log('PASI calculated:', this.pasiTotal);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        calcDlqi() {
            this.dlqiTotal = this.dlqi.reduce((a, b) => parseInt(a) + parseInt(b), 0);
            console.log('DLQI calculated:', this.dlqiTotal);
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}

// Dermatology Procedures Component
function dermProcedures() {
    return {
        availableProcedures: [
            { code: 'LASER', name: 'LASER' },
            { code: 'PEEL', name: 'Chemical Peel' },
            { code: 'PRP', name: 'PRP' },
            { code: 'MESO', name: 'Mesotherapy' },
            { code: 'MICRO', name: 'Microneedling' },
            { code: 'BOTOX', name: 'Botox' },
            { code: 'FILLER', name: 'Fillers' },
            { code: 'HF', name: 'HydraFacial' },
            { code: 'CRYO', name: 'Cryotherapy' },
            { code: 'EXCISION', name: 'Excision' },
            { code: 'BIOPSY', name: 'Biopsy' },
            { code: 'CAUTERY', name: 'Cautery' }
        ],
        selectedProcedures: [],
        procedureDetails: {},
        
        init() {
            console.log('dermProcedures initialized');
            // Load existing procedures if available
            @if($visit->procedures && $visit->procedures->isNotEmpty())
                @foreach($visit->procedures as $proc)
                    this.selectedProcedures.push('{{ $proc->procedure_code }}');
                    this.procedureDetails['{{ $proc->procedure_code }}'] = @json($proc->parameters ?? []);
                    this.procedureDetails['{{ $proc->procedure_code }}'].region = '{{ $proc->body_region }}';
                    this.procedureDetails['{{ $proc->procedure_code }}'].notes = '{{ $proc->notes }}';
                @endforeach
            @endif
        },
        
        toggleProcedure(code) {
            const idx = this.selectedProcedures.indexOf(code);
            if (idx > -1) {
                this.selectedProcedures.splice(idx, 1);
                delete this.procedureDetails[code];
            } else {
                this.selectedProcedures.push(code);
                this.procedureDetails[code] = { region: '', notes: '' };
            }
            console.log('Toggled procedure:', code, 'selected:', this.selectedProcedures);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        getProcName(code) {
            const proc = this.availableProcedures.find(p => p.code === code);
            return proc ? proc.name : code;
        },
        
        getSelectedProceduresData() {
            return this.selectedProcedures.map(code => ({
                code: code,
                name: this.getProcName(code),
                ...this.procedureDetails[code]
            }));
        }
    };
}
</script>
@endpush
