import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';

// ── Data Models ───────────────────────────────────────────────────────────────

enum Specialty { dermatology, physiotherapy, dental, general }

class PatientRecord {
  const PatientRecord({
    required this.id,
    required this.name,
    required this.age,
    required this.gender,
    required this.bloodGroup,
    required this.specialty,
    required this.lastVisitDate,
    required this.lastVisitType,
    required this.conditions,
    this.abhaId,
    this.unreadMessages = 0,
    this.isFlagged = false,
    this.isRecentVisitToday = false,
  });

  final int id;
  final String name;
  final int age;
  final String gender;
  final String bloodGroup;
  final Specialty specialty;
  final String lastVisitDate;
  final String lastVisitType;
  final List<String> conditions;
  final String? abhaId;
  final int unreadMessages;
  final bool isFlagged;
  final bool isRecentVisitToday;

  String get initials {
    final parts = name.trim().split(' ');
    if (parts.length >= 2) return '${parts[0][0]}${parts[1][0]}';
    return parts[0][0];
  }

  Color get avatarColor => switch (specialty) {
    Specialty.dermatology   => AppTheme.teal,
    Specialty.physiotherapy => const Color(0xFFF97316),
    Specialty.dental        => const Color(0xFF7C3AED),
    Specialty.general       => AppTheme.blue,
  };
}

final _allPatients = <PatientRecord>[
  PatientRecord(
    id: 1, name: 'Priya Mehta', age: 34, gender: 'F', bloodGroup: 'B+',
    specialty: Specialty.dermatology,
    lastVisitDate: '26 Mar 2026', lastVisitType: 'Follow-up · Chem Peel',
    conditions: ['Acne Grade 3', 'Melasma'],
    abhaId: '71-2211-4501-3321',
    unreadMessages: 2,
    isRecentVisitToday: true,
  ),
  PatientRecord(
    id: 2, name: 'Rajesh Kumar', age: 45, gender: 'M', bloodGroup: 'O+',
    specialty: Specialty.dermatology,
    lastVisitDate: '26 Mar 2026', lastVisitType: 'LASER Session #2',
    conditions: ['Androgenic Alopecia', 'Seborrheic Dermatitis', 'Dandruff'],
    abhaId: '23-4411-7782-0091',
    unreadMessages: 0,
    isRecentVisitToday: true,
  ),
  PatientRecord(
    id: 3, name: 'Ananya Patil', age: 28, gender: 'F', bloodGroup: 'A+',
    specialty: Specialty.dermatology,
    lastVisitDate: '26 Mar 2026', lastVisitType: 'New · Psoriasis',
    conditions: ['Psoriasis Vulgaris'],
    abhaId: null,
    unreadMessages: 1,
    isRecentVisitToday: true,
  ),
  PatientRecord(
    id: 4, name: 'Vikram Shah', age: 38, gender: 'M', bloodGroup: 'AB+',
    specialty: Specialty.dermatology,
    lastVisitDate: '24 Mar 2026', lastVisitType: 'PRP · Hair Loss',
    conditions: ['Alopecia Areata', 'Male Pattern Baldness'],
    abhaId: '55-9901-2244-7810',
    unreadMessages: 0,
    isRecentVisitToday: false,
  ),
  PatientRecord(
    id: 5, name: 'Meera Kapoor', age: 52, gender: 'F', bloodGroup: 'O-',
    specialty: Specialty.general,
    lastVisitDate: '22 Mar 2026', lastVisitType: 'Follow-up · Rosacea',
    conditions: ['Rosacea', 'Hypertension'],
    abhaId: '34-1122-8870-5544',
    unreadMessages: 0,
    isFlagged: true,
  ),
  PatientRecord(
    id: 6, name: 'Arjun Nair', age: 22, gender: 'M', bloodGroup: 'B-',
    specialty: Specialty.dermatology,
    lastVisitDate: '20 Mar 2026', lastVisitType: 'New · Eczema',
    conditions: ['Atopic Dermatitis'],
    abhaId: null,
    unreadMessages: 3,
  ),
  PatientRecord(
    id: 7, name: 'Sunita Reddy', age: 41, gender: 'F', bloodGroup: 'A-',
    specialty: Specialty.dermatology,
    lastVisitDate: '18 Mar 2026', lastVisitType: 'Procedure · Mole Removal',
    conditions: ['Sebaceous Cyst', 'Vitiligo'],
    abhaId: '90-3340-6612-0078',
    unreadMessages: 0,
  ),
  PatientRecord(
    id: 8, name: 'Kavitha Subramaniam', age: 31, gender: 'F', bloodGroup: 'O+',
    specialty: Specialty.physiotherapy,
    lastVisitDate: '15 Mar 2026', lastVisitType: 'Physio · Knee Rehab',
    conditions: ['ACL Injury', 'Post-surgical Rehab'],
    abhaId: '12-8823-5590-4417',
    unreadMessages: 1,
  ),
  PatientRecord(
    id: 9, name: 'Suresh Iyer', age: 58, gender: 'M', bloodGroup: 'B+',
    specialty: Specialty.physiotherapy,
    lastVisitDate: '14 Mar 2026', lastVisitType: 'Physio · Lower Back Pain',
    conditions: ['Lumbar Spondylosis', 'Sciatica', 'Diabetes T2'],
    abhaId: '67-0012-3345-8899',
    unreadMessages: 0,
    isFlagged: true,
  ),
  PatientRecord(
    id: 10, name: 'Deepa Krishnamurthy', age: 26, gender: 'F', bloodGroup: 'A+',
    specialty: Specialty.dental,
    lastVisitDate: '12 Mar 2026', lastVisitType: 'Dental · Root Canal',
    conditions: ['Dental Caries', 'Periapical Abscess'],
    abhaId: null,
    unreadMessages: 0,
  ),
  PatientRecord(
    id: 11, name: 'Mohit Agarwal', age: 47, gender: 'M', bloodGroup: 'AB-',
    specialty: Specialty.dental,
    lastVisitDate: '10 Mar 2026', lastVisitType: 'Dental · Crown Fitting',
    conditions: ['Bruxism'],
    abhaId: '88-4456-1123-7721',
    unreadMessages: 2,
  ),
  PatientRecord(
    id: 12, name: 'Nalini Venkatesh', age: 63, gender: 'F', bloodGroup: 'O+',
    specialty: Specialty.general,
    lastVisitDate: '5 Mar 2026', lastVisitType: 'Follow-up · General',
    conditions: ['Hypertension', 'Type 2 Diabetes', 'Hypothyroidism'],
    abhaId: '44-7789-0023-5560',
    unreadMessages: 0,
    isFlagged: true,
  ),
];

// ── Providers ─────────────────────────────────────────────────────────────────

enum PatientFilter { all, today, recent, flagged }

final _patientFilterProvider = StateProvider<PatientFilter>((ref) => PatientFilter.all);
final _patientSearchProvider = StateProvider<String>((ref) => '');

final _filteredPatientsProvider = Provider<List<PatientRecord>>((ref) {
  final filter = ref.watch(_patientFilterProvider);
  final query = ref.watch(_patientSearchProvider).toLowerCase().trim();

  List<PatientRecord> list = _allPatients;

  if (query.isNotEmpty) {
    list = list.where((p) {
      return p.name.toLowerCase().contains(query) ||
          (p.abhaId?.replaceAll('-', '').contains(query.replaceAll('-', '')) ?? false);
    }).toList();
  }

  list = switch (filter) {
    PatientFilter.all    => list,
    PatientFilter.today  => list.where((p) => p.isRecentVisitToday).toList(),
    PatientFilter.recent => list.take(6).toList(),
    PatientFilter.flagged => list.where((p) => p.isFlagged).toList(),
  };

  return list;
});

// ── Main Screen ───────────────────────────────────────────────────────────────

class PatientListScreen extends ConsumerStatefulWidget {
  const PatientListScreen({super.key});

  @override
  ConsumerState<PatientListScreen> createState() => _PatientListScreenState();
}

class _PatientListScreenState extends ConsumerState<PatientListScreen> {
  final _searchController = TextEditingController();
  bool _searchExpanded = false;
  final _scrollController = ScrollController();

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final filtered = ref.watch(_filteredPatientsProvider);
    final filter = ref.watch(_patientFilterProvider);

    return Scaffold(
      backgroundColor: AppTheme.surface,
      body: RefreshIndicator(
        color: AppTheme.blue,
        onRefresh: () async {
          await Future.delayed(const Duration(milliseconds: 800));
        },
        child: CustomScrollView(
          controller: _scrollController,
          slivers: [
            // ── SliverAppBar ──────────────────────────────────────────────────
            SliverAppBar(
              floating: true,
              snap: true,
              backgroundColor: Colors.white,
              elevation: 0,
              scrolledUnderElevation: 1,
              title: _searchExpanded
                  ? _SearchField(
                      controller: _searchController,
                      onChanged: (val) {
                        ref.read(_patientSearchProvider.notifier).state = val;
                      },
                      onClose: () {
                        setState(() => _searchExpanded = false);
                        _searchController.clear();
                        ref.read(_patientSearchProvider.notifier).state = '';
                      },
                    )
                  : const Text(
                      'Patients',
                      style: TextStyle(
                        fontFamily: 'Sora',
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.dark,
                      ),
                    ),
              actions: [
                if (!_searchExpanded)
                  IconButton(
                    icon: const Icon(Icons.search_rounded, color: AppTheme.dark),
                    onPressed: () => setState(() => _searchExpanded = true),
                  ),
                if (!_searchExpanded)
                  IconButton(
                    icon: const Icon(Icons.tune_rounded, color: AppTheme.dark),
                    onPressed: () {},
                  ),
              ],
              bottom: PreferredSize(
                preferredSize: const Size.fromHeight(100),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Stats bar
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                      child: Row(
                        children: [
                          _StatChip(label: 'Total', value: '248', color: AppTheme.blue),
                          const SizedBox(width: 8),
                          _StatChip(label: 'New this month', value: '12', color: AppTheme.green),
                          const SizedBox(width: 8),
                          _StatChip(label: 'Active cases', value: '34', color: AppTheme.amber),
                        ],
                      ),
                    ),
                    // Filter chips
                    SizedBox(
                      height: 44,
                      child: ListView(
                        scrollDirection: Axis.horizontal,
                        padding: const EdgeInsets.fromLTRB(16, 4, 16, 4),
                        children: [
                          _FilterChip(
                            label: 'All',
                            selected: filter == PatientFilter.all,
                            onTap: () => ref.read(_patientFilterProvider.notifier).state =
                                PatientFilter.all,
                          ),
                          const SizedBox(width: 8),
                          _FilterChip(
                            label: 'Today',
                            selected: filter == PatientFilter.today,
                            onTap: () => ref.read(_patientFilterProvider.notifier).state =
                                PatientFilter.today,
                          ),
                          const SizedBox(width: 8),
                          _FilterChip(
                            label: 'Recent',
                            selected: filter == PatientFilter.recent,
                            onTap: () => ref.read(_patientFilterProvider.notifier).state =
                                PatientFilter.recent,
                          ),
                          const SizedBox(width: 8),
                          _FilterChip(
                            label: 'Flagged',
                            selected: filter == PatientFilter.flagged,
                            onTap: () => ref.read(_patientFilterProvider.notifier).state =
                                PatientFilter.flagged,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // ── Patient List ──────────────────────────────────────────────────
            filtered.isEmpty
                ? SliverFillRemaining(child: _EmptyState())
                : SliverPadding(
                    padding: const EdgeInsets.fromLTRB(12, 8, 12, 80),
                    sliver: SliverList(
                      delegate: SliverChildBuilderDelegate(
                        (context, index) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: _PatientCard(patient: filtered[index]),
                        ),
                        childCount: filtered.length,
                      ),
                    ),
                  ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.go('/patients/new'),
        backgroundColor: AppTheme.blue,
        icon: const Icon(Icons.person_add_rounded, color: Colors.white),
        label: const Text(
          'New Patient',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
        ),
      ),
    );
  }
}

// ── Search Field ──────────────────────────────────────────────────────────────

class _SearchField extends StatelessWidget {
  const _SearchField({
    required this.controller,
    required this.onChanged,
    required this.onClose,
  });
  final TextEditingController controller;
  final ValueChanged<String> onChanged;
  final VoidCallback onClose;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      autofocus: true,
      onChanged: onChanged,
      style: const TextStyle(fontSize: 14),
      decoration: InputDecoration(
        hintText: 'Search patients, ABHA ID...',
        hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF9CA3AF)),
        prefixIcon: const Icon(Icons.search_rounded, size: 18, color: Color(0xFF9CA3AF)),
        suffixIcon: IconButton(
          icon: const Icon(Icons.close_rounded, size: 18, color: Color(0xFF9CA3AF)),
          onPressed: onClose,
        ),
        filled: true,
        fillColor: AppTheme.surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide.none,
        ),
        contentPadding: const EdgeInsets.symmetric(vertical: 10),
      ),
    );
  }
}

// ── Stat Chip ─────────────────────────────────────────────────────────────────

class _StatChip extends StatelessWidget {
  const _StatChip({required this.label, required this.value, required this.color});
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withOpacity(0.08),
        borderRadius: BorderRadius.circular(100),
        border: Border.all(color: color.withOpacity(0.2)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            value,
            style: TextStyle(
              fontFamily: 'Sora',
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: color.withOpacity(0.8),
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}

// ── Filter Chip ───────────────────────────────────────────────────────────────

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 7),
        decoration: BoxDecoration(
          color: selected ? AppTheme.blue : Colors.white,
          borderRadius: BorderRadius.circular(100),
          border: Border.all(
            color: selected ? AppTheme.blue : const Color(0xFFE5E7EB),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: selected ? Colors.white : const Color(0xFF6B7280),
          ),
        ),
      ),
    );
  }
}

// ── Patient Card ──────────────────────────────────────────────────────────────

class _PatientCard extends StatelessWidget {
  const _PatientCard({required this.patient});
  final PatientRecord patient;

  @override
  Widget build(BuildContext context) {
    final maxConditions = patient.conditions.take(2).toList();
    final extraConditions = patient.conditions.length - 2;

    return GestureDetector(
      onTap: () => context.go('/patients/${patient.id}'),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE5E7EB)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Avatar
            Stack(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: patient.avatarColor.withOpacity(0.15),
                  child: Text(
                    patient.initials,
                    style: TextStyle(
                      fontFamily: 'Sora',
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: patient.avatarColor,
                    ),
                  ),
                ),
                if (patient.unreadMessages > 0)
                  Positioned(
                    right: 0,
                    top: 0,
                    child: Container(
                      width: 14,
                      height: 14,
                      decoration: const BoxDecoration(
                        color: AppTheme.amber,
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          '${patient.unreadMessages}',
                          style: const TextStyle(
                            fontSize: 8,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(width: 12),
            // Patient info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Name + ABHA chip row
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          patient.name,
                          style: const TextStyle(
                            fontFamily: 'Sora',
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.dark,
                          ),
                        ),
                      ),
                      if (patient.abhaId != null)
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 7, vertical: 3),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(6),
                            border:
                                Border.all(color: AppTheme.blue.withOpacity(0.5)),
                          ),
                          child: const Text(
                            'ABHA',
                            style: TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.w700,
                              color: AppTheme.blue,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  // Age, gender, blood group
                  Text(
                    '${patient.age}y · ${patient.gender} · ${patient.bloodGroup}',
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF6B7280),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 4),
                  // Last visit
                  Text(
                    '${patient.lastVisitDate}  ·  ${patient.lastVisitType}',
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFF9CA3AF),
                    ),
                  ),
                  const SizedBox(height: 8),
                  // Conditions
                  Wrap(
                    spacing: 6,
                    runSpacing: 4,
                    children: [
                      ...maxConditions.map((c) => _ConditionChip(label: c)),
                      if (extraConditions > 0)
                        _ConditionChip(
                            label: '+$extraConditions more', isMore: true),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 4),
            if (patient.isFlagged)
              const Icon(Icons.flag_rounded, size: 16, color: AppTheme.red),
          ],
        ),
      ),
    );
  }
}

class _ConditionChip extends StatelessWidget {
  const _ConditionChip({required this.label, this.isMore = false});
  final String label;
  final bool isMore;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: isMore ? AppTheme.surface : const Color(0xFFF0FDF4),
        borderRadius: BorderRadius.circular(100),
        border: Border.all(
          color: isMore
              ? const Color(0xFFE5E7EB)
              : AppTheme.green.withOpacity(0.3),
        ),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: isMore ? const Color(0xFF9CA3AF) : AppTheme.green,
        ),
      ),
    );
  }
}

// ── Empty State ───────────────────────────────────────────────────────────────

class _EmptyState extends StatelessWidget {
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
              child: const Icon(Icons.search_off_rounded,
                  size: 36, color: AppTheme.blue),
            ),
            const SizedBox(height: 16),
            const Text(
              'No patients found',
              style: TextStyle(
                fontFamily: 'Sora',
                fontSize: 17,
                fontWeight: FontWeight.w700,
                color: AppTheme.dark,
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'Try adjusting your search or filter,\nor add a new patient.',
              textAlign: TextAlign.center,
              style: TextStyle(
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
