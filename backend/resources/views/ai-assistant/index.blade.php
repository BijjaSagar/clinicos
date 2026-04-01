@extends('layouts.app')

@section('title', 'AI Documentation Assistant')

@section('breadcrumb', 'AI Assistant')

@section('content')
<style>
.ai-gradient { background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 50%, #3b82f6 100%); }
.pulse-ring { animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite; }
@keyframes pulse-ring {
    0% { transform: scale(0.8); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 0; }
    100% { transform: scale(0.8); opacity: 0.5; }
}
.recording { animation: recording 1s ease-in-out infinite; }
@keyframes recording {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
.waveform { display: flex; align-items: center; gap: 3px; height: 40px; }
.waveform-bar { width: 4px; background: #8b5cf6; border-radius: 2px; animation: wave 1s ease-in-out infinite; }
.waveform-bar:nth-child(1) { animation-delay: 0s; height: 20px; }
.waveform-bar:nth-child(2) { animation-delay: 0.1s; height: 30px; }
.waveform-bar:nth-child(3) { animation-delay: 0.2s; height: 40px; }
.waveform-bar:nth-child(4) { animation-delay: 0.3s; height: 25px; }
.waveform-bar:nth-child(5) { animation-delay: 0.4s; height: 35px; }
@keyframes wave {
    0%, 100% { transform: scaleY(1); }
    50% { transform: scaleY(0.5); }
}
.note-section { transition: all 0.2s ease; }
.note-section:hover { background: #f8fafc; }
</style>
<div x-data="aiAssistant()" class="p-6 space-y-6">
    {{-- Header Card --}}
    <div class="ai-gradient rounded-2xl p-6 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold">AI Documentation Assistant</h1>
                <p class="text-white/80 mt-1">Voice-to-EMR transcription powered by AI</p>
            </div>
            <div class="ml-auto text-right">
                <div class="text-sm text-white/60">Model</div>
                <div class="font-semibold">GPT-4o + Whisper</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Voice Recording Panel --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <span class="text-lg">🎙️</span> Voice Recording
                </h2>
            </div>
            <div class="p-6">
                {{-- Settings --}}
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Specialty</label>
                        <select x-model="settings.specialty" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="general">General Practice</option>
                            <option value="dermatology">Dermatology</option>
                            <option value="ophthalmology">Ophthalmology</option>
                            <option value="orthopaedics">Orthopaedics</option>
                            <option value="ent">ENT</option>
                            <option value="gynaecology">Gynaecology</option>
                            <option value="dental">Dental</option>
                            <option value="physiotherapy">Physiotherapy</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Language</label>
                        <select x-model="settings.language" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="en">English</option>
                            <option value="hi">Hindi</option>
                            <option value="mr">Marathi</option>
                            <option value="ta">Tamil</option>
                            <option value="te">Telugu</option>
                            <option value="kn">Kannada</option>
                            <option value="ml">Malayalam</option>
                            <option value="bn">Bengali</option>
                            <option value="gu">Gujarati</option>
                        </select>
                    </div>
                </div>

                {{-- Recording Area --}}
                <div class="text-center py-8">
                    <div class="relative inline-block">
                        {{-- Pulse Ring --}}
                        <div x-show="isRecording" class="absolute inset-0 w-24 h-24 bg-red-500/30 rounded-full pulse-ring"></div>
                        
                        {{-- Main Button --}}
                        <button @click="toggleRecording()" :class="isRecording ? 'bg-red-500 recording' : 'ai-gradient'" class="relative w-24 h-24 rounded-full text-white flex items-center justify-center transition-all shadow-lg hover:shadow-xl">
                            <template x-if="!isRecording">
                                <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                    <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                                </svg>
                            </template>
                            <template x-if="isRecording">
                                <div class="w-8 h-8 bg-white rounded-sm"></div>
                            </template>
                        </button>
                    </div>

                    <div class="mt-4">
                        <p class="text-sm font-medium" :class="isRecording ? 'text-red-600' : 'text-gray-600'" x-text="isRecording ? 'Recording... Tap to stop' : 'Tap to start recording'"></p>
                        <p class="text-xs text-gray-400 mt-1" x-show="isRecording" x-text="formatDuration(recordingDuration)"></p>
                    </div>

                    {{-- Waveform --}}
                    <div x-show="isRecording" class="flex justify-center mt-4">
                        <div class="waveform">
                            <div class="waveform-bar"></div>
                            <div class="waveform-bar"></div>
                            <div class="waveform-bar"></div>
                            <div class="waveform-bar"></div>
                            <div class="waveform-bar"></div>
                        </div>
                    </div>
                </div>

                {{-- Or Upload --}}
                <div class="border-t border-gray-100 pt-4 mt-4">
                    <div class="text-center">
                        <span class="text-xs text-gray-400">or</span>
                    </div>
                    <label class="mt-2 flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-200 rounded-lg cursor-pointer hover:border-purple-400 transition-colors">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span class="text-sm text-gray-600">Upload audio file</span>
                        <input type="file" class="hidden" accept="audio/*" @change="uploadAudio($event)">
                    </label>
                </div>
            </div>
        </div>

        {{-- Transcription Panel --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <span class="text-lg">📝</span> Transcription
                </h2>
                <button x-show="transcription" @click="generateNotes()" :disabled="loading" class="px-3 py-1.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 disabled:opacity-50 flex items-center gap-2">
                    <svg x-show="loading" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Processing...' : '✨ Generate Notes'"></span>
                </button>
            </div>
            <div class="p-6">
                <template x-if="processing">
                    <div class="text-center py-12">
                        <div class="w-12 h-12 mx-auto mb-4 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin"></div>
                        <p class="text-sm text-gray-600">Transcribing audio...</p>
                    </div>
                </template>

                <template x-if="!processing && !transcription">
                    <div class="text-center py-12 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm">Record or upload audio to see transcription</p>
                    </div>
                </template>

                <template x-if="!processing && transcription">
                    <div>
                        <textarea x-model="transcription" rows="12" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm leading-relaxed focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none" placeholder="Transcription will appear here..."></textarea>
                        <div class="flex justify-between items-center mt-3 text-xs text-gray-400">
                            <span x-text="transcription.split(' ').length + ' words'"></span>
                            <button @click="transcription = ''" class="text-red-500 hover:text-red-600">Clear</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Generated Notes Panel --}}
    <div x-show="notes.history || notes.chief_complaint" x-transition class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-blue-50 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <span class="text-lg">📋</span> AI-Generated Clinical Notes
            </h2>
            <div class="flex gap-2">
                <button @click="extractCodes()" class="px-3 py-1.5 border border-purple-200 text-purple-600 text-sm font-medium rounded-lg hover:bg-purple-50 flex items-center gap-1">
                    🏥 Extract ICD Codes
                </button>
                <button @click="saveNotes()" class="px-3 py-1.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 flex items-center gap-1">
                    💾 Save to EMR
                </button>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Chief Complaint --}}
            <div class="note-section p-4 border border-gray-100 rounded-lg">
                <label class="block text-xs font-semibold text-purple-600 mb-2 uppercase tracking-wide">Chief Complaint</label>
                <textarea x-model="notes.chief_complaint" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"></textarea>
            </div>

            {{-- Assessment --}}
            <div class="note-section p-4 border border-gray-100 rounded-lg">
                <label class="block text-xs font-semibold text-green-600 mb-2 uppercase tracking-wide">Assessment / Diagnosis</label>
                <textarea x-model="notes.assessment" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none"></textarea>
            </div>

            {{-- History --}}
            <div class="note-section p-4 border border-gray-100 rounded-lg">
                <label class="block text-xs font-semibold text-blue-600 mb-2 uppercase tracking-wide">Subjective / History</label>
                <textarea x-model="notes.history" rows="6" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"></textarea>
            </div>

            {{-- Examination --}}
            <div class="note-section p-4 border border-gray-100 rounded-lg">
                <label class="block text-xs font-semibold text-amber-600 mb-2 uppercase tracking-wide">Objective / Examination</label>
                <textarea x-model="notes.examination" rows="6" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent resize-none"></textarea>
            </div>

            {{-- Plan --}}
            <div class="note-section p-4 border border-gray-100 rounded-lg md:col-span-2">
                <label class="block text-xs font-semibold text-indigo-600 mb-2 uppercase tracking-wide">Plan / Treatment</label>
                <textarea x-model="notes.plan" rows="4" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"></textarea>
            </div>

            {{-- ICD Codes --}}
            <div x-show="icdCodes.length" class="note-section p-4 border border-gray-100 rounded-lg md:col-span-2">
                <label class="block text-xs font-semibold text-rose-600 mb-2 uppercase tracking-wide">ICD-10 Codes</label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="code in icdCodes" :key="code.code">
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-rose-50 text-rose-700 rounded-full text-sm">
                            <strong x-text="code.code"></strong>
                            <span class="text-rose-500" x-text="' — ' + code.description"></span>
                        </span>
                    </template>
                </div>
            </div>

            {{-- Vitals --}}
            <div x-show="Object.keys(notes.vitals || {}).length" class="note-section p-4 border border-gray-100 rounded-lg md:col-span-2">
                <label class="block text-xs font-semibold text-teal-600 mb-2 uppercase tracking-wide">Extracted Vitals</label>
                <div class="flex gap-4">
                    <template x-if="notes.vitals?.bp">
                        <div class="px-3 py-2 bg-teal-50 rounded-lg text-sm">
                            <span class="text-teal-600 font-medium">BP:</span> <span x-text="notes.vitals.bp"></span>
                        </div>
                    </template>
                    <template x-if="notes.vitals?.pulse">
                        <div class="px-3 py-2 bg-teal-50 rounded-lg text-sm">
                            <span class="text-teal-600 font-medium">Pulse:</span> <span x-text="notes.vitals.pulse"></span>
                        </div>
                    </template>
                    <template x-if="notes.vitals?.spo2">
                        <div class="px-3 py-2 bg-teal-50 rounded-lg text-sm">
                            <span class="text-teal-600 font-medium">SpO2:</span> <span x-text="notes.vitals.spo2 + '%'"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
console.log('AI Documentation Assistant loaded');

function aiAssistant() {
    return {
        isRecording: false,
        recordingDuration: 0,
        processing: false,
        loading: false,
        transcription: '',
        mediaRecorder: null,
        audioChunks: [],
        recordingInterval: null,
        
        settings: {
            specialty: 'general',
            language: 'en',
            noteType: 'soap',
        },

        notes: {
            chief_complaint: '',
            history: '',
            examination: '',
            assessment: '',
            plan: '',
            vitals: {},
        },

        icdCodes: [],

        init() {
            console.log('AI Assistant initialized');
        },

        async toggleRecording() {
            if (this.isRecording) {
                this.stopRecording();
            } else {
                await this.startRecording();
            }
        },

        async startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.mediaRecorder = new MediaRecorder(stream);
                this.audioChunks = [];
                this.recordingDuration = 0;

                this.mediaRecorder.ondataavailable = (event) => {
                    this.audioChunks.push(event.data);
                };

                this.mediaRecorder.onstop = async () => {
                    const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                    await this.transcribeAudio(audioBlob);
                    stream.getTracks().forEach(track => track.stop());
                };

                this.mediaRecorder.start();
                this.isRecording = true;

                this.recordingInterval = setInterval(() => {
                    this.recordingDuration++;
                }, 1000);

                console.log('Recording started');
            } catch (error) {
                console.error('Recording error:', error);
                alert('Could not access microphone. Please check permissions.');
            }
        },

        stopRecording() {
            if (this.mediaRecorder && this.isRecording) {
                this.mediaRecorder.stop();
                this.isRecording = false;
                clearInterval(this.recordingInterval);
                console.log('Recording stopped');
            }
        },

        async uploadAudio(event) {
            const file = event.target.files[0];
            if (file) {
                await this.transcribeAudio(file);
            }
        },

        async transcribeAudio(audioBlob) {
            this.processing = true;
            console.log('Transcribing audio...');

            const formData = new FormData();
            formData.append('audio', audioBlob, 'recording.webm');
            formData.append('language', this.settings.language);

            try {
                const response = await fetch('/ai-assistant/transcribe', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    this.transcription = data.transcription;
                    console.log('Transcription successful');
                } else {
                    alert(data.error || 'Transcription failed');
                }
            } catch (error) {
                console.error('Transcription error:', error);
                alert('Transcription failed. Please try again.');
            } finally {
                this.processing = false;
            }
        },

        async generateNotes() {
            if (!this.transcription) return;

            this.loading = true;
            console.log('Generating notes...');

            try {
                const response = await fetch('/ai-assistant/generate-notes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        transcription: this.transcription,
                        specialty: this.settings.specialty,
                        note_type: this.settings.noteType,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.notes = data.notes;
                    console.log('Notes generated:', this.notes);
                } else {
                    alert(data.error || 'Note generation failed');
                }
            } catch (error) {
                console.error('Note generation error:', error);
                alert('Failed to generate notes');
            } finally {
                this.loading = false;
            }
        },

        async extractCodes() {
            const allNotes = [this.notes.chief_complaint, this.notes.history, this.notes.examination, this.notes.assessment, this.notes.plan].filter(Boolean).join('\n\n');

            if (!allNotes) {
                alert('No notes to extract codes from');
                return;
            }

            console.log('Extracting ICD codes...');

            try {
                const response = await fetch('/ai-assistant/extract-codes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        notes: allNotes,
                        specialty: this.settings.specialty,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.icdCodes = data.codes;
                    console.log('ICD codes extracted:', this.icdCodes);
                }
            } catch (error) {
                console.error('Code extraction error:', error);
            }
        },

        async saveNotes() {
            alert('To save notes, please open a patient visit first. You can copy these notes to the EMR.');
        },

        formatDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        },
    };
}
</script>
@endsection
