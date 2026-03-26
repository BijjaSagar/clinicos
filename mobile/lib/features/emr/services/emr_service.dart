import 'dart:developer' as developer;
import 'package:dio/dio.dart';
import '../../../core/services/api_service.dart';
import '../models/emr_template.dart';
import '../models/visit_model.dart';

class EmrService {
  final ApiService _apiService;

  EmrService(this._apiService);

  /// Get EMR template for a specialty
  Future<EmrTemplate> getTemplate(String specialty) async {
    developer.log('EmrService.getTemplate: Fetching template for $specialty',
        name: 'EmrService');

    try {
      final response = await _apiService.dio.get(
        '/emr/templates/$specialty',
      );

      developer.log(
          'EmrService.getTemplate: Template fetched successfully',
          name: 'EmrService');

      return EmrTemplate.fromJson(response.data['data']);
    } on DioException catch (e) {
      developer.log(
          'EmrService.getTemplate: DioException - ${e.message}',
          name: 'EmrService',
          error: e);
      rethrow;
    } catch (e) {
      developer.log('EmrService.getTemplate: Error - $e',
          name: 'EmrService', error: e);
      rethrow;
    }
  }

  /// Get visit details
  Future<Visit> getVisit(int visitId) async {
    developer.log('EmrService.getVisit: Fetching visit $visitId',
        name: 'EmrService');

    try {
      final response = await _apiService.dio.get('/visits/$visitId');

      developer.log('EmrService.getVisit: Visit fetched successfully',
          name: 'EmrService');

      return Visit.fromJson(response.data['data']);
    } catch (e) {
      developer.log('EmrService.getVisit: Error - $e',
          name: 'EmrService', error: e);
      rethrow;
    }
  }

  /// Create a new visit
  Future<Visit> createVisit({
    required int appointmentId,
    required int patientId,
    required int doctorId,
    required String chiefComplaint,
    String? examinationNotes,
    String? diagnosis,
    String? icdCode,
    String? plan,
    int? followupInDays,
    Map<String, dynamic>? emrData,
  }) async {
    developer.log(
        'EmrService.createVisit: Creating visit for appointment $appointmentId',
        name: 'EmrService');

    try {
      final response = await _apiService.dio.post('/visits', data: {
        'appointment_id': appointmentId,
        'patient_id': patientId,
        'doctor_id': doctorId,
        'chief_complaint': chiefComplaint,
        'examination_notes': examinationNotes,
        'diagnosis': diagnosis,
        'icd_code': icdCode,
        'plan': plan,
        'followup_in_days': followupInDays,
        'emr_data': emrData,
      });

      developer.log('EmrService.createVisit: Visit created successfully',
          name: 'EmrService');

      return Visit.fromJson(response.data['data']);
    } catch (e) {
      developer.log('EmrService.createVisit: Error - $e',
          name: 'EmrService', error: e);
      rethrow;
    }
  }

  /// Update visit
  Future<Visit> updateVisit(int visitId, Map<String, dynamic> data) async {
    developer.log('EmrService.updateVisit: Updating visit $visitId',
        name: 'EmrService');

    try {
      final response = await _apiService.dio.put('/visits/$visitId', data: data);

      developer.log('EmrService.updateVisit: Visit updated successfully',
          name: 'EmrService');

      return Visit.fromJson(response.data['data']);
    } catch (e) {
      developer.log('EmrService.updateVisit: Error - $e',
          name: 'EmrService', error: e);
      rethrow;
    }
  }

  /// Save EMR data (auto-save)
  Future<void> saveEmrData(int visitId, Map<String, dynamic> emrData) async {
    developer.log('EmrService.saveEmrData: Saving EMR data for visit $visitId',
        name: 'EmrService');

    try {
      await _apiService.dio.patch('/visits/$visitId/emr', data: {
        'emr_data': emrData,
      });

      developer.log('EmrService.saveEmrData: EMR data saved successfully',
          name: 'EmrService');
    } catch (e) {
      developer.log('EmrService.saveEmrData: Error - $e',
          name: 'EmrService', error: e);
      rethrow;
    }
  }

  /// Add lesion to visit
  Future<void> addLesion({
    required int visitId,
    required String bodyRegion,
    required String description,
    String? sizeCm,
    String? photoUrl,
  }) async {
    developer.log('EmrService.addLesion: Adding lesion to visit $visitId',
        name: 'EmrService');

    try {
      await _apiService.dio.post('/visits/$visitId/lesions', data: {
        'body_region': bodyRegion,
        'description': description,
        'size_cm': sizeCm,
        'photo_url': photoUrl,
      });

      developer.log('EmrService.addLesion: Lesion added successfully',
          name: 'EmrService');
    } catch (e) {
      developer.log('EmrService.addLesion: Error - $e',
          name: 'EmrService', error: e);
      rethrow;
    }
  }

  /// Add scale score to visit
  Future<void> addScale({
    required int visitId,
    required String scaleName,
    required double score,
    String? interpretation,
  }) async {
    developer.log('EmrService.addScale: Adding $scaleName scale to visit $visitId',
        name: 'EmrService');

    try {
      await _apiService.dio.post('/visits/$visitId/scales', data: {
        'scale_name': scaleName,
        'score': score,
        'interpretation': interpretation,
      });

      developer.log('EmrService.addScale: Scale added successfully',
          name: 'EmrService');
    } catch (e) {
      developer.log('EmrService.addScale: Error - $e',
          name: 'EmrService', error: e);
      rethrow;
    }
  }

  /// Add procedure to visit
  Future<void> addProcedure({
    required int visitId,
    required String procedureName,
    String? notes,
    int? performedBy,
  }) async {
    developer.log(
        'EmrService.addProcedure: Adding procedure to visit $visitId',
        name: 'EmrService');

    try {
      await _apiService.dio.post('/visits/$visitId/procedures', data: {
        'procedure_name': procedureName,
        'notes': notes,
        'performed_by': performedBy,
      });

      developer.log('EmrService.addProcedure: Procedure added successfully',
          name: 'EmrService');
    } catch (e) {
      developer.log('EmrService.addProcedure: Error - $e',
          name: 'EmrService', error: e);
      rethrow;
    }
  }

  /// Get patient visit history
  Future<List<Visit>> getPatientVisits(int patientId) async {
    developer.log(
        'EmrService.getPatientVisits: Fetching visits for patient $patientId',
        name: 'EmrService');

    try {
      final response = await _apiService.dio.get(
        '/patients/$patientId/visits',
      );

      developer.log('EmrService.getPatientVisits: Visits fetched successfully',
          name: 'EmrService');

      final List<dynamic> data = response.data['data'] ?? [];
      return data.map((json) => Visit.fromJson(json)).toList();
    } catch (e) {
      developer.log('EmrService.getPatientVisits: Error - $e',
          name: 'EmrService', error: e);
      rethrow;
    }
  }

  /// Calculate PASI score
  Map<String, dynamic> calculatePasi({
    required Map<String, Map<String, int>> components,
  }) {
    developer.log('EmrService.calculatePasi: Calculating PASI score',
        name: 'EmrService');

    const areaWeights = {
      'head': 0.1,
      'trunk': 0.3,
      'upper_extremities': 0.2,
      'lower_extremities': 0.4,
    };

    double totalScore = 0;
    final regionScores = <String, double>{};

    components.forEach((region, data) {
      final area = data['area'] ?? 0;
      final erythema = data['erythema'] ?? 0;
      final induration = data['induration'] ?? 0;
      final desquamation = data['desquamation'] ?? 0;

      final regionScore = (erythema + induration + desquamation) *
          area *
          (areaWeights[region] ?? 0);
      regionScores[region] = regionScore;
      totalScore += regionScore;
    });

    String interpretation;
    if (totalScore == 0) {
      interpretation = 'Clear';
    } else if (totalScore <= 3) {
      interpretation = 'Mild';
    } else if (totalScore <= 10) {
      interpretation = 'Moderate';
    } else if (totalScore <= 20) {
      interpretation = 'Severe';
    } else {
      interpretation = 'Very Severe';
    }

    developer.log(
        'EmrService.calculatePasi: Score=$totalScore, Interpretation=$interpretation',
        name: 'EmrService');

    return {
      'total': totalScore,
      'regions': regionScores,
      'interpretation': interpretation,
    };
  }

  /// Get DLQI interpretation
  String getDlqiInterpretation(int score) {
    developer.log('EmrService.getDlqiInterpretation: Score=$score',
        name: 'EmrService');

    if (score <= 1) {
      return "No effect on patient's life";
    } else if (score <= 5) {
      return "Small effect on patient's life";
    } else if (score <= 10) {
      return "Moderate effect on patient's life";
    } else if (score <= 20) {
      return "Very large effect on patient's life";
    } else {
      return "Extremely large effect on patient's life";
    }
  }
}
