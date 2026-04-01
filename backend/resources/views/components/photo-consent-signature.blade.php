{{-- Patient photo consent: canvas signature + POST to photo-vault/consent --}}
@props([
    'patientId',
    'title' => 'Patient consent signature',
])

@php
    $pid = (int) $patientId;
@endphp

<div
    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
    x-data="photoConsentSignature({{ $pid }})"
    x-init="initCanvas()"
>
    <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
        <div>
            <h4 class="text-sm font-semibold text-gray-900">{{ $title }}</h4>
            <p class="text-xs text-gray-500 mt-0.5">Sign in the box. Saved as a PNG with your consent record.</p>
        </div>
        <span
            class="text-xs font-medium px-2 py-0.5 rounded-full"
            :class="consentSaved ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'"
            x-text="consentSaved ? 'Consent on file' : 'Signature not saved yet'"
        ></span>
    </div>

    <div class="relative rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 overflow-hidden touch-none" style="min-height: 160px;">
        <canvas
            x-ref="sigCanvas"
            class="block w-full cursor-crosshair"
            style="height: 160px;"
            @mousedown.prevent="startDraw($event)"
            @mousemove.prevent="moveDraw($event)"
            @mouseup.prevent="endDraw()"
            @mouseleave.prevent="endDraw()"
            @touchstart.prevent="startDrawTouch($event)"
            @touchmove.prevent="moveDrawTouch($event)"
            @touchend.prevent="endDraw()"
        ></canvas>
    </div>

    <p class="text-xs text-red-600 mt-2" x-show="errorMessage" x-text="errorMessage"></p>

    <div class="flex flex-wrap items-center gap-2 mt-3">
        <button
            type="button"
            @click="clearCanvas()"
            class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
        >
            Clear
        </button>
        <button
            type="button"
            @click="saveConsent()"
            :disabled="saving"
            class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
            <span x-show="!saving">Save signed consent</span>
            <span x-show="saving">Saving…</span>
        </button>
    </div>
</div>

@once
    @push('scripts')
    <script>
        console.log('[photoConsentSignature] script registered');

        function photoConsentSignature(patientId) {
            return {
                patientId,
                saving: false,
                consentSaved: false,
                errorMessage: '',
                drawing: false,
                lastX: 0,
                lastY: 0,
                ctx: null,
                cssW: 400,
                cssH: 160,

                initCanvas() {
                    this.$nextTick(() => {
                        const c = this.$refs.sigCanvas;
                        if (!c) {
                            console.warn('[photoConsentSignature] canvas ref missing');
                            return;
                        }
                        this.resizeCanvas();
                        window.addEventListener('resize', () => this.resizeCanvas());
                        console.log('[photoConsentSignature] canvas ready', { patientId: this.patientId });
                    });
                },

                resizeCanvas() {
                    const c = this.$refs.sigCanvas;
                    if (!c) return;
                    const rect = c.getBoundingClientRect();
                    this.cssW = rect.width;
                    this.cssH = rect.height;
                    const dpr = window.devicePixelRatio || 1;
                    c.width = Math.max(1, Math.floor(rect.width * dpr));
                    c.height = Math.max(1, Math.floor(rect.height * dpr));
                    const ctx = c.getContext('2d');
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.scale(dpr, dpr);
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, rect.width, rect.height);
                    ctx.strokeStyle = '#111827';
                    ctx.lineWidth = 2.5;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    this.ctx = ctx;
                    console.log('[photoConsentSignature] resizeCanvas', { w: rect.width, h: rect.height, dpr });
                },

                clearCanvas() {
                    const c = this.$refs.sigCanvas;
                    if (!c || !this.ctx) return;
                    this.ctx.fillStyle = '#ffffff';
                    this.ctx.fillRect(0, 0, this.cssW, this.cssH);
                    this.consentSaved = false;
                    this.errorMessage = '';
                    console.log('[photoConsentSignature] canvas cleared');
                },

                _pos(e) {
                    const c = this.$refs.sigCanvas;
                    const r = c.getBoundingClientRect();
                    return { x: e.clientX - r.left, y: e.clientY - r.top };
                },

                startDraw(e) {
                    if (!this.ctx) return;
                    const p = this._pos(e);
                    this.drawing = true;
                    this.lastX = p.x;
                    this.lastY = p.y;
                    console.log('[photoConsentSignature] draw start', p);
                },

                moveDraw(e) {
                    if (!this.drawing || !this.ctx) return;
                    const p = this._pos(e);
                    this.ctx.beginPath();
                    this.ctx.moveTo(this.lastX, this.lastY);
                    this.ctx.lineTo(p.x, p.y);
                    this.ctx.stroke();
                    this.lastX = p.x;
                    this.lastY = p.y;
                },

                endDraw() {
                    this.drawing = false;
                },

                startDrawTouch(e) {
                    if (!e.touches || !e.touches[0]) return;
                    const t = e.touches[0];
                    this.startDraw({ clientX: t.clientX, clientY: t.clientY });
                },

                moveDrawTouch(e) {
                    if (!e.touches || !e.touches[0]) return;
                    const t = e.touches[0];
                    this.moveDraw({ clientX: t.clientX, clientY: t.clientY });
                },

                isCanvasBlank() {
                    const c = this.$refs.sigCanvas;
                    if (!c) return true;
                    const ctx = c.getContext('2d');
                    const data = ctx.getImageData(0, 0, c.width, c.height).data;
                    for (let i = 0; i < data.length; i += 4) {
                        const r = data[i];
                        const g = data[i + 1];
                        const b = data[i + 2];
                        if (r < 248 || g < 248 || b < 248) {
                            return false;
                        }
                    }
                    return true;
                },

                async saveConsent() {
                    const c = this.$refs.sigCanvas;
                    if (!c) {
                        this.errorMessage = 'Signature pad not ready.';
                        return;
                    }
                    if (this.isCanvasBlank()) {
                        this.errorMessage = 'Please sign in the box before saving.';
                        console.warn('[photoConsentSignature] save blocked: blank canvas');
                        return;
                    }

                    const dataUrl = c.toDataURL('image/png');
                    this.errorMessage = '';
                    this.saving = true;

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const url = @json(route('photo-vault.consent'));

                    console.log('[photoConsentSignature] POST consent', { url, patientId: this.patientId, dataUrlLen: dataUrl.length });

                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                patient_id: this.patientId,
                                consent_given: true,
                                signature_image: dataUrl,
                            }),
                        });

                        const body = await res.json().catch(() => ({}));
                        console.log('[photoConsentSignature] response', { status: res.status, body });

                        if (!res.ok) {
                            this.errorMessage = body.error || body.message || ('Save failed (' + res.status + ')');
                            this.consentSaved = false;
                            return;
                        }

                        this.consentSaved = true;
                        this.$dispatch('photo-consent-saved', { patientId: this.patientId });
                    } catch (err) {
                        console.error('[photoConsentSignature] fetch error', err);
                        this.errorMessage = err.message || 'Network error';
                        this.consentSaved = false;
                    } finally {
                        this.saving = false;
                    }
                },
            };
        }
    </script>
    @endpush
@endonce
