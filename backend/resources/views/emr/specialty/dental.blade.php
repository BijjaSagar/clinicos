{{-- 
    Dental EMR Template
    Includes: 32-tooth FDI chart, per-tooth treatment history, treatment plan, periodontal charting
--}}

<style>
/* Dental Chart Styles */
.dental-chart-container {
    padding: 20px;
    background: white;
    border-radius: 12px;
}
.tooth-row {
    display: flex;
    justify-content: center;
    gap: 2px;
    margin-bottom: 4px;
}
.tooth {
    width: 36px;
    height: 48px;
    border: 1.5px solid var(--border);
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2px;
    transition: all 0.15s;
    background: white;
    position: relative;
}
.tooth:hover {
    border-color: var(--blue);
    background: var(--blue-light);
}
.tooth.selected {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(20, 71, 230, 0.2);
}
.tooth.missing {
    background: #f3f4f6;
    opacity: 0.5;
}
.tooth.treated {
    border-color: var(--green);
    background: var(--green-light);
}
.tooth.caries {
    border-color: var(--red);
    background: #fff1f2;
}
.tooth.rct {
    border-color: var(--amber);
    background: #fffbeb;
}
.tooth-number {
    font-size: 9px;
    font-weight: 700;
    color: var(--text3);
}
.tooth-visual {
    width: 24px;
    height: 28px;
    border: 1px solid #e5e7eb;
    border-radius: 2px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: var(--text3);
}
.tooth-status-icon {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    font-size: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
}
.quadrant-label {
    font-size: 10px;
    font-weight: 600;
    color: var(--text3);
    text-align: center;
    padding: 4px;
}
.arch-divider {
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text3);
    font-size: 11px;
    font-weight: 500;
    margin: 8px 0;
}
.arch-divider::before, .arch-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
    margin: 0 12px;
}
</style>

{{-- DENTAL CHART SECTION --}}
<div class="form-section" x-data="dentalChart()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--blue)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/>
        </svg>
        <h3>Dental Chart (FDI Notation)</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        {{-- Legend --}}
        <div style="display:flex;gap:16px;margin-bottom:16px;font-size:11px;flex-wrap:wrap">
            <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:12px;border-radius:2px;background:white;border:1.5px solid var(--border)"></span> Present</span>
            <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:12px;border-radius:2px;background:#f3f4f6;opacity:0.6"></span> Missing</span>
            <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:12px;border-radius:2px;background:#fff1f2;border:1.5px solid var(--red)"></span> Caries</span>
            <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:12px;border-radius:2px;background:var(--green-light);border:1.5px solid var(--green)"></span> Treated</span>
            <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:12px;border-radius:2px;background:#fffbeb;border:1.5px solid var(--amber)"></span> RCT</span>
        </div>

        <div class="dental-chart-container">
            {{-- Upper Arch --}}
            <div class="quadrant-label">UPPER (Maxillary)</div>
            <div style="display:flex;justify-content:center;gap:20px">
                {{-- Upper Right (Q1: 18-11) --}}
                <div>
                    <div class="tooth-row">
                        <template x-for="num in [18,17,16,15,14,13,12,11]" :key="num">
                            <div class="tooth" 
                                 :class="getToothClass(num)"
                                 @click="selectTooth(num)">
                                <span class="tooth-number" x-text="num"></span>
                                <div class="tooth-visual" x-text="getToothSymbol(num)"></div>
                                <template x-if="teeth[num]?.status === 'caries'">
                                    <span class="tooth-status-icon" style="background:var(--red)">C</span>
                                </template>
                                <template x-if="teeth[num]?.restoration === 'rct'">
                                    <span class="tooth-status-icon" style="background:var(--amber)">R</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                
                {{-- Upper Left (Q2: 21-28) --}}
                <div>
                    <div class="tooth-row">
                        <template x-for="num in [21,22,23,24,25,26,27,28]" :key="num">
                            <div class="tooth" 
                                 :class="getToothClass(num)"
                                 @click="selectTooth(num)">
                                <span class="tooth-number" x-text="num"></span>
                                <div class="tooth-visual" x-text="getToothSymbol(num)"></div>
                                <template x-if="teeth[num]?.status === 'caries'">
                                    <span class="tooth-status-icon" style="background:var(--red)">C</span>
                                </template>
                                <template x-if="teeth[num]?.restoration === 'rct'">
                                    <span class="tooth-status-icon" style="background:var(--amber)">R</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            
            <div class="arch-divider">RIGHT ← Midline → LEFT</div>
            
            {{-- Lower Arch --}}
            <div style="display:flex;justify-content:center;gap:20px">
                {{-- Lower Right (Q4: 48-41) --}}
                <div>
                    <div class="tooth-row">
                        <template x-for="num in [48,47,46,45,44,43,42,41]" :key="num">
                            <div class="tooth" 
                                 :class="getToothClass(num)"
                                 @click="selectTooth(num)">
                                <span class="tooth-number" x-text="num"></span>
                                <div class="tooth-visual" x-text="getToothSymbol(num)"></div>
                                <template x-if="teeth[num]?.status === 'caries'">
                                    <span class="tooth-status-icon" style="background:var(--red)">C</span>
                                </template>
                                <template x-if="teeth[num]?.restoration === 'rct'">
                                    <span class="tooth-status-icon" style="background:var(--amber)">R</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
                
                {{-- Lower Left (Q3: 31-38) --}}
                <div>
                    <div class="tooth-row">
                        <template x-for="num in [31,32,33,34,35,36,37,38]" :key="num">
                            <div class="tooth" 
                                 :class="getToothClass(num)"
                                 @click="selectTooth(num)">
                                <span class="tooth-number" x-text="num"></span>
                                <div class="tooth-visual" x-text="getToothSymbol(num)"></div>
                                <template x-if="teeth[num]?.status === 'caries'">
                                    <span class="tooth-status-icon" style="background:var(--red)">C</span>
                                </template>
                                <template x-if="teeth[num]?.restoration === 'rct'">
                                    <span class="tooth-status-icon" style="background:var(--amber)">R</span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="quadrant-label">LOWER (Mandibular)</div>
        </div>

        {{-- Selected Tooth Details --}}
        <template x-if="selectedTooth !== null">
            <div style="margin-top:16px;padding:16px;background:var(--bg);border-radius:10px;border:1.5px solid var(--border)">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                    <div style="font-size:14px;font-weight:700;color:var(--dark)">
                        Tooth #<span x-text="selectedTooth"></span> - <span x-text="getToothName(selectedTooth)"></span>
                    </div>
                    <button type="button" @click="selectedTooth = null" style="color:var(--text3);font-size:18px;background:none;border:none;cursor:pointer">×</button>
                </div>
                
                <div class="form-row form-row-3" style="gap:10px">
                    <div class="field-group">
                        <label class="field-label">Status</label>
                        <select class="field-select" x-model="teeth[selectedTooth].status" @change="updateTooth()">
                            <option value="present">Present</option>
                            <option value="missing">Missing</option>
                            <option value="extracted">Extracted</option>
                            <option value="unerupted">Unerupted</option>
                            <option value="impacted">Impacted</option>
                            <option value="implant">Implant</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Caries</label>
                        <select class="field-select" x-model="teeth[selectedTooth].caries" @change="updateTooth()">
                            <option value="none">None</option>
                            <option value="initial">Initial</option>
                            <option value="moderate">Moderate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Restoration</label>
                        <select class="field-select" x-model="teeth[selectedTooth].restoration" @change="updateTooth()">
                            <option value="none">None</option>
                            <option value="amalgam">Amalgam</option>
                            <option value="composite">Composite</option>
                            <option value="crown">Crown</option>
                            <option value="bridge">Bridge</option>
                            <option value="rct">RCT</option>
                            <option value="veneer">Veneer</option>
                            <option value="implant_crown">Implant Crown</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row form-row-3" style="gap:10px;margin-top:10px">
                    <div class="field-group">
                        <label class="field-label">Mobility Grade</label>
                        <select class="field-select" x-model="teeth[selectedTooth].mobility" @change="updateTooth()">
                            <option value="">-</option>
                            <option value="0">0 - Normal</option>
                            <option value="1">I - Slight</option>
                            <option value="2">II - Moderate</option>
                            <option value="3">III - Severe</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Recession (mm)</label>
                        <input type="number" step="0.5" class="field-input" x-model="teeth[selectedTooth].recession" @input="updateTooth()" placeholder="0">
                    </div>
                    <div class="field-group">
                        <label class="field-label">BOP</label>
                        <select class="field-select" x-model="teeth[selectedTooth].bop" @change="updateTooth()">
                            <option value="">-</option>
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>
                
                <div class="field-group" style="margin-top:10px">
                    <label class="field-label">Notes</label>
                    <textarea class="field-textarea" x-model="teeth[selectedTooth].notes" @input="updateTooth()" rows="2" placeholder="Clinical notes for this tooth..."></textarea>
                </div>
            </div>
        </template>
        
        <input type="hidden" name="dental_teeth_data" :value="JSON.stringify(teeth)">
    </div>
</div>

{{-- TREATMENT PLAN SECTION --}}
<div class="form-section" x-data="dentalTreatmentPlan()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--green)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
        </svg>
        <h3>Treatment Plan</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:12px">
                <thead>
                    <tr style="background:var(--bg)">
                        <th style="padding:10px;text-align:left;font-weight:600;color:var(--text3);font-size:10px;text-transform:uppercase">Tooth</th>
                        <th style="padding:10px;text-align:left;font-weight:600;color:var(--text3);font-size:10px;text-transform:uppercase">Diagnosis</th>
                        <th style="padding:10px;text-align:left;font-weight:600;color:var(--text3);font-size:10px;text-transform:uppercase">Treatment</th>
                        <th style="padding:10px;text-align:center;font-weight:600;color:var(--text3);font-size:10px;text-transform:uppercase">Priority</th>
                        <th style="padding:10px;text-align:center;font-weight:600;color:var(--text3);font-size:10px;text-transform:uppercase">Est. Cost</th>
                        <th style="padding:10px;text-align:center;font-weight:600;color:var(--text3);font-size:10px;text-transform:uppercase">Status</th>
                        <th style="padding:10px;width:40px"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in treatments" :key="idx">
                        <tr style="border-bottom:1px solid var(--border)">
                            <td style="padding:8px">
                                <select class="field-select" style="width:70px;padding:6px 8px;font-size:12px" x-model="item.tooth" @change="updatePlan()">
                                    <option value="">-</option>
                                    <template x-for="t in allTeeth" :key="t">
                                        <option :value="t" x-text="t"></option>
                                    </template>
                                </select>
                            </td>
                            <td style="padding:8px">
                                <input type="text" class="field-input" style="padding:6px 8px;font-size:12px" x-model="item.diagnosis" placeholder="e.g. Caries, Pulpitis" @input="updatePlan()">
                            </td>
                            <td style="padding:8px">
                                <select class="field-select" style="padding:6px 8px;font-size:12px" x-model="item.treatment" @change="updatePlan()">
                                    <option value="">Select...</option>
                                    <optgroup label="Restorative">
                                        <option value="filling_composite">Composite Filling</option>
                                        <option value="filling_amalgam">Amalgam Filling</option>
                                        <option value="inlay">Inlay</option>
                                        <option value="onlay">Onlay</option>
                                    </optgroup>
                                    <optgroup label="Endodontic">
                                        <option value="rct_single">RCT (Single Canal)</option>
                                        <option value="rct_multi">RCT (Multi Canal)</option>
                                        <option value="re_rct">Re-RCT</option>
                                        <option value="pulpotomy">Pulpotomy</option>
                                    </optgroup>
                                    <optgroup label="Prosthodontic">
                                        <option value="crown_pfc">Crown (PFM)</option>
                                        <option value="crown_zirconia">Crown (Zirconia)</option>
                                        <option value="crown_ceramic">Crown (Full Ceramic)</option>
                                        <option value="bridge">Bridge</option>
                                        <option value="denture_partial">Partial Denture</option>
                                        <option value="denture_complete">Complete Denture</option>
                                    </optgroup>
                                    <optgroup label="Surgery">
                                        <option value="extraction_simple">Simple Extraction</option>
                                        <option value="extraction_surgical">Surgical Extraction</option>
                                        <option value="implant">Dental Implant</option>
                                    </optgroup>
                                    <optgroup label="Periodontal">
                                        <option value="scaling">Scaling</option>
                                        <option value="root_planing">Root Planing</option>
                                        <option value="flap_surgery">Flap Surgery</option>
                                    </optgroup>
                                    <optgroup label="Other">
                                        <option value="bleaching">Bleaching</option>
                                        <option value="veneer">Veneer</option>
                                        <option value="orthodontic">Orthodontic Treatment</option>
                                    </optgroup>
                                </select>
                            </td>
                            <td style="padding:8px;text-align:center">
                                <select class="field-select" style="width:80px;padding:6px 8px;font-size:11px" x-model="item.priority" @change="updatePlan()">
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </td>
                            <td style="padding:8px;text-align:center">
                                <input type="number" class="field-input" style="width:80px;padding:6px 8px;font-size:12px;text-align:center" x-model="item.cost" placeholder="₹" @input="updatePlan()">
                            </td>
                            <td style="padding:8px;text-align:center">
                                <select class="field-select" style="width:90px;padding:6px 8px;font-size:11px" x-model="item.status" @change="updatePlan()">
                                    <option value="planned">Planned</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </td>
                            <td style="padding:8px;text-align:center">
                                <button type="button" @click="removeTreatment(idx)" style="color:var(--text3);cursor:pointer;font-size:16px;background:none;border:none">×</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg);font-weight:600">
                        <td colspan="4" style="padding:10px;text-align:right;font-size:12px">Total Estimated Cost:</td>
                        <td style="padding:10px;text-align:center;font-size:13px;color:var(--dark)">₹<span x-text="totalCost.toLocaleString()"></span></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <button type="button" @click="addTreatment()" style="margin-top:10px;display:flex;align-items:center;gap:6px;padding:10px 14px;border:1.5px dashed var(--border);border-radius:8px;font-size:12px;color:var(--text3);cursor:pointer;background:none">
            <span>+</span> Add Treatment
        </button>
        
        <input type="hidden" name="dental_treatment_plan" :value="JSON.stringify(treatments)">
    </div>
</div>

{{-- TODAY'S PROCEDURE SECTION --}}
<div class="form-section" x-data="{ open: true }">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--teal)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
        </svg>
        <h3>Today's Procedure</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div class="form-row form-row-3">
            <div class="field-group">
                <label class="field-label">Tooth/Teeth</label>
                <input type="text" name="dental_proc_teeth" class="field-input" placeholder="e.g. 16, 17" value="{{ $visit->getStructuredField('dental.proc_teeth') ?? '' }}" @input="window.triggerAutoSave && window.triggerAutoSave()">
            </div>
            <div class="field-group">
                <label class="field-label">Procedure Done</label>
                <select name="dental_proc_done" class="field-select" @change="window.triggerAutoSave && window.triggerAutoSave()">
                    <option value="">Select...</option>
                    <option value="examination">Examination</option>
                    <option value="scaling">Scaling & Polishing</option>
                    <option value="filling">Filling</option>
                    <option value="rct_access">RCT - Access Opening</option>
                    <option value="rct_bmp">RCT - BMP</option>
                    <option value="rct_obturation">RCT - Obturation</option>
                    <option value="extraction">Extraction</option>
                    <option value="impression">Impression</option>
                    <option value="crown_prep">Crown Prep</option>
                    <option value="crown_cementation">Crown Cementation</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label">Anesthesia</label>
                <select name="dental_anesthesia" class="field-select" @change="window.triggerAutoSave && window.triggerAutoSave()">
                    <option value="">None</option>
                    <option value="lignocaine">Lignocaine</option>
                    <option value="articaine">Articaine</option>
                    <option value="infiltration">Infiltration</option>
                    <option value="block">Nerve Block</option>
                </select>
            </div>
        </div>
        
        <div class="form-row form-row-2" style="margin-top:10px">
            <div class="field-group">
                <label class="field-label">Material Used</label>
                <input type="text" name="dental_material" class="field-input" placeholder="e.g. Composite A2, GIC" value="{{ $visit->getStructuredField('dental.material') ?? '' }}" @input="window.triggerAutoSave && window.triggerAutoSave()">
            </div>
            <div class="field-group">
                <label class="field-label">Shade (if applicable)</label>
                <input type="text" name="dental_shade" class="field-input" placeholder="e.g. A2, A3" value="{{ $visit->getStructuredField('dental.shade') ?? '' }}" @input="window.triggerAutoSave && window.triggerAutoSave()">
            </div>
        </div>
        
        <div class="field-group" style="margin-top:10px">
            <label class="field-label">Procedure Notes</label>
            <textarea name="dental_proc_notes" class="field-textarea" rows="3" placeholder="Detailed procedure notes..." @input="window.triggerAutoSave && window.triggerAutoSave()">{{ $visit->getStructuredField('dental.proc_notes') ?? '' }}</textarea>
        </div>
    </div>
</div>

{{-- LAB WORK ORDER SECTION --}}
<div class="form-section" x-data="labWorkSection()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--amber)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
        </svg>
        <h3>Lab Work Order</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <template x-for="(order, idx) in labOrders" :key="idx">
            <div style="padding:14px;background:var(--bg);border-radius:10px;margin-bottom:10px">
                <div class="form-row form-row-3" style="gap:8px">
                    <div class="field-group">
                        <label class="field-label">Tooth</label>
                        <input type="text" class="field-input" x-model="order.tooth" placeholder="e.g. 16" @input="updateLabOrder()">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Work Type</label>
                        <select class="field-select" x-model="order.workType" @change="updateLabOrder()">
                            <option value="">Select...</option>
                            <option value="crown_pfm">Crown (PFM)</option>
                            <option value="crown_zirconia">Crown (Zirconia)</option>
                            <option value="crown_ceramic">Crown (Full Ceramic)</option>
                            <option value="bridge">Bridge</option>
                            <option value="veneer">Veneer</option>
                            <option value="inlay_onlay">Inlay/Onlay</option>
                            <option value="denture_partial">Partial Denture</option>
                            <option value="denture_complete">Complete Denture</option>
                            <option value="ortho_model">Ortho Model</option>
                            <option value="retainer">Retainer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Shade</label>
                        <input type="text" class="field-input" x-model="order.shade" placeholder="e.g. A2" @input="updateLabOrder()">
                    </div>
                </div>
                <div class="form-row form-row-2" style="gap:8px;margin-top:8px">
                    <div class="field-group">
                        <label class="field-label">Lab Vendor</label>
                        <input type="text" class="field-input" x-model="order.vendor" placeholder="Lab name" @input="updateLabOrder()">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Expected Delivery</label>
                        <input type="date" class="field-input" x-model="order.deliveryDate" @change="updateLabOrder()">
                    </div>
                </div>
                <div class="field-group" style="margin-top:8px">
                    <label class="field-label">Preparation Notes</label>
                    <textarea class="field-textarea" x-model="order.notes" rows="2" placeholder="Special instructions for lab..." @input="updateLabOrder()"></textarea>
                </div>
                <button type="button" @click="removeLabOrder(idx)" style="margin-top:8px;color:var(--red);font-size:12px;background:none;border:none;cursor:pointer">Remove Order</button>
            </div>
        </template>
        
        <button type="button" @click="addLabOrder()" style="display:flex;align-items:center;gap:6px;padding:10px 14px;border:1.5px dashed var(--border);border-radius:8px;font-size:12px;color:var(--text3);cursor:pointer;background:none">
            <span>+</span> Add Lab Work Order
        </button>
        
        <input type="hidden" name="dental_lab_orders" :value="JSON.stringify(labOrders)">
    </div>
</div>

{{-- X-RAY ATTACHMENT SECTION --}}
<div class="form-section" x-data="{ open: true }">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--text2)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
        </svg>
        <h3>X-Ray / Radiographs</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div class="photo-grid" style="grid-template-columns:repeat(3, 1fr)">
            {{-- Existing X-rays --}}
            @foreach($visit->photos->where('condition_tag', 'xray') ?? [] as $photo)
            <div class="photo-thumb" style="aspect-ratio:4/3">
                <img src="{{ route('patients.view-photo', [$patient, $photo->id]) }}" alt="X-ray" style="width:100%;height:100%;object-fit:cover">
                <div style="position:absolute;bottom:4px;left:4px;padding:2px 6px;background:rgba(0,0,0,0.6);color:white;font-size:9px;border-radius:4px">
                    {{ $photo->body_region ?? 'X-Ray' }}
                </div>
            </div>
            @endforeach
            
            {{-- Upload placeholder --}}
            <label class="photo-thumb" style="cursor:pointer;aspect-ratio:4/3">
                <input type="file" name="dental_xrays[]" accept="image/*" multiple style="display:none" @change="window.triggerAutoSave && window.triggerAutoSave()">
                <div class="photo-placeholder">
                    <svg style="width:24px;height:24px;color:var(--text3)" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    <span class="photo-label">Add X-Ray</span>
                </div>
            </label>
        </div>
        
        <div class="form-row form-row-2" style="margin-top:12px">
            <div class="field-group">
                <label class="field-label">X-Ray Type</label>
                <select name="dental_xray_type" class="field-select">
                    <option value="iopa">IOPA</option>
                    <option value="opg">OPG</option>
                    <option value="bitewing">Bitewing</option>
                    <option value="occlusal">Occlusal</option>
                    <option value="cbct">CBCT</option>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label">Teeth/Region</label>
                <input type="text" name="dental_xray_region" class="field-input" placeholder="e.g. 16-17, Upper right quadrant">
            </div>
        </div>
        
        <div class="field-group" style="margin-top:10px">
            <label class="field-label">Radiographic Findings</label>
            <textarea name="dental_xray_findings" class="field-textarea" rows="2" placeholder="Describe findings..." @input="window.triggerAutoSave && window.triggerAutoSave()">{{ $visit->getStructuredField('dental.xray_findings') ?? '' }}</textarea>
        </div>
    </div>
</div>

@push('scripts')
<script>
console.log('Dental EMR specialty template loaded');

// Dental Chart Component
function dentalChart() {
    const allTeeth = [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28,48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38];
    
    // Load existing teeth data
    const existingTeeth = @json($patient->dentalTeeth ?? []);
    const teethData = {};
    
    // Initialize all teeth with default values
    allTeeth.forEach(num => {
        teethData[num] = { status: 'present', caries: 'none', restoration: 'none', mobility: '', recession: '', bop: '', notes: '' };
    });
    
    // Populate with existing data
    if (existingTeeth.length > 0) {
        existingTeeth.forEach(tooth => {
            const code = parseInt(tooth.tooth_code);
            if (teethData[code]) {
                teethData[code] = {
                    status: tooth.status || 'present',
                    caries: tooth.caries || 'none',
                    restoration: tooth.restoration || 'none',
                    mobility: tooth.mobility_grade || '',
                    recession: tooth.recession_mm || '',
                    bop: tooth.bop ? '1' : '',
                    notes: tooth.notes || ''
                };
            }
        });
    }
    
    return {
        open: true,
        teeth: teethData,
        selectedTooth: null,
        allTeeth: allTeeth,
        
        getToothClass(num) {
            const t = this.teeth[num];
            let classes = [];
            if (this.selectedTooth === num) classes.push('selected');
            if (t.status === 'missing' || t.status === 'extracted') classes.push('missing');
            if (t.caries !== 'none' && t.caries) classes.push('caries');
            if (t.restoration === 'rct') classes.push('rct');
            if (t.restoration !== 'none' && t.restoration !== 'rct' && t.restoration) classes.push('treated');
            return classes.join(' ');
        },
        
        getToothSymbol(num) {
            const unit = num % 10;
            // Molars: 6,7,8 | Premolars: 4,5 | Canine: 3 | Incisors: 1,2
            if (unit >= 6) return 'M';
            if (unit >= 4) return 'P';
            if (unit === 3) return 'C';
            return 'I';
        },
        
        getToothName(num) {
            const unit = num % 10;
            const quadrant = Math.floor(num / 10);
            const position = quadrant <= 2 ? 'Upper' : 'Lower';
            const side = (quadrant === 1 || quadrant === 4) ? 'Right' : 'Left';
            
            let name = '';
            switch(unit) {
                case 1: name = 'Central Incisor'; break;
                case 2: name = 'Lateral Incisor'; break;
                case 3: name = 'Canine'; break;
                case 4: name = 'First Premolar'; break;
                case 5: name = 'Second Premolar'; break;
                case 6: name = 'First Molar'; break;
                case 7: name = 'Second Molar'; break;
                case 8: name = 'Third Molar (Wisdom)'; break;
            }
            return `${position} ${side} ${name}`;
        },
        
        selectTooth(num) {
            console.log('Selected tooth', num);
            this.selectedTooth = num;
        },
        
        updateTooth() {
            console.log('Updated tooth', this.selectedTooth, this.teeth[this.selectedTooth]);
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}

// Dental Treatment Plan Component
function dentalTreatmentPlan() {
    return {
        open: true,
        allTeeth: [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28,48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38],
        treatments: @json($visit->getStructuredField('dental.treatment_plan') ?? [
            { tooth: '', diagnosis: '', treatment: '', priority: 'medium', cost: '', status: 'planned' }
        ]),
        
        get totalCost() {
            return this.treatments.reduce((sum, t) => sum + (parseFloat(t.cost) || 0), 0);
        },
        
        addTreatment() {
            this.treatments.push({ tooth: '', diagnosis: '', treatment: '', priority: 'medium', cost: '', status: 'planned' });
            this.updatePlan();
        },
        
        removeTreatment(idx) {
            this.treatments.splice(idx, 1);
            this.updatePlan();
        },
        
        updatePlan() {
            console.log('Treatment plan updated', this.treatments);
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}

// Lab Work Section Component
function labWorkSection() {
    return {
        open: true,
        labOrders: @json($visit->getStructuredField('dental.lab_orders') ?? []),
        
        addLabOrder() {
            this.labOrders.push({ tooth: '', workType: '', shade: '', vendor: '', deliveryDate: '', notes: '' });
            this.updateLabOrder();
        },
        
        removeLabOrder(idx) {
            this.labOrders.splice(idx, 1);
            this.updateLabOrder();
        },
        
        updateLabOrder() {
            console.log('Lab orders updated', this.labOrders);
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}
</script>
@endpush
