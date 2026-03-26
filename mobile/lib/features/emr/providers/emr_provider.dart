import 'dart:developer' as developer;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/services/api_service.dart';
import '../services/emr_service.dart';
import '../models/emr_template.dart';
import '../models/visit_model.dart';

// API Service provider (from core)
final apiServiceProvider = Provider<ApiService>((ref) {
  developer.log('apiServiceProvider: Creating ApiService instance',
      name: 'EmrProvider');
  return ApiService();
});

// EMR Service provider
final emrServiceProvider = Provider<EmrService>((ref) {
  developer.log('emrServiceProvider: Creating EmrService instance',
      name: 'EmrProvider');
  final apiService = ref.watch(apiServiceProvider);
  return EmrService(apiService);
});

// EMR Template provider
final emrTemplateProvider =
    FutureProvider.family<EmrTemplate, String>((ref, specialty) async {
  developer.log('emrTemplateProvider: Fetching template for $specialty',
      name: 'EmrProvider');

  final emrService = ref.watch(emrServiceProvider);
  return emrService.getTemplate(specialty);
});

// Visit provider
final visitProvider = FutureProvider.family<Visit, int>((ref, visitId) async {
  developer.log('visitProvider: Fetching visit $visitId', name: 'EmrProvider');

  final emrService = ref.watch(emrServiceProvider);
  return emrService.getVisit(visitId);
});

// Patient visits provider
final patientVisitsProvider =
    FutureProvider.family<List<Visit>, int>((ref, patientId) async {
  developer.log('patientVisitsProvider: Fetching visits for patient $patientId',
      name: 'EmrProvider');

  final emrService = ref.watch(emrServiceProvider);
  return emrService.getPatientVisits(patientId);
});

// EMR Form State
class EmrFormState {
  final bool isLoading;
  final bool isSaving;
  final String? error;
  final Map<String, dynamic> formData;
  final DateTime? lastSaved;

  const EmrFormState({
    this.isLoading = false,
    this.isSaving = false,
    this.error,
    this.formData = const {},
    this.lastSaved,
  });

  EmrFormState copyWith({
    bool? isLoading,
    bool? isSaving,
    String? error,
    Map<String, dynamic>? formData,
    DateTime? lastSaved,
  }) {
    return EmrFormState(
      isLoading: isLoading ?? this.isLoading,
      isSaving: isSaving ?? this.isSaving,
      error: error,
      formData: formData ?? this.formData,
      lastSaved: lastSaved ?? this.lastSaved,
    );
  }
}

// EMR Form Notifier
class EmrFormNotifier extends StateNotifier<EmrFormState> {
  final EmrService _emrService;
  final int? visitId;

  EmrFormNotifier(this._emrService, this.visitId) : super(const EmrFormState()) {
    developer.log('EmrFormNotifier: Initialized with visitId=$visitId',
        name: 'EmrProvider');
    if (visitId != null) {
      _loadVisit();
    }
  }

  Future<void> _loadVisit() async {
    if (visitId == null) return;

    developer.log('EmrFormNotifier._loadVisit: Loading visit $visitId',
        name: 'EmrProvider');

    state = state.copyWith(isLoading: true);

    try {
      final visit = await _emrService.getVisit(visitId!);
      state = state.copyWith(
        isLoading: false,
        formData: {
          'chief_complaint': visit.chiefComplaint,
          'examination_notes': visit.examinationNotes,
          'diagnosis': visit.diagnosis,
          'icd_code': visit.icdCode,
          'plan': visit.plan,
          'followup_in_days': visit.followupInDays,
          ...?visit.emrData,
        },
      );
      developer.log('EmrFormNotifier._loadVisit: Visit loaded successfully',
          name: 'EmrProvider');
    } catch (e) {
      developer.log('EmrFormNotifier._loadVisit: Error - $e',
          name: 'EmrProvider', error: e);
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  void updateField(String fieldName, dynamic value) {
    developer.log('EmrFormNotifier.updateField: $fieldName = $value',
        name: 'EmrProvider');

    final newFormData = Map<String, dynamic>.from(state.formData);
    newFormData[fieldName] = value;
    state = state.copyWith(formData: newFormData);
  }

  void updateMultipleFields(Map<String, dynamic> fields) {
    developer.log('EmrFormNotifier.updateMultipleFields: ${fields.keys.toList()}',
        name: 'EmrProvider');

    final newFormData = Map<String, dynamic>.from(state.formData);
    newFormData.addAll(fields);
    state = state.copyWith(formData: newFormData);
  }

  Future<void> autoSave() async {
    if (visitId == null || state.isSaving) return;

    developer.log('EmrFormNotifier.autoSave: Saving EMR data',
        name: 'EmrProvider');

    state = state.copyWith(isSaving: true);

    try {
      await _emrService.saveEmrData(visitId!, state.formData);
      state = state.copyWith(
        isSaving: false,
        lastSaved: DateTime.now(),
      );
      developer.log('EmrFormNotifier.autoSave: EMR data saved successfully',
          name: 'EmrProvider');
    } catch (e) {
      developer.log('EmrFormNotifier.autoSave: Error - $e',
          name: 'EmrProvider', error: e);
      state = state.copyWith(
        isSaving: false,
        error: e.toString(),
      );
    }
  }

  Future<Visit?> createVisit({
    required int appointmentId,
    required int patientId,
    required int doctorId,
  }) async {
    developer.log('EmrFormNotifier.createVisit: Creating new visit',
        name: 'EmrProvider');

    state = state.copyWith(isSaving: true);

    try {
      final visit = await _emrService.createVisit(
        appointmentId: appointmentId,
        patientId: patientId,
        doctorId: doctorId,
        chiefComplaint: state.formData['chief_complaint'] ?? '',
        examinationNotes: state.formData['examination_notes'],
        diagnosis: state.formData['diagnosis'],
        icdCode: state.formData['icd_code'],
        plan: state.formData['plan'],
        followupInDays: state.formData['followup_in_days'],
        emrData: state.formData,
      );

      state = state.copyWith(
        isSaving: false,
        lastSaved: DateTime.now(),
      );

      developer.log('EmrFormNotifier.createVisit: Visit created with id=${visit.id}',
          name: 'EmrProvider');

      return visit;
    } catch (e) {
      developer.log('EmrFormNotifier.createVisit: Error - $e',
          name: 'EmrProvider', error: e);
      state = state.copyWith(
        isSaving: false,
        error: e.toString(),
      );
      return null;
    }
  }

  // PASI Calculator
  Map<String, dynamic> calculatePasi() {
    developer.log('EmrFormNotifier.calculatePasi: Calculating PASI',
        name: 'EmrProvider');

    final components = <String, Map<String, int>>{
      'head': Map<String, int>.from(state.formData['pasi_head'] ?? {}),
      'trunk': Map<String, int>.from(state.formData['pasi_trunk'] ?? {}),
      'upper_extremities':
          Map<String, int>.from(state.formData['pasi_upper'] ?? {}),
      'lower_extremities':
          Map<String, int>.from(state.formData['pasi_lower'] ?? {}),
    };

    return _emrService.calculatePasi(components: components);
  }

  // DLQI interpretation
  String getDlqiInterpretation(int score) {
    return _emrService.getDlqiInterpretation(score);
  }
}

// EMR Form Provider
final emrFormProvider =
    StateNotifierProvider.family<EmrFormNotifier, EmrFormState, int?>(
        (ref, visitId) {
  developer.log('emrFormProvider: Creating EmrFormNotifier for visitId=$visitId',
      name: 'EmrProvider');

  final emrService = ref.watch(emrServiceProvider);
  return EmrFormNotifier(emrService, visitId);
});

// PASI Score provider (calculated)
final pasiScoreProvider = Provider.family<Map<String, dynamic>?, int?>((ref, visitId) {
  developer.log('pasiScoreProvider: Getting PASI for visitId=$visitId',
      name: 'EmrProvider');

  final formState = ref.watch(emrFormProvider(visitId));
  
  // Check if PASI data exists
  if (formState.formData.containsKey('pasi_head') ||
      formState.formData.containsKey('pasi_trunk')) {
    final notifier = ref.read(emrFormProvider(visitId).notifier);
    return notifier.calculatePasi();
  }
  return null;
});

// Common diagnoses provider (dermatology)
final dermatologyDiagnosesProvider = Provider<List<Map<String, String>>>((ref) {
  developer.log('dermatologyDiagnosesProvider: Loading common diagnoses',
      name: 'EmrProvider');

  return [
    {'code': 'L70.0', 'name': 'Acne vulgaris'},
    {'code': 'L70.1', 'name': 'Acne conglobata'},
    {'code': 'L20.9', 'name': 'Atopic dermatitis'},
    {'code': 'L40.0', 'name': 'Psoriasis vulgaris'},
    {'code': 'L40.1', 'name': 'Generalized pustular psoriasis'},
    {'code': 'L80', 'name': 'Vitiligo'},
    {'code': 'L81.0', 'name': 'Post-inflammatory hyperpigmentation'},
    {'code': 'L81.1', 'name': 'Chloasma/Melasma'},
    {'code': 'B35.0', 'name': 'Tinea capitis'},
    {'code': 'B35.4', 'name': 'Tinea corporis'},
    {'code': 'B35.6', 'name': 'Tinea cruris'},
    {'code': 'B36.0', 'name': 'Pityriasis versicolor'},
    {'code': 'L23.9', 'name': 'Allergic contact dermatitis'},
    {'code': 'L50.0', 'name': 'Allergic urticaria'},
    {'code': 'L57.0', 'name': 'Actinic keratosis'},
    {'code': 'L82', 'name': 'Seborrheic keratosis'},
    {'code': 'L91.0', 'name': 'Keloid'},
    {'code': 'B07', 'name': 'Viral warts'},
    {'code': 'L63.0', 'name': 'Alopecia areata'},
    {'code': 'L64.0', 'name': 'Androgenetic alopecia'},
    {'code': 'L30.3', 'name': 'Infective dermatitis'},
    {'code': 'L43.9', 'name': 'Lichen planus'},
    {'code': 'L42', 'name': 'Pityriasis rosea'},
    {'code': 'L71.0', 'name': 'Rosacea'},
  ];
});

// Common drugs provider (dermatology)
final dermatologyDrugsProvider = Provider<List<Map<String, String>>>((ref) {
  developer.log('dermatologyDrugsProvider: Loading common drugs',
      name: 'EmrProvider');

  return [
    // Topical
    {'name': 'Adapalene 0.1% Gel', 'category': 'Retinoid', 'form': 'gel'},
    {'name': 'Tretinoin 0.025% Cream', 'category': 'Retinoid', 'form': 'cream'},
    {'name': 'Benzoyl Peroxide 2.5% Gel', 'category': 'Anti-acne', 'form': 'gel'},
    {'name': 'Clindamycin 1% Gel', 'category': 'Antibiotic', 'form': 'gel'},
    {'name': 'Mometasone 0.1% Cream', 'category': 'Steroid', 'form': 'cream'},
    {'name': 'Clobetasol 0.05% Ointment', 'category': 'Steroid', 'form': 'ointment'},
    {'name': 'Tacrolimus 0.1% Ointment', 'category': 'Calcineurin Inhibitor', 'form': 'ointment'},
    {'name': 'Hydroquinone 2% Cream', 'category': 'Depigmenting', 'form': 'cream'},
    {'name': 'Ketoconazole 2% Cream', 'category': 'Antifungal', 'form': 'cream'},
    {'name': 'Salicylic Acid 6% Ointment', 'category': 'Keratolytic', 'form': 'ointment'},
    // Systemic
    {'name': 'Doxycycline 100mg', 'category': 'Antibiotic', 'form': 'capsule'},
    {'name': 'Azithromycin 500mg', 'category': 'Antibiotic', 'form': 'tablet'},
    {'name': 'Isotretinoin 20mg', 'category': 'Retinoid', 'form': 'capsule'},
    {'name': 'Methotrexate 7.5mg', 'category': 'DMARD', 'form': 'tablet'},
    {'name': 'Prednisolone 20mg', 'category': 'Steroid', 'form': 'tablet'},
    {'name': 'Hydroxyzine 25mg', 'category': 'Antihistamine', 'form': 'tablet'},
    {'name': 'Cetirizine 10mg', 'category': 'Antihistamine', 'form': 'tablet'},
    {'name': 'Fluconazole 150mg', 'category': 'Antifungal', 'form': 'tablet'},
    {'name': 'Itraconazole 100mg', 'category': 'Antifungal', 'form': 'capsule'},
  ];
});
