import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'dart:developer' as developer;

import '../../features/auth/screens/login_screen.dart';
import '../../features/auth/screens/register_screen.dart';
import '../../features/auth/screens/clinic_setup_screen.dart';
import '../../features/auth/providers/auth_provider.dart';
import '../../features/dashboard/screens/dashboard_screen.dart';
import '../../features/scheduling/screens/schedule_screen.dart';
import '../../features/patients/screens/patient_list_screen.dart';
import '../../features/patients/screens/patient_profile_screen.dart';
import '../../features/emr/screens/emr_screen.dart';
import '../../features/billing/screens/billing_screen.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authProvider);
  
  developer.log('Creating router, auth state: ${authState.isAuthenticated}', name: 'Router');

  return GoRouter(
    initialLocation: '/login',
    debugLogDiagnostics: true,
    redirect: (context, state) {
      final isAuthenticated = authState.isAuthenticated;
      final isAuthRoute = state.matchedLocation == '/login' || 
                          state.matchedLocation == '/register';
      final isSetupRoute = state.matchedLocation == '/setup';

      developer.log(
        'Router redirect check: path=${state.matchedLocation}, auth=$isAuthenticated',
        name: 'Router',
      );

      // If not authenticated and not on auth route, redirect to login
      if (!isAuthenticated && !isAuthRoute) {
        developer.log('Redirecting to /login', name: 'Router');
        return '/login';
      }

      // If authenticated and on auth route (except setup), redirect to dashboard
      if (isAuthenticated && isAuthRoute) {
        developer.log('Redirecting to /dashboard', name: 'Router');
        return '/dashboard';
      }

      return null;
    },
    routes: [
      // Auth routes
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (context, state) {
          developer.log('Building LoginScreen', name: 'Router');
          return const LoginScreen();
        },
      ),
      GoRoute(
        path: '/register',
        name: 'register',
        builder: (context, state) {
          developer.log('Building RegisterScreen', name: 'Router');
          return const RegisterScreen();
        },
      ),
      GoRoute(
        path: '/setup',
        name: 'setup',
        builder: (context, state) {
          developer.log('Building ClinicSetupScreen', name: 'Router');
          return const ClinicSetupScreen();
        },
      ),

      // Main app shell with bottom navigation
      ShellRoute(
        builder: (context, state, child) {
          developer.log('Building MainShell', name: 'Router');
          return MainShell(child: child);
        },
        routes: [
          GoRoute(
            path: '/dashboard',
            name: 'dashboard',
            builder: (context, state) {
              developer.log('Building DashboardScreen', name: 'Router');
              return const DashboardScreen();
            },
          ),
          GoRoute(
            path: '/schedule',
            name: 'schedule',
            builder: (context, state) {
              developer.log('Building ScheduleScreen', name: 'Router');
              return const ScheduleScreen();
            },
          ),
          GoRoute(
            path: '/patients',
            name: 'patients',
            builder: (context, state) {
              developer.log('Building PatientListScreen', name: 'Router');
              return const PatientListScreen();
            },
            routes: [
              // New patient route
              GoRoute(
                path: 'new',
                name: 'new-patient',
                builder: (context, state) {
                  developer.log('Building NewPatientScreen (placeholder)', name: 'Router');
                  return const _PlaceholderScreen(title: 'New Patient');
                },
              ),
              // Patient profile
              GoRoute(
                path: ':id',
                name: 'patient-profile',
                builder: (context, state) {
                  final id = state.pathParameters['id']!;
                  developer.log('Building PatientProfileScreen for id: $id', name: 'Router');
                  
                  // Handle 'new' path specially
                  if (id == 'new') {
                    return const _PlaceholderScreen(title: 'New Patient');
                  }
                  
                  final patientId = int.tryParse(id);
                  if (patientId == null) {
                    developer.log('Invalid patient id: $id', name: 'Router');
                    return const _PlaceholderScreen(title: 'Invalid Patient');
                  }
                  
                  return PatientProfileScreen(patientId: patientId);
                },
                routes: [
                  // EMR routes
                  GoRoute(
                    path: 'emr/new',
                    name: 'new-emr',
                    builder: (context, state) {
                      final patientId = int.parse(state.pathParameters['id']!);
                      developer.log('Building NewEmrScreen for patient: $patientId', name: 'Router');
                      return EmrScreen(patientId: patientId, visitId: null);
                    },
                  ),
                  GoRoute(
                    path: 'emr/:visitId',
                    name: 'emr',
                    builder: (context, state) {
                      final patientId = int.parse(state.pathParameters['id']!);
                      final visitIdStr = state.pathParameters['visitId']!;
                      
                      developer.log('Building EmrScreen for patient: $patientId, visit: $visitIdStr', name: 'Router');
                      
                      // Handle 'new' visit
                      if (visitIdStr == 'new') {
                        return EmrScreen(patientId: patientId, visitId: null);
                      }
                      
                      final visitId = int.tryParse(visitIdStr);
                      return EmrScreen(patientId: patientId, visitId: visitId);
                    },
                  ),
                  // Dental chart
                  GoRoute(
                    path: 'dental',
                    name: 'dental-chart',
                    builder: (context, state) {
                      final patientId = int.parse(state.pathParameters['id']!);
                      developer.log('Building DentalChartScreen for patient: $patientId', name: 'Router');
                      return _PlaceholderScreen(title: 'Dental Chart - Patient $patientId');
                    },
                  ),
                  // Photo vault
                  GoRoute(
                    path: 'photos',
                    name: 'photo-vault',
                    builder: (context, state) {
                      final patientId = int.parse(state.pathParameters['id']!);
                      developer.log('Building PhotoVaultScreen for patient: $patientId', name: 'Router');
                      return _PlaceholderScreen(title: 'Photos - Patient $patientId');
                    },
                  ),
                ],
              ),
            ],
          ),
          GoRoute(
            path: '/billing',
            name: 'billing',
            builder: (context, state) {
              developer.log('Building BillingScreen', name: 'Router');
              return const BillingScreen();
            },
            routes: [
              GoRoute(
                path: ':invoiceId',
                name: 'invoice',
                builder: (context, state) {
                  final invoiceId = int.parse(state.pathParameters['invoiceId']!);
                  developer.log('Building InvoiceScreen for id: $invoiceId', name: 'Router');
                  return _PlaceholderScreen(title: 'Invoice #$invoiceId');
                },
              ),
            ],
          ),
          GoRoute(
            path: '/whatsapp',
            name: 'whatsapp',
            builder: (context, state) {
              developer.log('Building WhatsAppScreen (placeholder)', name: 'Router');
              return const _PlaceholderScreen(title: 'WhatsApp');
            },
          ),
          GoRoute(
            path: '/abdm',
            name: 'abdm',
            builder: (context, state) {
              developer.log('Building AbdmScreen (placeholder)', name: 'Router');
              return const _PlaceholderScreen(title: 'ABDM');
            },
          ),
          GoRoute(
            path: '/analytics',
            name: 'analytics',
            builder: (context, state) {
              developer.log('Building AnalyticsScreen (placeholder)', name: 'Router');
              return const _PlaceholderScreen(title: 'Analytics');
            },
          ),
        ],
      ),
    ],
    errorBuilder: (context, state) {
      developer.log('Router error: ${state.error}', name: 'Router', error: state.error);
      return Scaffold(
        appBar: AppBar(title: const Text('Error')),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 64, color: Colors.red),
              const SizedBox(height: 16),
              Text('Page not found: ${state.matchedLocation}'),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => context.go('/dashboard'),
                child: const Text('Go to Dashboard'),
              ),
            ],
          ),
        ),
      );
    },
  );
});

/// Bottom nav shell
class MainShell extends StatelessWidget {
  const MainShell({super.key, required this.child});
  final Widget child;

  static const _tabs = [
    '/dashboard',
    '/schedule',
    '/patients',
    '/billing',
    '/whatsapp',
  ];

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).uri.path;
    final index = _tabs.indexWhere((t) => location.startsWith(t)).clamp(0, 4);

    developer.log('MainShell build: location=$location, tabIndex=$index', name: 'MainShell');

    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: (i) {
          developer.log('Tab selected: $i -> ${_tabs[i]}', name: 'MainShell');
          context.go(_tabs[i]);
        },
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.grid_view_outlined),
            selectedIcon: Icon(Icons.grid_view_rounded),
            label: 'Dashboard',
          ),
          NavigationDestination(
            icon: Icon(Icons.calendar_month_outlined),
            selectedIcon: Icon(Icons.calendar_month_rounded),
            label: 'Schedule',
          ),
          NavigationDestination(
            icon: Icon(Icons.people_outline_rounded),
            selectedIcon: Icon(Icons.people_rounded),
            label: 'Patients',
          ),
          NavigationDestination(
            icon: Icon(Icons.receipt_long_outlined),
            selectedIcon: Icon(Icons.receipt_long_rounded),
            label: 'Billing',
          ),
          NavigationDestination(
            icon: Icon(Icons.chat_outlined),
            selectedIcon: Icon(Icons.chat_rounded),
            label: 'WhatsApp',
          ),
        ],
      ),
    );
  }
}

/// Placeholder screen for routes not yet implemented
class _PlaceholderScreen extends StatelessWidget {
  const _PlaceholderScreen({required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    developer.log('Building PlaceholderScreen: $title', name: 'PlaceholderScreen');
    
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.construction_rounded,
              size: 64,
              color: Theme.of(context).colorScheme.primary,
            ),
            const SizedBox(height: 16),
            Text(
              title,
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 8),
            Text(
              'Coming soon',
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
