import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'dart:developer' as developer;

class StorageService {
  static const _tokenKey = 'auth_token';
  static const _userKey = 'user_data';
  static const _clinicKey = 'clinic_data';

  final FlutterSecureStorage _storage;

  StorageService() : _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
  ) {
    developer.log('StorageService initialized', name: 'StorageService');
  }

  // Token management
  Future<void> saveToken(String token) async {
    developer.log('Saving auth token', name: 'StorageService');
    await _storage.write(key: _tokenKey, value: token);
    developer.log('Auth token saved successfully', name: 'StorageService');
  }

  Future<String?> getToken() async {
    developer.log('Getting auth token', name: 'StorageService');
    final token = await _storage.read(key: _tokenKey);
    developer.log('Token exists: ${token != null}', name: 'StorageService');
    return token;
  }

  Future<void> clearToken() async {
    developer.log('Clearing auth token', name: 'StorageService');
    await _storage.delete(key: _tokenKey);
    developer.log('Auth token cleared', name: 'StorageService');
  }

  // User data management
  Future<void> saveUserData(String userData) async {
    developer.log('Saving user data', name: 'StorageService');
    await _storage.write(key: _userKey, value: userData);
  }

  Future<String?> getUserData() async {
    developer.log('Getting user data', name: 'StorageService');
    return await _storage.read(key: _userKey);
  }

  Future<void> clearUserData() async {
    developer.log('Clearing user data', name: 'StorageService');
    await _storage.delete(key: _userKey);
  }

  // Clinic data management
  Future<void> saveClinicData(String clinicData) async {
    developer.log('Saving clinic data', name: 'StorageService');
    await _storage.write(key: _clinicKey, value: clinicData);
  }

  Future<String?> getClinicData() async {
    developer.log('Getting clinic data', name: 'StorageService');
    return await _storage.read(key: _clinicKey);
  }

  // Clear all data
  Future<void> clearAll() async {
    developer.log('Clearing all stored data', name: 'StorageService');
    await _storage.deleteAll();
    developer.log('All data cleared', name: 'StorageService');
  }

  // Check if logged in
  Future<bool> isLoggedIn() async {
    final token = await getToken();
    developer.log('Is logged in: ${token != null}', name: 'StorageService');
    return token != null;
  }
}
