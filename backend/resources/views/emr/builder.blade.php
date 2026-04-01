@extends('layouts.app')

@section('title', 'Custom EMR Builder')

@section('content')
@if(isset($emrBuilderSchemaReady) && !$emrBuilderSchemaReady)
<div class="mx-6 mt-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
    Custom EMR templates table is missing. Run <code class="bg-amber-100 px-1 rounded">php artisan migrate</code> to use the builder.
</div>
@endif
<style>
    .builder-sidebar {
        width: 280px;
        background: #f8fafc;
        border-right: 1px solid #e5e7eb;
        height: calc(100vh - 64px);
        overflow-y: auto;
        position: sticky;
        top: 64px;
    }
    .field-type {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        cursor: grab;
        transition: all 0.15s;
    }
    .field-type:hover {
        border-color: #3b82f6;
        box-shadow: 0 2px 8px rgba(59,130,246,0.15);
    }
    .field-type:active {
        cursor: grabbing;
    }
    .builder-canvas {
        flex: 1;
        min-height: calc(100vh - 64px);
        background: #f1f5f9;
        padding: 24px;
    }
    .canvas-dropzone {
        min-height: 400px;
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        background: white;
        padding: 20px;
        transition: all 0.2s;
    }
    .canvas-dropzone.drag-over {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .form-field {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 12px;
        position: relative;
        transition: all 0.15s;
    }
    .form-field:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .form-field.selected {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }
    .form-field .drag-handle {
        position: absolute;
        left: -10px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 40px;
        background: #e5e7eb;
        border-radius: 4px;
        cursor: grab;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.15s;
    }
    .form-field:hover .drag-handle {
        opacity: 1;
    }
    .field-actions {
        position: absolute;
        right: 12px;
        top: 12px;
        display: flex;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.15s;
    }
    .form-field:hover .field-actions {
        opacity: 1;
    }
    .properties-panel {
        width: 320px;
        background: white;
        border-left: 1px solid #e5e7eb;
        height: calc(100vh - 64px);
        overflow-y: auto;
        position: sticky;
        top: 64px;
    }
    .template-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .template-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 2px 8px rgba(59,130,246,0.1);
    }
    .template-card.active {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .form-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }
    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
    }
</style>

<div x-data="emrBuilder()" class="flex" @keydown.escape="selectedField = null">
    {{-- Left Sidebar - Field Types --}}
    <div class="builder-sidebar p-4">
        <h3 class="font-semibold text-gray-800 mb-4">Field Types</h3>
        <div class="space-y-2">
            @foreach($fieldTypes as $type => $info)
            <div class="field-type" draggable="true" @dragstart="dragField('{{ $type }}', $event)">
                <span class="text-xl">{{ $info['icon'] }}</span>
                <span class="text-sm font-medium text-gray-700">{{ $info['label'] }}</span>
            </div>
            @endforeach
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="font-semibold text-gray-800 mb-4">Templates</h3>
            <button @click="showTemplatesModal = true" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium">
                Manage Templates
            </button>
        </div>
    </div>

    {{-- Main Canvas --}}
    <div class="builder-canvas">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <input type="text" x-model="templateName" class="text-2xl font-bold bg-transparent border-none focus:outline-none" placeholder="Template Name">
                <input type="text" x-model="templateDescription" class="text-gray-600 text-sm bg-transparent border-none focus:outline-none w-full mt-1" placeholder="Add description...">
            </div>
            <div class="flex items-center gap-3">
                <select x-model="templateSpecialty" class="form-input w-48">
                    <option value="">All Specialties</option>
                    <option value="general">General</option>
                    <option value="dermatology">Dermatology</option>
                    <option value="dental">Dental</option>
                    <option value="physiotherapy">Physiotherapy</option>
                    <option value="ophthalmology">Ophthalmology</option>
                    <option value="orthopaedics">Orthopaedics</option>
                    <option value="ent">ENT</option>
                    <option value="gynaecology">Gynaecology</option>
                    <option value="cardiology">Cardiology</option>
                    <option value="pediatrics">Pediatrics</option>
                </select>
                <button @click="previewTemplate()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
                <button @click="saveTemplate()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-medium">
                    Save Template
                </button>
            </div>
        </div>

        {{-- Drop Zone --}}
        <div class="canvas-dropzone" 
             :class="{ 'drag-over': isDraggingOver }"
             @dragover.prevent="isDraggingOver = true"
             @dragleave="isDraggingOver = false"
             @drop.prevent="dropField($event)">
            
            <template x-if="fields.length === 0">
                <div class="text-center py-20 text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <p class="text-lg font-medium">Drag fields here to build your form</p>
                    <p class="text-sm mt-1">Start by dragging field types from the left sidebar</p>
                </div>
            </template>

            <template x-for="(field, index) in fields" :key="field.id">
                <div class="form-field" 
                     :class="{ 'selected': selectedField?.id === field.id }"
                     @click="selectField(field)"
                     draggable="true"
                     @dragstart="dragExistingField(index, $event)"
                     @dragover.prevent
                     @drop.prevent="reorderField(index)">
                    
                    <div class="drag-handle">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
                        </svg>
                    </div>

                    <div class="field-actions">
                        <button @click.stop="duplicateField(index)" class="p-1.5 hover:bg-gray-100 rounded text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <button @click.stop="deleteField(index)" class="p-1.5 hover:bg-red-50 rounded text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Field Preview --}}
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-sm" x-text="getFieldIcon(field.type)"></span>
                        <span class="text-sm font-medium text-gray-700" x-text="field.label || 'Untitled Field'"></span>
                        <span x-show="field.required" class="text-red-500">*</span>
                    </div>

                    {{-- Field Type Preview --}}
                    <div class="mt-2">
                        <template x-if="['text', 'number', 'date', 'time', 'datetime', 'email'].includes(field.type)">
                            <input type="text" class="form-input bg-gray-50" :placeholder="field.placeholder || 'Enter value...'" disabled>
                        </template>
                        <template x-if="field.type === 'textarea' || field.type === 'richtext'">
                            <textarea class="form-input bg-gray-50" rows="2" :placeholder="field.placeholder || 'Enter text...'" disabled></textarea>
                        </template>
                        <template x-if="field.type === 'select'">
                            <select class="form-input bg-gray-50" disabled>
                                <option>Select option...</option>
                                <template x-for="opt in (field.options || [])">
                                    <option x-text="opt"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="field.type === 'checkbox'">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" class="w-4 h-4 rounded" disabled>
                                <span class="text-sm text-gray-600" x-text="field.checkboxLabel || 'Checkbox option'"></span>
                            </label>
                        </template>
                        <template x-if="field.type === 'scale'">
                            <div class="flex items-center gap-2">
                                <template x-for="i in 11">
                                    <button class="w-8 h-8 rounded-full border border-gray-300 text-sm" x-text="i-1" disabled></button>
                                </template>
                            </div>
                        </template>
                        <template x-if="field.type === 'heading'">
                            <div class="text-lg font-semibold text-gray-800" x-text="field.headingText || 'Section Heading'"></div>
                        </template>
                        <template x-if="field.type === 'divider'">
                            <hr class="border-gray-300">
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Right Panel - Field Properties --}}
    <div class="properties-panel p-4" x-show="selectedField">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">Field Properties</h3>
            <button @click="selectedField = null" class="p-1 hover:bg-gray-100 rounded">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <template x-if="selectedField">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Field Type</label>
                    <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                        <span x-text="getFieldIcon(selectedField.type)"></span>
                        <span class="text-sm" x-text="getFieldLabel(selectedField.type)"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                    <input type="text" x-model="selectedField.label" @input="updateField()" class="form-input" placeholder="Field label">
                </div>

                <div x-show="!['heading', 'divider'].includes(selectedField.type)">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Field ID</label>
                    <input type="text" x-model="selectedField.fieldId" @input="updateField()" class="form-input" placeholder="field_id">
                </div>

                <div x-show="['text', 'textarea', 'number'].includes(selectedField.type)">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Placeholder</label>
                    <input type="text" x-model="selectedField.placeholder" @input="updateField()" class="form-input" placeholder="Placeholder text">
                </div>

                <div x-show="selectedField.type === 'heading'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heading Text</label>
                    <input type="text" x-model="selectedField.headingText" @input="updateField()" class="form-input" placeholder="Section title">
                </div>

                <div x-show="selectedField.type === 'checkbox'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Checkbox Label</label>
                    <input type="text" x-model="selectedField.checkboxLabel" @input="updateField()" class="form-input" placeholder="Checkbox text">
                </div>

                <div x-show="['select', 'multiselect', 'radio'].includes(selectedField.type)">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Options (one per line)</label>
                    <textarea x-model="optionsText" @input="updateOptions()" class="form-input" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                </div>

                <div x-show="selectedField.type === 'number'">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Min</label>
                            <input type="number" x-model="selectedField.min" @input="updateField()" class="form-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max</label>
                            <input type="number" x-model="selectedField.max" @input="updateField()" class="form-input">
                        </div>
                    </div>
                </div>

                <div x-show="!['heading', 'divider'].includes(selectedField.type)" class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="selectedField.required" @change="updateField()" class="w-4 h-4 rounded border-gray-300 text-blue-600">
                        <span class="text-sm text-gray-700">Required</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Help Text</label>
                    <input type="text" x-model="selectedField.helpText" @input="updateField()" class="form-input" placeholder="Additional instructions">
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Conditional Visibility</h4>
                    <div class="space-y-2">
                        <select x-model="selectedField.conditionField" @change="updateField()" class="form-input text-sm">
                            <option value="">Always visible</option>
                            <template x-for="f in fields.filter(f => f.id !== selectedField.id)">
                                <option :value="f.id" x-text="f.label || f.fieldId"></option>
                            </template>
                        </select>
                        <template x-if="selectedField.conditionField">
                            <div class="grid grid-cols-2 gap-2">
                                <select x-model="selectedField.conditionOperator" @change="updateField()" class="form-input text-sm">
                                    <option value="equals">Equals</option>
                                    <option value="not_equals">Not equals</option>
                                    <option value="contains">Contains</option>
                                    <option value="not_empty">Not empty</option>
                                </select>
                                <input type="text" x-model="selectedField.conditionValue" @input="updateField()" class="form-input text-sm" placeholder="Value">
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Templates Modal --}}
    <div x-show="showTemplatesModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-transition>
        <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-hidden" @click.away="showTemplatesModal = false">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-bold">Manage Templates</h2>
                <button @click="showTemplatesModal = false" class="p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                <div class="grid grid-cols-2 gap-4">
                    <div class="template-card" :class="{ 'active': !editingTemplateId }" @click="newTemplate()">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">New Template</div>
                                <div class="text-sm text-gray-500">Start from scratch</div>
                            </div>
                        </div>
                    </div>
                    @foreach($templates as $template)
                    <div class="template-card" :class="{ 'active': editingTemplateId === {{ $template->id }} }" @click="loadTemplate({{ json_encode($template) }})">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $template->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $template->specialty ?? 'All specialties' }} • v{{ $template->version }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click.stop="duplicateTemplate({{ $template->id }})" class="p-2 hover:bg-gray-100 rounded text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2"/>
                                    </svg>
                                </button>
                                <button @click.stop="deleteTemplate({{ $template->id }})" class="p-2 hover:bg-red-50 rounded text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Preview Modal --}}
    <div x-show="showPreviewModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-transition>
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-hidden" @click.away="showPreviewModal = false">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-bold">Form Preview</h2>
                <button @click="showPreviewModal = false" class="p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-100px)]">
                <form class="space-y-4">
                    <template x-for="field in fields" :key="field.id">
                        <div>
                            <template x-if="field.type === 'heading'">
                                <h3 class="text-lg font-semibold text-gray-900 mt-4" x-text="field.headingText || 'Section'"></h3>
                            </template>
                            <template x-if="field.type === 'divider'">
                                <hr class="border-gray-300 my-4">
                            </template>
                            <template x-if="!['heading', 'divider'].includes(field.type)">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <span x-text="field.label || 'Untitled'"></span>
                                        <span x-show="field.required" class="text-red-500">*</span>
                                    </label>
                                    <template x-if="field.type === 'text'">
                                        <input type="text" class="form-input" :placeholder="field.placeholder">
                                    </template>
                                    <template x-if="field.type === 'textarea'">
                                        <textarea class="form-input" rows="3" :placeholder="field.placeholder"></textarea>
                                    </template>
                                    <template x-if="field.type === 'number'">
                                        <input type="number" class="form-input" :min="field.min" :max="field.max" :placeholder="field.placeholder">
                                    </template>
                                    <template x-if="field.type === 'select'">
                                        <select class="form-input">
                                            <option value="">Select...</option>
                                            <template x-for="opt in (field.options || [])">
                                                <option x-text="opt"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="field.type === 'checkbox'">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" class="w-4 h-4 rounded">
                                            <span class="text-sm" x-text="field.checkboxLabel"></span>
                                        </label>
                                    </template>
                                    <template x-if="field.type === 'date'">
                                        <input type="date" class="form-input">
                                    </template>
                                    <template x-if="field.type === 'scale'">
                                        <div class="flex items-center gap-1">
                                            <template x-for="i in 11">
                                                <button type="button" class="w-8 h-8 rounded-full border border-gray-300 text-sm hover:bg-blue-50 hover:border-blue-300" x-text="i-1"></button>
                                            </template>
                                        </div>
                                    </template>
                                    <p x-show="field.helpText" class="text-xs text-gray-500 mt-1" x-text="field.helpText"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function emrBuilder() {
    const fieldTypes = @json($fieldTypes);
    
    return {
        fields: [],
        selectedField: null,
        editingTemplateId: null,
        templateName: 'New Custom Template',
        templateDescription: '',
        templateSpecialty: '',
        isDraggingOver: false,
        draggingFieldType: null,
        draggingFieldIndex: null,
        showTemplatesModal: false,
        showPreviewModal: false,
        optionsText: '',

        init() {
            console.log('EMR Builder initialized');
        },

        getFieldIcon(type) {
            return fieldTypes[type]?.icon || '📝';
        },

        getFieldLabel(type) {
            return fieldTypes[type]?.label || type;
        },

        dragField(type, event) {
            console.log('Dragging field type:', type);
            this.draggingFieldType = type;
            this.draggingFieldIndex = null;
            event.dataTransfer.effectAllowed = 'copy';
        },

        dragExistingField(index, event) {
            console.log('Dragging existing field at index:', index);
            this.draggingFieldIndex = index;
            this.draggingFieldType = null;
            event.dataTransfer.effectAllowed = 'move';
        },

        dropField(event) {
            console.log('Drop event');
            this.isDraggingOver = false;

            if (this.draggingFieldType) {
                const newField = {
                    id: 'field_' + Date.now(),
                    type: this.draggingFieldType,
                    label: '',
                    fieldId: this.draggingFieldType + '_' + (this.fields.length + 1),
                    required: false,
                    placeholder: '',
                    options: [],
                    helpText: ''
                };
                this.fields.push(newField);
                this.selectField(newField);
            }

            this.draggingFieldType = null;
        },

        reorderField(targetIndex) {
            if (this.draggingFieldIndex === null || this.draggingFieldIndex === targetIndex) return;

            const field = this.fields.splice(this.draggingFieldIndex, 1)[0];
            this.fields.splice(targetIndex, 0, field);
            this.draggingFieldIndex = null;
        },

        selectField(field) {
            this.selectedField = field;
            this.optionsText = (field.options || []).join('\n');
        },

        updateField() {
            console.log('Field updated:', this.selectedField);
        },

        updateOptions() {
            if (this.selectedField) {
                this.selectedField.options = this.optionsText.split('\n').filter(o => o.trim());
            }
        },

        duplicateField(index) {
            const original = this.fields[index];
            const copy = {
                ...original,
                id: 'field_' + Date.now(),
                fieldId: original.fieldId + '_copy'
            };
            this.fields.splice(index + 1, 0, copy);
        },

        deleteField(index) {
            if (this.selectedField?.id === this.fields[index].id) {
                this.selectedField = null;
            }
            this.fields.splice(index, 1);
        },

        newTemplate() {
            this.editingTemplateId = null;
            this.templateName = 'New Custom Template';
            this.templateDescription = '';
            this.templateSpecialty = '';
            this.fields = [];
            this.selectedField = null;
            this.showTemplatesModal = false;
        },

        async loadTemplate(template) {
            console.log('Loading template:', template);
            this.editingTemplateId = template.id;
            this.templateName = template.name;
            this.templateDescription = template.description || '';
            this.templateSpecialty = template.specialty || '';
            
            try {
                const response = await fetch(`/emr-builder/templates/${template.id}`);
                const data = await response.json();
                if (data.success) {
                    this.fields = data.template.fields || [];
                }
            } catch (e) {
                console.error('Load error:', e);
            }
            
            this.showTemplatesModal = false;
        },

        async saveTemplate() {
            if (!this.templateName.trim()) {
                alert('Please enter a template name');
                return;
            }

            const payload = {
                name: this.templateName,
                specialty: this.templateSpecialty || null,
                description: this.templateDescription || null,
                fields: this.fields,
                sections: [],
                settings: {}
            };

            try {
                const url = this.editingTemplateId 
                    ? `/emr-builder/templates/${this.editingTemplateId}`
                    : '/emr-builder/templates';
                const method = this.editingTemplateId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (data.success) {
                    alert('Template saved!');
                    if (!this.editingTemplateId) {
                        this.editingTemplateId = data.template_id;
                    }
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                console.error('Save error:', e);
                alert('Failed to save template');
            }
        },

        async duplicateTemplate(id) {
            try {
                const response = await fetch(`/emr-builder/templates/${id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (e) {
                console.error('Duplicate error:', e);
            }
        },

        async deleteTemplate(id) {
            if (!confirm('Delete this template?')) return;

            try {
                const response = await fetch(`/emr-builder/templates/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (e) {
                console.error('Delete error:', e);
            }
        },

        previewTemplate() {
            this.showPreviewModal = true;
        }
    };
}
</script>
@endsection
