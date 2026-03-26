import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'dart:developer' as developer;

import '../../../core/models/user_model.dart';
import '../../../core/services/api_service.dart';
import '../../../core/services/storage_service.dart';

// Auth state
class AuthState {
  final bool isAuthenticated;
  final bool isLoading;
  final User? user;
  final String? error;

  const AuthState({
    this.isAuthenticated = false,
    this.isLoading = false,
    this.user,
    this.error,
  });

  AuthState copyWith({
    bool? isAuthenticated,
    bool? isLoading,
    User? user,
    String? error,
  }) {
    return AuthState(
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isLoading: isLoading ?? this.isLoading,
      user: user ?? this.user,
      error: error,
    );
  }

  @override
  String toString() {
    return 'AuthState(isAuthenticated: $isAuthenticated, isLoading: $isLoading, user: ${user?.email})';
  }
}

// Auth notifier
class AuthNotifier extends StateNotifier<AuthState> {
  final ApiService _apiService;
  final StorageService _storageService;

  AuthNotifier(this._apiService, this._storageService) : super(const AuthState()) {
    developer.log('AuthNotifier initialized', name: 'AuthNotifier');
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    developer.log('Checking auth status', name: 'AuthNotifier');
    state = state.copyWith(isLoading: true);

    try {
      final token = await _storageService.getToken();
      developer.log('Token exists: ${token != null}', name: 'AuthNotifier');

      if (token != null) {
        // Try to get current user
        final user = await _apiService.getCurrentUser();
        if (user != null) {
          developer.log('User authenticated: ${user.email}', name: 'AuthNotifier');
          state = state.copyWith(
            isAuthenticated: true,
            isLoading: false,
            user: user,
          );
        } else {
          developer.log('Token invalid, clearing', name: 'AuthNotifier');
          await _storageService.clearToken();
          state = state.copyWith(isLoading: false);
        }
      } else {
        state = state.copyWith(isLoading: false);
      }
    } catch (e) {
      developer.log('Auth check error: $e', name: 'AuthNotifier', error: e);
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<bool> login(String email, String password) async {
    developer.log('Login attempt: $email', name: 'AuthNotifier');
    state = state.copyWith(isLoading: true, error: null);

    try {
      final response = await _apiService.login(email, password);
      developer.log('Login response received', name: 'AuthNotifier');

      if (response != null && response['token'] != null) {
        await _storageService.saveToken(response['token']);
        
        final user = User.fromJson(response['user']);
        developer.log('Login successful: ${user.email}', name: 'AuthNotifier');
        
        state = state.copyWith(
          isAuthenticated: true,
          isLoading: false,
          user: user,
        );
        return true;
      } else {
        developer.log('Login failed: no token', name: 'AuthNotifier');
        state = state.copyWith(
          isLoading: false,
          error: 'Invalid credentials',
        );
        return false;
      }
    } catch (e) {
      developer.log('Login error: $e', name: 'AuthNotifier', error: e);
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
      return false;
    }
  }

  Future<bool> register({
    required String clinicName,
    required String name,
    required String email,
    required String phone,
    required String password,
    required String specialty,
  }) async {
    developer.log('Register attempt: $email', name: 'AuthNotifier');
    state = state.copyWith(isLoading: true, error: null);

    try {
      final response = await _apiService.register(
        clinicName: clinicName,
        name: name,
        email: email,
        phone: phone,
        password: password,
        specialty: specialty,
      );
      developer.log('Register response received', name: 'AuthNotifier');

      if (response != null && response['token'] != null) {
        await _storageService.saveToken(response['token']);
        
        final user = User.fromJson(response['user']);
        developer.log('Registration successful: ${user.email}', name: 'AuthNotifier');
        
        state = state.copyWith(
          isAuthenticated: true,
          isLoading: false,
          user: user,
        );
        return true;
      } else {
        developer.log('Registration failed', name: 'AuthNotifier');
        state = state.copyWith(
          isLoading: false,
          error: 'Registration failed',
        );
        return false;
      }
    } catch (e) {
      developer.log('Registration error: $e', name: 'AuthNotifier', error: e);
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
      return false;
    }
  }

  Future<void> logout() async {
    developer.log('Logout', name: 'AuthNotifier');
    
    try {
      await _apiService.logout();
    } catch (e) {
      developer.log('Logout API error (ignored): $e', name: 'AuthNotifier');
    }
    
    await _storageService.clearToken();
    state = const AuthState();
    developer.log('Logged out successfully', name: 'AuthNotifier');
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Providers
final storageServiceProvider = Provider<StorageService>((ref) {
  developer.log('Creating StorageService provider', name: 'Providers');
  return StorageService();
});

final apiServiceProvider = Provider<ApiService>((ref) {
  developer.log('Creating ApiService provider', name: 'Providers');
  final storageService = ref.watch(storageServiceProvider);
  return ApiService(storageService);
});

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  developer.log('Creating AuthNotifier provider', name: 'Providers');
  final apiService = ref.watch(apiServiceProvider);
  final storageService = ref.watch(storageServiceProvider);
  return AuthNotifier(apiService, storageService);
});
