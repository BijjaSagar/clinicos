import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';

// ── Data Models ───────────────────────────────────────────────────────────────

enum AppointmentStatus { confirmed, waiting, newPatient, blocked }

class Appointment {
  const Appointment({
    required this.id,
    required this.visitId,
    required this.patientId,
    required this.patientName,
    required this.visitType,
    required this.status,
    required this.startHour,
    required this.startMinute,
    required this.durationMinutes,
    required this.initials,
    this.doctor = 'Dr. Priya Sharma',
  });

  final int id;
  final int visitId;
  final int patientId;
  final String patientName;
  final String visitType;
  final AppointmentStatus status;
  final int startHour;
  final int startMinute;
  final int durationMinutes;
  final String initials;
  final String doctor;

  String get timeLabel {
    final start = TimeOfDay(hour: startHour, minute: startMinute);
    final endMin = startHour * 60 + startMinute + durationMinutes;
    final end = TimeOfDay(hour: endMin ~/ 60, minute: endMin % 60);
    String fmt(TimeOfDay t) =>
        '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';
    return '${fmt(start)} – ${fmt(end)}';
  }
}

final _sampleAppointments = <Appointment>[
  const Appointment(
    id: 1, visitId: 101, patientId: 1,
    patientName: 'Priya Mehta', visitType: 'Follow-up · Chem Peel',
    status: AppointmentStatus.confirmed,
    startHour: 8, startMinute: 30, durationMinutes: 20, initials: 'PM',
  ),
  const Appointment(
    id: 2, visitId: 102, patientId: 2,
    patientName: 'Rajesh Kumar', visitType: 'LASER Session #2',
    status: AppointmentStatus.waiting,
    startHour: 9, startMinute: 0, durationMinutes: 30, initials: 'RK',
  ),
  const Appointment(
    id: 3, visitId: 103, patientId: 3,
    patientName: 'Ananya Patil', visitType: 'New · Psoriasis',
    status: AppointmentStatus.newPatient,
    startHour: 9, startMinute: 45, durationMinutes: 45, initials: 'AP',
  ),
  const Appointment(
    id: 4, visitId: 104, patientId: 4,
    patientName: 'Vikram Shah', visitType: 'PRP · Hair Loss',
    status: AppointmentStatus.confirmed,
    startHour: 11, startMinute: 0, durationMinutes: 30, initials: 'VS',
  ),
  const Appointment(
    id: 5, visitId: 105, patientId: 5,
    patientName: 'Meera Kapoor', visitType: 'Follow-up · Acne',
    status: AppointmentStatus.confirmed,
    startHour: 12, startMinute: 0, durationMinutes: 20, initials: 'MK',
  ),
  const Appointment(
    id: 6, visitId: 106, patientId: 6,
    patientName: 'Lunch Break', visitType: 'Blocked',
    status: AppointmentStatus.blocked,
    startHour: 13, startMinute: 0, durationMinutes: 60, initials: '—',
  ),
  const Appointment(
    id: 7, visitId: 107, patientId: 7,
    patientName: 'Arjun Nair', visitType: 'New · Eczema',
    status: AppointmentStatus.newPatient,
    startHour: 14, startMinute: 30, durationMinutes: 30, initials: 'AN',
  ),
  const Appointment(
    id: 8, visitId: 108, patientId: 8,
    patientName: 'Sunita Reddy', visitType: 'Procedure · Mole Removal',
    status: AppointmentStatus.confirmed,
    startHour: 16, startMinute: 0, durationMinutes: 45, initials: 'SR',
  ),
];

// ── Providers ─────────────────────────────────────────────────────────────────

final _selectedDayProvider = StateProvider<DateTime>((ref) {
  final now = DateTime.now();
  return DateTime(now.year, now.month, now.day);
});

final _selectedAppointmentProvider = StateProvider<Appointment?>((ref) => null);

// ── Main Screen ───────────────────────────────────────────────────────────────

class ScheduleScreen extends ConsumerWidget {
  const ScheduleScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final selectedDay = ref.watch(_selectedDayProvider);
    final selectedAppt = ref.watch(_selectedAppointmentProvider);

    return Scaffold(
      backgroundColor: AppTheme.surface,
      body: Column(
        children: [
          _WeekHeader(selectedDay: selectedDay),
          _DayTabBar(selectedDay: selectedDay),
          Expanded(
            child: Stack(
              children: [
                _TimeSlotGrid(selectedDay: selectedDay),
                if (selectedAppt != null)
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: _AppointmentDetailSheet(appt: selectedAppt),
                  ),
              ],
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showBookingSheet(context),
        backgroundColor: AppTheme.blue,
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: const Text(
          'Book Appointment',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
        ),
      ),
    );
  }
}

// ── Week Header ───────────────────────────────────────────────────────────────

class _WeekHeader extends ConsumerWidget {
  const _WeekHeader({required this.selectedDay});
  final DateTime selectedDay;

  DateTime _startOfWeek(DateTime d) {
    final monday = d.subtract(Duration(days: d.weekday - 1));
    return DateTime(monday.year, monday.month, monday.day);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final weekStart = _startOfWeek(selectedDay);
    final weekEnd = weekStart.add(const Duration(days: 6));
    final rangeLabel =
        '${weekStart.day}–${weekEnd.day} ${DateFormat('MMM yyyy').format(weekEnd)}';
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);

    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(8, 48, 8, 8),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.chevron_left_rounded, color: AppTheme.dark),
            onPressed: () {
              ref.read(_selectedDayProvider.notifier).state =
                  selectedDay.subtract(const Duration(days: 7));
            },
          ),
          Expanded(
            child: Column(
              children: [
                Text(
                  'Schedule',
                  style: const TextStyle(
                    fontFamily: 'Sora',
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.dark,
                  ),
                ),
                Text(
                  rangeLabel,
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF9CA3AF),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          TextButton(
            onPressed: () {
              ref.read(_selectedDayProvider.notifier).state = today;
            },
            style: TextButton.styleFrom(
              foregroundColor: AppTheme.blue,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
                side: const BorderSide(color: AppTheme.blue, width: 1),
              ),
            ),
            child: const Text('Today', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
          ),
          IconButton(
            icon: const Icon(Icons.chevron_right_rounded, color: AppTheme.dark),
            onPressed: () {
              ref.read(_selectedDayProvider.notifier).state =
                  selectedDay.add(const Duration(days: 7));
            },
          ),
        ],
      ),
    );
  }
}

// ── Day Tab Bar ───────────────────────────────────────────────────────────────

class _DayTabBar extends ConsumerWidget {
  const _DayTabBar({required this.selectedDay});
  final DateTime selectedDay;

  DateTime _startOfWeek(DateTime d) {
    final monday = d.subtract(Duration(days: d.weekday - 1));
    return DateTime(monday.year, monday.month, monday.day);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final weekStart = _startOfWeek(selectedDay);
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    return Container(
      color: Colors.white,
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: List.generate(7, (i) {
          final day = weekStart.add(Duration(days: i));
          final isSelected =
              day.year == selectedDay.year &&
              day.month == selectedDay.month &&
              day.day == selectedDay.day;
          final isToday =
              day.year == today.year &&
              day.month == today.month &&
              day.day == today.day;

          return Expanded(
            child: GestureDetector(
              onTap: () {
                ref.read(_selectedDayProvider.notifier).state = day;
                ref.read(_selectedAppointmentProvider.notifier).state = null;
              },
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    dayLabels[i],
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w500,
                      color: isSelected ? AppTheme.blue : const Color(0xFF9CA3AF),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: isSelected ? AppTheme.blue : Colors.transparent,
                      shape: BoxShape.circle,
                    ),
                    child: Center(
                      child: Text(
                        '${day.day}',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: isSelected ? Colors.white : AppTheme.dark,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 3),
                  if (isToday)
                    Container(
                      width: 4,
                      height: 4,
                      decoration: BoxDecoration(
                        color: isSelected ? Colors.white : AppTheme.blue,
                        shape: BoxShape.circle,
                      ),
                    )
                  else
                    const SizedBox(height: 4),
                ],
              ),
            ),
          );
        }),
      ),
    );
  }
}

// ── Time Slot Grid ────────────────────────────────────────────────────────────

class _TimeSlotGrid extends ConsumerWidget {
  const _TimeSlotGrid({required this.selectedDay});
  final DateTime selectedDay;

  static const double _hourHeight = 72.0;
  static const int _startHour = 8;
  static const int _endHour = 20;

  Color _statusColor(AppointmentStatus status) => switch (status) {
    AppointmentStatus.confirmed  => AppTheme.green,
    AppointmentStatus.waiting    => AppTheme.amber,
    AppointmentStatus.newPatient => AppTheme.blue,
    AppointmentStatus.blocked    => const Color(0xFF9CA3AF),
  };

  Color _statusBg(AppointmentStatus status) => switch (status) {
    AppointmentStatus.confirmed  => AppTheme.greenLight,
    AppointmentStatus.waiting    => const Color(0xFFFFF8EC),
    AppointmentStatus.newPatient => AppTheme.blueLight,
    AppointmentStatus.blocked    => const Color(0xFFF3F4F6),
  };

  String _statusLabel(AppointmentStatus status) => switch (status) {
    AppointmentStatus.confirmed  => 'Confirmed',
    AppointmentStatus.waiting    => 'Waiting',
    AppointmentStatus.newPatient => 'New',
    AppointmentStatus.blocked    => 'Blocked',
  };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final isToday = selectedDay == today;
    final totalHeight = (_endHour - _startHour) * _hourHeight;

    // Only show appointments for "today" (Mon 24 Mar 2026 in demo)
    final appointments = isToday ? _sampleAppointments : <Appointment>[];

    return SingleChildScrollView(
      padding: const EdgeInsets.only(bottom: 120),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(0, 8, 16, 0),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Time labels column
            SizedBox(
              width: 52,
              height: totalHeight,
              child: Stack(
                children: List.generate(_endHour - _startHour, (i) {
                  final hour = _startHour + i;
                  return Positioned(
                    top: i * _hourHeight - 8,
                    left: 0,
                    right: 0,
                    child: Text(
                      hour < 12
                          ? '$hour am'
                          : hour == 12
                              ? '12 pm'
                              : '${hour - 12} pm',
                      textAlign: TextAlign.right,
                      style: const TextStyle(
                        fontSize: 10,
                        color: Color(0xFF9CA3AF),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  );
                }),
              ),
            ),
            // Grid + appointments column
            Expanded(
              child: SizedBox(
                height: totalHeight,
                child: Stack(
                  children: [
                    // Hour lines
                    ...List.generate(_endHour - _startHour, (i) => Positioned(
                      top: i * _hourHeight,
                      left: 0,
                      right: 0,
                      child: Container(
                        height: 1,
                        color: const Color(0xFFE5E7EB),
                      ),
                    )),
                    // Current time indicator
                    if (isToday && now.hour >= _startHour && now.hour < _endHour)
                      Positioned(
                        top: (now.hour - _startHour) * _hourHeight +
                            (now.minute / 60) * _hourHeight -
                            1,
                        left: 0,
                        right: 0,
                        child: Row(
                          children: [
                            Container(
                              width: 8,
                              height: 8,
                              decoration: const BoxDecoration(
                                color: AppTheme.red,
                                shape: BoxShape.circle,
                              ),
                            ),
                            Expanded(
                              child: Container(height: 1.5, color: AppTheme.red),
                            ),
                          ],
                        ),
                      ),
                    // Appointment blocks
                    ...appointments.map((appt) {
                      final topOffset = (appt.startHour - _startHour) * _hourHeight +
                          (appt.startMinute / 60) * _hourHeight;
                      final blockHeight =
                          (appt.durationMinutes / 60) * _hourHeight;
                      final color = _statusColor(appt.status);
                      final bg = _statusBg(appt.status);

                      return Positioned(
                        top: topOffset + 2,
                        left: 4,
                        right: 0,
                        height: blockHeight - 4,
                        child: GestureDetector(
                          onTap: () {
                            if (appt.status == AppointmentStatus.blocked) return;
                            ref
                                .read(_selectedAppointmentProvider.notifier)
                                .state = appt;
                          },
                          child: Container(
                            decoration: BoxDecoration(
                              color: bg,
                              borderRadius: BorderRadius.circular(8),
                              border: Border(
                                left: BorderSide(color: color, width: 3),
                              ),
                            ),
                            padding: const EdgeInsets.fromLTRB(8, 5, 8, 5),
                            child: blockHeight < 36
                                ? Text(
                                    appt.patientName,
                                    style: TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600,
                                      color: color,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  )
                                : Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Text(
                                        appt.patientName,
                                        style: TextStyle(
                                          fontFamily: 'Sora',
                                          fontSize: 11,
                                          fontWeight: FontWeight.w700,
                                          color: color,
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      if (blockHeight > 50)
                                        Text(
                                          appt.visitType,
                                          style: const TextStyle(
                                            fontSize: 10,
                                            color: Color(0xFF6B7280),
                                          ),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      if (blockHeight > 64)
                                        const SizedBox(height: 2),
                                      if (blockHeight > 64)
                                        _StatusPill(
                                          label: _statusLabel(appt.status),
                                          color: color,
                                        ),
                                    ],
                                  ),
                          ),
                        ),
                      );
                    }),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.label, required this.color});
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.15),
        borderRadius: BorderRadius.circular(100),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 9,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}

// ── Appointment Detail Sheet ──────────────────────────────────────────────────

class _AppointmentDetailSheet extends ConsumerWidget {
  const _AppointmentDetailSheet({required this.appt});
  final Appointment appt;

  Color _statusColor(AppointmentStatus status) => switch (status) {
    AppointmentStatus.confirmed  => AppTheme.green,
    AppointmentStatus.waiting    => AppTheme.amber,
    AppointmentStatus.newPatient => AppTheme.blue,
    AppointmentStatus.blocked    => const Color(0xFF9CA3AF),
  };

  String _statusLabel(AppointmentStatus status) => switch (status) {
    AppointmentStatus.confirmed  => 'Confirmed',
    AppointmentStatus.waiting    => 'Waiting',
    AppointmentStatus.newPatient => 'New Patient',
    AppointmentStatus.blocked    => 'Blocked',
  };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final color = _statusColor(appt.status);

    return Container(
      margin: const EdgeInsets.fromLTRB(12, 0, 12, 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE5E7EB)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: color.withOpacity(0.15),
                child: Text(
                  appt.initials,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: color,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      appt.patientName,
                      style: const TextStyle(
                        fontFamily: 'Sora',
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.dark,
                      ),
                    ),
                    Text(
                      appt.visitType,
                      style: const TextStyle(fontSize: 12, color: Color(0xFF9CA3AF)),
                    ),
                  ],
                ),
              ),
              GestureDetector(
                onTap: () =>
                    ref.read(_selectedAppointmentProvider.notifier).state = null,
                child: const Icon(Icons.close_rounded, color: Color(0xFF9CA3AF), size: 20),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              _DetailChip(icon: Icons.access_time_rounded, label: appt.timeLabel),
              const SizedBox(width: 8),
              _DetailChip(icon: Icons.person_rounded, label: appt.doctor),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(100),
                ),
                child: Text(
                  _statusLabel(appt.status),
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: color,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () =>
                      context.go('/patients/${appt.patientId}/emr/${appt.visitId}'),
                  icon: const Icon(Icons.play_arrow_rounded, size: 18),
                  label: const Text('Start Consultation'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppTheme.blue,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 11),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              OutlinedButton(
                onPressed: () {},
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppTheme.amber,
                  side: const BorderSide(color: AppTheme.amber, width: 1.5),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: const Text('Reschedule',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
              ),
              const SizedBox(width: 8),
              OutlinedButton(
                onPressed: () {},
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppTheme.red,
                  side: const BorderSide(color: AppTheme.red, width: 1.5),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                child: const Text('Cancel',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _DetailChip extends StatelessWidget {
  const _DetailChip({required this.icon, required this.label});
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: const Color(0xFF6B7280)),
          const SizedBox(width: 4),
          Text(label,
              style: const TextStyle(
                  fontSize: 11, color: Color(0xFF6B7280), fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}

// ── Book Appointment Bottom Sheet ─────────────────────────────────────────────

void _showBookingSheet(BuildContext context) {
  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => const _BookingSheet(),
  );
}

class _BookingSheet extends StatefulWidget {
  const _BookingSheet();

  @override
  State<_BookingSheet> createState() => _BookingSheetState();
}

class _BookingSheetState extends State<_BookingSheet> {
  final _searchController = TextEditingController();
  String _visitType = 'Follow-up';
  int _duration = 30;
  DateTime _selectedDate = DateTime.now();
  TimeOfDay _selectedTime = const TimeOfDay(hour: 10, minute: 0);

  final _visitTypes = ['New', 'Follow-up', 'Procedure', 'Emergency'];
  final _durations = [15, 20, 30, 45, 60];

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  String get _dateLabel => DateFormat('EEE, d MMM yyyy').format(_selectedDate);
  String get _timeLabel =>
      '${_selectedTime.hour.toString().padLeft(2, '0')}:${_selectedTime.minute.toString().padLeft(2, '0')}';

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(8, 0, 8, 8),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.all(Radius.circular(20)),
      ),
      padding: EdgeInsets.fromLTRB(
          20, 20, 20, MediaQuery.of(context).viewInsets.bottom + 20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Handle
          Center(
            child: Container(
              width: 36,
              height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFFE5E7EB),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 16),
          const Text(
            'Book Appointment',
            style: TextStyle(
              fontFamily: 'Sora',
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppTheme.dark,
            ),
          ),
          const SizedBox(height: 18),
          // Patient search
          TextField(
            controller: _searchController,
            decoration: const InputDecoration(
              hintText: 'Search patients, ABHA ID...',
              prefixIcon: Icon(Icons.search_rounded, size: 18, color: Color(0xFF9CA3AF)),
              hintStyle: TextStyle(fontSize: 13, color: Color(0xFF9CA3AF)),
            ),
          ),
          const SizedBox(height: 14),
          // Date & Time row
          Row(
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _selectedDate,
                      firstDate: DateTime.now(),
                      lastDate: DateTime.now().add(const Duration(days: 90)),
                      builder: (ctx, child) => Theme(
                        data: Theme.of(ctx).copyWith(
                          colorScheme: const ColorScheme.light(primary: AppTheme.blue),
                        ),
                        child: child!,
                      ),
                    );
                    if (picked != null) setState(() => _selectedDate = picked);
                  },
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    decoration: BoxDecoration(
                      border: Border.all(color: const Color(0xFFE5E7EB)),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_today_rounded,
                            size: 14, color: AppTheme.blue),
                        const SizedBox(width: 8),
                        Text(_dateLabel,
                            style: const TextStyle(
                                fontSize: 13, fontWeight: FontWeight.w500)),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              GestureDetector(
                onTap: () async {
                  final picked = await showTimePicker(
                    context: context,
                    initialTime: _selectedTime,
                    builder: (ctx, child) => Theme(
                      data: Theme.of(ctx).copyWith(
                        colorScheme: const ColorScheme.light(primary: AppTheme.blue),
                      ),
                      child: child!,
                    ),
                  );
                  if (picked != null) setState(() => _selectedTime = picked);
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: BoxDecoration(
                    border: Border.all(color: const Color(0xFFE5E7EB)),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.access_time_rounded,
                          size: 14, color: AppTheme.blue),
                      const SizedBox(width: 8),
                      Text(_timeLabel,
                          style: const TextStyle(
                              fontSize: 13, fontWeight: FontWeight.w500)),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          // Visit type
          const Text('Visit Type',
              style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF374151))),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: _visitTypes.map((type) {
              final selected = _visitType == type;
              return ChoiceChip(
                label: Text(type),
                selected: selected,
                onSelected: (_) => setState(() => _visitType = type),
                selectedColor: AppTheme.blueLight,
                labelStyle: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: selected ? AppTheme.blue : const Color(0xFF374151),
                ),
                backgroundColor: AppTheme.surface,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(100),
                  side: BorderSide(
                    color: selected ? AppTheme.blue : const Color(0xFFE5E7EB),
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 14),
          // Duration
          const Text('Duration',
              style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF374151))),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: _durations.map((d) {
              final selected = _duration == d;
              return ChoiceChip(
                label: Text('${d}m'),
                selected: selected,
                onSelected: (_) => setState(() => _duration = d),
                selectedColor: AppTheme.blueLight,
                labelStyle: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: selected ? AppTheme.blue : const Color(0xFF374151),
                ),
                backgroundColor: AppTheme.surface,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(100),
                  side: BorderSide(
                    color: selected ? AppTheme.blue : const Color(0xFFE5E7EB),
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => Navigator.pop(context),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.blue,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: const Text(
                'Confirm Booking',
                style: TextStyle(
                    fontSize: 15, fontWeight: FontWeight.w700, fontFamily: 'Sora'),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
