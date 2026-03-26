import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'dart:developer' as developer;

class ClinicSetupScreen extends ConsumerStatefulWidget {
  const ClinicSetupScreen({super.key});

  @override
  ConsumerState<ClinicSetupScreen> createState() => _ClinicSetupScreenState();
}

class _ClinicSetupScreenState extends ConsumerState<ClinicSetupScreen> {
  int _currentStep = 0;
  bool _isLoading = false;

  // Step 1: Basic info
  final _addressController = TextEditingController();
  final _cityController = TextEditingController(text: 'Pune');
  final _pincodeController = TextEditingController();
  final _gstinController = TextEditingController();

  // Step 2: Rooms
  final List<Map<String, dynamic>> _rooms = [];

  // Step 3: Services
  final List<Map<String, dynamic>> _services = [];

  @override
  void initState() {
    super.initState();
    developer.log('ClinicSetupScreen initialized', name: 'ClinicSetupScreen');
  }

  @override
  void dispose() {
    developer.log('ClinicSetupScreen disposed', name: 'ClinicSetupScreen');
    _addressController.dispose();
    _cityController.dispose();
    _pincodeController.dispose();
    _gstinController.dispose();
    super.dispose();
  }

  Future<void> _completeSetup() async {
    developer.log('Completing clinic setup', name: 'ClinicSetupScreen');
    setState(() => _isLoading = true);

    try {
      // TODO: Save clinic setup to API
      await Future.delayed(const Duration(seconds: 1));

      if (mounted) {
        developer.log('Setup complete, navigating to dashboard', name: 'ClinicSetupScreen');
        context.go('/dashboard');
      }
    } catch (e) {
      developer.log('Setup error: $e', name: 'ClinicSetupScreen', error: e);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _addRoom() {
    developer.log('Adding room dialog', name: 'ClinicSetupScreen');
    showDialog(
      context: context,
      builder: (context) {
        final nameController = TextEditingController();
        String selectedType = 'consultation';

        return AlertDialog(
          title: const Text('Add Room'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nameController,
                decoration: const InputDecoration(
                  labelText: 'Room Name',
                  hintText: 'e.g., Consultation Room 1',
                ),
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                value: selectedType,
                decoration: const InputDecoration(labelText: 'Room Type'),
                items: const [
                  DropdownMenuItem(value: 'consultation', child: Text('Consultation')),
                  DropdownMenuItem(value: 'procedure', child: Text('Procedure')),
                  DropdownMenuItem(value: 'laser', child: Text('Laser')),
                  DropdownMenuItem(value: 'physio', child: Text('Physiotherapy')),
                  DropdownMenuItem(value: 'dental', child: Text('Dental')),
                ],
                onChanged: (value) => selectedType = value!,
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () {
                if (nameController.text.isNotEmpty) {
                  setState(() {
                    _rooms.add({
                      'name': nameController.text,
                      'type': selectedType,
                    });
                  });
                  developer.log('Room added: ${nameController.text}', name: 'ClinicSetupScreen');
                  Navigator.pop(context);
                }
              },
              child: const Text('Add'),
            ),
          ],
        );
      },
    );
  }

  void _addService() {
    developer.log('Adding service dialog', name: 'ClinicSetupScreen');
    showDialog(
      context: context,
      builder: (context) {
        final nameController = TextEditingController();
        final durationController = TextEditingController(text: '15');
        final priceController = TextEditingController();

        return AlertDialog(
          title: const Text('Add Service'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nameController,
                decoration: const InputDecoration(
                  labelText: 'Service Name',
                  hintText: 'e.g., Consultation, LASER Session',
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: durationController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Duration (minutes)',
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: priceController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Price (₹)',
                  prefixText: '₹ ',
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () {
                if (nameController.text.isNotEmpty) {
                  setState(() {
                    _services.add({
                      'name': nameController.text,
                      'duration': int.tryParse(durationController.text) ?? 15,
                      'price': double.tryParse(priceController.text) ?? 0,
                    });
                  });
                  developer.log('Service added: ${nameController.text}', name: 'ClinicSetupScreen');
                  Navigator.pop(context);
                }
              },
              child: const Text('Add'),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    developer.log('Building ClinicSetupScreen, step: $_currentStep', name: 'ClinicSetupScreen');
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Clinic Setup'),
        actions: [
          TextButton(
            onPressed: () {
              developer.log('Skip setup', name: 'ClinicSetupScreen');
              context.go('/dashboard');
            },
            child: const Text('Skip for now'),
          ),
        ],
      ),
      body: Stepper(
        currentStep: _currentStep,
        onStepContinue: () {
          if (_currentStep < 2) {
            setState(() => _currentStep++);
          } else {
            _completeSetup();
          }
        },
        onStepCancel: () {
          if (_currentStep > 0) {
            setState(() => _currentStep--);
          }
        },
        controlsBuilder: (context, details) {
          return Padding(
            padding: const EdgeInsets.only(top: 16),
            child: Row(
              children: [
                FilledButton(
                  onPressed: _isLoading ? null : details.onStepContinue,
                  child: _isLoading && _currentStep == 2
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Text(_currentStep == 2 ? 'Complete Setup' : 'Continue'),
                ),
                const SizedBox(width: 12),
                if (_currentStep > 0)
                  TextButton(
                    onPressed: details.onStepCancel,
                    child: const Text('Back'),
                  ),
              ],
            ),
          );
        },
        steps: [
          // Step 1: Clinic Info
          Step(
            title: const Text('Clinic Information'),
            subtitle: const Text('Address and tax details'),
            isActive: _currentStep >= 0,
            state: _currentStep > 0 ? StepState.complete : StepState.indexed,
            content: Column(
              children: [
                TextField(
                  controller: _addressController,
                  maxLines: 2,
                  decoration: InputDecoration(
                    labelText: 'Address',
                    hintText: 'Shop No. 101, ABC Tower...',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: TextField(
                        controller: _cityController,
                        decoration: InputDecoration(
                          labelText: 'City',
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextField(
                        controller: _pincodeController,
                        keyboardType: TextInputType.number,
                        maxLength: 6,
                        decoration: InputDecoration(
                          labelText: 'PIN Code',
                          counterText: '',
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: _gstinController,
                  textCapitalization: TextCapitalization.characters,
                  decoration: InputDecoration(
                    labelText: 'GSTIN (Optional)',
                    hintText: '27XXXXX1234X1Z5',
                    helperText: 'Required for GST billing',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Step 2: Rooms
          Step(
            title: const Text('Rooms'),
            subtitle: Text('${_rooms.length} rooms added'),
            isActive: _currentStep >= 1,
            state: _currentStep > 1 ? StepState.complete : StepState.indexed,
            content: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Add rooms for scheduling (optional)',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 16),
                if (_rooms.isNotEmpty) ...[
                  ...List.generate(_rooms.length, (index) {
                    final room = _rooms[index];
                    return Card(
                      child: ListTile(
                        leading: const Icon(Icons.meeting_room_outlined),
                        title: Text(room['name']),
                        subtitle: Text(room['type']),
                        trailing: IconButton(
                          icon: const Icon(Icons.delete_outline),
                          onPressed: () {
                            setState(() => _rooms.removeAt(index));
                          },
                        ),
                      ),
                    );
                  }),
                  const SizedBox(height: 8),
                ],
                OutlinedButton.icon(
                  onPressed: _addRoom,
                  icon: const Icon(Icons.add),
                  label: const Text('Add Room'),
                ),
              ],
            ),
          ),

          // Step 3: Services
          Step(
            title: const Text('Services'),
            subtitle: Text('${_services.length} services added'),
            isActive: _currentStep >= 2,
            state: StepState.indexed,
            content: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Add services you offer (optional)',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 16),
                if (_services.isNotEmpty) ...[
                  ...List.generate(_services.length, (index) {
                    final service = _services[index];
                    return Card(
                      child: ListTile(
                        leading: const Icon(Icons.medical_services_outlined),
                        title: Text(service['name']),
                        subtitle: Text('${service['duration']} min • ₹${service['price']}'),
                        trailing: IconButton(
                          icon: const Icon(Icons.delete_outline),
                          onPressed: () {
                            setState(() => _services.removeAt(index));
                          },
                        ),
                      ),
                    );
                  }),
                  const SizedBox(height: 8),
                ],
                OutlinedButton.icon(
                  onPressed: _addService,
                  icon: const Icon(Icons.add),
                  label: const Text('Add Service'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
