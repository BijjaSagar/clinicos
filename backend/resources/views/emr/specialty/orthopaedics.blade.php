{{-- Orthopaedics EMR Template --}}
{{-- Variables: $visit, $patient --}}

<style>
.ortho-section { margin-bottom: 20px; }
.ortho-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.ortho-header { padding: 12px 16px; background: linear-gradient(135deg, #fef3c7, #fde68a); border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px; cursor: pointer; }
.ortho-header h3 { font-size: 14px; font-weight: 600; color: #92400e; margin: 0; }
.ortho-body { padding: 16px; }
.ortho-grid { display: grid; gap: 12px; }
.ortho-grid-2 { grid-template-columns: repeat(2, 1fr); }
.ortho-grid-3 { grid-template-columns: repeat(3, 1fr); }
.ortho-grid-4 { grid-template-columns: repeat(4, 1fr); }
.ortho-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.ortho-input { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; }
.ortho-input:focus { outline: none; border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); }
.joint-select { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.joint-btn { padding: 6px 12px; border: 1px solid #e5e7eb; border-radius: 20px; font-size: 12px; background: white; cursor: pointer; transition: all 0.15s; }
.joint-btn:hover { background: #fef3c7; border-color: #f59e0b; }
.joint-btn.selected { background: #f59e0b; color: white; border-color: #f59e0b; }
.rom-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.rom-table th { background: #fef3c7; padding: 10px; font-size: 11px; font-weight: 600; color: #92400e; text-transform: uppercase; }
.rom-table td { padding: 8px; border-bottom: 1px solid #f1f5f9; }
.rom-table input { width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; text-align: center; }
.grade-btn { width: 32px; height: 32px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.grade-btn:hover { background: #fef3c7; }
.grade-btn.selected { background: #f59e0b; color: white; border-color: #f59e0b; }
.test-chip { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #f1f5f9; border-radius: 8px; font-size: 12px; margin: 4px; cursor: pointer; transition: all 0.15s; }
.test-chip:hover { background: #e2e8f0; }
.test-chip.positive { background: #fee2e2; color: #dc2626; }
.test-chip.negative { background: #dcfce7; color: #166534; }
.fracture-diagram { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; text-align: center; }
.xray-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.xray-upload { aspect-ratio: 1; border: 2px dashed #d1d5db; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.xray-upload:hover { border-color: #f59e0b; background: #fef3c7; }
</style>

<div x-data="orthopaedicsEMR()" class="ortho-section">
    {{-- Joint Selection --}}
    <div class="ortho-card">
        <div class="ortho-header" @click="sections.joint = !sections.joint">
            <span style="font-size: 18px;">🦴</span>
            <h3>Affected Joint / Region</h3>
            <span style="margin-left: auto; color: #92400e;" x-text="sections.joint ? '▼' : '▶'"></span>
        </div>
        <div class="ortho-body" x-show="sections.joint" x-collapse>
            <div class="ortho-label">Select Joint(s)</div>
            <div class="joint-select">
                <template x-for="joint in joints" :key="joint">
                    <button type="button" class="joint-btn" :class="{'selected': selectedJoints.includes(joint)}" @click="toggleJoint(joint)" x-text="joint"></button>
                </template>
            </div>
            
            <div class="ortho-grid ortho-grid-2" style="margin-top: 12px;">
                <div>
                    <div class="ortho-label">Side</div>
                    <select class="ortho-input" x-model="examData.side" @change="updateExam()">
                        <option value="">Select</option>
                        <option value="Right">Right</option>
                        <option value="Left">Left</option>
                        <option value="Bilateral">Bilateral</option>
                    </select>
                </div>
                <div>
                    <div class="ortho-label">Duration of Symptoms</div>
                    <select class="ortho-input" x-model="examData.duration" @change="updateExam()">
                        <option value="">Select</option>
                        <option value="< 1 week">< 1 week</option>
                        <option value="1-4 weeks">1-4 weeks</option>
                        <option value="1-3 months">1-3 months</option>
                        <option value="3-6 months">3-6 months</option>
                        <option value="> 6 months">> 6 months</option>
                        <option value="> 1 year">> 1 year</option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="ortho_joints" :value="JSON.stringify(selectedJoints)">
        </div>
    </div>

    {{-- Pain Assessment --}}
    <div class="ortho-card" style="margin-top: 16px;">
        <div class="ortho-header" @click="sections.pain = !sections.pain">
            <span style="font-size: 18px;">😣</span>
            <h3>Pain Assessment</h3>
            <span style="margin-left: auto; color: #92400e;" x-text="sections.pain ? '▼' : '▶'"></span>
        </div>
        <div class="ortho-body" x-show="sections.pain" x-collapse>
            <div style="margin-bottom: 16px;">
                <div class="ortho-label">VAS Pain Scale (0-10)</div>
                <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                    <span style="font-size: 11px; color: #9ca3af;">No Pain</span>
                    <input type="range" min="0" max="10" step="1" x-model="painData.vas" @input="updatePain()" style="flex: 1;">
                    <span style="font-size: 11px; color: #9ca3af;">Worst Pain</span>
                </div>
                <div style="text-align: center; margin-top: 8px;">
                    <span style="font-size: 32px; font-weight: 700;" :style="'color:' + getPainColor(painData.vas)" x-text="painData.vas"></span>
                    <span style="font-size: 14px; color: #64748b;">/10</span>
                </div>
            </div>

            <div class="ortho-grid ortho-grid-2">
                <div>
                    <div class="ortho-label">Pain Character</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                        <template x-for="char in ['Sharp', 'Dull', 'Aching', 'Burning', 'Shooting', 'Throbbing', 'Stabbing']" :key="char">
                            <button type="button" class="joint-btn" :class="{'selected': painData.character.includes(char)}" @click="togglePainChar(char)" x-text="char" style="font-size: 11px; padding: 4px 10px;"></button>
                        </template>
                    </div>
                </div>
                <div>
                    <div class="ortho-label">Pain Pattern</div>
                    <select class="ortho-input" x-model="painData.pattern" @change="updatePain()">
                        <option value="">Select</option>
                        <option value="Constant">Constant</option>
                        <option value="Intermittent">Intermittent</option>
                        <option value="Activity-related">Activity-related</option>
                        <option value="Night pain">Night pain</option>
                        <option value="Morning stiffness">Morning stiffness</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 12px;">
                <div class="ortho-label">Aggravating Factors</div>
                <textarea class="ortho-input" style="min-height: 60px;" x-model="painData.aggravating" @change="updatePain()" placeholder="Walking, stairs, sitting..."></textarea>
            </div>
            <input type="hidden" name="ortho_pain" :value="JSON.stringify(painData)">
        </div>
    </div>

    {{-- Range of Motion --}}
    <div class="ortho-card" style="margin-top: 16px;">
        <div class="ortho-header" @click="sections.rom = !sections.rom">
            <span style="font-size: 18px;">📐</span>
            <h3>Range of Motion</h3>
            <span style="margin-left: auto; color: #92400e;" x-text="sections.rom ? '▼' : '▶'"></span>
        </div>
        <div class="ortho-body" x-show="sections.rom" x-collapse>
            <table class="rom-table">
                <thead>
                    <tr>
                        <th>Movement</th>
                        <th>Active</th>
                        <th>Passive</th>
                        <th>Normal</th>
                        <th>Pain</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, idx) in romData" :key="idx">
                        <tr>
                            <td>
                                <input type="text" x-model="row.movement" @change="updateROM()" placeholder="e.g., Flexion" style="font-weight: 500;">
                            </td>
                            <td><input type="text" x-model="row.active" @change="updateROM()" placeholder="0-120°"></td>
                            <td><input type="text" x-model="row.passive" @change="updateROM()" placeholder="0-130°"></td>
                            <td><input type="text" x-model="row.normal" @change="updateROM()" placeholder="0-140°"></td>
                            <td>
                                <select x-model="row.pain" @change="updateROM()" style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;">
                                    <option value="">-</option>
                                    <option value="None">None</option>
                                    <option value="Mild">Mild</option>
                                    <option value="Moderate">Moderate</option>
                                    <option value="Severe">Severe</option>
                                </select>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <button type="button" @click="addROMRow()" style="margin-top: 8px; padding: 6px 12px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; font-size: 12px; cursor: pointer;">+ Add Movement</button>
            <input type="hidden" name="ortho_rom" :value="JSON.stringify(romData)">
        </div>
    </div>

    {{-- Muscle Power (MRC Grading) --}}
    <div class="ortho-card" style="margin-top: 16px;">
        <div class="ortho-header" @click="sections.power = !sections.power">
            <span style="font-size: 18px;">💪</span>
            <h3>Muscle Power (MRC Grading)</h3>
            <span style="margin-left: auto; color: #92400e;" x-text="sections.power ? '▼' : '▶'"></span>
        </div>
        <div class="ortho-body" x-show="sections.power" x-collapse>
            <div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                <template x-for="grade in mrcGrades" :key="grade.value">
                    <div style="padding: 6px 10px; background: #f8fafc; border-radius: 6px; font-size: 11px;">
                        <span style="font-weight: 700;" x-text="grade.value"></span>: <span x-text="grade.label"></span>
                    </div>
                </template>
            </div>
            
            <table class="rom-table">
                <thead>
                    <tr>
                        <th>Muscle Group</th>
                        <th>Right</th>
                        <th>Left</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, idx) in powerData" :key="idx">
                        <tr>
                            <td>
                                <input type="text" x-model="row.muscle" @change="updatePower()" placeholder="e.g., Quadriceps" style="font-weight: 500;">
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px; justify-content: center;">
                                    <template x-for="g in [0,1,2,3,4,5]" :key="g">
                                        <button type="button" class="grade-btn" :class="{'selected': row.right == g}" @click="row.right = g; updatePower()" x-text="g"></button>
                                    </template>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px; justify-content: center;">
                                    <template x-for="g in [0,1,2,3,4,5]" :key="g">
                                        <button type="button" class="grade-btn" :class="{'selected': row.left == g}" @click="row.left = g; updatePower()" x-text="g"></button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <button type="button" @click="addPowerRow()" style="margin-top: 8px; padding: 6px 12px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; font-size: 12px; cursor: pointer;">+ Add Muscle</button>
            <input type="hidden" name="ortho_power" :value="JSON.stringify(powerData)">
        </div>
    </div>

    {{-- Special Tests --}}
    <div class="ortho-card" style="margin-top: 16px;">
        <div class="ortho-header" @click="sections.tests = !sections.tests">
            <span style="font-size: 18px;">🔍</span>
            <h3>Special Tests</h3>
            <span style="margin-left: auto; color: #92400e;" x-text="sections.tests ? '▼' : '▶'"></span>
        </div>
        <div class="ortho-body" x-show="sections.tests" x-collapse>
            {{-- Knee Tests --}}
            <div style="margin-bottom: 16px;">
                <div class="ortho-label">Knee Tests</div>
                <div style="display: flex; flex-wrap: wrap;">
                    <template x-for="test in kneeTests" :key="test">
                        <div class="test-chip" :class="getTestClass(test)" @click="cycleTest(test)" x-text="test + ' ' + getTestSymbol(test)"></div>
                    </template>
                </div>
            </div>

            {{-- Shoulder Tests --}}
            <div style="margin-bottom: 16px;">
                <div class="ortho-label">Shoulder Tests</div>
                <div style="display: flex; flex-wrap: wrap;">
                    <template x-for="test in shoulderTests" :key="test">
                        <div class="test-chip" :class="getTestClass(test)" @click="cycleTest(test)" x-text="test + ' ' + getTestSymbol(test)"></div>
                    </template>
                </div>
            </div>

            {{-- Spine Tests --}}
            <div style="margin-bottom: 16px;">
                <div class="ortho-label">Spine Tests</div>
                <div style="display: flex; flex-wrap: wrap;">
                    <template x-for="test in spineTests" :key="test">
                        <div class="test-chip" :class="getTestClass(test)" @click="cycleTest(test)" x-text="test + ' ' + getTestSymbol(test)"></div>
                    </template>
                </div>
            </div>

            {{-- Hip Tests --}}
            <div>
                <div class="ortho-label">Hip Tests</div>
                <div style="display: flex; flex-wrap: wrap;">
                    <template x-for="test in hipTests" :key="test">
                        <div class="test-chip" :class="getTestClass(test)" @click="cycleTest(test)" x-text="test + ' ' + getTestSymbol(test)"></div>
                    </template>
                </div>
            </div>
            <input type="hidden" name="ortho_special_tests" :value="JSON.stringify(specialTests)">
        </div>
    </div>

    {{-- Fracture Classification (if applicable) --}}
    <div class="ortho-card" style="margin-top: 16px;">
        <div class="ortho-header" @click="sections.fracture = !sections.fracture">
            <span style="font-size: 18px;">🦴</span>
            <h3>Fracture Details (if applicable)</h3>
            <span style="margin-left: auto; color: #92400e;" x-text="sections.fracture ? '▼' : '▶'"></span>
        </div>
        <div class="ortho-body" x-show="sections.fracture" x-collapse>
            <div class="ortho-grid ortho-grid-3">
                <div>
                    <div class="ortho-label">Bone</div>
                    <select class="ortho-input" x-model="fractureData.bone" @change="updateFracture()">
                        <option value="">Select</option>
                        <option value="Clavicle">Clavicle</option>
                        <option value="Humerus">Humerus</option>
                        <option value="Radius">Radius</option>
                        <option value="Ulna">Ulna</option>
                        <option value="Femur">Femur</option>
                        <option value="Tibia">Tibia</option>
                        <option value="Fibula">Fibula</option>
                        <option value="Patella">Patella</option>
                        <option value="Metacarpal">Metacarpal</option>
                        <option value="Metatarsal">Metatarsal</option>
                        <option value="Phalanx">Phalanx</option>
                        <option value="Vertebra">Vertebra</option>
                        <option value="Pelvis">Pelvis</option>
                    </select>
                </div>
                <div>
                    <div class="ortho-label">Location</div>
                    <select class="ortho-input" x-model="fractureData.location" @change="updateFracture()">
                        <option value="">Select</option>
                        <option value="Proximal">Proximal</option>
                        <option value="Middle">Middle</option>
                        <option value="Distal">Distal</option>
                        <option value="Intra-articular">Intra-articular</option>
                    </select>
                </div>
                <div>
                    <div class="ortho-label">Type</div>
                    <select class="ortho-input" x-model="fractureData.type" @change="updateFracture()">
                        <option value="">Select</option>
                        <option value="Transverse">Transverse</option>
                        <option value="Oblique">Oblique</option>
                        <option value="Spiral">Spiral</option>
                        <option value="Comminuted">Comminuted</option>
                        <option value="Greenstick">Greenstick</option>
                        <option value="Compression">Compression</option>
                        <option value="Avulsion">Avulsion</option>
                    </select>
                </div>
            </div>

            <div class="ortho-grid ortho-grid-2" style="margin-top: 12px;">
                <div>
                    <div class="ortho-label">Open / Closed</div>
                    <select class="ortho-input" x-model="fractureData.openClosed" @change="updateFracture()">
                        <option value="">Select</option>
                        <option value="Closed">Closed</option>
                        <option value="Open Grade I">Open Grade I</option>
                        <option value="Open Grade II">Open Grade II</option>
                        <option value="Open Grade IIIA">Open Grade IIIA</option>
                        <option value="Open Grade IIIB">Open Grade IIIB</option>
                        <option value="Open Grade IIIC">Open Grade IIIC</option>
                    </select>
                </div>
                <div>
                    <div class="ortho-label">Displacement</div>
                    <select class="ortho-input" x-model="fractureData.displacement" @change="updateFracture()">
                        <option value="">Select</option>
                        <option value="Undisplaced">Undisplaced</option>
                        <option value="Minimally displaced">Minimally displaced</option>
                        <option value="Displaced">Displaced</option>
                        <option value="Angulated">Angulated</option>
                        <option value="Shortened">Shortened</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 12px;">
                <div class="ortho-label">Classification System (if applicable)</div>
                <input type="text" class="ortho-input" x-model="fractureData.classification" @change="updateFracture()" placeholder="e.g., AO/OTA, Garden, Neer...">
            </div>
            <input type="hidden" name="ortho_fracture" :value="JSON.stringify(fractureData)">
        </div>
    </div>

    {{-- Treatment Plan --}}
    <div class="ortho-card" style="margin-top: 16px;">
        <div class="ortho-header" @click="sections.treatment = !sections.treatment">
            <span style="font-size: 18px;">💊</span>
            <h3>Treatment Plan</h3>
            <span style="margin-left: auto; color: #92400e;" x-text="sections.treatment ? '▼' : '▶'"></span>
        </div>
        <div class="ortho-body" x-show="sections.treatment" x-collapse>
            <div class="ortho-label">Treatment Type</div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px;">
                <template x-for="tx in treatmentTypes" :key="tx">
                    <button type="button" class="joint-btn" :class="{'selected': treatmentPlan.types.includes(tx)}" @click="toggleTreatment(tx)" x-text="tx"></button>
                </template>
            </div>

            <template x-if="treatmentPlan.types.includes('Conservative')">
                <div style="padding: 12px; background: #f8fafc; border-radius: 8px; margin-bottom: 12px;">
                    <div class="ortho-label">Conservative Management</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px;">
                        <template x-for="item in conservativeOptions" :key="item">
                            <button type="button" class="joint-btn" :class="{'selected': treatmentPlan.conservative.includes(item)}" @click="toggleConservative(item)" x-text="item" style="font-size: 11px;"></button>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="treatmentPlan.types.includes('Surgical')">
                <div style="padding: 12px; background: #fff7ed; border-radius: 8px; margin-bottom: 12px;">
                    <div class="ortho-label">Planned Surgery</div>
                    <input type="text" class="ortho-input" x-model="treatmentPlan.surgery" @change="updateTreatment()" placeholder="e.g., ORIF, THR, ACL Reconstruction...">
                </div>
            </template>

            <div>
                <div class="ortho-label">Notes</div>
                <textarea class="ortho-input" style="min-height: 80px;" x-model="treatmentPlan.notes" @change="updateTreatment()" placeholder="Additional treatment notes..."></textarea>
            </div>
            <input type="hidden" name="ortho_treatment" :value="JSON.stringify(treatmentPlan)">
        </div>
    </div>
</div>

<script>
console.log('Orthopaedics EMR template loaded');

function orthopaedicsEMR() {
    return {
        sections: {
            joint: true,
            pain: true,
            rom: true,
            power: false,
            tests: false,
            fracture: false,
            treatment: true
        },
        
        joints: ['Shoulder', 'Elbow', 'Wrist', 'Hand', 'Hip', 'Knee', 'Ankle', 'Foot', 'Cervical Spine', 'Thoracic Spine', 'Lumbar Spine', 'Sacroiliac'],
        selectedJoints: @json($visit->getStructuredField('ortho.joints') ?? []),
        
        examData: @json($visit->getStructuredField('ortho.exam') ?? ['side' => '', 'duration' => '']),
        
        painData: @json($visit->getStructuredField('ortho.pain') ?? [
            'vas' => 0,
            'character' => [],
            'pattern' => '',
            'aggravating' => ''
        ]),
        
        romData: @json($visit->getStructuredField('ortho.rom') ?? [
            { movement: 'Flexion', active: '', passive: '', normal: '', pain: '' },
            { movement: 'Extension', active: '', passive: '', normal: '', pain: '' },
            { movement: 'Abduction', active: '', passive: '', normal: '', pain: '' },
            { movement: 'Rotation', active: '', passive: '', normal: '', pain: '' }
        ]),
        
        mrcGrades: [
            { value: 0, label: 'No contraction' },
            { value: 1, label: 'Flicker' },
            { value: 2, label: 'Movement, gravity eliminated' },
            { value: 3, label: 'Against gravity' },
            { value: 4, label: 'Against resistance' },
            { value: 5, label: 'Normal' }
        ],
        
        powerData: @json($visit->getStructuredField('ortho.power') ?? [
            { muscle: '', right: '', left: '' }
        ]),
        
        kneeTests: ['Anterior Drawer', 'Posterior Drawer', 'Lachman', 'McMurray', 'Apley', 'Varus Stress', 'Valgus Stress', 'Patella Apprehension'],
        shoulderTests: ['Neer', 'Hawkins', 'Empty Can', 'Speed', 'Yergason', 'O\'Brien', 'Apprehension', 'Sulcus Sign'],
        spineTests: ['SLR', 'Lasegue', 'Femoral Stretch', 'Spurling', 'Distraction', 'FABER', 'Schober'],
        hipTests: ['Thomas Test', 'Trendelenburg', 'FABER', 'FADIR', 'Log Roll', 'Ober Test'],
        
        specialTests: @json($visit->getStructuredField('ortho.specialTests') ?? {}),
        
        fractureData: @json($visit->getStructuredField('ortho.fracture') ?? [
            'bone' => '',
            'location' => '',
            'type' => '',
            'openClosed' => '',
            'displacement' => '',
            'classification' => ''
        ]),
        
        treatmentTypes: ['Conservative', 'Physiotherapy', 'Injection', 'Surgical', 'Immobilization'],
        conservativeOptions: ['Rest', 'Ice', 'Compression', 'Elevation', 'Brace/Support', 'Medications', 'Exercises'],
        
        treatmentPlan: @json($visit->getStructuredField('ortho.treatment') ?? [
            'types' => [],
            'conservative' => [],
            'surgery' => '',
            'notes' => ''
        ]),
        
        init() {
            console.log('Orthopaedics EMR initialized');
        },
        
        toggleJoint(joint) {
            const idx = this.selectedJoints.indexOf(joint);
            if (idx > -1) {
                this.selectedJoints.splice(idx, 1);
            } else {
                this.selectedJoints.push(joint);
            }
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateExam() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        getPainColor(vas) {
            if (vas <= 3) return '#22c55e';
            if (vas <= 6) return '#f59e0b';
            return '#dc2626';
        },
        
        togglePainChar(char) {
            const idx = this.painData.character.indexOf(char);
            if (idx > -1) {
                this.painData.character.splice(idx, 1);
            } else {
                this.painData.character.push(char);
            }
            this.updatePain();
        },
        
        updatePain() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        addROMRow() {
            this.romData.push({ movement: '', active: '', passive: '', normal: '', pain: '' });
        },
        
        updateROM() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        addPowerRow() {
            this.powerData.push({ muscle: '', right: '', left: '' });
        },
        
        updatePower() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        getTestClass(test) {
            const result = this.specialTests[test];
            if (result === 'positive') return 'positive';
            if (result === 'negative') return 'negative';
            return '';
        },
        
        getTestSymbol(test) {
            const result = this.specialTests[test];
            if (result === 'positive') return '(+)';
            if (result === 'negative') return '(-)';
            return '';
        },
        
        cycleTest(test) {
            const current = this.specialTests[test];
            if (!current) {
                this.specialTests[test] = 'positive';
            } else if (current === 'positive') {
                this.specialTests[test] = 'negative';
            } else {
                delete this.specialTests[test];
            }
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateFracture() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        toggleTreatment(tx) {
            const idx = this.treatmentPlan.types.indexOf(tx);
            if (idx > -1) {
                this.treatmentPlan.types.splice(idx, 1);
            } else {
                this.treatmentPlan.types.push(tx);
            }
            this.updateTreatment();
        },
        
        toggleConservative(item) {
            const idx = this.treatmentPlan.conservative.indexOf(item);
            if (idx > -1) {
                this.treatmentPlan.conservative.splice(idx, 1);
            } else {
                this.treatmentPlan.conservative.push(item);
            }
            this.updateTreatment();
        },
        
        updateTreatment() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        }
    };
}
</script>
