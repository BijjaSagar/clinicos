import 'package:flutter/material.dart';

/// ClinicOS Design System
/// Primary: Blue #1447E6  |  Accent: Teal #0891B2  |  Success: Green #059669
class AppTheme {
  AppTheme._();

  // ── Brand Colors ──────────────────────────────────────────────────────────
  static const blue       = Color(0xFF1447E6);
  static const blueDark   = Color(0xFF0F35B8);
  static const blueLight  = Color(0xFFEFF3FF);
  static const teal       = Color(0xFF0891B2);
  static const tealLight  = Color(0xFFE0F2FE);
  static const green      = Color(0xFF059669);
  static const greenLight = Color(0xFFECFDF5);
  static const amber      = Color(0xFFD97706);
  static const red        = Color(0xFFDC2626);
  static const dark       = Color(0xFF0D1117);
  static const dark2      = Color(0xFF161B27);
  static const surface    = Color(0xFFF3F4F6);

  static ThemeData get light => ThemeData(
    useMaterial3: true,
    fontFamily: 'Inter',
    colorScheme: ColorScheme.fromSeed(
      seedColor: blue,
      brightness: Brightness.light,
      primary: blue,
      secondary: teal,
      surface: Colors.white,
      surfaceContainerHighest: surface,
      error: red,
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.white,
      foregroundColor: dark,
      elevation: 0,
      scrolledUnderElevation: 1,
      centerTitle: false,
      titleTextStyle: TextStyle(
        fontFamily: 'Sora',
        fontSize: 18,
        fontWeight: FontWeight.w700,
        color: dark,
      ),
    ),
    cardTheme: CardTheme(
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Color(0xFFE5E7EB)),
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: blue,
        foregroundColor: Colors.white,
        elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        textStyle: const TextStyle(
          fontFamily: 'Inter',
          fontSize: 14,
          fontWeight: FontWeight.w600,
        ),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: blue,
        side: const BorderSide(color: blue, width: 1.5),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFFE5E7EB)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: Color(0xFFE5E7EB)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: blue, width: 1.5),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      labelStyle: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 13),
    ),
    bottomNavigationBarTheme: const BottomNavigationBarThemeData(
      backgroundColor: Colors.white,
      selectedItemColor: blue,
      unselectedItemColor: Color(0xFF9CA3AF),
      elevation: 8,
      type: BottomNavigationBarType.fixed,
      selectedLabelStyle: TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
      unselectedLabelStyle: TextStyle(fontSize: 11),
    ),
    chipTheme: ChipThemeData(
      backgroundColor: surface,
      selectedColor: blueLight,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(100)),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      labelStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
    ),
    dividerTheme: const DividerThemeData(color: Color(0xFFE5E7EB), thickness: 1),
    scaffoldBackgroundColor: surface,
  );

  static ThemeData get dark => ThemeData(
    useMaterial3: true,
    fontFamily: 'Inter',
    brightness: Brightness.dark,
    scaffoldBackgroundColor: dark,
    colorScheme: ColorScheme.fromSeed(
      seedColor: blue,
      brightness: Brightness.dark,
      primary: blue,
      surface: dark2,
    ),
  );
}
