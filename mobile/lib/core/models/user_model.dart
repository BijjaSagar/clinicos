import 'dart:developer' as developer;

class User {
  final int id;
  final int clinicId;
  final String name;
  final String email;
  final String? phone;
  final String role;
  final String? specialty;
  final String? qualification;
  final String? hprId;
  final bool isActive;
  final Clinic? clinic;

  User({
    required this.id,
    required this.clinicId,
    required this.name,
    required this.email,
    this.phone,
    required this.role,
    this.specialty,
    this.qualification,
    this.hprId,
    this.isActive = true,
    this.clinic,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    developer.log('Parsing User from JSON: ${json['email']}', name: 'User');
    
    return User(
      id: json['id'] as int,
      clinicId: json['clinic_id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String?,
      role: json['role'] as String,
      specialty: json['specialty'] as String?,
      qualification: json['qualification'] as String?,
      hprId: json['hpr_id'] as String?,
      isActive: json['is_active'] as bool? ?? true,
      clinic: json['clinic'] != null ? Clinic.fromJson(json['clinic']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    developer.log('Converting User to JSON: $email', name: 'User');
    
    return {
      'id': id,
      'clinic_id': clinicId,
      'name': name,
      'email': email,
      'phone': phone,
      'role': role,
      'specialty': specialty,
      'qualification': qualification,
      'hpr_id': hprId,
      'is_active': isActive,
      'clinic': clinic?.toJson(),
    };
  }

  bool get isDoctor => role == 'doctor';
  bool get isOwner => role == 'owner';
  bool get isReceptionist => role == 'receptionist';

  String get displayName => name;
  String get initials {
    final parts = name.split(' ');
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
  }

  @override
  String toString() => 'User(id: $id, email: $email, role: $role)';
}

class Clinic {
  final int id;
  final String name;
  final String slug;
  final String plan;
  final List<String> specialties;
  final String? gstin;
  final String city;
  final String state;
  final bool isActive;
  final bool abdmM1Live;
  final bool abdmM2Live;
  final DateTime? trialEndsAt;

  Clinic({
    required this.id,
    required this.name,
    required this.slug,
    required this.plan,
    required this.specialties,
    this.gstin,
    required this.city,
    required this.state,
    this.isActive = true,
    this.abdmM1Live = false,
    this.abdmM2Live = false,
    this.trialEndsAt,
  });

  factory Clinic.fromJson(Map<String, dynamic> json) {
    developer.log('Parsing Clinic from JSON: ${json['name']}', name: 'Clinic');
    
    List<String> parseSpecialties(dynamic value) {
      if (value is List) {
        return value.cast<String>();
      }
      if (value is String) {
        return [value];
      }
      return [];
    }
    
    return Clinic(
      id: json['id'] as int,
      name: json['name'] as String,
      slug: json['slug'] as String,
      plan: json['plan'] as String,
      specialties: parseSpecialties(json['specialties']),
      gstin: json['gstin'] as String?,
      city: json['city'] as String? ?? 'Pune',
      state: json['state'] as String? ?? 'Maharashtra',
      isActive: json['is_active'] as bool? ?? true,
      abdmM1Live: json['abdm_m1_live'] as bool? ?? false,
      abdmM2Live: json['abdm_m2_live'] as bool? ?? false,
      trialEndsAt: json['trial_ends_at'] != null 
          ? DateTime.parse(json['trial_ends_at']) 
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    developer.log('Converting Clinic to JSON: $name', name: 'Clinic');
    
    return {
      'id': id,
      'name': name,
      'slug': slug,
      'plan': plan,
      'specialties': specialties,
      'gstin': gstin,
      'city': city,
      'state': state,
      'is_active': isActive,
      'abdm_m1_live': abdmM1Live,
      'abdm_m2_live': abdmM2Live,
      'trial_ends_at': trialEndsAt?.toIso8601String(),
    };
  }

  bool get isTrialActive {
    if (trialEndsAt == null) return false;
    return trialEndsAt!.isAfter(DateTime.now());
  }

  int get daysLeftInTrial {
    if (trialEndsAt == null) return 0;
    return trialEndsAt!.difference(DateTime.now()).inDays;
  }

  @override
  String toString() => 'Clinic(id: $id, name: $name, plan: $plan)';
}
