import 'dart:developer' as developer;

class Visit {
  final int id;
  final int? appointmentId;
  final int clinicId;
  final int patientId;
  final int doctorId;
  final String? chiefComplaint;
  final String? examinationNotes;
  final String? diagnosis;
  final String? icdCode;
  final String? plan;
  final int? followupInDays;
  final Map<String, dynamic>? emrData;
  final DateTime createdAt;
  final DateTime updatedAt;
  final List<VisitLesion>? lesions;
  final List<VisitScale>? scales;
  final List<VisitProcedure>? procedures;

  Visit({
    required this.id,
    this.appointmentId,
    required this.clinicId,
    required this.patientId,
    required this.doctorId,
    this.chiefComplaint,
    this.examinationNotes,
    this.diagnosis,
    this.icdCode,
    this.plan,
    this.followupInDays,
    this.emrData,
    required this.createdAt,
    required this.updatedAt,
    this.lesions,
    this.scales,
    this.procedures,
  });

  factory Visit.fromJson(Map<String, dynamic> json) {
    developer.log('Visit.fromJson: Parsing visit ${json['id']}', name: 'Visit');

    return Visit(
      id: json['id'],
      appointmentId: json['appointment_id'],
      clinicId: json['clinic_id'],
      patientId: json['patient_id'],
      doctorId: json['doctor_id'],
      chiefComplaint: json['chief_complaint'],
      examinationNotes: json['examination_notes'],
      diagnosis: json['diagnosis'],
      icdCode: json['icd_code'],
      plan: json['plan'],
      followupInDays: json['followup_in_days'],
      emrData: json['emr_data'] != null
          ? Map<String, dynamic>.from(json['emr_data'])
          : null,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      lesions: (json['lesions'] as List<dynamic>?)
          ?.map((l) => VisitLesion.fromJson(l))
          .toList(),
      scales: (json['scales'] as List<dynamic>?)
          ?.map((s) => VisitScale.fromJson(s))
          .toList(),
      procedures: (json['procedures'] as List<dynamic>?)
          ?.map((p) => VisitProcedure.fromJson(p))
          .toList(),
    );
  }

  Map<String, dynamic> toJson() {
    developer.log('Visit.toJson: Converting visit $id to JSON', name: 'Visit');

    return {
      'id': id,
      'appointment_id': appointmentId,
      'clinic_id': clinicId,
      'patient_id': patientId,
      'doctor_id': doctorId,
      'chief_complaint': chiefComplaint,
      'examination_notes': examinationNotes,
      'diagnosis': diagnosis,
      'icd_code': icdCode,
      'plan': plan,
      'followup_in_days': followupInDays,
      'emr_data': emrData,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
      if (lesions != null) 'lesions': lesions!.map((l) => l.toJson()).toList(),
      if (scales != null) 'scales': scales!.map((s) => s.toJson()).toList(),
      if (procedures != null)
        'procedures': procedures!.map((p) => p.toJson()).toList(),
    };
  }
}

class VisitLesion {
  final int id;
  final int visitId;
  final String bodyRegion;
  final String? description;
  final String? sizeCm;
  final String? photoUrl;
  final DateTime createdAt;

  VisitLesion({
    required this.id,
    required this.visitId,
    required this.bodyRegion,
    this.description,
    this.sizeCm,
    this.photoUrl,
    required this.createdAt,
  });

  factory VisitLesion.fromJson(Map<String, dynamic> json) {
    developer.log('VisitLesion.fromJson: Parsing lesion ${json['id']}',
        name: 'VisitLesion');

    return VisitLesion(
      id: json['id'],
      visitId: json['visit_id'],
      bodyRegion: json['body_region'],
      description: json['description'],
      sizeCm: json['size_cm'],
      photoUrl: json['photo_url'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'visit_id': visitId,
      'body_region': bodyRegion,
      'description': description,
      'size_cm': sizeCm,
      'photo_url': photoUrl,
      'created_at': createdAt.toIso8601String(),
    };
  }
}

class VisitScale {
  final int id;
  final int visitId;
  final String scaleName;
  final double score;
  final String? interpretation;
  final DateTime createdAt;

  VisitScale({
    required this.id,
    required this.visitId,
    required this.scaleName,
    required this.score,
    this.interpretation,
    required this.createdAt,
  });

  factory VisitScale.fromJson(Map<String, dynamic> json) {
    developer.log('VisitScale.fromJson: Parsing scale ${json['id']}',
        name: 'VisitScale');

    return VisitScale(
      id: json['id'],
      visitId: json['visit_id'],
      scaleName: json['scale_name'],
      score: (json['score'] as num).toDouble(),
      interpretation: json['interpretation'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'visit_id': visitId,
      'scale_name': scaleName,
      'score': score,
      'interpretation': interpretation,
      'created_at': createdAt.toIso8601String(),
    };
  }
}

class VisitProcedure {
  final int id;
  final int visitId;
  final String procedureName;
  final String? notes;
  final int? performedBy;
  final DateTime createdAt;

  VisitProcedure({
    required this.id,
    required this.visitId,
    required this.procedureName,
    this.notes,
    this.performedBy,
    required this.createdAt,
  });

  factory VisitProcedure.fromJson(Map<String, dynamic> json) {
    developer.log('VisitProcedure.fromJson: Parsing procedure ${json['id']}',
        name: 'VisitProcedure');

    return VisitProcedure(
      id: json['id'],
      visitId: json['visit_id'],
      procedureName: json['procedure_name'],
      notes: json['notes'],
      performedBy: json['performed_by'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'visit_id': visitId,
      'procedure_name': procedureName,
      'notes': notes,
      'performed_by': performedBy,
      'created_at': createdAt.toIso8601String(),
    };
  }
}
