{{-- Gynaecology / Obstetrics EMR Template --}}
{{-- Variables: $visit, $patient --}}

<style>
.gynae-section { margin-bottom: 20px; }
.gynae-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
.gynae-header { padding: 12px 16px; background: linear-gradient(135deg, #fdf2f8, #fce7f3); border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px; cursor: pointer; }
.gynae-header h3 { font-size: 14px; font-weight: 600; color: #be185d; margin: 0; }
.gynae-body { padding: 16px; }
.gynae-grid { display: grid; gap: 12px; }
.gynae-grid-2 { grid-template-columns: repeat(2, 1fr); }
.gynae-grid-3 { grid-template-columns: repeat(3, 1fr); }
.gynae-grid-4 { grid-template-columns: repeat(4, 1fr); }
.gynae-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.gynae-input { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; }
.gynae-input:focus { outline: none; border-color: #ec4899; box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1); }
.mode-toggle { display: flex; gap: 8px; margin-bottom: 16px; }
.mode-btn { flex: 1; padding: 12px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; text-align: center; transition: all 0.15s; }
.mode-btn:hover { background: #fdf2f8; }
.mode-btn.selected { background: linear-gradient(135deg, #ec4899, #f472b6); color: white; border-color: #ec4899; }
.finding-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f1f5f9; border-radius: 8px; font-size: 12px; margin: 3px; cursor: pointer; transition: all 0.15s; }
.finding-chip:hover { background: #e2e8f0; }
.finding-chip.selected { background: #ec4899; color: white; }
.obs-timeline { display: flex; gap: 8px; overflow-x: auto; padding: 12px 0; }
.trimester-card { min-width: 150px; padding: 12px; background: #f8fafc; border-radius: 10px; border: 1px solid #e5e7eb; }
.trimester-card.active { background: #fdf2f8; border-color: #ec4899; }
.edd-display { font-size: 24px; font-weight: 700; color: #be185d; text-align: center; padding: 16px; background: #fdf2f8; border-radius: 12px; }
</style>

<div x-data="gynaecologyEMR()" class="gynae-section">
    {{-- Mode Selection --}}
    <div class="mode-toggle">
        <button type="button" class="mode-btn" :class="{'selected': mode === 'gynae'}" @click="mode = 'gynae'">
            🌸 Gynaecology
        </button>
        <button type="button" class="mode-btn" :class="{'selected': mode === 'obs'}" @click="mode = 'obs'">
            🤰 Obstetrics / Antenatal
        </button>
    </div>

    {{-- Gynaecology Mode --}}
    <template x-if="mode === 'gynae'">
        <div>
            {{-- Menstrual History --}}
            <div class="gynae-card">
                <div class="gynae-header" @click="sections.menstrual = !sections.menstrual">
                    <span style="font-size: 18px;">📅</span>
                    <h3>Menstrual History</h3>
                    <span style="margin-left: auto; color: #be185d;" x-text="sections.menstrual ? '▼' : '▶'"></span>
                </div>
                <div class="gynae-body" x-show="sections.menstrual" x-collapse>
                    <div class="gynae-grid gynae-grid-4">
                        <div>
                            <div class="gynae-label">LMP</div>
                            <input type="date" class="gynae-input" x-model="menstrualData.lmp" @change="updateMenstrual()">
                        </div>
                        <div>
                            <div class="gynae-label">Cycle Length</div>
                            <input type="number" class="gynae-input" x-model="menstrualData.cycleLength" @change="updateMenstrual()" placeholder="28 days">
                        </div>
                        <div>
                            <div class="gynae-label">Duration of Flow</div>
                            <input type="number" class="gynae-input" x-model="menstrualData.flowDuration" @change="updateMenstrual()" placeholder="5 days">
                        </div>
                        <div>
                            <div class="gynae-label">Regularity</div>
                            <select class="gynae-input" x-model="menstrualData.regularity" @change="updateMenstrual()">
                                <option value="Regular">Regular</option>
                                <option value="Irregular">Irregular</option>
                                <option value="Amenorrhea">Amenorrhea</option>
                                <option value="Oligomenorrhea">Oligomenorrhea</option>
                                <option value="Polymenorrhea">Polymenorrhea</option>
                            </select>
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-3" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">Flow Amount</div>
                            <select class="gynae-input" x-model="menstrualData.flowAmount" @change="updateMenstrual()">
                                <option value="Normal">Normal</option>
                                <option value="Scanty">Scanty</option>
                                <option value="Heavy (Menorrhagia)">Heavy (Menorrhagia)</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Pain (Dysmenorrhea)</div>
                            <select class="gynae-input" x-model="menstrualData.dysmenorrhea" @change="updateMenstrual()">
                                <option value="None">None</option>
                                <option value="Mild">Mild</option>
                                <option value="Moderate">Moderate</option>
                                <option value="Severe">Severe</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Clots</div>
                            <select class="gynae-input" x-model="menstrualData.clots" @change="updateMenstrual()">
                                <option value="None">None</option>
                                <option value="Small">Small</option>
                                <option value="Large">Large</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 12px;">
                        <div class="gynae-label">Associated Symptoms</div>
                        <div style="display: flex; flex-wrap: wrap;">
                            <template x-for="symptom in menstrualSymptoms" :key="symptom">
                                <button type="button" class="finding-chip" :class="{'selected': menstrualData.symptoms.includes(symptom)}" @click="toggleMenstrualSymptom(symptom)" x-text="symptom"></button>
                            </template>
                        </div>
                    </div>
                    <input type="hidden" name="gynae_menstrual" :value="JSON.stringify(menstrualData)">
                </div>
            </div>

            {{-- Obstetric History --}}
            <div class="gynae-card" style="margin-top: 16px;">
                <div class="gynae-header" @click="sections.obsHistory = !sections.obsHistory">
                    <span style="font-size: 18px;">👶</span>
                    <h3>Obstetric History</h3>
                    <span style="margin-left: auto; color: #be185d;" x-text="sections.obsHistory ? '▼' : '▶'"></span>
                </div>
                <div class="gynae-body" x-show="sections.obsHistory" x-collapse>
                    <div class="gynae-grid gynae-grid-4">
                        <div style="text-align: center;">
                            <div class="gynae-label">Gravida (G)</div>
                            <input type="number" class="gynae-input" x-model="obsHistory.gravida" @change="updateObsHistory()" style="text-align: center; font-size: 20px; font-weight: 700;">
                        </div>
                        <div style="text-align: center;">
                            <div class="gynae-label">Para (P)</div>
                            <input type="number" class="gynae-input" x-model="obsHistory.para" @change="updateObsHistory()" style="text-align: center; font-size: 20px; font-weight: 700;">
                        </div>
                        <div style="text-align: center;">
                            <div class="gynae-label">Living (L)</div>
                            <input type="number" class="gynae-input" x-model="obsHistory.living" @change="updateObsHistory()" style="text-align: center; font-size: 20px; font-weight: 700;">
                        </div>
                        <div style="text-align: center;">
                            <div class="gynae-label">Abortions (A)</div>
                            <input type="number" class="gynae-input" x-model="obsHistory.abortions" @change="updateObsHistory()" style="text-align: center; font-size: 20px; font-weight: 700;">
                        </div>
                    </div>

                    <div style="margin-top: 12px;">
                        <div class="gynae-label">Previous Deliveries</div>
                        <textarea class="gynae-input" x-model="obsHistory.previousDeliveries" @change="updateObsHistory()" placeholder="Details of previous pregnancies, mode of delivery, complications..."></textarea>
                    </div>
                    <input type="hidden" name="gynae_obs_history" :value="JSON.stringify(obsHistory)">
                </div>
            </div>

            {{-- Per Vaginal Examination --}}
            <div class="gynae-card" style="margin-top: 16px;">
                <div class="gynae-header" @click="sections.pv = !sections.pv">
                    <span style="font-size: 18px;">🔍</span>
                    <h3>Per Vaginal Examination</h3>
                    <span style="margin-left: auto; color: #be185d;" x-text="sections.pv ? '▼' : '▶'"></span>
                </div>
                <div class="gynae-body" x-show="sections.pv" x-collapse>
                    <div class="gynae-grid gynae-grid-2">
                        <div>
                            <div class="gynae-label">Vulva</div>
                            <select class="gynae-input" x-model="pvExam.vulva" @change="updatePV()">
                                <option value="Normal">Normal</option>
                                <option value="Atrophic">Atrophic</option>
                                <option value="Discharge">Discharge</option>
                                <option value="Ulcer">Ulcer</option>
                                <option value="Warts">Warts</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Vagina</div>
                            <select class="gynae-input" x-model="pvExam.vagina" @change="updatePV()">
                                <option value="Healthy">Healthy</option>
                                <option value="Atrophic">Atrophic</option>
                                <option value="Discharge">Discharge</option>
                                <option value="Cystocele">Cystocele</option>
                                <option value="Rectocele">Rectocele</option>
                            </select>
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-2" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">Cervix</div>
                            <select class="gynae-input" x-model="pvExam.cervix" @change="updatePV()">
                                <option value="Healthy">Healthy</option>
                                <option value="Erosion">Erosion</option>
                                <option value="Nabothian cyst">Nabothian cyst</option>
                                <option value="Hypertrophied">Hypertrophied</option>
                                <option value="Suspicious growth">Suspicious growth</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Discharge</div>
                            <select class="gynae-input" x-model="pvExam.discharge" @change="updatePV()">
                                <option value="Nil">Nil</option>
                                <option value="White">White</option>
                                <option value="Curd-like">Curd-like (Candida)</option>
                                <option value="Greenish">Greenish</option>
                                <option value="Frothy">Frothy (Trichomonas)</option>
                                <option value="Blood-stained">Blood-stained</option>
                            </select>
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-2" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">Uterus Size</div>
                            <select class="gynae-input" x-model="pvExam.uterusSize" @change="updatePV()">
                                <option value="Normal">Normal</option>
                                <option value="Bulky">Bulky</option>
                                <option value="6 weeks">6 weeks</option>
                                <option value="8 weeks">8 weeks</option>
                                <option value="10 weeks">10 weeks</option>
                                <option value="12 weeks">12 weeks</option>
                                <option value="Atrophic">Atrophic</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Uterus Position</div>
                            <select class="gynae-input" x-model="pvExam.uterusPosition" @change="updatePV()">
                                <option value="Anteverted">Anteverted</option>
                                <option value="Retroverted">Retroverted</option>
                                <option value="Axial">Axial</option>
                            </select>
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-2" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">Adnexae</div>
                            <textarea class="gynae-input" x-model="pvExam.adnexae" @change="updatePV()" placeholder="Mass, tenderness..."></textarea>
                        </div>
                        <div>
                            <div class="gynae-label">POD (Pouch of Douglas)</div>
                            <select class="gynae-input" x-model="pvExam.pod" @change="updatePV()">
                                <option value="Free">Free</option>
                                <option value="Full">Full</option>
                                <option value="Tender">Tender</option>
                                <option value="Nodular">Nodular</option>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="gynae_pv" :value="JSON.stringify(pvExam)">
                </div>
            </div>

            {{-- Pap Smear / Screening --}}
            <div class="gynae-card" style="margin-top: 16px;">
                <div class="gynae-header" @click="sections.screening = !sections.screening">
                    <span style="font-size: 18px;">🔬</span>
                    <h3>Screening / Pap Smear</h3>
                    <span style="margin-left: auto; color: #be185d;" x-text="sections.screening ? '▼' : '▶'"></span>
                </div>
                <div class="gynae-body" x-show="sections.screening" x-collapse>
                    <div class="gynae-grid gynae-grid-3">
                        <div>
                            <div class="gynae-label">Last Pap Smear</div>
                            <input type="date" class="gynae-input" x-model="screening.lastPap" @change="updateScreening()">
                        </div>
                        <div>
                            <div class="gynae-label">Result</div>
                            <select class="gynae-input" x-model="screening.papResult" @change="updateScreening()">
                                <option value="">Select</option>
                                <option value="Normal">Normal</option>
                                <option value="ASCUS">ASCUS</option>
                                <option value="LSIL">LSIL</option>
                                <option value="HSIL">HSIL</option>
                                <option value="AGC">AGC</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">HPV Status</div>
                            <select class="gynae-input" x-model="screening.hpv" @change="updateScreening()">
                                <option value="">Not done</option>
                                <option value="Negative">Negative</option>
                                <option value="Positive">Positive</option>
                                <option value="High-risk positive">High-risk positive</option>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="gynae_screening" :value="JSON.stringify(screening)">
                </div>
            </div>
        </div>
    </template>

    {{-- Obstetrics Mode --}}
    <template x-if="mode === 'obs'">
        <div>
            {{-- Current Pregnancy --}}
            <div class="gynae-card">
                <div class="gynae-header" @click="sections.pregnancy = !sections.pregnancy">
                    <span style="font-size: 18px;">🤰</span>
                    <h3>Current Pregnancy</h3>
                    <span style="margin-left: auto; color: #be185d;" x-text="sections.pregnancy ? '▼' : '▶'"></span>
                </div>
                <div class="gynae-body" x-show="sections.pregnancy" x-collapse>
                    <div class="gynae-grid gynae-grid-3">
                        <div>
                            <div class="gynae-label">LMP</div>
                            <input type="date" class="gynae-input" x-model="pregnancy.lmp" @change="calculateEDD()">
                        </div>
                        <div>
                            <div class="gynae-label">EDD (Calculated)</div>
                            <input type="date" class="gynae-input" x-model="pregnancy.edd" readonly style="background: #f8fafc;">
                        </div>
                        <div>
                            <div class="gynae-label">Gestational Age</div>
                            <div class="edd-display" x-text="pregnancy.gestationalAge || 'Enter LMP'"></div>
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-3" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">Gravida</div>
                            <input type="number" class="gynae-input" x-model="pregnancy.gravida" @change="updatePregnancy()">
                        </div>
                        <div>
                            <div class="gynae-label">Para</div>
                            <input type="number" class="gynae-input" x-model="pregnancy.para" @change="updatePregnancy()">
                        </div>
                        <div>
                            <div class="gynae-label">Living</div>
                            <input type="number" class="gynae-input" x-model="pregnancy.living" @change="updatePregnancy()">
                        </div>
                    </div>

                    <div style="margin-top: 16px;">
                        <div class="gynae-label">High Risk Factors</div>
                        <div style="display: flex; flex-wrap: wrap;">
                            <template x-for="risk in riskFactors" :key="risk">
                                <button type="button" class="finding-chip" :class="{'selected': pregnancy.risks.includes(risk)}" @click="toggleRisk(risk)" x-text="risk"></button>
                            </template>
                        </div>
                    </div>
                    <input type="hidden" name="obs_pregnancy" :value="JSON.stringify(pregnancy)">
                </div>
            </div>

            {{-- Antenatal Examination --}}
            <div class="gynae-card" style="margin-top: 16px;">
                <div class="gynae-header" @click="sections.anc = !sections.anc">
                    <span style="font-size: 18px;">🩺</span>
                    <h3>Antenatal Examination</h3>
                    <span style="margin-left: auto; color: #be185d;" x-text="sections.anc ? '▼' : '▶'"></span>
                </div>
                <div class="gynae-body" x-show="sections.anc" x-collapse>
                    <div class="gynae-grid gynae-grid-4">
                        <div>
                            <div class="gynae-label">Weight (kg)</div>
                            <input type="number" step="0.1" class="gynae-input" x-model="ancExam.weight" @change="updateANC()">
                        </div>
                        <div>
                            <div class="gynae-label">BP (mmHg)</div>
                            <input type="text" class="gynae-input" x-model="ancExam.bp" @change="updateANC()" placeholder="120/80">
                        </div>
                        <div>
                            <div class="gynae-label">Pallor</div>
                            <select class="gynae-input" x-model="ancExam.pallor" @change="updateANC()">
                                <option value="Absent">Absent</option>
                                <option value="Mild">Mild</option>
                                <option value="Moderate">Moderate</option>
                                <option value="Severe">Severe</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Edema</div>
                            <select class="gynae-input" x-model="ancExam.edema" @change="updateANC()">
                                <option value="Nil">Nil</option>
                                <option value="Pedal +">Pedal +</option>
                                <option value="Pedal ++">Pedal ++</option>
                                <option value="Generalized">Generalized</option>
                            </select>
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-3" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">Fundal Height (cm)</div>
                            <input type="number" class="gynae-input" x-model="ancExam.fundalHeight" @change="updateANC()">
                        </div>
                        <div>
                            <div class="gynae-label">Lie</div>
                            <select class="gynae-input" x-model="ancExam.lie" @change="updateANC()">
                                <option value="">Select</option>
                                <option value="Longitudinal">Longitudinal</option>
                                <option value="Oblique">Oblique</option>
                                <option value="Transverse">Transverse</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Presentation</div>
                            <select class="gynae-input" x-model="ancExam.presentation" @change="updateANC()">
                                <option value="">Select</option>
                                <option value="Cephalic">Cephalic</option>
                                <option value="Breech">Breech</option>
                                <option value="Shoulder">Shoulder</option>
                            </select>
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-3" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">Engagement</div>
                            <select class="gynae-input" x-model="ancExam.engagement" @change="updateANC()">
                                <option value="">Select</option>
                                <option value="Not engaged">Not engaged</option>
                                <option value="1/5">1/5 palpable</option>
                                <option value="2/5">2/5 palpable</option>
                                <option value="3/5">3/5 palpable</option>
                                <option value="4/5">4/5 palpable</option>
                                <option value="Engaged">Fully engaged</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Fetal Heart (bpm)</div>
                            <input type="number" class="gynae-input" x-model="ancExam.fhr" @change="updateANC()" placeholder="120-160">
                        </div>
                        <div>
                            <div class="gynae-label">Liquor</div>
                            <select class="gynae-input" x-model="ancExam.liquor" @change="updateANC()">
                                <option value="">Select</option>
                                <option value="Adequate">Adequate</option>
                                <option value="Oligohydramnios">Oligohydramnios</option>
                                <option value="Polyhydramnios">Polyhydramnios</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 12px;">
                        <div class="gynae-label">Fetal Movements</div>
                        <select class="gynae-input" x-model="ancExam.fetalMovements" @change="updateANC()">
                            <option value="Good">Good</option>
                            <option value="Reduced">Reduced</option>
                            <option value="Not yet felt">Not yet felt</option>
                        </select>
                    </div>
                    <input type="hidden" name="obs_anc" :value="JSON.stringify(ancExam)">
                </div>
            </div>

            {{-- Investigations --}}
            <div class="gynae-card" style="margin-top: 16px;">
                <div class="gynae-header" @click="sections.investigations = !sections.investigations">
                    <span style="font-size: 18px;">🧪</span>
                    <h3>Antenatal Investigations</h3>
                    <span style="margin-left: auto; color: #be185d;" x-text="sections.investigations ? '▼' : '▶'"></span>
                </div>
                <div class="gynae-body" x-show="sections.investigations" x-collapse>
                    <div class="gynae-grid gynae-grid-3">
                        <div>
                            <div class="gynae-label">Blood Group</div>
                            <select class="gynae-input" x-model="investigations.bloodGroup" @change="updateInvestigations()">
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Hemoglobin (g/dL)</div>
                            <input type="number" step="0.1" class="gynae-input" x-model="investigations.hb" @change="updateInvestigations()">
                        </div>
                        <div>
                            <div class="gynae-label">HIV</div>
                            <select class="gynae-input" x-model="investigations.hiv" @change="updateInvestigations()">
                                <option value="">Not done</option>
                                <option value="Negative">Negative</option>
                                <option value="Positive">Positive</option>
                            </select>
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-3" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">HBsAg</div>
                            <select class="gynae-input" x-model="investigations.hbsag" @change="updateInvestigations()">
                                <option value="">Not done</option>
                                <option value="Negative">Negative</option>
                                <option value="Positive">Positive</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">VDRL</div>
                            <select class="gynae-input" x-model="investigations.vdrl" @change="updateInvestigations()">
                                <option value="">Not done</option>
                                <option value="Non-reactive">Non-reactive</option>
                                <option value="Reactive">Reactive</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Urine Routine</div>
                            <select class="gynae-input" x-model="investigations.urine" @change="updateInvestigations()">
                                <option value="">Not done</option>
                                <option value="Normal">Normal</option>
                                <option value="Albumin +">Albumin +</option>
                                <option value="Sugar +">Sugar +</option>
                                <option value="Albumin & Sugar +">Albumin & Sugar +</option>
                            </select>
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-2" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">GTT / GDM Screening</div>
                            <select class="gynae-input" x-model="investigations.gtt" @change="updateInvestigations()">
                                <option value="">Not done</option>
                                <option value="Normal">Normal</option>
                                <option value="GDM">GDM</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">TSH</div>
                            <input type="text" class="gynae-input" x-model="investigations.tsh" @change="updateInvestigations()" placeholder="mIU/L">
                        </div>
                    </div>
                    <input type="hidden" name="obs_investigations" :value="JSON.stringify(investigations)">
                </div>
            </div>

            {{-- USG Summary --}}
            <div class="gynae-card" style="margin-top: 16px;">
                <div class="gynae-header" @click="sections.usg = !sections.usg">
                    <span style="font-size: 18px;">📷</span>
                    <h3>Ultrasound Summary</h3>
                    <span style="margin-left: auto; color: #be185d;" x-text="sections.usg ? '▼' : '▶'"></span>
                </div>
                <div class="gynae-body" x-show="sections.usg" x-collapse>
                    <div class="gynae-grid gynae-grid-3">
                        <div>
                            <div class="gynae-label">USG Date</div>
                            <input type="date" class="gynae-input" x-model="usg.date" @change="updateUSG()">
                        </div>
                        <div>
                            <div class="gynae-label">GA by USG</div>
                            <input type="text" class="gynae-input" x-model="usg.ga" @change="updateUSG()" placeholder="e.g., 32w 4d">
                        </div>
                        <div>
                            <div class="gynae-label">EFW (g)</div>
                            <input type="number" class="gynae-input" x-model="usg.efw" @change="updateUSG()">
                        </div>
                    </div>

                    <div class="gynae-grid gynae-grid-3" style="margin-top: 12px;">
                        <div>
                            <div class="gynae-label">AFI (cm)</div>
                            <input type="number" step="0.1" class="gynae-input" x-model="usg.afi" @change="updateUSG()">
                        </div>
                        <div>
                            <div class="gynae-label">Placenta Location</div>
                            <select class="gynae-input" x-model="usg.placenta" @change="updateUSG()">
                                <option value="">Select</option>
                                <option value="Anterior">Anterior</option>
                                <option value="Posterior">Posterior</option>
                                <option value="Fundal">Fundal</option>
                                <option value="Low-lying">Low-lying</option>
                                <option value="Previa">Previa</option>
                            </select>
                        </div>
                        <div>
                            <div class="gynae-label">Anomalies</div>
                            <select class="gynae-input" x-model="usg.anomalies" @change="updateUSG()">
                                <option value="None">None</option>
                                <option value="Suspected">Suspected</option>
                                <option value="Confirmed">Confirmed</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 12px;">
                        <div class="gynae-label">USG Remarks</div>
                        <textarea class="gynae-input" x-model="usg.remarks" @change="updateUSG()" placeholder="Additional findings..."></textarea>
                    </div>
                    <input type="hidden" name="obs_usg" :value="JSON.stringify(usg)">
                </div>
            </div>
        </div>
    </template>

    {{-- Common Diagnoses --}}
    <div class="gynae-card" style="margin-top: 16px;">
        <div class="gynae-header" @click="sections.diagnosis = !sections.diagnosis">
            <span style="font-size: 18px;">📋</span>
            <h3>Quick Diagnoses</h3>
            <span style="margin-left: auto; color: #be185d;" x-text="sections.diagnosis ? '▼' : '▶'"></span>
        </div>
        <div class="gynae-body" x-show="sections.diagnosis" x-collapse>
            <div class="gynae-label" x-text="mode === 'gynae' ? 'Gynaecology Diagnoses' : 'Obstetric Diagnoses'"></div>
            <div style="display: flex; flex-wrap: wrap; margin-top: 8px;">
                <template x-for="dx in (mode === 'gynae' ? gynaeDiagnoses : obsDiagnoses)" :key="dx.code">
                    <button type="button" class="finding-chip" :class="{'selected': selectedDiagnoses.includes(dx.code)}" @click="toggleDiagnosis(dx)">
                        <span x-text="dx.name"></span>
                    </button>
                </template>
            </div>
            <input type="hidden" name="gynae_diagnoses" :value="JSON.stringify(selectedDiagnoses)">
        </div>
    </div>
</div>

<script>
console.log('Gynaecology EMR template loaded');

function gynaecologyEMR() {
    return {
        mode: @json($visit->getStructuredField('gynae.mode') ?? 'gynae'),
        
        sections: {
            menstrual: true,
            obsHistory: true,
            pv: false,
            screening: false,
            pregnancy: true,
            anc: true,
            investigations: false,
            usg: false,
            diagnosis: true
        },
        
        menstrualSymptoms: ['Backache', 'Headache', 'Nausea', 'Breast tenderness', 'Mood changes', 'Bloating', 'Fatigue'],
        
        menstrualData: @json($visit->getStructuredField('gynae.menstrual') ?? [
            'lmp' => '',
            'cycleLength' => 28,
            'flowDuration' => 5,
            'regularity' => 'Regular',
            'flowAmount' => 'Normal',
            'dysmenorrhea' => 'None',
            'clots' => 'None',
            'symptoms' => []
        ]),
        
        obsHistory: @json($visit->getStructuredField('gynae.obsHistory') ?? [
            'gravida' => 0,
            'para' => 0,
            'living' => 0,
            'abortions' => 0,
            'previousDeliveries' => ''
        ]),
        
        pvExam: @json($visit->getStructuredField('gynae.pv') ?? [
            'vulva' => 'Normal',
            'vagina' => 'Healthy',
            'cervix' => 'Healthy',
            'discharge' => 'Nil',
            'uterusSize' => 'Normal',
            'uterusPosition' => 'Anteverted',
            'adnexae' => '',
            'pod' => 'Free'
        ]),
        
        screening: @json($visit->getStructuredField('gynae.screening') ?? [
            'lastPap' => '',
            'papResult' => '',
            'hpv' => ''
        ]),
        
        riskFactors: ['Previous CS', 'GDM', 'PIH', 'Rh negative', 'Multiple gestation', 'APH', 'IUGR', 'Preterm labor', 'Elderly primi', 'Bad obstetric history'],
        
        pregnancy: @json($visit->getStructuredField('obs.pregnancy') ?? [
            'lmp' => '',
            'edd' => '',
            'gestationalAge' => '',
            'gravida' => 1,
            'para' => 0,
            'living' => 0,
            'risks' => []
        ]),
        
        ancExam: @json($visit->getStructuredField('obs.anc') ?? [
            'weight' => '',
            'bp' => '',
            'pallor' => 'Absent',
            'edema' => 'Nil',
            'fundalHeight' => '',
            'lie' => '',
            'presentation' => '',
            'engagement' => '',
            'fhr' => '',
            'liquor' => '',
            'fetalMovements' => 'Good'
        ]),
        
        investigations: @json($visit->getStructuredField('obs.investigations') ?? [
            'bloodGroup' => '',
            'hb' => '',
            'hiv' => '',
            'hbsag' => '',
            'vdrl' => '',
            'urine' => '',
            'gtt' => '',
            'tsh' => ''
        ]),
        
        usg: @json($visit->getStructuredField('obs.usg') ?? [
            'date' => '',
            'ga' => '',
            'efw' => '',
            'afi' => '',
            'placenta' => '',
            'anomalies' => 'None',
            'remarks' => ''
        ]),
        
        gynaeDiagnoses: [
            { code: 'N91.2', name: 'Amenorrhea' },
            { code: 'N92.0', name: 'Menorrhagia' },
            { code: 'N94.6', name: 'Dysmenorrhea' },
            { code: 'N80.9', name: 'Endometriosis' },
            { code: 'D25.9', name: 'Uterine Fibroid' },
            { code: 'N83.2', name: 'Ovarian Cyst' },
            { code: 'N84.0', name: 'Endometrial Polyp' },
            { code: 'N76.0', name: 'Vaginitis' },
            { code: 'N72', name: 'Cervicitis' },
            { code: 'N81.1', name: 'Uterine Prolapse' },
            { code: 'E28.2', name: 'PCOS' },
            { code: 'N97.9', name: 'Infertility' }
        ],
        
        obsDiagnoses: [
            { code: 'Z34.0', name: 'Normal Pregnancy' },
            { code: 'O24.4', name: 'GDM' },
            { code: 'O13', name: 'PIH' },
            { code: 'O14.1', name: 'Preeclampsia' },
            { code: 'O36.5', name: 'IUGR' },
            { code: 'O60.0', name: 'Preterm Labor' },
            { code: 'O44.0', name: 'Placenta Previa' },
            { code: 'O45.0', name: 'Placental Abruption' },
            { code: 'O41.0', name: 'Oligohydramnios' },
            { code: 'O40', name: 'Polyhydramnios' },
            { code: 'O32.1', name: 'Breech Presentation' },
            { code: 'O99.0', name: 'Anemia in Pregnancy' }
        ],
        
        selectedDiagnoses: @json($visit->getStructuredField('gynae.diagnoses') ?? []),
        
        init() {
            console.log('Gynaecology EMR initialized');
            if (this.pregnancy.lmp) {
                this.calculateEDD();
            }
        },
        
        toggleMenstrualSymptom(symptom) {
            const idx = this.menstrualData.symptoms.indexOf(symptom);
            if (idx > -1) {
                this.menstrualData.symptoms.splice(idx, 1);
            } else {
                this.menstrualData.symptoms.push(symptom);
            }
            this.updateMenstrual();
        },
        
        updateMenstrual() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateObsHistory() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updatePV() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateScreening() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        calculateEDD() {
            if (this.pregnancy.lmp) {
                const lmp = new Date(this.pregnancy.lmp);
                const edd = new Date(lmp);
                edd.setDate(edd.getDate() + 280);
                this.pregnancy.edd = edd.toISOString().split('T')[0];
                
                const today = new Date();
                const diffDays = Math.floor((today - lmp) / (1000 * 60 * 60 * 24));
                const weeks = Math.floor(diffDays / 7);
                const days = diffDays % 7;
                this.pregnancy.gestationalAge = `${weeks}w ${days}d`;
            }
            this.updatePregnancy();
        },
        
        toggleRisk(risk) {
            const idx = this.pregnancy.risks.indexOf(risk);
            if (idx > -1) {
                this.pregnancy.risks.splice(idx, 1);
            } else {
                this.pregnancy.risks.push(risk);
            }
            this.updatePregnancy();
        },
        
        updatePregnancy() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateANC() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateInvestigations() {
            if (window.triggerAutoSave) window.triggerAutoSave();
        },
        
        updateUSG() {
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
