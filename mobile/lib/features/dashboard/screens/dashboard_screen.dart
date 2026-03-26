import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      backgroundColor: AppTheme.surface,
      body: CustomScrollView(
        slivers: [
          _buildAppBar(context),
          SliverPadding(
            padding: const EdgeInsets.all(16),
            sliver: SliverList(
              delegate: SliverChildListDelegate([
                _AbdmStatusBanner(),
                const SizedBox(height: 16),
                _StatsRow(),
                const SizedBox(height: 16),
                _TodayScheduleCard(),
                const SizedBox(height: 16),
                _AiSuggestionsCard(),
                const SizedBox(height: 16),
                _WhatsAppActivityCard(),
                const SizedBox(height: 80),
              ]),
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.go('/patients'),
        backgroundColor: AppTheme.blue,
        icon: const Icon(Icons.person_add_rounded, color: Colors.white),
        label: const Text('New Patient',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
      ),
    );
  }

  SliverAppBar _buildAppBar(BuildContext context) {
    return SliverAppBar(
      floating: true,
      snap: true,
      backgroundColor: Colors.white,
      elevation: 0,
      title: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Good morning 👋', style: TextStyle(fontFamily: 'Sora', fontSize: 16, fontWeight: FontWeight.w700, color: AppTheme.dark)),
          Text('Dr. Priya Sharma · Thu 27 Mar',
              style: TextStyle(fontSize: 12, color: Colors.grey[500], fontWeight: FontWeight.w400)),
        ],
      ),
      actions: [
        IconButton(
          icon: Stack(children: [
            const Icon(Icons.notifications_outlined, color: AppTheme.dark),
            Positioned(right: 0, top: 0, child: Container(
              width: 8, height: 8, decoration: const BoxDecoration(color: AppTheme.red, shape: BoxShape.circle),
            )),
          ]),
          onPressed: () {},
        ),
        Padding(
          padding: const EdgeInsets.only(right: 12),
          child: CircleAvatar(
            radius: 18,
            backgroundColor: AppTheme.teal,
            child: const Text('PS', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12)),
          ),
        ),
      ],
    );
  }
}

class _AbdmStatusBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: [Color(0xFF0D1117), Color(0xFF0D1F3C)]),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Container(
            width: 38, height: 38,
            decoration: BoxDecoration(
              color: Colors.blue.withOpacity(.2),
              border: Border.all(color: Colors.blue.withOpacity(.3)),
              borderRadius: BorderRadius.circular(9),
            ),
            child: const Icon(Icons.shield_rounded, color: Color(0xFF93C5FD), size: 18),
          ),
          const SizedBox(width: 12),
          const Expanded(child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('ABDM Compliance Active', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w700)),
              Text('ABHA live · HFR registered · 38 records synced this month',
                  style: TextStyle(color: Color(0xFF64748B), fontSize: 11)),
            ],
          )),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: AppTheme.green.withOpacity(.15),
              borderRadius: BorderRadius.circular(100),
            ),
            child: const Text('M1 ✓', style: TextStyle(color: Color(0xFF6EE7B7), fontSize: 11, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }
}

class _StatsRow extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _StatCard(label: "Today's Patients", value: '14', delta: '+3', positive: true),
        const SizedBox(width: 10),
        _StatCard(label: "Today's Revenue", value: '₹18.4K', delta: '+12%', positive: true),
        const SizedBox(width: 10),
        _StatCard(label: 'Pending Dues', value: '₹9.2K', delta: '3 inv.', positive: false),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.label, required this.value, required this.delta, required this.positive});
  final String label, value, delta;
  final bool positive;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: const Color(0xFFE5E7EB)),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(fontSize: 11, color: Color(0xFF9CA3AF), fontWeight: FontWeight.w500)),
            const SizedBox(height: 6),
            Text(value, style: const TextStyle(fontFamily: 'Sora', fontSize: 20, fontWeight: FontWeight.w800, color: AppTheme.dark)),
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
              decoration: BoxDecoration(
                color: positive ? AppTheme.greenLight : const Color(0xFFFFF1F2),
                borderRadius: BorderRadius.circular(100),
              ),
              child: Text(delta, style: TextStyle(
                fontSize: 10, fontWeight: FontWeight.w600,
                color: positive ? AppTheme.green : AppTheme.red,
              )),
            ),
          ],
        ),
      ),
    );
  }
}

class _TodayScheduleCard extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final appointments = [
      {'time': '10:30', 'name': 'Priya Mehta', 'type': 'Follow-up #4 · Chem Peel', 'status': 'in', 'initials': 'PM'},
      {'time': '10:50', 'name': 'Rajesh Kumar', 'type': 'LASER Session #2', 'status': 'waiting', 'initials': 'RK'},
      {'time': '11:15', 'name': 'Ananya Patil', 'type': 'New Patient · Psoriasis', 'status': 'confirmed', 'initials': 'AP'},
      {'time': '11:40', 'name': 'Vikram Shah', 'type': 'PRP Session · Hair Loss', 'status': 'confirmed', 'initials': 'VS'},
    ];

    return Card(
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
            child: Row(
              children: [
                const Text("Today's Schedule", style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppTheme.dark)),
                const Spacer(),
                TextButton(
                  onPressed: () => context.go('/schedule'),
                  child: const Text('Full calendar →', style: TextStyle(fontSize: 12, color: AppTheme.blue)),
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          ...appointments.map((a) => _AppointmentTile(appt: a)),
        ],
      ),
    );
  }
}

class _AppointmentTile extends StatelessWidget {
  const _AppointmentTile({required this.appt});
  final Map<String, String> appt;

  @override
  Widget build(BuildContext context) {
    final statusColor = switch (appt['status']) {
      'in'        => AppTheme.green,
      'waiting'   => AppTheme.amber,
      'confirmed' => AppTheme.blue,
      _           => Colors.grey,
    };
    final statusLabel = switch (appt['status']) {
      'in'        => 'In Consultation',
      'waiting'   => 'Waiting',
      'confirmed' => 'Confirmed',
      _           => 'Unknown',
    };

    return InkWell(
      onTap: () => context.go('/patients/1/emr/1'), // TODO: real IDs
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        child: Row(
          children: [
            SizedBox(
              width: 42,
              child: Text(appt['time']!, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF9CA3AF))),
            ),
            CircleAvatar(
              radius: 18,
              backgroundColor: AppTheme.blue.withOpacity(.15),
              child: Text(appt['initials']!, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppTheme.blue)),
            ),
            const SizedBox(width: 10),
            Expanded(child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(appt['name']!, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppTheme.dark)),
                Text(appt['type']!, style: const TextStyle(fontSize: 11, color: Color(0xFF9CA3AF))),
              ],
            )),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: statusColor.withOpacity(.1),
                borderRadius: BorderRadius.circular(100),
              ),
              child: Text(statusLabel, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: statusColor)),
            ),
          ],
        ),
      ),
    );
  }
}

class _AiSuggestionsCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppTheme.blue.withOpacity(.05), AppTheme.teal.withOpacity(.05)],
        ),
        border: Border.all(color: AppTheme.blue.withOpacity(.15)),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Container(
              width: 28, height: 28,
              decoration: BoxDecoration(color: AppTheme.blue, borderRadius: BorderRadius.circular(7)),
              child: const Icon(Icons.auto_awesome, color: Colors.white, size: 14),
            ),
            const SizedBox(width: 8),
            const Text('AI Suggestions', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppTheme.dark)),
          ]),
          const SizedBox(height: 12),
          _AiSuggestion(icon: '📋', title: "Start Priya Mehta's note", body: 'In consultation now. Tap to open Dermatology EMR.'),
          const SizedBox(height: 8),
          _AiSuggestion(icon: '💊', title: 'Prescription template ready', body: 'Acne Grade 3 — Adapalene 0.1% + Clindamycin 1% suggested.'),
          const SizedBox(height: 8),
          _AiSuggestion(icon: '📅', title: '6 patients due for recall', body: 'Psoriasis review at 6 weeks. Send WhatsApp batch?'),
        ],
      ),
    );
  }
}

class _AiSuggestion extends StatelessWidget {
  const _AiSuggestion({required this.icon, required this.title, required this.body});
  final String icon, title, body;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Row(
        children: [
          Text(icon, style: const TextStyle(fontSize: 16)),
          const SizedBox(width: 10),
          Expanded(child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.dark)),
              Text(body, style: const TextStyle(fontSize: 11, color: Color(0xFF9CA3AF))),
            ],
          )),
          const Icon(Icons.arrow_forward_ios_rounded, size: 12, color: Color(0xFF9CA3AF)),
        ],
      ),
    );
  }
}

class _WhatsAppActivityCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Card(
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
            child: Row(
              children: [
                const Text('WhatsApp Activity', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppTheme.dark)),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(color: AppTheme.red.withOpacity(.1), borderRadius: BorderRadius.circular(100)),
                  child: const Text('3 unread', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppTheme.red)),
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          _WaTile(name: 'Priya Mehta', message: 'Prescription + AI summary sent', time: '10:47', delivered: true),
          _WaTile(name: 'Meera Kapoor', message: '"Can I reschedule to tomorrow?"', time: '10:02', unread: true),
          _WaTile(name: 'Rajesh Kumar', message: 'Appointment reminder sent', time: '09:30', delivered: true),
        ],
      ),
    );
  }
}

class _WaTile extends StatelessWidget {
  const _WaTile({required this.name, required this.message, required this.time, this.delivered = false, this.unread = false});
  final String name, message, time;
  final bool delivered, unread;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: unread ? AppTheme.amber.withOpacity(.05) : null,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 32, height: 32,
            decoration: BoxDecoration(color: const Color(0xFF25D366), borderRadius: BorderRadius.circular(8)),
            child: const Icon(Icons.chat, color: Colors.white, size: 15),
          ),
          const SizedBox(width: 10),
          Expanded(child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(name, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.dark)),
              Text(message, style: TextStyle(fontSize: 11, color: unread ? AppTheme.amber : const Color(0xFF9CA3AF))),
            ],
          )),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(time, style: const TextStyle(fontSize: 10, color: Color(0xFF9CA3AF))),
              if (delivered) const Text('✓✓', style: TextStyle(fontSize: 10, color: Color(0xFF25D366))),
              if (unread) Container(
                width: 8, height: 8,
                decoration: const BoxDecoration(color: AppTheme.amber, shape: BoxShape.circle),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
