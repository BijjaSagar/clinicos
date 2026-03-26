import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme.dart';

// ── State Providers ────────────────────────────────────────────────────────────

final _selectedDurationProvider = StateProvider<String?>((ref) => null);
final _igaScoreProvider = StateProvider<int>((ref) => 0);
final _pasiScoreProvider = StateProvider<double>((ref) => 0.0);
final _whatsAppToggleProvider = StateProvider<bool>((ref) => true);
final _autoSaveProvider = StateProvider<String>((ref) => 'Saved');
final _lesionMarkersProvider =
    StateProvider<List<Offset>>((ref) => []);
final _prescriptionDrugsProvider =
    StateProvider<List<_Drug>>((ref) => [
  const _Drug(
    name: 'Adapalene 0.1% Gel',
    strength: '0.1%',
    frequency: 'OD',
    duration: '3 months',
    instructions: 'Apply thin layer at night on affected area',
  ),
  const _Drug(
    name: 'Sunscreen SPF 50+',
    strength: 'SPF 50+',
    frequency: 'BD',
    duration: 'Ongoing',
    instructions: 'Apply 30 minutes before sun exposure',
  ),
]);

// ── Data Models ────────────────────────────────────────────────────────────────

class _Drug {
  final String name;
  final String strength;
  final String frequency;
  final String duration;
  final String instructions;

  const _Drug({
    required this.name,
    required this.strength,
    required this.frequency,
    required this.duration,
    required this.instructions,
  });

  _Drug copyWith({
    String? name,
    String? strength,
    String? frequency,
    String? duration,
    String? instructions,
  }) =>
      _Drug(
        name: name ?? this.name,
        strength: strength ?? this.strength,
        frequency: frequency ?? this.frequency,
        duration: duration ?? this.duration,
        instructions: instructions ?? this.instructions,
      );
}

// ── Main Screen ────────────────────────────────────────────────────────────────

class EmrScreen extends ConsumerStatefulWidget {
  final int patientId;
  final int? visitId;

  const EmrScreen({
    super.key,
    required this.patientId,
    this.visitId,
  });

  @override
  ConsumerState<EmrScreen> createState() => _EmrScreenState();
}

class _EmrScreenState extends ConsumerState<EmrScreen>
    with TickerProviderStateMixin {
  late final TabController _tabController;

  // Chief Complaint controllers
  final _chiefComplaintCtrl = TextEditingController();
  final _hpiCtrl = TextEditingController();
  final _pastHistoryCtrl = TextEditingController();
  final _allergiesCtrl = TextEditingController();

  // Vitals controllers
  final _bpCtrl = TextEditingController(text: '120/80');
  final _pulseCtrl = TextEditingController(text: '72');
  final _tempCtrl = TextEditingController(text: '98.6');
  final _spo2Ctrl = TextEditingController(text: '98');
  final _weightCtrl = TextEditingController(text: '65');

  // Plan controllers
  final _diagnosisCtrl = TextEditingController();
  final _planCtrl = TextEditingController();

  DateTime? _followUpDate;

  static const _durations = [
    '1 day', '1 week', '1 month', '3 months', '6 months', '>1 year'
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _chiefComplaintCtrl.dispose();
    _hpiCtrl.dispose();
    _pastHistoryCtrl.dispose();
    _allergiesCtrl.dispose();
    _bpCtrl.dispose();
    _pulseCtrl.dispose();
    _tempCtrl.dispose();
    _spo2Ctrl.dispose();
    _weightCtrl.dispose();
    _diagnosisCtrl.dispose();
    _planCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.surface,
      body: Column(
        children: [
          _PatientHeaderBar(
            patientId: widget.patientId,
            visitId: widget.visitId,
          ),
          _buildTabBar(),
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _ChiefComplaintTab(
                  chiefComplaintCtrl: _chiefComplaintCtrl,
                  hpiCtrl: _hpiCtrl,
                  pastHistoryCtrl: _pastHistoryCtrl,
                  allergiesCtrl: _allergiesCtrl,
                  durations: _durations,
                ),
                _ExaminationTab(
                  bpCtrl: _bpCtrl,
                  pulseCtrl: _pulseCtrl,
                  tempCtrl: _tempCtrl,
                  spo2Ctrl: _spo2Ctrl,
                  weightCtrl: _weightCtrl,
                ),
                const _PrescriptionTab(),
                _PlanTab(
                  diagnosisCtrl: _diagnosisCtrl,
                  planCtrl: _planCtrl,
                  followUpDate: _followUpDate,
                  onFollowUpDateChanged: (date) =>
                      setState(() => _followUpDate = date),
                ),
              ],
            ),
          ),
          _BottomBar(patientId: widget.patientId, visitId: widget.visitId),
        ],
      ),
    );
  }

  Widget _buildTabBar() {
    return Container(
      color: Colors.white,
      child: TabBar(
        controller: _tabController,
        isScrollable: true,
        tabAlignment: TabAlignment.start,
        labelColor: AppTheme.blue,
        unselectedLabelColor: const Color(0xFF6B7280),
        indicatorColor: AppTheme.blue,
        indicatorWeight: 2.5,
        labelStyle: const TextStyle(
          fontFamily: 'Inter',
          fontSize: 13,
          fontWeight: FontWeight.w600,
        ),
        unselectedLabelStyle: const TextStyle(
          fontFamily: 'Inter',
          fontSize: 13,
          fontWeight: FontWeight.w400,
        ),
        tabs: const [
          Tab(text: 'Chief Complaint'),
          Tab(text: 'Examination'),
          Tab(text: 'Prescription'),
          Tab(text: 'Plan'),
        ],
      ),
    );
  }
}

// ── Patient Header Bar ─────────────────────────────────────────────────────────

class _PatientHeaderBar extends StatelessWidget {
  final int patientId;
  final int visitId;

  const _PatientHeaderBar({
    required this.patientId,
    required this.visitId,
  });

  @override
  Widget build(BuildContext context) {
    final top = MediaQuery.of(context).padding.top;
    return Container(
      padding: EdgeInsets.only(top: top + 8, left: 16, right: 16, bottom: 12),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: Color(0xFFE5E7EB))),
      ),
      child: Row(
        children: [
          // Back button
          GestureDetector(
            onTap: () => Navigator.of(context).maybePop(),
            child: Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: AppTheme.surface,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.arrow_back_ios_new_rounded,
                  size: 16, color: AppTheme.dark),
            ),
          ),
          const SizedBox(width: 12),
          // Patient info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Text(
                      'Pooja Mehta',
                      style: TextStyle(
                        fontFamily: 'Sora',
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.dark,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      '28 yrs, F',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[500],
                        fontWeight: FontWeight.w400,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    _AbhaChip(),
                    const SizedBox(width: 6),
                    _VisitTypeBadge(label: 'Follow-up'),
                  ],
                ),
              ],
            ),
          ),
          // AI Dictate button
          ElevatedButton.icon(
            onPressed: () => _showAiDictateSheet(context),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.blue,
              foregroundColor: Colors.white,
              padding:
                  const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8)),
            ),
            icon: const Icon(Icons.mic_rounded, size: 16),
            label: const Text(
              'AI Dictate',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }

  void _showAiDictateSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => const _AiDictateSheet(),
    );
  }
}

class _AbhaChip extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: AppTheme.tealLight,
        borderRadius: BorderRadius.circular(100),
        border: Border.all(color: AppTheme.teal.withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: const BoxDecoration(
              color: AppTheme.teal,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 4),
          const Text(
            'ABHA Linked',
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w600,
              color: AppTheme.teal,
            ),
          ),
        ],
      ),
    );
  }
}

class _VisitTypeBadge extends StatelessWidget {
  final String label;

  const _VisitTypeBadge({required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: AppTheme.blueLight,
        borderRadius: BorderRadius.circular(100),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: AppTheme.blue,
        ),
      ),
    );
  }
}

class _AiDictateSheet extends StatelessWidget {
  const _AiDictateSheet();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: const Color(0xFFE5E7EB),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(height: 20),
          const Text(
            'AI Dictation',
            style: TextStyle(
              fontFamily: 'Sora',
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppTheme.dark,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Tap the microphone to start dictating. AI will auto-fill the EMR fields.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 13, color: Colors.grey[600]),
          ),
          const SizedBox(height: 28),
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: AppTheme.blueLight,
              shape: BoxShape.circle,
              border: Border.all(color: AppTheme.blue.withOpacity(0.3), width: 3),
            ),
            child: const Icon(Icons.mic_rounded,
                size: 36, color: AppTheme.blue),
          ),
          const SizedBox(height: 16),
          const Text(
            'Tap to start recording',
            style: TextStyle(
              fontSize: 12,
              color: Color(0xFF6B7280),
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}

// ── Chief Complaint Tab ────────────────────────────────────────────────────────

class _ChiefComplaintTab extends ConsumerWidget {
  final TextEditingController chiefComplaintCtrl;
  final TextEditingController hpiCtrl;
  final TextEditingController pastHistoryCtrl;
  final TextEditingController allergiesCtrl;
  final List<String> durations;

  const _ChiefComplaintTab({
    required this.chiefComplaintCtrl,
    required this.hpiCtrl,
    required this.pastHistoryCtrl,
    required this.allergiesCtrl,
    required this.durations,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final selectedDuration = ref.watch(_selectedDurationProvider);

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _SectionCard(
            title: 'Chief Complaint',
            child: Column(
              children: [
                _FormField(
                  controller: chiefComplaintCtrl,
                  label: 'Chief Complaint',
                  hint: 'Describe the primary complaint...',
                  maxLines: 3,
                ),
                const SizedBox(height: 12),
                // Duration chip selector
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Duration',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF6B7280),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: durations.map((d) {
                        final isSelected = selectedDuration == d;
                        return GestureDetector(
                          onTap: () => ref
                              .read(_selectedDurationProvider.notifier)
                              .state = isSelected ? null : d,
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 150),
                            padding: const EdgeInsets.symmetric(
                                horizontal: 14, vertical: 7),
                            decoration: BoxDecoration(
                              color: isSelected
                                  ? AppTheme.blue
                                  : Colors.white,
                              borderRadius: BorderRadius.circular(100),
                              border: Border.all(
                                color: isSelected
                                    ? AppTheme.blue
                                    : const Color(0xFFE5E7EB),
                              ),
                            ),
                            child: Text(
                              d,
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: isSelected
                                    ? Colors.white
                                    : const Color(0xFF374151),
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            title: 'History',
            child: Column(
              children: [
                _FormField(
                  controller: hpiCtrl,
                  label: 'History of Present Illness',
                  hint: 'Onset, progression, aggravating/relieving factors...',
                  maxLines: 4,
                ),
                const SizedBox(height: 12),
                _FormField(
                  controller: pastHistoryCtrl,
                  label: 'Past Medical History',
                  hint:
                      'Previous diagnoses, surgeries, hospitalizations...',
                  maxLines: 3,
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            title: 'Allergies',
            titleColor: AppTheme.red,
            titleIcon: Icons.warning_amber_rounded,
            child: TextFormField(
              controller: allergiesCtrl,
              maxLines: 2,
              decoration: InputDecoration(
                labelText: 'Allergies',
                hintText: 'Drug allergies, food allergies, environmental...',
                hintStyle:
                    TextStyle(fontSize: 13, color: Colors.grey[400]),
                filled: true,
                fillColor: const Color(0xFFFFF5F5),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide:
                      const BorderSide(color: AppTheme.red, width: 1.5),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: BorderSide(
                      color: AppTheme.red.withOpacity(0.5), width: 1.5),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide:
                      const BorderSide(color: AppTheme.red, width: 2),
                ),
                contentPadding: const EdgeInsets.symmetric(
                    horizontal: 14, vertical: 12),
              ),
            ),
          ),
          const SizedBox(height: 80),
        ],
      ),
    );
  }
}

// ── Examination Tab ────────────────────────────────────────────────────────────

class _ExaminationTab extends ConsumerWidget {
  final TextEditingController bpCtrl;
  final TextEditingController pulseCtrl;
  final TextEditingController tempCtrl;
  final TextEditingController spo2Ctrl;
  final TextEditingController weightCtrl;

  const _ExaminationTab({
    required this.bpCtrl,
    required this.pulseCtrl,
    required this.tempCtrl,
    required this.spo2Ctrl,
    required this.weightCtrl,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final pasiScore = ref.watch(_pasiScoreProvider);
    final igaScore = ref.watch(_igaScoreProvider);
    final markers = ref.watch(_lesionMarkersProvider);

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Vitals
          _SectionCard(
            title: 'Vitals',
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                        child: _VitalCard(
                            label: 'BP',
                            unit: 'mmHg',
                            icon: Icons.favorite_rounded,
                            iconColor: AppTheme.red,
                            controller: bpCtrl)),
                    const SizedBox(width: 8),
                    Expanded(
                        child: _VitalCard(
                            label: 'Pulse',
                            unit: 'bpm',
                            icon: Icons.monitor_heart_rounded,
                            iconColor: AppTheme.red,
                            controller: pulseCtrl)),
                    const SizedBox(width: 8),
                    Expanded(
                        child: _VitalCard(
                            label: 'Temp',
                            unit: '°F',
                            icon: Icons.thermostat_rounded,
                            iconColor: AppTheme.amber,
                            controller: tempCtrl)),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                        child: _VitalCard(
                            label: 'SpO2',
                            unit: '%',
                            icon: Icons.air_rounded,
                            iconColor: AppTheme.blue,
                            controller: spo2Ctrl)),
                    const SizedBox(width: 8),
                    Expanded(
                        child: _VitalCard(
                            label: 'Weight',
                            unit: 'kg',
                            icon: Icons.scale_rounded,
                            iconColor: AppTheme.teal,
                            controller: weightCtrl)),
                    const SizedBox(width: 8),
                    const Expanded(child: SizedBox()),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          // Body Map
          _SectionCard(
            title: 'Body Map',
            subtitle: 'Tap to mark lesion sites',
            child: Column(
              children: [
                Container(
                  height: 280,
                  decoration: BoxDecoration(
                    color: AppTheme.surface,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFE5E7EB)),
                  ),
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      GestureDetector(
                        onTapDown: (details) {
                          final box =
                              context.findRenderObject() as RenderBox?;
                          if (box == null) return;
                          final localPos = details.localPosition;
                          ref
                              .read(_lesionMarkersProvider.notifier)
                              .state = [...markers, localPos];
                        },
                        child: CustomPaint(
                          size: const Size(120, 260),
                          painter: _BodyOutlinePainter(),
                        ),
                      ),
                      // Lesion markers
                      ...markers.map((offset) => Positioned(
                            left: offset.dx - 6,
                            top: offset.dy - 6,
                            child: Container(
                              width: 12,
                              height: 12,
                              decoration: BoxDecoration(
                                color: AppTheme.green.withOpacity(0.8),
                                shape: BoxShape.circle,
                                border: Border.all(
                                    color: Colors.white, width: 1.5),
                                boxShadow: [
                                  BoxShadow(
                                    color: AppTheme.green.withOpacity(0.4),
                                    blurRadius: 4,
                                    spreadRadius: 1,
                                  )
                                ],
                              ),
                            ),
                          )),
                    ],
                  ),
                ),
                if (markers.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Row(
                      children: [
                        Icon(Icons.circle,
                            size: 10, color: AppTheme.green),
                        const SizedBox(width: 6),
                        Text(
                          '${markers.length} lesion site${markers.length > 1 ? 's' : ''} marked',
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppTheme.green,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        const Spacer(),
                        GestureDetector(
                          onTap: () => ref
                              .read(_lesionMarkersProvider.notifier)
                              .state = [],
                          child: Text(
                            'Clear all',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[500],
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          // Dermatology Scales
          _SectionCard(
            title: 'Dermatology Scales',
            child: Column(
              children: [
                // PASI Score
                Row(
                  children: [
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'PASI Score',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: AppTheme.dark,
                            ),
                          ),
                          SizedBox(height: 2),
                          Text(
                            'Psoriasis Area and Severity Index',
                            style: TextStyle(
                              fontSize: 11,
                              color: Color(0xFF9CA3AF),
                            ),
                          ),
                        ],
                      ),
                    ),
                    _ScoreControl(
                      value: pasiScore.toStringAsFixed(1),
                      onDecrement: () {
                        final current =
                            ref.read(_pasiScoreProvider);
                        if (current > 0) {
                          ref.read(_pasiScoreProvider.notifier).state =
                              (current - 0.5).clamp(0.0, 72.0);
                        }
                      },
                      onIncrement: () {
                        final current =
                            ref.read(_pasiScoreProvider);
                        ref.read(_pasiScoreProvider.notifier).state =
                            (current + 0.5).clamp(0.0, 72.0);
                      },
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                // PASI severity indicator
                _PasiSeverityBar(score: pasiScore),
                const SizedBox(height: 16),
                const Divider(height: 1),
                const SizedBox(height: 16),
                // IGA Score
                Row(
                  children: [
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'IGA Score',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: AppTheme.dark,
                            ),
                          ),
                          SizedBox(height: 2),
                          Text(
                            'Investigator Global Assessment (0–4)',
                            style: TextStyle(
                              fontSize: 11,
                              color: Color(0xFF9CA3AF),
                            ),
                          ),
                        ],
                      ),
                    ),
                    Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(8),
                        border:
                            Border.all(color: const Color(0xFFE5E7EB)),
                      ),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 4, vertical: 2),
                      child: DropdownButton<int>(
                        value: igaScore,
                        underline: const SizedBox.shrink(),
                        isDense: true,
                        items: List.generate(5, (i) {
                          const labels = [
                            'Clear',
                            'Almost Clear',
                            'Mild',
                            'Moderate',
                            'Severe'
                          ];
                          return DropdownMenuItem(
                            value: i,
                            child: Text(
                              '$i – ${labels[i]}',
                              style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500),
                            ),
                          );
                        }),
                        onChanged: (val) {
                          if (val != null) {
                            ref
                                .read(_igaScoreProvider.notifier)
                                .state = val;
                          }
                        },
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 80),
        ],
      ),
    );
  }
}

class _VitalCard extends StatelessWidget {
  final String label;
  final String unit;
  final IconData icon;
  final Color iconColor;
  final TextEditingController controller;

  const _VitalCard({
    required this.label,
    required this.unit,
    required this.icon,
    required this.iconColor,
    required this.controller,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 14, color: iconColor),
              const SizedBox(width: 4),
              Text(
                label,
                style: const TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF6B7280)),
              ),
            ],
          ),
          const SizedBox(height: 6),
          TextFormField(
            controller: controller,
            keyboardType: TextInputType.text,
            style: const TextStyle(
                fontSize: 14, fontWeight: FontWeight.w700, color: AppTheme.dark),
            decoration: InputDecoration(
              isDense: true,
              contentPadding:
                  const EdgeInsets.symmetric(horizontal: 0, vertical: 4),
              border: InputBorder.none,
              enabledBorder: InputBorder.none,
              focusedBorder: UnderlineInputBorder(
                borderSide:
                    BorderSide(color: iconColor, width: 1.5),
              ),
              suffixText: unit,
              suffixStyle: TextStyle(fontSize: 10, color: Colors.grey[400]),
            ),
          ),
        ],
      ),
    );
  }
}

class _ScoreControl extends StatelessWidget {
  final String value;
  final VoidCallback onDecrement;
  final VoidCallback onIncrement;

  const _ScoreControl({
    required this.value,
    required this.onDecrement,
    required this.onIncrement,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _ScoreButton(icon: Icons.remove_rounded, onTap: onDecrement),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            child: Text(
              value,
              style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: AppTheme.dark),
            ),
          ),
          _ScoreButton(icon: Icons.add_rounded, onTap: onIncrement),
        ],
      ),
    );
  }
}

class _ScoreButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _ScoreButton({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        child: Icon(icon, size: 16, color: AppTheme.blue),
      ),
    );
  }
}

class _PasiSeverityBar extends StatelessWidget {
  final double score;

  const _PasiSeverityBar({required this.score});

  @override
  Widget build(BuildContext context) {
    final segments = [
      ('Clear\n0', const Color(0xFF059669)),
      ('Mild\n1–6', const Color(0xFF34D399)),
      ('Mod\n7–12', const Color(0xFFF59E0B)),
      ('Severe\n≥12', const Color(0xFFEF4444)),
    ];

    int activeIdx;
    if (score == 0) {
      activeIdx = 0;
    } else if (score <= 6) {
      activeIdx = 1;
    } else if (score <= 12) {
      activeIdx = 2;
    } else {
      activeIdx = 3;
    }

    return Row(
      children: segments.asMap().entries.map((entry) {
        final idx = entry.key;
        final seg = entry.value;
        final isActive = idx == activeIdx;

        return Expanded(
          child: Container(
            margin: EdgeInsets.only(right: idx < 3 ? 4 : 0),
            padding: const EdgeInsets.symmetric(vertical: 5),
            decoration: BoxDecoration(
              color: isActive ? seg.$2 : seg.$2.withOpacity(0.15),
              borderRadius: BorderRadius.circular(4),
            ),
            child: Text(
              seg.$1,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 9,
                fontWeight: FontWeight.w600,
                color: isActive ? Colors.white : seg.$2,
                height: 1.4,
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}

// Body outline using CustomPainter
class _BodyOutlinePainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xFFCBD5E1)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2.0
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    final cx = size.width / 2;

    // Head
    final headRadius = size.width * 0.18;
    canvas.drawCircle(Offset(cx, headRadius + 4), headRadius, paint);

    // Neck
    canvas.drawLine(
      Offset(cx - 8, headRadius * 2 + 4),
      Offset(cx - 8, headRadius * 2 + 18),
      paint,
    );
    canvas.drawLine(
      Offset(cx + 8, headRadius * 2 + 4),
      Offset(cx + 8, headRadius * 2 + 18),
      paint,
    );

    // Torso
    final torsoTop = headRadius * 2 + 18;
    final torsoBottom = torsoTop + size.height * 0.32;
    final torsoPath = Path();
    torsoPath.moveTo(cx - 22, torsoTop);
    torsoPath.lineTo(cx - 26, torsoTop + size.height * 0.06);
    torsoPath.lineTo(cx - 22, torsoBottom);
    torsoPath.lineTo(cx + 22, torsoBottom);
    torsoPath.lineTo(cx + 26, torsoTop + size.height * 0.06);
    torsoPath.lineTo(cx + 22, torsoTop);
    torsoPath.close();
    canvas.drawPath(torsoPath, paint);

    // Left arm
    canvas.drawLine(
        Offset(cx - 26, torsoTop + size.height * 0.02),
        Offset(cx - 36, torsoTop + size.height * 0.18), paint);
    canvas.drawLine(
        Offset(cx - 36, torsoTop + size.height * 0.18),
        Offset(cx - 32, torsoBottom - 10), paint);

    // Right arm
    canvas.drawLine(
        Offset(cx + 26, torsoTop + size.height * 0.02),
        Offset(cx + 36, torsoTop + size.height * 0.18), paint);
    canvas.drawLine(
        Offset(cx + 36, torsoTop + size.height * 0.18),
        Offset(cx + 32, torsoBottom - 10), paint);

    // Left leg
    canvas.drawLine(
        Offset(cx - 10, torsoBottom),
        Offset(cx - 14, torsoBottom + size.height * 0.2), paint);
    canvas.drawLine(
        Offset(cx - 14, torsoBottom + size.height * 0.2),
        Offset(cx - 12, torsoBottom + size.height * 0.38), paint);

    // Right leg
    canvas.drawLine(
        Offset(cx + 10, torsoBottom),
        Offset(cx + 14, torsoBottom + size.height * 0.2), paint);
    canvas.drawLine(
        Offset(cx + 14, torsoBottom + size.height * 0.2),
        Offset(cx + 12, torsoBottom + size.height * 0.38), paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

// ── Prescription Tab ───────────────────────────────────────────────────────────

class _PrescriptionTab extends ConsumerStatefulWidget {
  const _PrescriptionTab();

  @override
  ConsumerState<_PrescriptionTab> createState() =>
      _PrescriptionTabState();
}

class _PrescriptionTabState extends ConsumerState<_PrescriptionTab> {
  final _searchCtrl = TextEditingController();

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final drugs = ref.watch(_prescriptionDrugsProvider);

    return Column(
      children: [
        // Drug search
        Container(
          color: Colors.white,
          padding: const EdgeInsets.all(16),
          child: TextField(
            controller: _searchCtrl,
            decoration: InputDecoration(
              hintText: 'Search drugs, generics, brands...',
              hintStyle:
                  TextStyle(fontSize: 13, color: Colors.grey[400]),
              prefixIcon: const Icon(Icons.search_rounded,
                  size: 20, color: Color(0xFF9CA3AF)),
              filled: true,
              fillColor: AppTheme.surface,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: BorderSide.none,
              ),
              contentPadding: const EdgeInsets.symmetric(
                  horizontal: 16, vertical: 12),
            ),
          ),
        ),
        // Drug list
        Expanded(
          child: ListView(
            padding:
                const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    '${drugs.length} medications added',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[500],
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  TextButton.icon(
                    onPressed: () => _showAddDrugSheet(context, ref),
                    style: TextButton.styleFrom(
                      foregroundColor: AppTheme.blue,
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                    ),
                    icon: const Icon(Icons.add_circle_outline_rounded,
                        size: 16),
                    label: const Text(
                      'Add Drug',
                      style: TextStyle(
                          fontSize: 13, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              ...drugs.asMap().entries.map((entry) {
                final idx = entry.key;
                final drug = entry.value;
                return _DrugListTile(
                  drug: drug,
                  index: idx + 1,
                  onDelete: () {
                    final updated = List<_Drug>.from(drugs)
                      ..removeAt(idx);
                    ref
                        .read(_prescriptionDrugsProvider.notifier)
                        .state = updated;
                  },
                );
              }),
              const SizedBox(height: 16),
              OutlinedButton.icon(
                onPressed: () => _showAddDrugSheet(context, ref),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppTheme.blue,
                  side: const BorderSide(color: AppTheme.blue),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10)),
                ),
                icon: const Icon(Icons.add_rounded),
                label: const Text(
                  'Add Another Drug',
                  style: TextStyle(fontWeight: FontWeight.w600),
                ),
              ),
              const SizedBox(height: 80),
            ],
          ),
        ),
      ],
    );
  }

  void _showAddDrugSheet(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
          borderRadius:
              BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => _AddDrugSheet(
        onAdd: (drug) {
          final drugs =
              ref.read(_prescriptionDrugsProvider);
          ref
              .read(_prescriptionDrugsProvider.notifier)
              .state = [...drugs, drug];
        },
      ),
    );
  }
}

class _DrugListTile extends StatelessWidget {
  final _Drug drug;
  final int index;
  final VoidCallback onDelete;

  const _DrugListTile({
    required this.drug,
    required this.index,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: AppTheme.blueLight,
                borderRadius: BorderRadius.circular(6),
              ),
              alignment: Alignment.center,
              child: Text(
                '$index',
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: AppTheme.blue,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    drug.name,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: AppTheme.dark,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      _DosageBadge(label: drug.frequency),
                      const SizedBox(width: 6),
                      _DosageBadge(
                          label: drug.duration, color: AppTheme.teal),
                      const SizedBox(width: 6),
                      if (drug.strength.isNotEmpty)
                        _DosageBadge(
                            label: drug.strength,
                            color: const Color(0xFF7C3AED)),
                    ],
                  ),
                  if (drug.instructions.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Text(
                      drug.instructions,
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey[500],
                        fontStyle: FontStyle.italic,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            GestureDetector(
              onTap: onDelete,
              child: Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF2F2),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Icon(Icons.delete_outline_rounded,
                    size: 16, color: AppTheme.red),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DosageBadge extends StatelessWidget {
  final String label;
  final Color color;

  const _DosageBadge({
    required this.label,
    this.color = AppTheme.blue,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(100),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _AddDrugSheet extends StatefulWidget {
  final void Function(_Drug drug) onAdd;

  const _AddDrugSheet({required this.onAdd});

  @override
  State<_AddDrugSheet> createState() => _AddDrugSheetState();
}

class _AddDrugSheetState extends State<_AddDrugSheet> {
  final _drugNameCtrl = TextEditingController();
  final _strengthCtrl = TextEditingController();
  final _durationCtrl = TextEditingController();
  final _instructionsCtrl = TextEditingController();
  String _selectedFrequency = 'OD';

  static const _frequencies = ['OD', 'BD', 'TDS', 'QID', 'SOS'];

  @override
  void dispose() {
    _drugNameCtrl.dispose();
    _strengthCtrl.dispose();
    _durationCtrl.dispose();
    _instructionsCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text(
                'Add Medication',
                style: TextStyle(
                  fontFamily: 'Sora',
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: AppTheme.dark,
                ),
              ),
              const Spacer(),
              GestureDetector(
                onTap: () => Navigator.pop(context),
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: AppTheme.surface,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Icon(Icons.close_rounded,
                      size: 18, color: Color(0xFF6B7280)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          _FormField(
              controller: _drugNameCtrl,
              label: 'Drug Name',
              hint: 'e.g. Betamethasone 0.05% Cream'),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _FormField(
                    controller: _strengthCtrl,
                    label: 'Strength / Dose',
                    hint: 'e.g. 0.05%, 500mg'),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _FormField(
                    controller: _durationCtrl,
                    label: 'Duration',
                    hint: 'e.g. 2 weeks'),
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Text(
            'Frequency',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: Color(0xFF6B7280),
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: _frequencies.map((f) {
              final isSelected = _selectedFrequency == f;
              return Expanded(
                child: GestureDetector(
                  onTap: () =>
                      setState(() => _selectedFrequency = f),
                  child: Container(
                    margin: EdgeInsets.only(
                        right: f != _frequencies.last ? 6 : 0),
                    padding: const EdgeInsets.symmetric(vertical: 9),
                    decoration: BoxDecoration(
                      color:
                          isSelected ? AppTheme.blue : Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: isSelected
                            ? AppTheme.blue
                            : const Color(0xFFE5E7EB),
                      ),
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      f,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: isSelected
                            ? Colors.white
                            : AppTheme.dark,
                      ),
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
          const SizedBox(height: 12),
          _FormField(
            controller: _instructionsCtrl,
            label: 'Instructions',
            hint: 'e.g. Apply at night, take after meals...',
            maxLines: 2,
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () {
                if (_drugNameCtrl.text.trim().isEmpty) return;
                widget.onAdd(_Drug(
                  name: _drugNameCtrl.text.trim(),
                  strength: _strengthCtrl.text.trim(),
                  frequency: _selectedFrequency,
                  duration: _durationCtrl.text.trim(),
                  instructions: _instructionsCtrl.text.trim(),
                ));
                Navigator.pop(context);
              },
              child: const Text('Add to Prescription'),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Plan Tab ───────────────────────────────────────────────────────────────────

class _PlanTab extends ConsumerWidget {
  final TextEditingController diagnosisCtrl;
  final TextEditingController planCtrl;
  final DateTime? followUpDate;
  final void Function(DateTime?) onFollowUpDateChanged;

  const _PlanTab({
    required this.diagnosisCtrl,
    required this.planCtrl,
    required this.followUpDate,
    required this.onFollowUpDateChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final whatsAppOn = ref.watch(_whatsAppToggleProvider);

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          _SectionCard(
            title: 'Diagnosis & Assessment',
            child: Column(
              children: [
                _FormField(
                  controller: diagnosisCtrl,
                  label: 'Diagnosis / Assessment',
                  hint:
                      'ICD-10 code or free text diagnosis...',
                  maxLines: 3,
                ),
                const SizedBox(height: 12),
                _FormField(
                  controller: planCtrl,
                  label: 'Plan & Instructions',
                  hint:
                      'Treatment plan, lifestyle advice, follow-up instructions...',
                  maxLines: 4,
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            title: 'Follow-up',
            child: GestureDetector(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: DateTime.now()
                      .add(const Duration(days: 30)),
                  firstDate: DateTime.now(),
                  lastDate: DateTime.now()
                      .add(const Duration(days: 365)),
                  builder: (ctx, child) => Theme(
                    data: ThemeData.light().copyWith(
                      colorScheme: const ColorScheme.light(
                        primary: AppTheme.blue,
                      ),
                    ),
                    child: child!,
                  ),
                );
                onFollowUpDateChanged(picked);
              },
              child: Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: 14, vertical: 14),
                decoration: BoxDecoration(
                  color: followUpDate != null
                      ? AppTheme.blueLight
                      : Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(
                    color: followUpDate != null
                        ? AppTheme.blue.withOpacity(0.4)
                        : const Color(0xFFE5E7EB),
                  ),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.calendar_today_rounded,
                      size: 18,
                      color: followUpDate != null
                          ? AppTheme.blue
                          : const Color(0xFF9CA3AF),
                    ),
                    const SizedBox(width: 10),
                    Text(
                      followUpDate != null
                          ? _formatDate(followUpDate!)
                          : 'Select next follow-up date',
                      style: TextStyle(
                        fontSize: 14,
                        color: followUpDate != null
                            ? AppTheme.blue
                            : const Color(0xFF9CA3AF),
                        fontWeight: followUpDate != null
                            ? FontWeight.w600
                            : FontWeight.w400,
                      ),
                    ),
                    const Spacer(),
                    if (followUpDate != null)
                      GestureDetector(
                        onTap: () => onFollowUpDateChanged(null),
                        child: Icon(Icons.close_rounded,
                            size: 16, color: Colors.grey[400]),
                      ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            title: 'Communication',
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: const Color(0xFFDCFCE7),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.chat_rounded,
                      size: 20, color: Color(0xFF16A34A)),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Send via WhatsApp',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppTheme.dark,
                        ),
                      ),
                      SizedBox(height: 2),
                      Text(
                        'Share prescription with patient on WhatsApp',
                        style: TextStyle(
                          fontSize: 11,
                          color: Color(0xFF9CA3AF),
                        ),
                      ),
                    ],
                  ),
                ),
                Switch(
                  value: whatsAppOn,
                  onChanged: (val) => ref
                      .read(_whatsAppToggleProvider.notifier)
                      .state = val,
                  activeColor: AppTheme.green,
                  activeTrackColor: AppTheme.greenLight,
                ),
              ],
            ),
          ),
          const SizedBox(height: 80),
        ],
      ),
    );
  }

  String _formatDate(DateTime date) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    return '${date.day} ${months[date.month - 1]} ${date.year}';
  }
}

// ── Bottom Bar ─────────────────────────────────────────────────────────────────

class _BottomBar extends ConsumerWidget {
  final int patientId;
  final int visitId;

  const _BottomBar({required this.patientId, required this.visitId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final saveStatus = ref.watch(_autoSaveProvider);

    return Container(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 12,
        bottom: MediaQuery.of(context).padding.bottom + 12,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Color(0xFFE5E7EB))),
      ),
      child: Row(
        children: [
          // Auto-save indicator
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 8,
                height: 8,
                decoration: BoxDecoration(
                  color: saveStatus == 'Saved'
                      ? AppTheme.green
                      : AppTheme.amber,
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 6),
              Text(
                saveStatus == 'Saved' ? 'Auto-saved' : 'Saving...',
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.grey[500],
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(width: 12),
          // Finalise button
          Expanded(
            child: ElevatedButton(
              onPressed: () => _showFinaliseDialog(context, ref),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.blue,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10)),
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.check_circle_outline_rounded, size: 18),
                  SizedBox(width: 8),
                  Text(
                    'Finalise & Complete',
                    style: TextStyle(
                        fontWeight: FontWeight.w700, fontSize: 15),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showFinaliseDialog(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text(
          'Finalise Visit?',
          style: TextStyle(
              fontFamily: 'Sora',
              fontWeight: FontWeight.w700,
              color: AppTheme.dark),
        ),
        content: const Text(
          'This will complete the EMR for this visit. You can still edit it within 24 hours.',
          style: TextStyle(fontSize: 14, color: Color(0xFF6B7280)),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel',
                style: TextStyle(color: Color(0xFF6B7280))),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  backgroundColor: AppTheme.green,
                  content: const Row(
                    children: [
                      Icon(Icons.check_circle_rounded,
                          color: Colors.white, size: 18),
                      SizedBox(width: 8),
                      Text('Visit completed successfully',
                          style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w600)),
                    ],
                  ),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10)),
                  behavior: SnackBarBehavior.floating,
                  margin: const EdgeInsets.all(16),
                ),
              );
            },
            child: const Text('Finalise'),
          ),
        ],
      ),
    );
  }
}

// ── Shared Widgets ─────────────────────────────────────────────────────────────

class _SectionCard extends StatelessWidget {
  final String title;
  final String? subtitle;
  final Color? titleColor;
  final IconData? titleIcon;
  final Widget child;

  const _SectionCard({
    required this.title,
    this.subtitle,
    this.titleColor,
    this.titleIcon,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                if (titleIcon != null) ...[
                  Icon(titleIcon, size: 16, color: titleColor ?? AppTheme.dark),
                  const SizedBox(width: 6),
                ],
                Text(
                  title,
                  style: TextStyle(
                    fontFamily: 'Sora',
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: titleColor ?? AppTheme.dark,
                  ),
                ),
                if (subtitle != null) ...[
                  const SizedBox(width: 8),
                  Text(
                    subtitle!,
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFF9CA3AF),
                    ),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 12),
            child,
          ],
        ),
      ),
    );
  }
}

class _FormField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String? hint;
  final int maxLines;

  const _FormField({
    required this.controller,
    required this.label,
    this.hint,
    this.maxLines = 1,
  });

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      style: const TextStyle(fontSize: 14, color: AppTheme.dark),
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        hintStyle: TextStyle(fontSize: 13, color: Colors.grey[400]),
        alignLabelWithHint: maxLines > 1,
      ),
    );
  }
}
