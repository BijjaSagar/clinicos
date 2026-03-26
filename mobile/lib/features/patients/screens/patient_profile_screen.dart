import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';

// ── Data Models ───────────────────────────────────────────────────────────────

class PatientDetail {
  const PatientDetail({
    required this.id,
    required this.name,
    required this.age,
    required this.gender,
    required this.bloodGroup,
    required this.abhaId,
    required this.initials,
    required this.avatarColor,
    required this.allergies,
    required this.height,
    required this.weight,
    required this.bmi,
    required this.conditions,
    required this.medications,
    required this.emergencyContact,
    required this.visits,
  });

  final int id;
  final String name;
  final int age;
  final String gender;
  final String bloodGroup;
  final String? abhaId;
  final String initials;
  final Color avatarColor;
  final List<String> allergies;
  final String height;
  final String weight;
  final String bmi;
  final List<String> conditions;
  final List<MedicationEntry> medications;
  final EmergencyContact emergencyContact;
  final List<VisitHistoryEntry> visits;
}

class MedicationEntry {
  const MedicationEntry({
    required this.name,
    required this.dose,
    required this.frequency,
    required this.since,
  });

  final String name;
  final String dose;
  final String frequency;
  final String since;
}

class EmergencyContact {
  const EmergencyContact({
    required this.name,
    required this.relation,
    required this.phone,
  });

  final String name;
  final String relation;
  final String phone;
}

class VisitHistoryEntry {
  const VisitHistoryEntry({
    required this.visitId,
    required this.date,
    required this.visitType,
    required this.doctor,
    required this.summary,
  });

  final int visitId;
  final String date;
  final String visitType;
  final String doctor;
  final String summary;
}

// ── Sample Data ───────────────────────────────────────────────────────────────

PatientDetail _getPatientDetail(int id) {
  // In production this would come from a provider/API
  return PatientDetail(
    id: id,
    name: 'Priya Mehta',
    age: 34,
    gender: 'Female',
    bloodGroup: 'B+',
    abhaId: '71-2211-4501-3321',
    initials: 'PM',
    avatarColor: AppTheme.teal,
    allergies: ['Penicillin', 'Sulfamethoxazole'],
    height: '162 cm',
    weight: '58 kg',
    bmi: '22.1',
    conditions: ['Acne Grade 3', 'Melasma', 'Post-inflammatory Hyperpigmentation'],
    medications: const [
      MedicationEntry(
        name: 'Adapalene 0.1% Gel',
        dose: 'Topical',
        frequency: 'Once daily (night)',
        since: 'Jan 2026',
      ),
      MedicationEntry(
        name: 'Clindamycin 1% Solution',
        dose: 'Topical',
        frequency: 'Twice daily',
        since: 'Jan 2026',
      ),
      MedicationEntry(
        name: 'Kojic Acid Cream 2%',
        dose: 'Topical',
        frequency: 'Once daily (morning)',
        since: 'Feb 2026',
      ),
      MedicationEntry(
        name: 'Sunscreen SPF 50+',
        dose: 'Topical',
        frequency: 'Daily',
        since: 'Ongoing',
      ),
    ],
    emergencyContact: const EmergencyContact(
      name: 'Rohan Mehta',
      relation: 'Husband',
      phone: '+91 98765 43210',
    ),
    visits: const [
      VisitHistoryEntry(
        visitId: 101,
        date: '26 Mar 2026',
        visitType: 'Follow-up · Chem Peel',
        doctor: 'Dr. Priya Sharma',
        summary: 'Session #4 completed. Mild erythema noted, resolving. '
            'Continue home regimen. Next: 3 weeks.',
      ),
      VisitHistoryEntry(
        visitId: 96,
        date: '3 Mar 2026',
        visitType: 'Follow-up · Chem Peel',
        doctor: 'Dr. Priya Sharma',
        summary: 'Session #3 completed. Patient tolerated well. PIH improving significantly.',
      ),
      VisitHistoryEntry(
        visitId: 89,
        date: '11 Feb 2026',
        visitType: 'Follow-up · Chem Peel',
        doctor: 'Dr. Priya Sharma',
        summary: 'Session #2. Discussed sunscreen compliance. Added Kojic Acid cream.',
      ),
      VisitHistoryEntry(
        visitId: 82,
        date: '18 Jan 2026',
        visitType: 'New Patient · Acne',
        doctor: 'Dr. Priya Sharma',
        summary: 'Initial consultation. Acne Grade 3 with PIH. Initiated topical regimen. '
            'Planned 4-session chemical peel series.',
      ),
      VisitHistoryEntry(
        visitId: 71,
        date: '5 Nov 2025',
        visitType: 'Follow-up · Melasma',
        doctor: 'Dr. Ravi Menon',
        summary: 'Referred back from Dr. Ravi. Melasma on cheeks, moderate. '
            'Hydroquinone cream 2% prescribed.',
      ),
    ],
  );
}

// ── Screen ────────────────────────────────────────────────────────────────────

class PatientProfileScreen extends ConsumerStatefulWidget {
  const PatientProfileScreen({super.key, required this.patientId});
  final int patientId;

  @override
  ConsumerState<PatientProfileScreen> createState() => _PatientProfileScreenState();
}

class _PatientProfileScreenState extends ConsumerState<PatientProfileScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final patient = _getPatientDetail(widget.patientId);

    return Scaffold(
      backgroundColor: AppTheme.surface,
      body: NestedScrollView(
        headerSliverBuilder: (context, innerBoxIsScrolled) => [
          SliverAppBar(
            pinned: true,
            expandedHeight: 220,
            backgroundColor: Colors.white,
            leading: IconButton(
              icon: const Icon(Icons.arrow_back_rounded, color: AppTheme.dark),
              onPressed: () => context.pop(),
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.edit_rounded, color: AppTheme.dark, size: 20),
                onPressed: () {},
              ),
              IconButton(
                icon: const Icon(Icons.more_vert_rounded, color: AppTheme.dark),
                onPressed: () {},
              ),
            ],
            flexibleSpace: FlexibleSpaceBar(
              collapseMode: CollapseMode.pin,
              background: _HeaderCard(patient: patient),
            ),
            bottom: TabBar(
              controller: _tabController,
              isScrollable: true,
              tabAlignment: TabAlignment.start,
              indicatorColor: AppTheme.blue,
              indicatorWeight: 2.5,
              labelColor: AppTheme.blue,
              unselectedLabelColor: const Color(0xFF9CA3AF),
              labelStyle: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                fontFamily: 'Inter',
              ),
              unselectedLabelStyle: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                fontFamily: 'Inter',
              ),
              tabs: const [
                Tab(text: 'Overview'),
                Tab(text: 'Visit History'),
                Tab(text: 'Photos'),
                Tab(text: 'Billing'),
                Tab(text: 'Documents'),
              ],
            ),
          ),
        ],
        body: TabBarView(
          controller: _tabController,
          children: [
            _OverviewTab(patient: patient),
            _VisitHistoryTab(patient: patient),
            _PlaceholderTab(
              icon: Icons.photo_library_outlined,
              label: 'No clinical photos yet',
              subLabel: 'Use the camera to capture and\ncompare treatment progress.',
            ),
            _PlaceholderTab(
              icon: Icons.receipt_long_outlined,
              label: 'No billing records',
              subLabel: 'Bills and invoices will appear here.',
            ),
            _PlaceholderTab(
              icon: Icons.folder_outlined,
              label: 'No documents uploaded',
              subLabel: 'Lab reports, consent forms and\nother documents appear here.',
            ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.go('/patients/${widget.patientId}/emr/new'),
        backgroundColor: AppTheme.blue,
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: const Text(
          'New Visit',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
        ),
      ),
    );
  }
}

// ── Header Card ───────────────────────────────────────────────────────────────

class _HeaderCard extends StatelessWidget {
  const _HeaderCard({required this.patient});
  final PatientDetail patient;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(20, 70, 20, 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Large avatar
          CircleAvatar(
            radius: 36,
            backgroundColor: patient.avatarColor.withOpacity(0.15),
            child: Text(
              patient.initials,
              style: TextStyle(
                fontFamily: 'Sora',
                fontSize: 22,
                fontWeight: FontWeight.w700,
                color: patient.avatarColor,
              ),
            ),
          ),
          const SizedBox(width: 16),
          // Patient info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  patient.name,
                  style: const TextStyle(
                    fontFamily: 'Sora',
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.dark,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  '${patient.age}y · ${patient.gender} · ${patient.bloodGroup}',
                  style: const TextStyle(
                    fontSize: 13,
                    color: Color(0xFF6B7280),
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  children: [
                    if (patient.abhaId != null)
                      _InfoChip(
                        label: 'ABHA ${patient.abhaId!}',
                        color: AppTheme.blue,
                        icon: Icons.verified_rounded,
                      ),
                    ...patient.allergies.map(
                      (a) => _InfoChip(label: a, color: AppTheme.red),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  const _InfoChip({required this.label, required this.color, this.icon});
  final String label;
  final Color color;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.08),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 10, color: color),
            const SizedBox(width: 4),
          ],
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w600,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Overview Tab ──────────────────────────────────────────────────────────────

class _OverviewTab extends StatelessWidget {
  const _OverviewTab({required this.patient});
  final PatientDetail patient;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 100),
      children: [
        // Vitals row
        _SectionHeader(title: 'Vitals'),
        const SizedBox(height: 8),
        Row(
          children: [
            _VitalCard(label: 'Height', value: patient.height, icon: Icons.height_rounded),
            const SizedBox(width: 10),
            _VitalCard(label: 'Weight', value: patient.weight, icon: Icons.monitor_weight_outlined),
            const SizedBox(width: 10),
            _VitalCard(
              label: 'BMI',
              value: patient.bmi,
              icon: Icons.analytics_outlined,
              isHighlighted: double.tryParse(patient.bmi)?.let((b) => b > 25) ?? false,
            ),
          ],
        ),
        const SizedBox(height: 16),

        // Active conditions
        _SectionHeader(title: 'Active Conditions'),
        const SizedBox(height: 8),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Wrap(
              spacing: 8,
              runSpacing: 8,
              children: patient.conditions.map(
                (c) => Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppTheme.greenLight,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: AppTheme.green.withOpacity(0.3)),
                  ),
                  child: Text(
                    c,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppTheme.green,
                    ),
                  ),
                ),
              ).toList(),
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Medications
        _SectionHeader(title: 'Current Medications'),
        const SizedBox(height: 8),
        Card(
          child: Column(
            children: patient.medications
                .asMap()
                .entries
                .map((e) => Column(
                      children: [
                        _MedicationTile(med: e.value),
                        if (e.key < patient.medications.length - 1)
                          const Divider(height: 1, indent: 14, endIndent: 14),
                      ],
                    ))
                .toList(),
          ),
        ),
        const SizedBox(height: 16),

        // Emergency contact
        _SectionHeader(title: 'Emergency Contact'),
        const SizedBox(height: 8),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: AppTheme.red.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(
                    Icons.emergency_rounded,
                    color: AppTheme.red,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        patient.emergencyContact.name,
                        style: const TextStyle(
                          fontFamily: 'Sora',
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: AppTheme.dark,
                        ),
                      ),
                      Text(
                        patient.emergencyContact.relation,
                        style: const TextStyle(
                          fontSize: 12,
                          color: Color(0xFF9CA3AF),
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  patient.emergencyContact.phone,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.blue,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(left: 2),
      child: Text(
        title,
        style: const TextStyle(
          fontFamily: 'Sora',
          fontSize: 13,
          fontWeight: FontWeight.w700,
          color: Color(0xFF374151),
          letterSpacing: 0.2,
        ),
      ),
    );
  }
}

class _VitalCard extends StatelessWidget {
  const _VitalCard({
    required this.label,
    required this.value,
    required this.icon,
    this.isHighlighted = false,
  });
  final String label;
  final String value;
  final IconData icon;
  final bool isHighlighted;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: isHighlighted ? const Color(0xFFFFF8EC) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isHighlighted ? AppTheme.amber.withOpacity(0.4) : const Color(0xFFE5E7EB),
          ),
        ),
        child: Column(
          children: [
            Icon(
              icon,
              size: 18,
              color: isHighlighted ? AppTheme.amber : AppTheme.teal,
            ),
            const SizedBox(height: 6),
            Text(
              value,
              style: TextStyle(
                fontFamily: 'Sora',
                fontSize: 15,
                fontWeight: FontWeight.w700,
                color: isHighlighted ? AppTheme.amber : AppTheme.dark,
              ),
            ),
            Text(
              label,
              style: const TextStyle(
                fontSize: 10,
                color: Color(0xFF9CA3AF),
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MedicationTile extends StatelessWidget {
  const _MedicationTile({required this.med});
  final MedicationEntry med;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: AppTheme.tealLight,
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.medication_rounded,
                size: 16, color: AppTheme.teal),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  med.name,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.dark,
                  ),
                ),
                Text(
                  '${med.dose} · ${med.frequency}',
                  style: const TextStyle(
                    fontSize: 11,
                    color: Color(0xFF9CA3AF),
                  ),
                ),
              ],
            ),
          ),
          Text(
            med.since,
            style: const TextStyle(
              fontSize: 11,
              color: Color(0xFF9CA3AF),
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Visit History Tab ─────────────────────────────────────────────────────────

class _VisitHistoryTab extends StatelessWidget {
  const _VisitHistoryTab({required this.patient});
  final PatientDetail patient;

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 100),
      itemCount: patient.visits.length,
      itemBuilder: (context, index) {
        final visit = patient.visits[index];
        final isFirst = index == 0;
        final isLast = index == patient.visits.length - 1;

        return _VisitTimelineItem(
          visit: visit,
          patientId: patient.id,
          isFirst: isFirst,
          isLast: isLast,
        );
      },
    );
  }
}

class _VisitTimelineItem extends StatelessWidget {
  const _VisitTimelineItem({
    required this.visit,
    required this.patientId,
    required this.isFirst,
    required this.isLast,
  });
  final VisitHistoryEntry visit;
  final int patientId;
  final bool isFirst;
  final bool isLast;

  Color get _dotColor => isFirst ? AppTheme.blue : const Color(0xFFD1D5DB);

  @override
  Widget build(BuildContext context) {
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Timeline line + dot
          SizedBox(
            width: 28,
            child: Column(
              children: [
                if (!isFirst)
                  Flexible(
                    flex: 0,
                    child: Container(
                      width: 2,
                      height: 12,
                      color: const Color(0xFFE5E7EB),
                    ),
                  ),
                Container(
                  width: 12,
                  height: 12,
                  decoration: BoxDecoration(
                    color: _dotColor,
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: isFirst ? AppTheme.blue : const Color(0xFFE5E7EB),
                      width: isFirst ? 2 : 1,
                    ),
                  ),
                ),
                if (!isLast)
                  Expanded(
                    child: Container(
                      width: 2,
                      color: const Color(0xFFE5E7EB),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          // Visit card
          Expanded(
            child: GestureDetector(
              onTap: () => context.go('/patients/$patientId/emr/${visit.visitId}'),
              child: Container(
                margin: const EdgeInsets.only(bottom: 10),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: isFirst ? AppTheme.blueLight : Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isFirst
                        ? AppTheme.blue.withOpacity(0.25)
                        : const Color(0xFFE5E7EB),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            visit.visitType,
                            style: TextStyle(
                              fontFamily: 'Sora',
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: isFirst ? AppTheme.blue : AppTheme.dark,
                            ),
                          ),
                        ),
                        if (isFirst)
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: AppTheme.blue,
                              borderRadius: BorderRadius.circular(100),
                            ),
                            child: const Text(
                              'Latest',
                              style: TextStyle(
                                fontSize: 9,
                                fontWeight: FontWeight.w700,
                                color: Colors.white,
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        const Icon(Icons.calendar_today_rounded,
                            size: 11, color: Color(0xFF9CA3AF)),
                        const SizedBox(width: 4),
                        Text(
                          visit.date,
                          style: const TextStyle(
                            fontSize: 11,
                            color: Color(0xFF9CA3AF),
                          ),
                        ),
                        const SizedBox(width: 10),
                        const Icon(Icons.person_outline_rounded,
                            size: 11, color: Color(0xFF9CA3AF)),
                        const SizedBox(width: 4),
                        Text(
                          visit.doctor,
                          style: const TextStyle(
                            fontSize: 11,
                            color: Color(0xFF9CA3AF),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      visit.summary,
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF6B7280),
                        height: 1.5,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        Text(
                          'Open EMR →',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: isFirst ? AppTheme.blue : const Color(0xFF9CA3AF),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Placeholder Tab ───────────────────────────────────────────────────────────

class _PlaceholderTab extends StatelessWidget {
  const _PlaceholderTab({
    required this.icon,
    required this.label,
    required this.subLabel,
  });
  final IconData icon;
  final String label;
  final String subLabel;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                color: AppTheme.blueLight,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Icon(icon, size: 32, color: AppTheme.blue),
            ),
            const SizedBox(height: 16),
            Text(
              label,
              style: const TextStyle(
                fontFamily: 'Sora',
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: AppTheme.dark,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              subLabel,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 13,
                color: Color(0xFF9CA3AF),
                height: 1.5,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Extension helper ──────────────────────────────────────────────────────────

extension _Let<T> on T {
  R let<R>(R Function(T) block) => block(this);
}
