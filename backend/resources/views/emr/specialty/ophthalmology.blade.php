{{-- Ophthalmology EMR Template --}}
{{-- Variables: $visit, $patient --}}

<style>
.ophthal-section { margin-bottom: 20px; }
.ophthal-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.ophthal-header { padding: 12px 16px; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px; cursor: pointer; }
.ophthal-header h3 { font-size: 14px; font-weight: 600; color: #0c4a6e; margin: 0; }
.ophthal-body { padding: 16px; }
.ophthal-grid { display: grid; gap: 12px; }
.ophthal-grid-2 { grid-template-columns: repeat(2, 1fr); }
.ophthal-grid-3 { grid-template-columns: repeat(3, 1fr); }
.ophthal-grid-4 { grid-template-columns: repeat(4, 1fr); }
.eye-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.eye-badge { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; font-size: 11px; font-weight: 700; }
.eye-badge-od { background: #dbeafe; color: #1e40af; }
.eye-badge-os { background: #dcfce7; color: #166534; }
.va-input { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; text-align: center; }
.va-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.snellen-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-top: 8px; }
.snellen-btn { padding: 6px; font-size: 11px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; cursor: pointer; transition: all 0.15s; }
.snellen-btn:hover { background: #f3f4f6; }
.snellen-btn.selected { background: #3b82f6; color: white; border-color: #3b82f6; }
.iop-display { font-size: 24px; font-weight: 700; text-align: center; padding: 12px; border-radius: 8px; }
.iop-normal { background: #dcfce7; color: #166534; }
.iop-borderline { background: #fef3c7; color: #92400e; }
.iop-high { background: #fee2e2; color: #dc2626; }
.refraction-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.refraction-table th { background: #f8fafc; padding: 10px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
.refraction-table td { padding: 8px; border-bottom: 1px solid #f1f5f9; }
.refraction-table input { width: 100%; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 13px; text-align: center; }
.slit-lamp-grid { display: grid; grid-template-columns: auto 1fr 1fr; gap: 8px; align-items: center; }
.slit-lamp-label { font-size: 12px; font-weight: 500; color: #374151; padding: 8px 0; }
.fundus-canvas { width: 100%; aspect-ratio: 1; background: #1a1a2e; border-radius: 50%; position: relative; overflow: hidden; }
.fundus-disc { position: absolute; width: 30%; height: 30%; background: #ffcc80; border-radius: 50%; top: 35%; }
.fundus-disc-od { left: 25%; }
.fundus-disc-os { right: 25%; }
.diagnosis-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f1f5f9; border-radius: 20px; font-size: 12px; margin: 4px; cursor: pointer; transition: all 0.15s; }
.diagnosis-chip:hover { background: #e2e8f0; }
.diagnosis-chip.selected { background: #0ea5e9; color: white; }
</style>

<div x-data="ophthalmologyEMR()" class="ophthal-section">
    @if(!empty($visit))
    <div style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
        <span style="font-size:12px;color:#64748b;font-weight:600;">Rx PDFs (from saved EMR):</span>
        <a href="{{ route('prescriptions.spectacle-pdf', $visit) }}" target="_blank" rel="noopener" style="padding:6px 12px;border-radius:8px;background:#0ea5e9;color:white;font-size:12px;font-weight:600;text-decoration:none;">Spectacle PDF</a>
        <a href="{{ route('prescriptions.contact-lens-pdf', $visit) }}" target="_blank" rel="noopener" style="padding:6px 12px;border-radius:8px;background:#0369a1;color:white;font-size:12px;font-weight:600;text-decoration:none;">Contact lens PDF</a>
    </div>
    @endif
    {{-- Visual Acuity Section --}}
    <div class="ophthal-card">
        <div class="ophthal-header" @click="sections.va = !sections.va">
            <span style="font-size: 18px;">👁️</span>
            <h3>Visual Acuity</h3>
            <span style="margin-left: auto; color: #64748b;" x-text="sections.va ? '▼' : '▶'"></span>
        </div>
        <div class="ophthal-body" x-show="sections.va" x-collapse>
            <div class="ophthal-grid ophthal-grid-2" style="gap: 24px;">
                {{-- Right Eye (OD) --}}
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <span class="eye-badge eye-badge-od">OD</span>
                        <span style="font-weight: 600; color: #1e40af;">Right Eye</span>
                    </div>
                    
                    <div class="ophthal-grid ophthal-grid-3" style="margin-bottom: 12px;">
                        <div>
                            <div class="eye-label">Unaided</div>
                            <input type="text" class="va-input" x-model="vaData.od.unaided" @change="updateVA()" placeholder="6/6">
                        </div>
                        <div>
                            <div class="eye-label">Pinhole</div>
                            <input type="text" class="va-input" x-model="vaData.od.pinhole" @change="updateVA()" placeholder="6/6">
                        </div>
                        <div>
                            <div class="eye-label">BCVA</div>
                            <input type="text" class="va-input" x-model="vaData.od.bcva" @change="updateVA()" placeholder="6/6">
                        </div>
                    </div>
                    
                    <div class="eye-label">Quick Select (Snellen)</div>
                    <div class="snellen-grid">
                        <template x-for="va in snellenValues" :key="va">
                            <button type="button" class="snellen-btn" :class="{'selected': vaData.od.unaided === va}" @click="vaData.od.unaided = va; updateVA()" x-text="va"></button>
                        </template>
                    </div>
                </div>

                {{-- Left Eye (OS) --}}
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <span class="eye-badge eye-badge-os">OS</span>
                        <span style="font-weight: 600; color: #166534;">Left Eye</span>
                    </div>
                    
                    <div class="ophthal-grid ophthal-grid-3" style="margin-bottom: 12px;">
                        <div>
                            <div class="eye-label">Unaided</div>
                            <input type="text" class="va-input" x-model="vaData.os.unaided" @change="updateVA()" placeholder="6/6">
                        </div>
                        <div>
                            <div class="eye-label">Pinhole</div>
                            <input type="text" class="va-input" x-model="vaData.os.pinhole" @change="updateVA()" placeholder="6/6">
                        </div>
                        <div>
                            <div class="eye-label">BCVA</div>
                            <input type="text" class="va-input" x-model="vaData.os.bcva" @change="updateVA()" placeholder="6/6">
                        </div>
                    </div>
                    
                    <div class="eye-label">Quick Select (Snellen)</div>
                    <div class="snellen-grid">
                        <template x-for="va in snellenValues" :key="va">
                            <button type="button" class="snellen-btn" :class="{'selected': vaData.os.unaided === va}" @click="vaData.os.unaided = va; updateVA()" x-text="va"></button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Near Vision --}}
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                <div class="eye-label" style="margin-bottom: 8px;">Near Vision (at 40cm)</div>
                <div class="ophthal-grid ophthal-grid-4">
                    <div>
                        <div class="eye-label">OD Near</div>
                        <select class="va-input" x-model="vaData.od.near" @change="updateVA()">
                            <option value="">Select</option>
                            <option value="N5">N5</option>
                            <option value="N6">N6</option>
                            <option value="N8">N8</option>
                            <option value="N10">N10</option>
                            <option value="N12">N12</option>
                            <option value="N18">N18</option>
                            <option value="N24">N24</option>
                            <option value="N36">N36</option>
                        </select>
                    </div>
                    <div>
                        <div class="eye-label">OS Near</div>
                        <select class="va-input" x-model="vaData.os.near" @change="updateVA()">
                            <option value="">Select</option>
                            <option value="N5">N5</option>
                            <option value="N6">N6</option>
                            <option value="N8">N8</option>
                            <option value="N10">N10</option>
                            <option value="N12">N12</option>
                            <option value="N18">N18</option>
                            <option value="N24">N24</option>
                            <option value="N36">N36</option>
                        </select>
                    </div>
                    <div>
                        <div class="eye-label">Color Vision</div>
                        <select class="va-input" x-model="vaData.colorVision" @change="updateVA()">
                            <option value="">Select</option>
                            <option value="Normal">Normal</option>
                            <option value="Protanopia">Protanopia</option>
                            <option value="Deuteranopia">Deuteranopia</option>
                            <option value="Tritanopia">Tritanopia</option>
                        </select>
                    </div>
                    <div>
                        <div class="eye-label">Test Used</div>
                        <select class="va-input" x-model="vaData.testUsed" @change="updateVA()">
                            <option value="Snellen">Snellen Chart</option>
                            <option value="LogMAR">LogMAR</option>
                            <option value="E-Chart">E-Chart</option>
                            <option value="Landolt">Landolt C</option>
                        </select>
                    </div>
                </div>
            </div>
            <input type="hidden" name="va_data" :value="JSON.stringify(vaData)">
        </div>
    </div>

    {{-- Intraocular Pressure (IOP) --}}
    <div class="ophthal-card" style="margin-top: 16px;">
        <div class="ophthal-header" @click="sections.iop = !sections.iop">
            <span style="font-size: 18px;">🔵</span>
            <h3>Intraocular Pressure (IOP)</h3>
            <span style="margin-left: auto; color: #64748b;" x-text="sections.iop ? '▼' : '▶'"></span>
        </div>
        <div class="ophthal-body" x-show="sections.iop" x-collapse>
            <div class="ophthal-grid ophthal-grid-2" style="gap: 24px;">
                {{-- OD IOP --}}
                <div style="text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px;">
                        <span class="eye-badge eye-badge-od">OD</span>
                        <span style="font-weight: 600;">Right Eye</span>
                    </div>
                    <div class="iop-display" :class="getIOPClass(iopData.od)">
                        <span x-text="iopData.od || '--'"></span> mmHg
                    </div>
                    <input type="range" min="5" max="60" step="1" x-model="iopData.od" @input="updateIOP()" style="width: 100%; margin-top: 12px;">
                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #9ca3af;">
                        <span>5</span>
                        <span style="color: #22c55e;">Normal: 10-21</span>
                        <span>60</span>
                    </div>
                </div>

                {{-- OS IOP --}}
                <div style="text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px;">
                        <span class="eye-badge eye-badge-os">OS</span>
                        <span style="font-weight: 600;">Left Eye</span>
                    </div>
                    <div class="iop-display" :class="getIOPClass(iopData.os)">
                        <span x-text="iopData.os || '--'"></span> mmHg
                    </div>
                    <input type="range" min="5" max="60" step="1" x-model="iopData.os" @input="updateIOP()" style="width: 100%; margin-top: 12px;">
                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #9ca3af;">
                        <span>5</span>
                        <span style="color: #22c55e;">Normal: 10-21</span>
                        <span>60</span>
                    </div>
                </div>
            </div>

            <div class="ophthal-grid ophthal-grid-3" style="margin-top: 16px;">
                <div>
                    <div class="eye-label">Method</div>
                    <select class="va-input" x-model="iopData.method" @change="updateIOP()">
                        <option value="NCT">NCT (Non-Contact)</option>
                        <option value="GAT">GAT (Goldmann)</option>
                        <option value="iCare">iCare Tonometer</option>
                        <option value="Schiotz">Schiotz</option>
                        <option value="Tonopen">Tonopen</option>
                    </select>
                </div>
                <div>
                    <div class="eye-label">Time Measured</div>
                    <input type="time" class="va-input" x-model="iopData.time" @change="updateIOP()">
                </div>
                <div>
                    <div class="eye-label">CCT (OD / OS)</div>
                    <div style="display: flex; gap: 8px;">
                        <input type="number" class="va-input" placeholder="OD μm" x-model="iopData.cctOD" @change="updateIOP()">
                        <input type="number" class="va-input" placeholder="OS μm" x-model="iopData.cctOS" @change="updateIOP()">
                    </div>
                </div>
            </div>
            <input type="hidden" name="iop_data" :value="JSON.stringify(iopData)">
        </div>
    </div>

    {{-- Refraction --}}
    <div class="ophthal-card" style="margin-top: 16px;">
        <div class="ophthal-header" @click="sections.refraction = !sections.refraction">
            <span style="font-size: 18px;">👓</span>
            <h3>Refraction</h3>
            <span style="margin-left: auto; color: #64748b;" x-text="sections.refraction ? '▼' : '▶'"></span>
        </div>
        <div class="ophthal-body" x-show="sections.refraction" x-collapse>
            <div style="margin-bottom: 12px;">
                <div class="eye-label">Refraction Type</div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <template x-for="type in ['Subjective', 'Cycloplegic', 'Manifest', 'Auto-Refraction']" :key="type">
                        <button type="button" class="diagnosis-chip" :class="{'selected': refractionData.type === type}" @click="refractionData.type = type; updateRefraction()" x-text="type"></button>
                    </template>
                </div>
            </div>

            <table class="refraction-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Eye</th>
                        <th>Sphere</th>
                        <th>Cylinder</th>
                        <th>Axis</th>
                        <th>Add</th>
                        <th>Prism</th>
                        <th>Base</th>
                        <th>VA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="eye-badge eye-badge-od">OD</span></td>
                        <td><input type="text" x-model="refractionData.od.sphere" @change="updateRefraction()" placeholder="+/-0.00"></td>
                        <td><input type="text" x-model="refractionData.od.cylinder" @change="updateRefraction()" placeholder="-0.00"></td>
                        <td><input type="number" min="0" max="180" x-model="refractionData.od.axis" @change="updateRefraction()" placeholder="0-180"></td>
                        <td><input type="text" x-model="refractionData.od.add" @change="updateRefraction()" placeholder="+0.00"></td>
                        <td><input type="text" x-model="refractionData.od.prism" @change="updateRefraction()" placeholder="0.00"></td>
                        <td>
                            <select x-model="refractionData.od.base" @change="updateRefraction()">
                                <option value="">-</option>
                                <option value="BI">BI</option>
                                <option value="BO">BO</option>
                                <option value="BU">BU</option>
                                <option value="BD">BD</option>
                            </select>
                        </td>
                        <td><input type="text" x-model="refractionData.od.va" @change="updateRefraction()" placeholder="6/6"></td>
                    </tr>
                    <tr>
                        <td><span class="eye-badge eye-badge-os">OS</span></td>
                        <td><input type="text" x-model="refractionData.os.sphere" @change="updateRefraction()" placeholder="+/-0.00"></td>
                        <td><input type="text" x-model="refractionData.os.cylinder" @change="updateRefraction()" placeholder="-0.00"></td>
                        <td><input type="number" min="0" max="180" x-model="refractionData.os.axis" @change="updateRefraction()" placeholder="0-180"></td>
                        <td><input type="text" x-model="refractionData.os.add" @change="updateRefraction()" placeholder="+0.00"></td>
                        <td><input type="text" x-model="refractionData.os.prism" @change="updateRefraction()" placeholder="0.00"></td>
                        <td>
                            <select x-model="refractionData.os.base" @change="updateRefraction()">
                                <option value="">-</option>
                                <option value="BI">BI</option>
                                <option value="BO">BO</option>
                                <option value="BU">BU</option>
                                <option value="BD">BD</option>
                            </select>
                        </td>
                        <td><input type="text" x-model="refractionData.os.va" @change="updateRefraction()" placeholder="6/6"></td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 12px;">
                <div class="eye-label">PD (Pupillary Distance)</div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="flex: 1;">
                        <input type="number" class="va-input" x-model="refractionData.pdDistance" @change="updateRefraction()" placeholder="Distance PD">
                        <span style="font-size: 11px; color: #9ca3af;">Distance (mm)</span>
                    </div>
                    <div style="flex: 1;">
                        <input type="number" class="va-input" x-model="refractionData.pdNear" @change="updateRefraction()" placeholder="Near PD">
                        <span style="font-size: 11px; color: #9ca3af;">Near (mm)</span>
                    </div>
                    <label style="display: flex; align-items: center; gap: 6px;">
                        <input type="checkbox" x-model="refractionData.isFinalPrescription" @change="updateRefraction()">
                        <span style="font-size: 12px;">Final Prescription</span>
                    </label>
                </div>
            </div>
            <input type="hidden" name="refraction_data" :value="JSON.stringify(refractionData)">
        </div>
    </div>

    {{-- Spectacle & Contact lens (saved to ophthal.spectacle_rx / ophthal.contact_lens_rx) --}}
    <div class="ophthal-card" style="margin-top: 16px;">
        <div class="ophthal-header" @click="sections.rxPdf = !sections.rxPdf">
            <span style="font-size: 18px;">📝</span>
            <h3>Spectacle &amp; contact lens Rx (for PDF)</h3>
            <span style="margin-left: auto; color: #64748b;" x-text="sections.rxPdf ? '▼' : '▶'"></span>
        </div>
        <div class="ophthal-body" x-show="sections.rxPdf" x-collapse>
            <div style="margin-bottom: 12px;">
                <button type="button" class="snellen-btn" @click="copyRefractionToSpectacle()">Copy sphere/cyl/axis/add from refraction above</button>
            </div>
            <div class="eye-label" style="margin-bottom:8px;">Spectacle (final)</div>
            <table class="refraction-table" style="margin-bottom:16px;">
                <thead>
                    <tr>
                        <th>Eye</th><th>Sph</th><th>Cyl</th><th>Axis</th><th>Add</th><th>VA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>OD</td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.od.sphere" @change="updateSpectacleRx()"></td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.od.cylinder" @change="updateSpectacleRx()"></td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.od.axis" @change="updateSpectacleRx()"></td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.od.add" @change="updateSpectacleRx()"></td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.od.va" @change="updateSpectacleRx()"></td>
                    </tr>
                    <tr>
                        <td>OS</td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.os.sphere" @change="updateSpectacleRx()"></td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.os.cylinder" @change="updateSpectacleRx()"></td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.os.axis" @change="updateSpectacleRx()"></td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.os.add" @change="updateSpectacleRx()"></td>
                        <td><input type="text" class="va-input" x-model="spectacleRx.os.va" @change="updateSpectacleRx()"></td>
                    </tr>
                </tbody>
            </table>
            <div class="ophthal-grid ophthal-grid-2" style="margin-bottom:12px;">
                <div>
                    <div class="eye-label">PD distance (mm)</div>
                    <input type="text" class="va-input" x-model="spectacleRx.pdDistance" @change="updateSpectacleRx()">
                </div>
                <div>
                    <div class="eye-label">PD near (mm)</div>
                    <input type="text" class="va-input" x-model="spectacleRx.pdNear" @change="updateSpectacleRx()">
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <div class="eye-label">Instructions</div>
                <textarea class="va-input" style="min-height:48px;width:100%" x-model="spectacleRx.instructions" @change="updateSpectacleRx()" placeholder="Use / avoid / tint..."></textarea>
            </div>
            <div class="eye-label" style="margin:16px 0 8px;">Contact lenses</div>
            <table class="refraction-table">
                <thead>
                    <tr>
                        <th>Eye</th><th>BC</th><th>Power</th><th>Dia</th><th>Cyl</th><th>Axis</th><th>Brand</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>OD</td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.od.bc" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.od.power" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.od.dia" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.od.cyl" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.od.axis" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.od.brand" @change="updateContactLensRx()"></td>
                    </tr>
                    <tr>
                        <td>OS</td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.os.bc" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.os.power" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.os.dia" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.os.cyl" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.os.axis" @change="updateContactLensRx()"></td>
                        <td><input type="text" class="va-input" x-model="contactLensRx.os.brand" @change="updateContactLensRx()"></td>
                    </tr>
                </tbody>
            </table>
            <div class="ophthal-grid ophthal-grid-2" style="margin-top:12px;">
                <div>
                    <div class="eye-label">Modality</div>
                    <select class="va-input" x-model="contactLensRx.modality" @change="updateContactLensRx()">
                        <option value="soft">Soft</option>
                        <option value="rigid">Rigid / RGP</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>
                <div>
                    <div class="eye-label">Wear schedule</div>
                    <input type="text" class="va-input" x-model="contactLensRx.wearSchedule" @change="updateContactLensRx()" placeholder="Daily disposable / monthly...">
                </div>
            </div>
            <input type="hidden" name="ophthal_spectacle_rx" :value="JSON.stringify(spectacleRx)">
            <input type="hidden" name="ophthal_contact_lens_rx" :value="JSON.stringify(contactLensRx)">
        </div>
    </div>

    {{-- Slit Lamp Examination --}}
    <div class="ophthal-card" style="margin-top: 16px;">
        <div class="ophthal-header" @click="sections.slitLamp = !sections.slitLamp">
            <span style="font-size: 18px;">🔬</span>
            <h3>Slit Lamp Examination</h3>
            <span style="margin-left: auto; color: #64748b;" x-text="sections.slitLamp ? '▼' : '▶'"></span>
        </div>
        <div class="ophthal-body" x-show="sections.slitLamp" x-collapse>
            <div class="slit-lamp-grid">
                <div class="slit-lamp-label"></div>
                <div style="text-align: center;"><span class="eye-badge eye-badge-od">OD</span></div>
                <div style="text-align: center;"><span class="eye-badge eye-badge-os">OS</span></div>

                <div class="slit-lamp-label">Lids / Adnexa</div>
                <select class="va-input" x-model="slitLampData.od.lids" @change="updateSlitLamp()">
                    <option value="Normal">Normal</option>
                    <option value="Ptosis">Ptosis</option>
                    <option value="Ectropion">Ectropion</option>
                    <option value="Entropion">Entropion</option>
                    <option value="Trichiasis">Trichiasis</option>
                    <option value="Chalazion">Chalazion</option>
                    <option value="Stye">Stye</option>
                </select>
                <select class="va-input" x-model="slitLampData.os.lids" @change="updateSlitLamp()">
                    <option value="Normal">Normal</option>
                    <option value="Ptosis">Ptosis</option>
                    <option value="Ectropion">Ectropion</option>
                    <option value="Entropion">Entropion</option>
                    <option value="Trichiasis">Trichiasis</option>
                    <option value="Chalazion">Chalazion</option>
                    <option value="Stye">Stye</option>
                </select>

                <div class="slit-lamp-label">Conjunctiva</div>
                <select class="va-input" x-model="slitLampData.od.conjunctiva" @change="updateSlitLamp()">
                    <option value="Normal">Normal</option>
                    <option value="Injection">Injection</option>
                    <option value="Chemosis">Chemosis</option>
                    <option value="Papillae">Papillae</option>
                    <option value="Follicles">Follicles</option>
                    <option value="Pterygium">Pterygium</option>
                    <option value="Pinguecula">Pinguecula</option>
                </select>
                <select class="va-input" x-model="slitLampData.os.conjunctiva" @change="updateSlitLamp()">
                    <option value="Normal">Normal</option>
                    <option value="Injection">Injection</option>
                    <option value="Chemosis">Chemosis</option>
                    <option value="Papillae">Papillae</option>
                    <option value="Follicles">Follicles</option>
                    <option value="Pterygium">Pterygium</option>
                    <option value="Pinguecula">Pinguecula</option>
                </select>

                <div class="slit-lamp-label">Cornea</div>
                <input type="text" class="va-input" x-model="slitLampData.od.cornea" @change="updateSlitLamp()" placeholder="Clear / SPK / Edema...">
                <input type="text" class="va-input" x-model="slitLampData.os.cornea" @change="updateSlitLamp()" placeholder="Clear / SPK / Edema...">

                <div class="slit-lamp-label">Anterior Chamber</div>
                <select class="va-input" x-model="slitLampData.od.ac" @change="updateSlitLamp()">
                    <option value="Deep & Quiet">Deep & Quiet</option>
                    <option value="Shallow">Shallow</option>
                    <option value="Cells 1+">Cells 1+</option>
                    <option value="Cells 2+">Cells 2+</option>
                    <option value="Cells 3+">Cells 3+</option>
                    <option value="Flare 1+">Flare 1+</option>
                    <option value="Flare 2+">Flare 2+</option>
                    <option value="Hypopyon">Hypopyon</option>
                    <option value="Hyphema">Hyphema</option>
                </select>
                <select class="va-input" x-model="slitLampData.os.ac" @change="updateSlitLamp()">
                    <option value="Deep & Quiet">Deep & Quiet</option>
                    <option value="Shallow">Shallow</option>
                    <option value="Cells 1+">Cells 1+</option>
                    <option value="Cells 2+">Cells 2+</option>
                    <option value="Cells 3+">Cells 3+</option>
                    <option value="Flare 1+">Flare 1+</option>
                    <option value="Flare 2+">Flare 2+</option>
                    <option value="Hypopyon">Hypopyon</option>
                    <option value="Hyphema">Hyphema</option>
                </select>

                <div class="slit-lamp-label">Iris</div>
                <select class="va-input" x-model="slitLampData.od.iris" @change="updateSlitLamp()">
                    <option value="Normal">Normal</option>
                    <option value="Synechiae">Synechiae</option>
                    <option value="Rubeosis">Rubeosis</option>
                    <option value="Atrophy">Atrophy</option>
                    <option value="Coloboma">Coloboma</option>
                    <option value="Nevus">Nevus</option>
                </select>
                <select class="va-input" x-model="slitLampData.os.iris" @change="updateSlitLamp()">
                    <option value="Normal">Normal</option>
                    <option value="Synechiae">Synechiae</option>
                    <option value="Rubeosis">Rubeosis</option>
                    <option value="Atrophy">Atrophy</option>
                    <option value="Coloboma">Coloboma</option>
                    <option value="Nevus">Nevus</option>
                </select>

                <div class="slit-lamp-label">Pupil</div>
                <input type="text" class="va-input" x-model="slitLampData.od.pupil" @change="updateSlitLamp()" placeholder="RRR / RAPD...">
                <input type="text" class="va-input" x-model="slitLampData.os.pupil" @change="updateSlitLamp()" placeholder="RRR / RAPD...">

                <div class="slit-lamp-label">Lens (LOCS III)</div>
                <select class="va-input" x-model="slitLampData.od.lens" @change="updateSlitLamp()">
                    <option value="Clear">Clear</option>
                    <option value="NS1">NS1</option>
                    <option value="NS2">NS2</option>
                    <option value="NS3">NS3</option>
                    <option value="NS4">NS4</option>
                    <option value="PSC">PSC</option>
                    <option value="Cortical">Cortical</option>
                    <option value="Mature">Mature</option>
                    <option value="Hypermature">Hypermature</option>
                    <option value="IOL">IOL</option>
                    <option value="Aphakic">Aphakic</option>
                </select>
                <select class="va-input" x-model="slitLampData.os.lens" @change="updateSlitLamp()">
                    <option value="Clear">Clear</option>
                    <option value="NS1">NS1</option>
                    <option value="NS2">NS2</option>
                    <option value="NS3">NS3</option>
                    <option value="NS4">NS4</option>
                    <option value="PSC">PSC</option>
                    <option value="Cortical">Cortical</option>
                    <option value="Mature">Mature</option>
                    <option value="Hypermature">Hypermature</option>
                    <option value="IOL">IOL</option>
                    <option value="Aphakic">Aphakic</option>
                </select>
            </div>
            <input type="hidden" name="slit_lamp_data" :value="JSON.stringify(slitLampData)">
        </div>
    </div>

    {{-- Fundus Examination --}}
    <div class="ophthal-card" style="margin-top: 16px;">
        <div class="ophthal-header" @click="sections.fundus = !sections.fundus">
            <span style="font-size: 18px;">🔴</span>
            <h3>Fundus Examination</h3>
            <span style="margin-left: auto; color: #64748b;" x-text="sections.fundus ? '▼' : '▶'"></span>
        </div>
        <div class="ophthal-body" x-show="sections.fundus" x-collapse>
            <div class="ophthal-grid ophthal-grid-2" style="gap: 24px;">
                {{-- OD Fundus --}}
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <span class="eye-badge eye-badge-od">OD</span>
                        <span style="font-weight: 600;">Right Eye</span>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Media</div>
                        <select class="va-input" x-model="fundusData.od.media" @change="updateFundus()">
                            <option value="Clear">Clear</option>
                            <option value="Hazy">Hazy</option>
                            <option value="Not Visible">Not Visible</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Disc</div>
                        <input type="text" class="va-input" x-model="fundusData.od.disc" @change="updateFundus()" placeholder="Pink, healthy margins...">
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Cup:Disc Ratio</div>
                        <input type="text" class="va-input" x-model="fundusData.od.cdr" @change="updateFundus()" placeholder="0.3">
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Macula</div>
                        <input type="text" class="va-input" x-model="fundusData.od.macula" @change="updateFundus()" placeholder="FR+, no edema...">
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Vessels</div>
                        <input type="text" class="va-input" x-model="fundusData.od.vessels" @change="updateFundus()" placeholder="Normal AV ratio...">
                    </div>
                    
                    <div>
                        <div class="eye-label">Periphery</div>
                        <textarea class="va-input" style="min-height: 60px;" x-model="fundusData.od.periphery" @change="updateFundus()" placeholder="No holes/tears, attached retina..."></textarea>
                    </div>
                </div>

                {{-- OS Fundus --}}
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <span class="eye-badge eye-badge-os">OS</span>
                        <span style="font-weight: 600;">Left Eye</span>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Media</div>
                        <select class="va-input" x-model="fundusData.os.media" @change="updateFundus()">
                            <option value="Clear">Clear</option>
                            <option value="Hazy">Hazy</option>
                            <option value="Not Visible">Not Visible</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Disc</div>
                        <input type="text" class="va-input" x-model="fundusData.os.disc" @change="updateFundus()" placeholder="Pink, healthy margins...">
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Cup:Disc Ratio</div>
                        <input type="text" class="va-input" x-model="fundusData.os.cdr" @change="updateFundus()" placeholder="0.3">
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Macula</div>
                        <input type="text" class="va-input" x-model="fundusData.os.macula" @change="updateFundus()" placeholder="FR+, no edema...">
                    </div>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="eye-label">Vessels</div>
                        <input type="text" class="va-input" x-model="fundusData.os.vessels" @change="updateFundus()" placeholder="Normal AV ratio...">
                    </div>
                    
                    <div>
                        <div class="eye-label">Periphery</div>
                        <textarea class="va-input" style="min-height: 60px;" x-model="fundusData.os.periphery" @change="updateFundus()" placeholder="No holes/tears, attached retina..."></textarea>
                    </div>
                </div>
            </div>

            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                <div class="eye-label">Dilated With</div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <template x-for="drop in ['Tropicamide 1%', 'Phenylephrine 2.5%', 'Cyclopentolate 1%', 'Homatropine 2%']" :key="drop">
                        <button type="button" class="diagnosis-chip" :class="{'selected': fundusData.dilatedWith.includes(drop)}" @click="toggleDilationDrop(drop)" x-text="drop"></button>
                    </template>
                </div>
            </div>
            <input type="hidden" name="fundus_data" :value="JSON.stringify(fundusData)">
        </div>
    </div>

    {{-- Common Diagnoses --}}
    <div class="ophthal-card" style="margin-top: 16px;">
        <div class="ophthal-header" @click="sections.diagnosis = !sections.diagnosis">
            <span style="font-size: 18px;">📋</span>
            <h3>Quick Diagnoses</h3>
            <span style="margin-left: auto; color: #64748b;" x-text="sections.diagnosis ? '▼' : '▶'"></span>
        </div>
        <div class="ophthal-body" x-show="sections.diagnosis" x-collapse>
            <div class="eye-label">Common Ophthalmology Diagnoses</div>
            <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px;">
                <template x-for="dx in commonDiagnoses" :key="dx.code">
                    <button type="button" class="diagnosis-chip" :class="{'selected': selectedDiagnoses.includes(dx.code)}" @click="toggleDiagnosis(dx)">
                        <span x-text="dx.name"></span>
                        <span style="font-size: 10px; opacity: 0.7;" x-text="dx.code"></span>
                    </button>
                </template>
            </div>
            <input type="hidden" name="ophthal_diagnoses" :value="JSON.stringify(selectedDiagnoses)">
        </div>
    </div>
</div>

<script>
console.log('Ophthalmology EMR template loaded');

function ophthalmologyEMR() {
    return {
        sections: {
            va: true,
            iop: true,
            refraction: true,
            rxPdf: true,
            slitLamp: false,
            fundus: false,
            diagnosis: true
        },
        
        snellenValues: ['6/6', '6/9', '6/12', '6/18', '6/24', '6/36', '6/60', '3/60', '1/60', 'HM', 'PL', 'NPL'],
        
        vaData: @json(($visit ?? null)?->getStructuredField('ophthal.va') ?? [
            'od' => ['unaided' => '', 'pinhole' => '', 'bcva' => '', 'near' => ''],
            'os' => ['unaided' => '', 'pinhole' => '', 'bcva' => '', 'near' => ''],
            'colorVision' => 'Normal',
            'testUsed' => 'Snellen'
        ]),
        
        iopData: @json(($visit ?? null)?->getStructuredField('ophthal.iop') ?? [
            'od' => '',
            'os' => '',
            'method' => 'NCT',
            'time' => '',
            'cctOD' => '',
            'cctOS' => ''
        ]),
        
        refractionData: @json(($visit ?? null)?->getStructuredField('ophthal.refraction') ?? [
            'type' => 'Subjective',
            'od' => ['sphere' => '', 'cylinder' => '', 'axis' => '', 'add' => '', 'prism' => '', 'base' => '', 'va' => ''],
            'os' => ['sphere' => '', 'cylinder' => '', 'axis' => '', 'add' => '', 'prism' => '', 'base' => '', 'va' => ''],
            'pdDistance' => '',
            'pdNear' => '',
            'isFinalPrescription' => false
        ]),
        
        slitLampData: @json(($visit ?? null)?->getStructuredField('ophthal.slitLamp') ?? [
            'od' => ['lids' => 'Normal', 'conjunctiva' => 'Normal', 'cornea' => 'Clear', 'ac' => 'Deep & Quiet', 'iris' => 'Normal', 'pupil' => 'RRR', 'lens' => 'Clear'],
            'os' => ['lids' => 'Normal', 'conjunctiva' => 'Normal', 'cornea' => 'Clear', 'ac' => 'Deep & Quiet', 'iris' => 'Normal', 'pupil' => 'RRR', 'lens' => 'Clear']
        ]),
        
        fundusData: @json(($visit ?? null)?->getStructuredField('ophthal.fundus') ?? [
            'od' => ['media' => 'Clear', 'disc' => '', 'cdr' => '', 'macula' => '', 'vessels' => '', 'periphery' => ''],
            'os' => ['media' => 'Clear', 'disc' => '', 'cdr' => '', 'macula' => '', 'vessels' => '', 'periphery' => ''],
            'dilatedWith' => []
        ]),
        
        commonDiagnoses: [
            { code: 'H52.1', name: 'Myopia' },
            { code: 'H52.0', name: 'Hypermetropia' },
            { code: 'H52.2', name: 'Astigmatism' },
            { code: 'H52.4', name: 'Presbyopia' },
            { code: 'H25.9', name: 'Senile Cataract' },
            { code: 'H40.1', name: 'POAG' },
            { code: 'H40.2', name: 'PACG' },
            { code: 'H40.0', name: 'Glaucoma Suspect' },
            { code: 'H35.3', name: 'Diabetic Retinopathy' },
            { code: 'H35.31', name: 'NPDR' },
            { code: 'H35.32', name: 'PDR' },
            { code: 'H35.81', name: 'Diabetic Macular Edema' },
            { code: 'H35.30', name: 'ARMD' },
            { code: 'H10.1', name: 'Allergic Conjunctivitis' },
            { code: 'H10.0', name: 'Bacterial Conjunctivitis' },
            { code: 'H04.1', name: 'Dry Eye' },
            { code: 'H16.0', name: 'Corneal Ulcer' },
            { code: 'H20.0', name: 'Acute Iritis' },
            { code: 'H33.0', name: 'Retinal Detachment' },
            { code: 'H34.1', name: 'CRAO' },
            { code: 'H34.2', name: 'CRVO' }
        ],
        
        selectedDiagnoses: @json(($visit ?? null)?->getStructuredField('ophthal.diagnoses') ?? []),

        spectacleRx: @json(($visit ?? null)?->getStructuredField('ophthal.spectacle_rx') ?? [
            'od' => ['sphere' => '', 'cylinder' => '', 'axis' => '', 'add' => '', 'va' => ''],
            'os' => ['sphere' => '', 'cylinder' => '', 'axis' => '', 'add' => '', 'va' => ''],
            'pdDistance' => '', 'pdNear' => '', 'instructions' => '', 'lensType' => 'SV'
        ]),

        contactLensRx: @json(($visit ?? null)?->getStructuredField('ophthal.contact_lens_rx') ?? [
            'od' => ['bc' => '', 'power' => '', 'dia' => '', 'cyl' => '', 'axis' => '', 'brand' => ''],
            'os' => ['bc' => '', 'power' => '', 'dia' => '', 'cyl' => '', 'axis' => '', 'brand' => ''],
            'modality' => 'soft', 'wearSchedule' => ''
        ]),
        
        init() {
            console.log('Ophthalmology EMR initialized');
        },

        copyRefractionToSpectacle() {
            this.spectacleRx.od = {
                sphere: this.refractionData.od.sphere || '',
                cylinder: this.refractionData.od.cylinder || '',
                axis: this.refractionData.od.axis || '',
                add: this.refractionData.od.add || '',
                va: this.refractionData.od.va || ''
            };
            this.spectacleRx.os = {
                sphere: this.refractionData.os.sphere || '',
                cylinder: this.refractionData.os.cylinder || '',
                axis: this.refractionData.os.axis || '',
                add: this.refractionData.os.add || '',
                va: this.refractionData.os.va || ''
            };
            this.spectacleRx.pdDistance = this.refractionData.pdDistance || '';
            this.spectacleRx.pdNear = this.refractionData.pdNear || '';
            this.updateSpectacleRx();
        },

        updateSpectacleRx() {
            console.log('spectacleRx updated', this.spectacleRx);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },

        updateContactLensRx() {
            console.log('contactLensRx updated', this.contactLensRx);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        getIOPClass(value) {
            const iop = parseFloat(value);
            if (isNaN(iop)) return '';
            if (iop <= 21) return 'iop-normal';
            if (iop <= 25) return 'iop-borderline';
            return 'iop-high';
        },
        
        updateVA() {
            console.log('VA updated:', this.vaData);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateIOP() {
            console.log('IOP updated:', this.iopData);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateRefraction() {
            console.log('Refraction updated:', this.refractionData);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateSlitLamp() {
            console.log('Slit lamp updated:', this.slitLampData);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateFundus() {
            console.log('Fundus updated:', this.fundusData);
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        toggleDilationDrop(drop) {
            const idx = this.fundusData.dilatedWith.indexOf(drop);
            if (idx > -1) {
                this.fundusData.dilatedWith.splice(idx, 1);
            } else {
                this.fundusData.dilatedWith.push(drop);
            }
            this.updateFundus();
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
