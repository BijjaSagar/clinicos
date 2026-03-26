import 'package:dio/dio.dart';
import 'dart:developer' as developer;

import '../models/user_model.dart';
import 'storage_service.dart';

class ApiService {
  static const String baseUrl = 'https://api.clinicos.in/api/v1';
  
  final Dio _dio;
  final StorageService _storageService;

  ApiService(this._storageService) : _dio = Dio(BaseOptions(
    baseUrl: baseUrl,
    connectTimeout: const Duration(seconds: 30),
    receiveTimeout: const Duration(seconds: 30),
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  )) {
    developer.log('ApiService initialized with baseUrl: $baseUrl', name: 'ApiService');
    _setupInterceptors();
  }

  void _setupInterceptors() {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          developer.log('API Request: ${options.method} ${options.path}', name: 'ApiService');
          developer.log('Request data: ${options.data}', name: 'ApiService');

          // Add auth token if available
          final token = await _storageService.getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
            developer.log('Added auth token to request', name: 'ApiService');
          }

          return handler.next(options);
        },
        onResponse: (response, handler) {
          developer.log(
            'API Response: ${response.statusCode} ${response.requestOptions.path}',
            name: 'ApiService',
          );
          developer.log('Response data: ${response.data}', name: 'ApiService');
          return handler.next(response);
        },
        onError: (error, handler) {
          developer.log(
            'API Error: ${error.response?.statusCode} ${error.requestOptions.path}',
            name: 'ApiService',
            error: error,
          );
          developer.log('Error response: ${error.response?.data}', name: 'ApiService');

          // Handle 401 - Unauthorized
          if (error.response?.statusCode == 401) {
            developer.log('Unauthorized - clearing token', name: 'ApiService');
            _storageService.clearToken();
          }

          return handler.next(error);
        },
      ),
    );
  }

  // Auth endpoints
  Future<Map<String, dynamic>?> login(String email, String password) async {
    developer.log('Login API call: $email', name: 'ApiService');
    
    try {
      final response = await _dio.post('/auth/login', data: {
        'email': email,
        'password': password,
      });

      developer.log('Login successful', name: 'ApiService');
      return response.data;
    } on DioException catch (e) {
      developer.log('Login failed: ${e.message}', name: 'ApiService', error: e);
      throw _handleError(e);
    }
  }

  Future<Map<String, dynamic>?> register({
    required String clinicName,
    required String name,
    required String email,
    required String phone,
    required String password,
    required String specialty,
  }) async {
    developer.log('Register API call: $email', name: 'ApiService');
    
    // Generate slug from clinic name
    final slug = clinicName
        .toLowerCase()
        .replaceAll(RegExp(r'[^a-z0-9]'), '-')
        .replaceAll(RegExp(r'-+'), '-')
        .replaceAll(RegExp(r'^-|-$'), '');
    
    try {
      final response = await _dio.post('/auth/register', data: {
        'clinic_name': clinicName,
        'clinic_slug': slug,
        'specialties': [specialty],
        'name': name,
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': password,
      });

      developer.log('Registration successful', name: 'ApiService');
      return response.data;
    } on DioException catch (e) {
      developer.log('Registration failed: ${e.message}', name: 'ApiService', error: e);
      throw _handleError(e);
    }
  }

  Future<void> logout() async {
    developer.log('Logout API call', name: 'ApiService');
    
    try {
      await _dio.post('/auth/logout');
      developer.log('Logout successful', name: 'ApiService');
    } on DioException catch (e) {
      developer.log('Logout failed: ${e.message}', name: 'ApiService', error: e);
      // Don't throw - we still want to clear local state
    }
  }

  Future<User?> getCurrentUser() async {
    developer.log('Get current user API call', name: 'ApiService');
    
    try {
      final response = await _dio.get('/auth/me');
      developer.log('Got current user', name: 'ApiService');
      return User.fromJson(response.data['user']);
    } on DioException catch (e) {
      developer.log('Get current user failed: ${e.message}', name: 'ApiService', error: e);
      return null;
    }
  }

  // Patient endpoints
  Future<List<Map<String, dynamic>>> getPatients({
    String? search,
    int page = 1,
    int perPage = 20,
  }) async {
    developer.log('Get patients API call', name: 'ApiService');
    
    try {
      final response = await _dio.get('/patients', queryParameters: {
        if (search != null) 'search': search,
        'page': page,
        'per_page': perPage,
      });
      
      developer.log('Got patients: ${response.data['data']?.length ?? 0}', name: 'ApiService');
      return List<Map<String, dynamic>>.from(response.data['data'] ?? []);
    } on DioException catch (e) {
      developer.log('Get patients failed: ${e.message}', name: 'ApiService', error: e);
      throw _handleError(e);
    }
  }

  Future<Map<String, dynamic>> getPatient(int id) async {
    developer.log('Get patient API call: $id', name: 'ApiService');
    
    try {
      final response = await _dio.get('/patients/$id');
      developer.log('Got patient', name: 'ApiService');
      return response.data['patient'];
    } on DioException catch (e) {
      developer.log('Get patient failed: ${e.message}', name: 'ApiService', error: e);
      throw _handleError(e);
    }
  }

  // Appointment endpoints
  Future<List<Map<String, dynamic>>> getAppointments({
    String? date,
    int? doctorId,
  }) async {
    developer.log('Get appointments API call', name: 'ApiService');
    
    try {
      final response = await _dio.get('/appointments', queryParameters: {
        if (date != null) 'date': date,
        if (doctorId != null) 'doctor_id': doctorId,
      });
      
      developer.log('Got appointments: ${response.data['appointments']?.length ?? 0}', name: 'ApiService');
      return List<Map<String, dynamic>>.from(response.data['appointments'] ?? []);
    } on DioException catch (e) {
      developer.log('Get appointments failed: ${e.message}', name: 'ApiService', error: e);
      throw _handleError(e);
    }
  }

  // Invoice endpoints
  Future<List<Map<String, dynamic>>> getInvoices({
    String? status,
    int page = 1,
  }) async {
    developer.log('Get invoices API call', name: 'ApiService');
    
    try {
      final response = await _dio.get('/invoices', queryParameters: {
        if (status != null) 'status': status,
        'page': page,
      });
      
      developer.log('Got invoices: ${response.data['data']?.length ?? 0}', name: 'ApiService');
      return List<Map<String, dynamic>>.from(response.data['data'] ?? []);
    } on DioException catch (e) {
      developer.log('Get invoices failed: ${e.message}', name: 'ApiService', error: e);
      throw _handleError(e);
    }
  }

  // Analytics endpoints
  Future<Map<String, dynamic>> getDashboard() async {
    developer.log('Get dashboard API call', name: 'ApiService');
    
    try {
      final response = await _dio.get('/analytics/dashboard');
      developer.log('Got dashboard data', name: 'ApiService');
      return response.data['dashboard'];
    } on DioException catch (e) {
      developer.log('Get dashboard failed: ${e.message}', name: 'ApiService', error: e);
      throw _handleError(e);
    }
  }

  // Error handling
  Exception _handleError(DioException e) {
    developer.log('Handling error: ${e.type}', name: 'ApiService');

    if (e.response != null) {
      final data = e.response!.data;
      if (data is Map && data['message'] != null) {
        return Exception(data['message']);
      }
      if (e.response!.statusCode == 422 && data is Map && data['errors'] != null) {
        final errors = data['errors'] as Map;
        final firstError = errors.values.first;
        if (firstError is List && firstError.isNotEmpty) {
          return Exception(firstError.first);
        }
      }
    }

    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return Exception('Connection timeout. Please check your internet.');
      case DioExceptionType.connectionError:
        return Exception('No internet connection.');
      default:
        return Exception('Something went wrong. Please try again.');
    }
  }
}
