{{-- 
    Physiotherapy EMR Template
    Includes: ROM measurements, MMT grading, VAS pain scale, session notes, HEP
--}}

{{-- ASSESSMENT SECTION --}}
<div class="form-section" x-data="{ open: true }">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--blue)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
        </svg>
        <h3>Initial Assessment</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div class="form-row form-row-3">
            <div class="field-group">
                <label class="field-label">Mechanism of Injury</label>
                <select name="physio_mechanism" class="field-select" @change="window.triggerAutoSave && window.triggerAutoSave()">
                    <option value="">Select...</option>
                    <option value="trauma" {{ ($visit->getStructuredField('physio.mechanism') ?? '') == 'trauma' ? 'selected' : '' }}>Trauma/Accident</option>
                    <option value="overuse" {{ ($visit->getStructuredField('physio.mechanism') ?? '') == 'overuse' ? 'selected' : '' }}>Overuse/Repetitive</option>
                    <option value="degenerative" {{ ($visit->getStructuredField('physio.mechanism') ?? '') == 'degenerative' ? 'selected' : '' }}>Degenerative</option>
                    <option value="postural" {{ ($visit->getStructuredField('physio.mechanism') ?? '') == 'postural' ? 'selected' : '' }}>Postural</option>
                    <option value="post_surgical" {{ ($visit->getStructuredField('physio.mechanism') ?? '') == 'post_surgical' ? 'selected' : '' }}>Post-Surgical</option>
                    <option value="unknown" {{ ($visit->getStructuredField('physio.mechanism') ?? '') == 'unknown' ? 'selected' : '' }}>Unknown/Insidious</option>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label">Affected Area</label>
                <select name="physio_body_part" class="field-select" @change="window.triggerAutoSave && window.triggerAutoSave()">
                    <option value="">Select...</option>
                    <optgroup label="Upper Body">
                        <option value="cervical">Cervical Spine</option>
                        <option value="shoulder">Shoulder</option>
                        <option value="elbow">Elbow</option>
                        <option value="wrist">Wrist/Hand</option>
                    </optgroup>
                    <optgroup label="Lower Body">
                        <option value="lumbar">Lumbar Spine</option>
                        <option value="hip">Hip</option>
                        <option value="knee">Knee</option>
                        <option value="ankle">Ankle/Foot</option>
                    </optgroup>
                    <optgroup label="Other">
                        <option value="thoracic">Thoracic Spine</option>
                        <option value="multiple">Multiple Areas</option>
                    </optgroup>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label">Duration of Symptoms</label>
                <input type="text" name="physio_duration" class="field-input" value="{{ $visit->getStructuredField('physio.duration') ?? '' }}" placeholder="e.g. 2 weeks, 3 months" @input="window.triggerAutoSave && window.triggerAutoSave()">
            </div>
        </div>
        
        <div class="form-row form-row-2">
            <div class="field-group">
                <label class="field-label">Referring Doctor</label>
                <input type="text" name="physio_referring_doctor" class="field-input" value="{{ $visit->getStructuredField('physio.referring_doctor') ?? '' }}" placeholder="Dr. Name" @input="window.triggerAutoSave && window.triggerAutoSave()">
            </div>
            <div class="field-group">
                <label class="field-label">Previous Treatment</label>
                <input type="text" name="physio_previous_treatment" class="field-input" value="{{ $visit->getStructuredField('physio.previous_treatment') ?? '' }}" placeholder="e.g. Medications, Rest, Other PT" @input="window.triggerAutoSave && window.triggerAutoSave()">
            </div>
        </div>
    </div>
</div>

{{-- VAS PAIN SCALE SECTION --}}
<div class="form-section" x-data="{ open: true, vasScore: {{ $visit->getStructuredField('physio.vas_score') ?? 5 }} }">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--red)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z"/>
        </svg>
        <h3>Pain Assessment (VAS)</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div style="margin-bottom:16px">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px">
                <div style="font-size:13px;font-weight:600;color:var(--dark)">VAS Score:</div>
                <div style="flex:1">
                    <input type="range" min="0" max="10" step="1" x-model="vasScore" name="physio_vas_score" 
                           style="width:100%;accent-color:var(--red)" @input="window.triggerAutoSave && window.triggerAutoSave()">
                </div>
                <div style="width:60px;padding:8px 12px;border-radius:8px;text-align:center;font-size:18px;font-weight:700"
                     :class="vasScore <= 3 ? 'sr-mild' : (vasScore <= 6 ? 'sr-mod' : 'sr-sev')"
                     x-text="vasScore">
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text3);padding:0 4px">
                <span>0 - No Pain</span>
                <span>5 - Moderate</span>
                <span>10 - Worst Pain</span>
            </div>
        </div>
        
        <div class="form-row form-row-3">
            <div class="field-group">
                <label class="field-label">Pain Character</label>
                <select name="physio_pain_character" class="field-select" @change="window.triggerAutoSave && window.triggerAutoSave()">
                    <option value="">Select...</option>
                    <option value="sharp">Sharp</option>
                    <option value="dull">Dull/Aching</option>
                    <option value="burning">Burning</option>
                    <option value="throbbing">Throbbing</option>
                    <option value="shooting">Shooting</option>
                    <option value="stabbing">Stabbing</option>
                    <option value="cramping">Cramping</option>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label">Pain Pattern</label>
                <select name="physio_pain_pattern" class="field-select" @change="window.triggerAutoSave && window.triggerAutoSave()">
                    <option value="">Select...</option>
                    <option value="constant">Constant</option>
                    <option value="intermittent">Intermittent</option>
                    <option value="activity_related">Activity-Related</option>
                    <option value="night">Night Pain</option>
                    <option value="morning">Morning Stiffness</option>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label">Aggravating Factors</label>
                <input type="text" name="physio_aggravating" class="field-input" placeholder="e.g. Walking, Sitting" @input="window.triggerAutoSave && window.triggerAutoSave()">
            </div>
        </div>
    </div>
</div>

{{-- RANGE OF MOTION SECTION --}}
<div class="form-section" x-data="romSection()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--teal)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
        </svg>
        <h3>Range of Motion (ROM)</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:12px">
                <thead>
                    <tr style="background:var(--bg)">
                        <th style="padding:10px;text-align:left;font-weight:600;color:var(--text3);text-transform:uppercase;font-size:10px">Movement</th>
                        <th style="padding:10px;text-align:center;font-weight:600;color:var(--text3);text-transform:uppercase;font-size:10px">Active ROM</th>
                        <th style="padding:10px;text-align:center;font-weight:600;color:var(--text3);text-transform:uppercase;font-size:10px">Passive ROM</th>
                        <th style="padding:10px;text-align:center;font-weight:600;color:var(--text3);text-transform:uppercase;font-size:10px">Normal</th>
                        <th style="padding:10px;text-align:center;font-weight:600;color:var(--text3);text-transform:uppercase;font-size:10px">End Feel</th>
                        <th style="padding:10px;text-align:center;font-weight:600;color:var(--text3);text-transform:uppercase;font-size:10px">Pain</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, idx) in romData" :key="idx">
                        <tr style="border-bottom:1px solid var(--border)">
                            <td style="padding:8px">
                                <input type="text" class="field-input" style="padding:6px 8px;font-size:12px" x-model="row.movement" placeholder="e.g. Flexion" @input="updateRom()">
                            </td>
                            <td style="padding:8px;text-align:center">
                                <input type="text" class="field-input" style="width:70px;padding:6px 8px;font-size:12px;text-align:center" x-model="row.active" placeholder="°" @input="updateRom()">
                            </td>
                            <td style="padding:8px;text-align:center">
                                <input type="text" class="field-input" style="width:70px;padding:6px 8px;font-size:12px;text-align:center" x-model="row.passive" placeholder="°" @input="updateRom()">
                            </td>
                            <td style="padding:8px;text-align:center">
                                <input type="text" class="field-input" style="width:70px;padding:6px 8px;font-size:12px;text-align:center" x-model="row.normal" placeholder="°" @input="updateRom()">
                            </td>
                            <td style="padding:8px;text-align:center">
                                <select class="field-select" style="padding:6px 8px;font-size:11px" x-model="row.endFeel" @change="updateRom()">
                                    <option value="">-</option>
                                    <option value="soft">Soft</option>
                                    <option value="firm">Firm</option>
                                    <option value="hard">Hard</option>
                                    <option value="empty">Empty</option>
                                    <option value="spasm">Spasm</option>
                                </select>
                            </td>
                            <td style="padding:8px;text-align:center">
                                <select class="field-select" style="padding:6px 8px;font-size:11px" x-model="row.pain" @change="updateRom()">
                                    <option value="">-</option>
                                    <option value="none">None</option>
                                    <option value="mild">Mild</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="severe">Severe</option>
                                </select>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <button type="button" @click="addRomRow()" style="margin-top:10px;display:flex;align-items:center;gap:6px;padding:8px 12px;border:1.5px dashed var(--border);border-radius:8px;font-size:12px;color:var(--text3);cursor:pointer;background:none">
            <span>+</span> Add Movement
        </button>
        
        <input type="hidden" name="physio_rom_data" :value="JSON.stringify(romData)">
    </div>
</div>

{{-- MANUAL MUSCLE TESTING (MMT) SECTION --}}
<div class="form-section" x-data="mmtSection()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--green)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
        </svg>
        <h3>Manual Muscle Testing (MMT)</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div style="margin-bottom:12px;padding:10px;background:var(--bg);border-radius:8px;font-size:11px;color:var(--text3)">
            <strong>Grading:</strong> 0=No contraction | 1=Flicker | 2=Moves without gravity | 3=Against gravity | 4=Against resistance | 5=Normal
        </div>
        
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:10px">
            <template x-for="(muscle, idx) in mmtData" :key="idx">
                <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--bg);border-radius:8px">
                    <input type="text" class="field-input" style="flex:1;padding:6px 8px;font-size:12px" x-model="muscle.name" placeholder="Muscle group" @input="updateMmt()">
                    <select class="field-select" style="width:60px;padding:6px 8px;font-size:12px;font-weight:700" x-model="muscle.grade" @change="updateMmt()">
                        <option value="">-</option>
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <button type="button" @click="removeMmt(idx)" style="color:var(--text3);cursor:pointer;font-size:14px;background:none;border:none">×</button>
                </div>
            </template>
        </div>
        
        <button type="button" @click="addMmt()" style="margin-top:10px;display:flex;align-items:center;gap:6px;padding:8px 12px;border:1.5px dashed var(--border);border-radius:8px;font-size:12px;color:var(--text3);cursor:pointer;background:none">
            <span>+</span> Add Muscle
        </button>
        
        <input type="hidden" name="physio_mmt_data" :value="JSON.stringify(mmtData)">
    </div>
</div>

{{-- TREATMENT MODALITIES SECTION --}}
<div class="form-section" x-data="treatmentSection()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--amber)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
        </svg>
        <h3>Treatment Modalities</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div class="proc-grid" style="grid-template-columns:repeat(4, 1fr)">
            <template x-for="modality in modalities" :key="modality.code">
                <div class="proc-chip" 
                     :class="selectedModalities.includes(modality.code) ? 'selected' : ''"
                     @click="toggleModality(modality.code)">
                    <span x-text="modality.name"></span>
                </div>
            </template>
        </div>

        {{-- Modality Parameters --}}
        <template x-for="code in selectedModalities" :key="code">
            <div style="margin-top:12px;padding:14px;background:var(--bg);border-radius:10px">
                <div style="font-size:12px;font-weight:700;color:var(--dark);margin-bottom:10px" x-text="getModalityName(code)"></div>
                
                <div class="form-row form-row-3" style="gap:8px">
                    <div class="field-group" x-show="['TENS', 'IFT', 'US'].includes(code)">
                        <label class="field-label">Frequency/Intensity</label>
                        <input type="text" class="field-input" x-model="modalityParams[code].frequency" placeholder="e.g. 100Hz" @input="updateTreatment()">
                    </div>
                    <div class="field-group" x-show="['TENS', 'IFT', 'US', 'SWD', 'LASER'].includes(code)">
                        <label class="field-label">Duration</label>
                        <input type="text" class="field-input" x-model="modalityParams[code].duration" placeholder="e.g. 15 mins" @input="updateTreatment()">
                    </div>
                    <div class="field-group" x-show="['US'].includes(code)">
                        <label class="field-label">Mode</label>
                        <select class="field-select" x-model="modalityParams[code].mode" @change="updateTreatment()">
                            <option value="">Select...</option>
                            <option value="continuous">Continuous</option>
                            <option value="pulsed">Pulsed</option>
                        </select>
                    </div>
                    <div class="field-group" x-show="['HEAT', 'ICE'].includes(code)">
                        <label class="field-label">Application Method</label>
                        <input type="text" class="field-input" x-model="modalityParams[code].method" placeholder="e.g. Hot pack, Ice pack" @input="updateTreatment()">
                    </div>
                    <div class="field-group" x-show="['MANUAL'].includes(code)">
                        <label class="field-label">Technique</label>
                        <input type="text" class="field-input" x-model="modalityParams[code].technique" placeholder="e.g. Joint mobilization Grade III" @input="updateTreatment()">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Body Part</label>
                        <input type="text" class="field-input" x-model="modalityParams[code].bodyPart" placeholder="e.g. Lower back" @input="updateTreatment()">
                    </div>
                </div>
            </div>
        </template>
        
        <input type="hidden" name="physio_treatment_data" :value="JSON.stringify(getTreatmentData())">
    </div>
</div>

{{-- HOME EXERCISE PROGRAMME (HEP) SECTION --}}
<div class="form-section" x-data="hepSection()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--blue)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
        </svg>
        <h3>Home Exercise Programme (HEP)</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div style="margin-bottom:12px">
            <template x-for="(exercise, idx) in exercises" :key="idx">
                <div style="padding:14px;background:var(--bg);border-radius:10px;margin-bottom:10px">
                    <div style="display:flex;align-items:flex-start;gap:12px">
                        <div style="flex:1">
                            <div class="form-row form-row-2" style="gap:8px;margin-bottom:8px">
                                <div class="field-group">
                                    <label class="field-label">Exercise Name</label>
                                    <input type="text" class="field-input" x-model="exercise.name" placeholder="e.g. Knee Flexion Stretch" @input="updateHep()">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Body Part</label>
                                    <input type="text" class="field-input" x-model="exercise.bodyPart" placeholder="e.g. Knee, Shoulder" @input="updateHep()">
                                </div>
                            </div>
                            <div class="form-row" style="grid-template-columns:repeat(4, 1fr);gap:8px;margin-bottom:8px">
                                <div class="field-group">
                                    <label class="field-label">Sets</label>
                                    <input type="number" class="field-input" x-model="exercise.sets" placeholder="3" @input="updateHep()">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Reps</label>
                                    <input type="number" class="field-input" x-model="exercise.reps" placeholder="10" @input="updateHep()">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Hold (sec)</label>
                                    <input type="number" class="field-input" x-model="exercise.hold" placeholder="10" @input="updateHep()">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Freq/Day</label>
                                    <input type="number" class="field-input" x-model="exercise.frequency" placeholder="2" @input="updateHep()">
                                </div>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Instructions</label>
                                <textarea class="field-textarea" x-model="exercise.instructions" rows="2" placeholder="Detailed instructions for the patient..." @input="updateHep()"></textarea>
                            </div>
                        </div>
                        <button type="button" @click="removeExercise(idx)" style="color:var(--text3);cursor:pointer;font-size:18px;background:none;border:none;padding:4px">×</button>
                    </div>
                </div>
            </template>
        </div>
        
        <div style="display:flex;gap:8px">
            <button type="button" @click="addExercise()" style="display:flex;align-items:center;gap:6px;padding:10px 14px;border:1.5px dashed var(--border);border-radius:8px;font-size:12px;color:var(--text3);cursor:pointer;background:none">
                <span>+</span> Add Exercise
            </button>
            
            <button type="button" @click="sendHepWhatsApp()" style="display:flex;align-items:center;gap:6px;padding:10px 14px;border:none;border-radius:8px;font-size:12px;color:white;cursor:pointer;background:#25D366">
                <svg style="width:16px;height:16px" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Send HEP via WhatsApp
            </button>
        </div>
        
        <input type="hidden" name="physio_hep_data" :value="JSON.stringify(exercises)">
    </div>
</div>

{{-- SESSION GOALS SECTION --}}
<div class="form-section" x-data="{ open: true }">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--green)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3>Goals & Progress</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div class="form-row form-row-2">
            <div class="field-group">
                <label class="field-label">Short-Term Goals (2 weeks)</label>
                <textarea name="physio_stg" class="field-textarea" rows="2" placeholder="e.g. Reduce pain to VAS 3, Achieve 90° knee flexion" @input="window.triggerAutoSave && window.triggerAutoSave()">{{ $visit->getStructuredField('physio.stg') ?? '' }}</textarea>
            </div>
            <div class="field-group">
                <label class="field-label">Long-Term Goals (6-8 weeks)</label>
                <textarea name="physio_ltg" class="field-textarea" rows="2" placeholder="e.g. Return to sports, Full ROM, Pain-free ADL" @input="window.triggerAutoSave && window.triggerAutoSave()">{{ $visit->getStructuredField('physio.ltg') ?? '' }}</textarea>
            </div>
        </div>
        
        <div class="form-row form-row-3" style="margin-top:12px">
            <div class="field-group">
                <label class="field-label">Session # / Total Planned</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="number" name="physio_session_current" class="field-input" style="width:60px" value="{{ $visit->getStructuredField('physio.session_current') ?? 1 }}" @input="window.triggerAutoSave && window.triggerAutoSave()">
                    <span style="color:var(--text3)">/</span>
                    <input type="number" name="physio_sessions_total" class="field-input" style="width:60px" value="{{ $visit->getStructuredField('physio.sessions_total') ?? 10 }}" @input="window.triggerAutoSave && window.triggerAutoSave()">
                </div>
            </div>
            <div class="field-group">
                <label class="field-label">Compliance</label>
                <select name="physio_compliance" class="field-select" @change="window.triggerAutoSave && window.triggerAutoSave()">
                    <option value="">Select...</option>
                    <option value="excellent">Excellent</option>
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
                </select>
            </div>
            <div class="field-group">
                <label class="field-label">Progress</label>
                <select name="physio_progress" class="field-select" @change="window.triggerAutoSave && window.triggerAutoSave()">
                    <option value="">Select...</option>
                    <option value="improving">Improving</option>
                    <option value="stable">Stable</option>
                    <option value="declining">Declining</option>
                    <option value="goals_met">Goals Met</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- OUTCOME MEASURES SECTION --}}

{{-- FIM (Functional Independence Measure) --}}
<div class="form-section" x-data="fimSection()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--purple, #7c3aed)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
        </svg>
        <h3>Outcome Measures — FIM (Functional Independence Measure)</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div style="margin-bottom:12px;padding:10px;background:var(--bg);border-radius:8px;font-size:11px;color:var(--text3)">
            <strong>Scoring:</strong> 1=Total Assist &nbsp;|&nbsp; 2=Maximal Assist &nbsp;|&nbsp; 3=Moderate Assist &nbsp;|&nbsp; 4=Minimal Assist &nbsp;|&nbsp; 5=Supervision &nbsp;|&nbsp; 6=Modified Independence &nbsp;|&nbsp; 7=Complete Independence &nbsp;|&nbsp; <strong>Total range: 18–126</strong>
        </div>

        {{-- Self-Care --}}
        <div style="margin-bottom:14px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)">Self-Care</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:8px">
                <template x-for="item in fimData.selfCare" :key="item.key">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                        <span style="font-size:12px;flex:1" x-text="item.label"></span>
                        <select class="field-select" style="width:55px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateFim()">
                            <option value="">-</option>
                            <template x-for="n in [1,2,3,4,5,6,7]" :key="n"><option :value="n" x-text="n"></option></template>
                        </select>
                    </div>
                </template>
            </div>
        </div>

        {{-- Sphincter Control --}}
        <div style="margin-bottom:14px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)">Sphincter Control</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:8px">
                <template x-for="item in fimData.sphincter" :key="item.key">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                        <span style="font-size:12px;flex:1" x-text="item.label"></span>
                        <select class="field-select" style="width:55px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateFim()">
                            <option value="">-</option>
                            <template x-for="n in [1,2,3,4,5,6,7]" :key="n"><option :value="n" x-text="n"></option></template>
                        </select>
                    </div>
                </template>
            </div>
        </div>

        {{-- Transfers --}}
        <div style="margin-bottom:14px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)">Transfers</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:8px">
                <template x-for="item in fimData.transfers" :key="item.key">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                        <span style="font-size:12px;flex:1" x-text="item.label"></span>
                        <select class="field-select" style="width:55px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateFim()">
                            <option value="">-</option>
                            <template x-for="n in [1,2,3,4,5,6,7]" :key="n"><option :value="n" x-text="n"></option></template>
                        </select>
                    </div>
                </template>
            </div>
        </div>

        {{-- Locomotion --}}
        <div style="margin-bottom:14px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)">Locomotion</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:8px">
                <template x-for="item in fimData.locomotion" :key="item.key">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                        <span style="font-size:12px;flex:1" x-text="item.label"></span>
                        <select class="field-select" style="width:55px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateFim()">
                            <option value="">-</option>
                            <template x-for="n in [1,2,3,4,5,6,7]" :key="n"><option :value="n" x-text="n"></option></template>
                        </select>
                    </div>
                </template>
            </div>
        </div>

        {{-- Communication --}}
        <div style="margin-bottom:14px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)">Communication</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:8px">
                <template x-for="item in fimData.communication" :key="item.key">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                        <span style="font-size:12px;flex:1" x-text="item.label"></span>
                        <select class="field-select" style="width:55px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateFim()">
                            <option value="">-</option>
                            <template x-for="n in [1,2,3,4,5,6,7]" :key="n"><option :value="n" x-text="n"></option></template>
                        </select>
                    </div>
                </template>
            </div>
        </div>

        {{-- Social Cognition --}}
        <div style="margin-bottom:16px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)">Social Cognition</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:8px">
                <template x-for="item in fimData.socialCognition" :key="item.key">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                        <span style="font-size:12px;flex:1" x-text="item.label"></span>
                        <select class="field-select" style="width:55px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateFim()">
                            <option value="">-</option>
                            <template x-for="n in [1,2,3,4,5,6,7]" :key="n"><option :value="n" x-text="n"></option></template>
                        </select>
                    </div>
                </template>
            </div>
        </div>

        {{-- FIM Total --}}
        <div style="display:flex;align-items:center;gap:16px;padding:12px 16px;background:var(--bg);border-radius:10px;border:1.5px solid var(--border)">
            <span style="font-size:13px;font-weight:600;color:var(--text2)">FIM Total Score</span>
            <span style="font-size:22px;font-weight:800;color:var(--purple, #7c3aed)" x-text="fimTotal()"></span>
            <span style="font-size:11px;color:var(--text3)">/ 126 &nbsp;(min 18)</span>
        </div>

        <input type="hidden" name="physio_fim_data" :value="JSON.stringify(fimData)">
    </div>
</div>

{{-- Barthel Index --}}
<div class="form-section" x-data="barthelSection()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--teal, #0d9488)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
        </svg>
        <h3>Outcome Measures — Barthel Index</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div style="margin-bottom:12px;padding:10px;background:var(--bg);border-radius:8px;font-size:11px;color:var(--text3)">
            <strong>Interpretation:</strong> 0–20 = Total dependence &nbsp;|&nbsp; 21–60 = Severe dependence &nbsp;|&nbsp; 61–90 = Moderate dependence &nbsp;|&nbsp; 91–99 = Slight dependence &nbsp;|&nbsp; 100 = Full independence
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:8px;margin-bottom:16px">
            <template x-for="item in barthelData" :key="item.key">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                    <span style="font-size:12px;flex:1" x-text="item.label"></span>
                    <select class="field-select" style="width:60px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateBarthel()">
                        <option value="">-</option>
                        <template x-for="opt in item.options" :key="opt"><option :value="opt" x-text="opt"></option></template>
                    </select>
                </div>
            </template>
        </div>

        {{-- Barthel Total --}}
        <div style="display:flex;align-items:center;gap:16px;padding:12px 16px;background:var(--bg);border-radius:10px;border:1.5px solid var(--border)">
            <span style="font-size:13px;font-weight:600;color:var(--text2)">Barthel Total</span>
            <span style="font-size:22px;font-weight:800;color:var(--teal, #0d9488)" x-text="barthelTotal()"></span>
            <span style="font-size:11px;color:var(--text3)">/ 100</span>
            <span style="font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:var(--bg2, #f1f5f9);color:var(--text2)" x-text="barthelInterpretation()"></span>
        </div>

        <input type="hidden" name="physio_barthel_data" :value="JSON.stringify(barthelData)">
    </div>
</div>

{{-- WOMAC --}}
<div class="form-section" x-data="womacSection()">
    <div class="form-section-header" @click="open = !open">
        <svg style="width:18px;height:18px;color:var(--orange, #ea580c)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3"/>
        </svg>
        <h3>Outcome Measures — WOMAC (Knee / Hip OA)</h3>
        <span class="toggle" x-text="open ? '−' : '+'"></span>
    </div>
    <div class="form-body" x-show="open" x-collapse>
        <div style="margin-bottom:12px;padding:10px;background:var(--bg);border-radius:8px;font-size:11px;color:var(--text3)">
            <strong>Scoring per item:</strong> 0=None &nbsp;|&nbsp; 1=Mild &nbsp;|&nbsp; 2=Moderate &nbsp;|&nbsp; 3=Severe &nbsp;|&nbsp; 4=Extreme &nbsp;|&nbsp; <strong>Total range: 0–96</strong> (Pain 0–20 | Stiffness 0–8 | Function 0–68)
        </div>

        {{-- Pain Subscale A1-A5 --}}
        <div style="margin-bottom:14px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)">
                Pain Subscale &nbsp;<span style="font-weight:400;font-size:11px;color:var(--text3)">(A1–A5, max 20)</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:8px">
                <template x-for="item in womacData.pain" :key="item.key">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                        <span style="font-size:12px;flex:1"><span style="font-weight:600;color:var(--text3);margin-right:4px" x-text="item.key + ':'"></span><span x-text="item.label"></span></span>
                        <select class="field-select" style="width:55px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateWomac()">
                            <option value="">-</option>
                            <option value="0">0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                </template>
            </div>
            <div style="margin-top:6px;font-size:12px;color:var(--text3)">Pain subscale total: <strong x-text="womacSubscale('pain') + ' / 20'"></strong></div>
        </div>

        {{-- Stiffness Subscale B1-B2 --}}
        <div style="margin-bottom:14px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)">
                Stiffness Subscale &nbsp;<span style="font-weight:400;font-size:11px;color:var(--text3)">(B1–B2, max 8)</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:8px">
                <template x-for="item in womacData.stiffness" :key="item.key">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                        <span style="font-size:12px;flex:1"><span style="font-weight:600;color:var(--text3);margin-right:4px" x-text="item.key + ':'"></span><span x-text="item.label"></span></span>
                        <select class="field-select" style="width:55px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateWomac()">
                            <option value="">-</option>
                            <option value="0">0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                </template>
            </div>
            <div style="margin-top:6px;font-size:12px;color:var(--text3)">Stiffness subscale total: <strong x-text="womacSubscale('stiffness') + ' / 8'"></strong></div>
        </div>

        {{-- Physical Function Subscale C1-C17 --}}
        <div style="margin-bottom:16px">
            <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border)">
                Physical Function Subscale &nbsp;<span style="font-weight:400;font-size:11px;color:var(--text3)">(C1–C17, max 68)</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:8px">
                <template x-for="item in womacData.function" :key="item.key">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;gap:8px">
                        <span style="font-size:12px;flex:1"><span style="font-weight:600;color:var(--text3);margin-right:4px" x-text="item.key + ':'"></span><span x-text="item.label"></span></span>
                        <select class="field-select" style="width:55px;padding:5px 6px;font-size:13px;font-weight:700;text-align:center" x-model.number="item.score" @change="updateWomac()">
                            <option value="">-</option>
                            <option value="0">0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                </template>
            </div>
            <div style="margin-top:6px;font-size:12px;color:var(--text3)">Physical function subscale total: <strong x-text="womacSubscale('function') + ' / 68'"></strong></div>
        </div>

        {{-- WOMAC Total --}}
        <div style="display:flex;align-items:center;gap:16px;padding:12px 16px;background:var(--bg);border-radius:10px;border:1.5px solid var(--border)">
            <span style="font-size:13px;font-weight:600;color:var(--text2)">WOMAC Total</span>
            <span style="font-size:22px;font-weight:800;color:var(--orange, #ea580c)" x-text="womacTotal()"></span>
            <span style="font-size:11px;color:var(--text3)">/ 96</span>
        </div>

        <input type="hidden" name="physio_womac_data" :value="JSON.stringify(womacData)">
    </div>
</div>

@push('scripts')
<script>
console.log('Physiotherapy EMR specialty template loaded');

// ROM Section Component
function romSection() {
    return {
        open: true,
        romData: @json($visit->getStructuredField('physio.rom') ?? [
            { movement: 'Flexion', active: '', passive: '', normal: '', endFeel: '', pain: '' },
            { movement: 'Extension', active: '', passive: '', normal: '', endFeel: '', pain: '' },
            { movement: 'Abduction', active: '', passive: '', normal: '', endFeel: '', pain: '' },
            { movement: 'Adduction', active: '', passive: '', normal: '', endFeel: '', pain: '' },
        ]),
        
        addRomRow() {
            this.romData.push({ movement: '', active: '', passive: '', normal: '', endFeel: '', pain: '' });
            this.updateRom();
        },
        
        updateRom() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}

// MMT Section Component
function mmtSection() {
    return {
        open: true,
        mmtData: @json($visit->getStructuredField('physio.mmt') ?? [
            { name: 'Quadriceps', grade: '' },
            { name: 'Hamstrings', grade: '' },
            { name: 'Hip Flexors', grade: '' },
        ]),
        
        addMmt() {
            this.mmtData.push({ name: '', grade: '' });
            this.updateMmt();
        },
        
        removeMmt(idx) {
            this.mmtData.splice(idx, 1);
            this.updateMmt();
        },
        
        updateMmt() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}

// Treatment Section Component
function treatmentSection() {
    return {
        open: true,
        modalities: [
            { code: 'TENS', name: 'TENS' },
            { code: 'IFT', name: 'IFT' },
            { code: 'US', name: 'Ultrasound' },
            { code: 'SWD', name: 'SWD' },
            { code: 'LASER', name: 'LASER' },
            { code: 'HEAT', name: 'Heat Therapy' },
            { code: 'ICE', name: 'Ice/Cryo' },
            { code: 'MANUAL', name: 'Manual Therapy' },
            { code: 'TRACTION', name: 'Traction' },
            { code: 'EXERCISE', name: 'Therapeutic Exercise' },
            { code: 'TAPE', name: 'Kinesio Taping' },
            { code: 'CUPPING', name: 'Cupping' }
        ],
        selectedModalities: @json($visit->getStructuredField('physio.modalities') ?? []),
        modalityParams: @json($visit->getStructuredField('physio.modality_params') ?? {}),
        
        toggleModality(code) {
            const idx = this.selectedModalities.indexOf(code);
            if (idx > -1) {
                this.selectedModalities.splice(idx, 1);
                delete this.modalityParams[code];
            } else {
                this.selectedModalities.push(code);
                this.modalityParams[code] = {};
            }
            this.updateTreatment();
        },
        
        getModalityName(code) {
            const m = this.modalities.find(m => m.code === code);
            return m ? m.name : code;
        },
        
        getTreatmentData() {
            return {
                modalities: this.selectedModalities,
                params: this.modalityParams
            };
        },
        
        updateTreatment() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}

// FIM Section Component
function fimSection() {
    const saved = @json($visit->getStructuredField('physio.fim') ?? null);

    const defaults = {
        selfCare: [
            { key: 'sc1', label: 'Eating',        score: '' },
            { key: 'sc2', label: 'Grooming',      score: '' },
            { key: 'sc3', label: 'Bathing',       score: '' },
            { key: 'sc4', label: 'Dressing – Upper', score: '' },
            { key: 'sc5', label: 'Dressing – Lower', score: '' },
            { key: 'sc6', label: 'Toileting',     score: '' },
        ],
        sphincter: [
            { key: 'sp1', label: 'Bladder Management', score: '' },
            { key: 'sp2', label: 'Bowel Management',   score: '' },
        ],
        transfers: [
            { key: 'tr1', label: 'Bed / Chair / Wheelchair', score: '' },
            { key: 'tr2', label: 'Toilet',                   score: '' },
            { key: 'tr3', label: 'Tub / Shower',             score: '' },
        ],
        locomotion: [
            { key: 'lo1', label: 'Walk / Wheelchair', score: '' },
            { key: 'lo2', label: 'Stairs',            score: '' },
        ],
        communication: [
            { key: 'co1', label: 'Comprehension', score: '' },
            { key: 'co2', label: 'Expression',    score: '' },
        ],
        socialCognition: [
            { key: 'sg1', label: 'Social Interaction', score: '' },
            { key: 'sg2', label: 'Problem Solving',    score: '' },
            { key: 'sg3', label: 'Memory',             score: '' },
        ],
    };

    // Merge saved scores into defaults if available
    function mergeGroup(defGroup, savedGroup) {
        if (!savedGroup) return defGroup;
        return defGroup.map(item => {
            const found = savedGroup.find(s => s.key === item.key);
            return found ? { ...item, score: found.score } : item;
        });
    }

    const fimData = saved ? {
        selfCare:       mergeGroup(defaults.selfCare,       saved.selfCare),
        sphincter:      mergeGroup(defaults.sphincter,      saved.sphincter),
        transfers:      mergeGroup(defaults.transfers,      saved.transfers),
        locomotion:     mergeGroup(defaults.locomotion,     saved.locomotion),
        communication:  mergeGroup(defaults.communication,  saved.communication),
        socialCognition:mergeGroup(defaults.socialCognition,saved.socialCognition),
    } : defaults;

    return {
        open: false,
        fimData,

        allItems() {
            return [
                ...this.fimData.selfCare,
                ...this.fimData.sphincter,
                ...this.fimData.transfers,
                ...this.fimData.locomotion,
                ...this.fimData.communication,
                ...this.fimData.socialCognition,
            ];
        },

        fimTotal() {
            return this.allItems().reduce((sum, item) => sum + (Number(item.score) || 0), 0);
        },

        updateFim() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
    };
}

// Barthel Index Section Component
function barthelSection() {
    const savedArr = @json($visit->getStructuredField('physio.barthel') ?? null);

    const defaults = [
        { key: 'b_feeding',   label: 'Feeding',    score: '', options: [0, 5, 10] },
        { key: 'b_bathing',   label: 'Bathing',    score: '', options: [0, 5] },
        { key: 'b_grooming',  label: 'Grooming',   score: '', options: [0, 5] },
        { key: 'b_dressing',  label: 'Dressing',   score: '', options: [0, 5, 10] },
        { key: 'b_bowels',    label: 'Bowels',     score: '', options: [0, 5, 10] },
        { key: 'b_bladder',   label: 'Bladder',    score: '', options: [0, 5, 10] },
        { key: 'b_toilet',    label: 'Toilet Use', score: '', options: [0, 5, 10] },
        { key: 'b_transfers', label: 'Transfers',  score: '', options: [0, 5, 10, 15] },
        { key: 'b_mobility',  label: 'Mobility',   score: '', options: [0, 5, 10, 15] },
        { key: 'b_stairs',    label: 'Stairs',     score: '', options: [0, 5, 10] },
    ];

    const barthelData = savedArr
        ? defaults.map(item => {
            const found = savedArr.find(s => s.key === item.key);
            return found ? { ...item, score: found.score } : item;
          })
        : defaults;

    return {
        open: false,
        barthelData,

        barthelTotal() {
            return this.barthelData.reduce((sum, item) => sum + (Number(item.score) || 0), 0);
        },

        barthelInterpretation() {
            const t = this.barthelTotal();
            if (t <= 20)  return 'Total dependence';
            if (t <= 60)  return 'Severe dependence';
            if (t <= 90)  return 'Moderate dependence';
            if (t <= 99)  return 'Slight dependence';
            return 'Full independence';
        },

        updateBarthel() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
    };
}

// WOMAC Section Component
function womacSection() {
    const saved = @json($visit->getStructuredField('physio.womac') ?? null);

    const defaults = {
        pain: [
            { key: 'A1', label: 'Walking on a flat surface', score: '' },
            { key: 'A2', label: 'Going up or down stairs',   score: '' },
            { key: 'A3', label: 'At night while in bed',     score: '' },
            { key: 'A4', label: 'Sitting or lying',          score: '' },
            { key: 'A5', label: 'Standing upright',          score: '' },
        ],
        stiffness: [
            { key: 'B1', label: 'Morning stiffness after waking',          score: '' },
            { key: 'B2', label: 'Stiffness after sitting / lying / resting', score: '' },
        ],
        function: [
            { key: 'C1',  label: 'Descending stairs',               score: '' },
            { key: 'C2',  label: 'Ascending stairs',                score: '' },
            { key: 'C3',  label: 'Rising from sitting',             score: '' },
            { key: 'C4',  label: 'Standing',                        score: '' },
            { key: 'C5',  label: 'Bending to floor / picking up object', score: '' },
            { key: 'C6',  label: 'Walking on flat surface',         score: '' },
            { key: 'C7',  label: 'Getting in / out of car',         score: '' },
            { key: 'C8',  label: 'Going shopping',                  score: '' },
            { key: 'C9',  label: 'Putting on socks / stockings',    score: '' },
            { key: 'C10', label: 'Rising from bed',                 score: '' },
            { key: 'C11', label: 'Taking off socks / stockings',    score: '' },
            { key: 'C12', label: 'Lying in bed',                    score: '' },
            { key: 'C13', label: 'Getting in / out of bath',        score: '' },
            { key: 'C14', label: 'Sitting',                         score: '' },
            { key: 'C15', label: 'Getting on / off toilet',         score: '' },
            { key: 'C16', label: 'Heavy domestic duties',           score: '' },
            { key: 'C17', label: 'Light domestic duties',           score: '' },
        ],
    };

    function mergeGroup(defGroup, savedGroup) {
        if (!savedGroup) return defGroup;
        return defGroup.map(item => {
            const found = savedGroup.find(s => s.key === item.key);
            return found ? { ...item, score: found.score } : item;
        });
    }

    const womacData = saved ? {
        pain:      mergeGroup(defaults.pain,      saved.pain),
        stiffness: mergeGroup(defaults.stiffness, saved.stiffness),
        function:  mergeGroup(defaults.function,  saved.function),
    } : defaults;

    return {
        open: false,
        womacData,

        womacSubscale(subscale) {
            return (this.womacData[subscale] || [])
                .reduce((sum, item) => sum + (Number(item.score) || 0), 0);
        },

        womacTotal() {
            return this.womacSubscale('pain')
                 + this.womacSubscale('stiffness')
                 + this.womacSubscale('function');
        },

        updateWomac() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
    };
}

// HEP Section Component
function hepSection() {
    return {
        open: true,
        exercises: @json($visit->physioHep ?? []),
        
        init() {
            if (this.exercises.length === 0) {
                this.exercises = [{ name: '', bodyPart: '', sets: '', reps: '', hold: '', frequency: '', instructions: '' }];
            }
        },
        
        addExercise() {
            this.exercises.push({ name: '', bodyPart: '', sets: '', reps: '', hold: '', frequency: '', instructions: '' });
            this.updateHep();
        },
        
        removeExercise(idx) {
            this.exercises.splice(idx, 1);
            this.updateHep();
        },
        
        updateHep() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        sendHepWhatsApp() {
            // TODO: Implement WhatsApp HEP sending
            alert('WhatsApp HEP integration coming soon!');
        }
    };
}
</script>
@endpush
