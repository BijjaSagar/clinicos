<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Custom EMR Builder Controller
 * 
 * No-code EMR template builder for clinics to create custom forms:
 * - Drag & drop field builder
 * - Field types: text, number, select, checkbox, date, scale, etc.
 * - Conditional visibility rules
 * - Section grouping
 * - Template versioning
 */
class CustomEmrBuilderController extends Controller
{
    private array $fieldTypes = [
        'text' => ['label' => 'Text Input', 'icon' => '📝'],
        'textarea' => ['label' => 'Long Text', 'icon' => '📄'],
        'number' => ['label' => 'Number', 'icon' => '🔢'],
        'select' => ['label' => 'Dropdown', 'icon' => '📋'],
        'multiselect' => ['label' => 'Multi-Select', 'icon' => '☑️'],
        'checkbox' => ['label' => 'Checkbox', 'icon' => '✅'],
        'radio' => ['label' => 'Radio Buttons', 'icon' => '🔘'],
        'date' => ['label' => 'Date', 'icon' => '📅'],
        'time' => ['label' => 'Time', 'icon' => '🕐'],
        'datetime' => ['label' => 'Date & Time', 'icon' => '📆'],
        'scale' => ['label' => 'Scale (0-10)', 'icon' => '📊'],
        'slider' => ['label' => 'Slider', 'icon' => '🎚️'],
        'file' => ['label' => 'File Upload', 'icon' => '📎'],
        'image' => ['label' => 'Image Upload', 'icon' => '🖼️'],
        'signature' => ['label' => 'Signature', 'icon' => '✍️'],
        'heading' => ['label' => 'Section Heading', 'icon' => '📌'],
        'divider' => ['label' => 'Divider', 'icon' => '➖'],
        'richtext' => ['label' => 'Rich Text Editor', 'icon' => '📰'],
        'table' => ['label' => 'Data Table', 'icon' => '📊'],
        'bodymap' => ['label' => 'Body Diagram', 'icon' => '🧍'],
    ];

    /**
     * Show EMR builder interface
     */
    public function index(): View
    {
        Log::info('CustomEmrBuilderController: Loading builder');

        $clinicId = auth()->user()->clinic_id;

        $templates = collect();
        $emrBuilderSchemaReady = Schema::hasTable('custom_emr_templates');

        try {
            if ($emrBuilderSchemaReady) {
                $templates = DB::table('custom_emr_templates')
                    ->where('clinic_id', $clinicId)
                    ->orderBy('name')
                    ->get();
            } else {
                Log::warning('CustomEmrBuilderController: custom_emr_templates missing — run migrations', [
                    'clinic_id' => $clinicId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('CustomEmrBuilderController: index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return view('emr.builder', [
            'templates' => $templates,
            'fieldTypes' => $this->fieldTypes,
            'emrBuilderSchemaReady' => $emrBuilderSchemaReady,
        ]);
    }

    /**
     * Get all templates
     */
    public function getTemplates(): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $templates = DB::table('custom_emr_templates')
            ->where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'specialty' => $t->specialty,
                'description' => $t->description,
                'field_count' => count(json_decode($t->fields, true) ?? []),
                'is_active' => $t->is_active,
                'version' => $t->version,
                'updated_at' => $t->updated_at,
            ]);

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Get template details
     */
    public function getTemplate(int $templateId): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $template = DB::table('custom_emr_templates')
            ->where('id', $templateId)
            ->where('clinic_id', $clinicId)
            ->first();

        if (!$template) {
            return response()->json(['success' => false, 'error' => 'Template not found'], 404);
        }

        return response()->json([
            'success' => true,
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'specialty' => $template->specialty,
                'description' => $template->description,
                'fields' => json_decode($template->fields, true) ?? [],
                'sections' => json_decode($template->sections, true) ?? [],
                'settings' => json_decode($template->settings, true) ?? [],
                'is_active' => $template->is_active,
                'version' => $template->version,
            ],
        ]);
    }

    /**
     * Create a new template
     */
    public function createTemplate(Request $request): JsonResponse
    {
        Log::info('CustomEmrBuilderController: Creating template');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'specialty' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'fields' => 'required|array',
            'sections' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $clinicId = auth()->user()->clinic_id;

        try {
            $templateId = DB::table('custom_emr_templates')->insertGetId([
                'clinic_id' => $clinicId,
                'name' => $validated['name'],
                'specialty' => $validated['specialty'] ?? null,
                'description' => $validated['description'] ?? null,
                'fields' => json_encode($validated['fields']),
                'sections' => json_encode($validated['sections'] ?? []),
                'settings' => json_encode($validated['settings'] ?? []),
                'is_active' => true,
                'version' => 1,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('CustomEmrBuilderController: Template created', ['template_id' => $templateId]);

            return response()->json([
                'success' => true,
                'template_id' => $templateId,
                'message' => 'Template created successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('CustomEmrBuilderController: Create error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a template
     */
    public function updateTemplate(Request $request, int $templateId): JsonResponse
    {
        Log::info('CustomEmrBuilderController: Updating template', ['template_id' => $templateId]);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'specialty' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'fields' => 'sometimes|array',
            'sections' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $clinicId = auth()->user()->clinic_id;

        try {
            $template = DB::table('custom_emr_templates')
                ->where('id', $templateId)
                ->where('clinic_id', $clinicId)
                ->first();

            if (!$template) {
                return response()->json(['success' => false, 'error' => 'Template not found'], 404);
            }

            $updateData = [
                'updated_at' => now(),
                'version' => $template->version + 1,
            ];

            if (isset($validated['name'])) $updateData['name'] = $validated['name'];
            if (isset($validated['specialty'])) $updateData['specialty'] = $validated['specialty'];
            if (isset($validated['description'])) $updateData['description'] = $validated['description'];
            if (isset($validated['fields'])) $updateData['fields'] = json_encode($validated['fields']);
            if (isset($validated['sections'])) $updateData['sections'] = json_encode($validated['sections']);
            if (isset($validated['settings'])) $updateData['settings'] = json_encode($validated['settings']);
            if (isset($validated['is_active'])) $updateData['is_active'] = $validated['is_active'];

            DB::table('custom_emr_templates')
                ->where('id', $templateId)
                ->update($updateData);

            Log::info('CustomEmrBuilderController: Template updated', ['template_id' => $templateId]);

            return response()->json([
                'success' => true,
                'message' => 'Template updated',
                'version' => $updateData['version'],
            ]);
        } catch (\Throwable $e) {
            Log::error('CustomEmrBuilderController: Update error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a template
     */
    public function deleteTemplate(int $templateId): JsonResponse
    {
        Log::info('CustomEmrBuilderController: Deleting template', ['template_id' => $templateId]);

        $clinicId = auth()->user()->clinic_id;

        try {
            $deleted = DB::table('custom_emr_templates')
                ->where('id', $templateId)
                ->where('clinic_id', $clinicId)
                ->delete();

            if (!$deleted) {
                return response()->json(['success' => false, 'error' => 'Template not found'], 404);
            }

            Log::info('CustomEmrBuilderController: Template deleted', ['template_id' => $templateId]);

            return response()->json([
                'success' => true,
                'message' => 'Template deleted',
            ]);
        } catch (\Throwable $e) {
            Log::error('CustomEmrBuilderController: Delete error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Duplicate a template
     */
    public function duplicateTemplate(int $templateId): JsonResponse
    {
        Log::info('CustomEmrBuilderController: Duplicating template', ['template_id' => $templateId]);

        $clinicId = auth()->user()->clinic_id;

        try {
            $template = DB::table('custom_emr_templates')
                ->where('id', $templateId)
                ->where('clinic_id', $clinicId)
                ->first();

            if (!$template) {
                return response()->json(['success' => false, 'error' => 'Template not found'], 404);
            }

            $newId = DB::table('custom_emr_templates')->insertGetId([
                'clinic_id' => $clinicId,
                'name' => $template->name . ' (Copy)',
                'specialty' => $template->specialty,
                'description' => $template->description,
                'fields' => $template->fields,
                'sections' => $template->sections,
                'settings' => $template->settings,
                'is_active' => false,
                'version' => 1,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('CustomEmrBuilderController: Template duplicated', ['new_id' => $newId]);

            return response()->json([
                'success' => true,
                'template_id' => $newId,
                'message' => 'Template duplicated',
            ]);
        } catch (\Throwable $e) {
            Log::error('CustomEmrBuilderController: Duplicate error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get field types
     */
    public function getFieldTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'field_types' => $this->fieldTypes,
        ]);
    }

    /**
     * Export template as JSON
     */
    public function exportTemplate(int $templateId): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        $template = DB::table('custom_emr_templates')
            ->where('id', $templateId)
            ->where('clinic_id', $clinicId)
            ->first();

        if (!$template) {
            return response()->json(['success' => false, 'error' => 'Template not found'], 404);
        }

        return response()->json([
            'success' => true,
            'export' => [
                'name' => $template->name,
                'specialty' => $template->specialty,
                'description' => $template->description,
                'fields' => json_decode($template->fields, true),
                'sections' => json_decode($template->sections, true),
                'settings' => json_decode($template->settings, true),
                'version' => $template->version,
                'exported_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Import template from JSON
     */
    public function importTemplate(Request $request): JsonResponse
    {
        Log::info('CustomEmrBuilderController: Importing template');

        $validated = $request->validate([
            'template' => 'required|array',
            'template.name' => 'required|string|max:100',
            'template.fields' => 'required|array',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $import = $validated['template'];

        try {
            $templateId = DB::table('custom_emr_templates')->insertGetId([
                'clinic_id' => $clinicId,
                'name' => $import['name'] . ' (Imported)',
                'specialty' => $import['specialty'] ?? null,
                'description' => $import['description'] ?? null,
                'fields' => json_encode($import['fields']),
                'sections' => json_encode($import['sections'] ?? []),
                'settings' => json_encode($import['settings'] ?? []),
                'is_active' => false,
                'version' => 1,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('CustomEmrBuilderController: Template imported', ['template_id' => $templateId]);

            return response()->json([
                'success' => true,
                'template_id' => $templateId,
                'message' => 'Template imported successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('CustomEmrBuilderController: Import error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
