import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';

// ── Data Models ────────────────────────────────────────────────────────────────

enum InvoiceStatus { paid, pending, overdue }

class Invoice {
  final int id;
  final String invoiceNumber;
  final String patientName;
  final String date;
  final String services;
  final double subtotal;
  final double gstPercent;
  final InvoiceStatus status;
  final String? paymentMethod;

  const Invoice({
    required this.id,
    required this.invoiceNumber,
    required this.patientName,
    required this.date,
    required this.services,
    required this.subtotal,
    required this.gstPercent,
    required this.status,
    this.paymentMethod,
  });

  double get cgst => subtotal * (gstPercent / 200);
  double get sgst => subtotal * (gstPercent / 200);
  double get grandTotal => subtotal + cgst + sgst;
}

// ── Static Data ────────────────────────────────────────────────────────────────

const _allInvoices = [
  Invoice(
    id: 1,
    invoiceNumber: 'INV-2026-0234',
    patientName: 'Pooja Mehta',
    date: '25 Mar 2026',
    services: 'Chemical Peel + Consultation',
    subtotal: 2300,
    gstPercent: 18,
    status: InvoiceStatus.paid,
    paymentMethod: 'UPI',
  ),
  Invoice(
    id: 2,
    invoiceNumber: 'INV-2026-0233',
    patientName: 'Rahul Sharma',
    date: '24 Mar 2026',
    services: 'Laser Toning + Follow-up',
    subtotal: 4500,
    gstPercent: 18,
    status: InvoiceStatus.pending,
  ),
  Invoice(
    id: 3,
    invoiceNumber: 'INV-2026-0232',
    patientName: 'Ananya Krishnan',
    date: '22 Mar 2026',
    services: 'PRP Hair Treatment',
    subtotal: 6000,
    gstPercent: 18,
    status: InvoiceStatus.paid,
    paymentMethod: 'Card',
  ),
  Invoice(
    id: 4,
    invoiceNumber: 'INV-2026-0231',
    patientName: 'Vikram Joshi',
    date: '20 Mar 2026',
    services: 'Acne Scar Treatment + Medications',
    subtotal: 3200,
    gstPercent: 18,
    status: InvoiceStatus.overdue,
  ),
  Invoice(
    id: 5,
    invoiceNumber: 'INV-2026-0230',
    patientName: 'Sunita Patel',
    date: '18 Mar 2026',
    services: 'Consultation + Prescription',
    subtotal: 800,
    gstPercent: 18,
    status: InvoiceStatus.paid,
    paymentMethod: 'Cash',
  ),
  Invoice(
    id: 6,
    invoiceNumber: 'INV-2026-0229',
    patientName: 'Arjun Nair',
    date: '15 Mar 2026',
    services: 'Dermal Filler (1ml)',
    subtotal: 12000,
    gstPercent: 18,
    status: InvoiceStatus.paid,
    paymentMethod: 'UPI',
  ),
  Invoice(
    id: 7,
    invoiceNumber: 'INV-2026-0228',
    patientName: 'Kavya Reddy',
    date: '12 Mar 2026',
    services: 'Hydrafacial + Sunscreen',
    subtotal: 2800,
    gstPercent: 18,
    status: InvoiceStatus.pending,
  ),
  Invoice(
    id: 8,
    invoiceNumber: 'INV-2026-0227',
    patientName: 'Deepak Malhotra',
    date: '10 Mar 2026',
    services: 'Psoriasis Review + PASI Assessment',
    subtotal: 1500,
    gstPercent: 18,
    status: InvoiceStatus.overdue,
  ),
  Invoice(
    id: 9,
    invoiceNumber: 'INV-2026-0226',
    patientName: 'Priya Iyer',
    date: '08 Mar 2026',
    services: 'Chemical Peel + Anti-aging Treatment',
    subtotal: 5500,
    gstPercent: 18,
    status: InvoiceStatus.paid,
    paymentMethod: 'Card',
  ),
  Invoice(
    id: 10,
    invoiceNumber: 'INV-2026-0225',
    patientName: 'Rohan Verma',
    date: '05 Mar 2026',
    services: 'New Consultation + Patch Test',
    subtotal: 1200,
    gstPercent: 18,
    status: InvoiceStatus.pending,
  ),
];

// ── Providers ──────────────────────────────────────────────────────────────────

final _billingFilterProvider = StateProvider<int>((ref) => 0);
final _expandedInvoiceProvider = StateProvider<int?>((ref) => null);

// ── Main Screen ────────────────────────────────────────────────────────────────

class BillingScreen extends ConsumerWidget {
  const BillingScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filterIndex = ref.watch(_billingFilterProvider);

    final filteredInvoices = switch (filterIndex) {
      1 => _allInvoices
          .where((i) => i.status == InvoiceStatus.paid)
          .toList(),
      2 => _allInvoices
          .where((i) => i.status == InvoiceStatus.pending)
          .toList(),
      3 => _allInvoices
          .where((i) => i.status == InvoiceStatus.overdue)
          .toList(),
      _ => _allInvoices.toList(),
    };

    final totalFiltered =
        filteredInvoices.fold(0.0, (sum, i) => sum + i.grandTotal);

    return Scaffold(
      backgroundColor: AppTheme.surface,
      body: CustomScrollView(
        slivers: [
          _buildSliverAppBar(context),
          SliverToBoxAdapter(
            child: Column(
              children: [
                _StatsRow(),
                _FilterTabBar(filterIndex: filterIndex, ref: ref),
              ],
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            sliver: filteredInvoices.isEmpty
                ? SliverFillRemaining(
                    child: _EmptyState(filterIndex: filterIndex),
                  )
                : SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) {
                        return _InvoiceTile(
                          invoice: filteredInvoices[index],
                        );
                      },
                      childCount: filteredInvoices.length,
                    ),
                  ),
          ),
          // Bottom padding for summary bar
          const SliverToBoxAdapter(child: SizedBox(height: 80)),
        ],
      ),
      bottomNavigationBar: _SummaryBar(
        total: totalFiltered,
        count: filteredInvoices.length,
        filterIndex: filterIndex,
      ),
    );
  }

  SliverAppBar _buildSliverAppBar(BuildContext context) {
    return SliverAppBar(
      pinned: true,
      backgroundColor: Colors.white,
      elevation: 0,
      scrolledUnderElevation: 1,
      title: const Text(
        'Billing',
        style: TextStyle(
          fontFamily: 'Sora',
          fontSize: 20,
          fontWeight: FontWeight.w700,
          color: AppTheme.dark,
        ),
      ),
      actions: [
        Padding(
          padding: const EdgeInsets.only(right: 16),
          child: ElevatedButton.icon(
            onPressed: () {},
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.blue,
              foregroundColor: Colors.white,
              padding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8)),
            ),
            icon: const Icon(Icons.add_rounded, size: 16),
            label: const Text(
              'New Invoice',
              style:
                  TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ),
        ),
      ],
    );
  }
}

// ── Stats Row ──────────────────────────────────────────────────────────────────

class _StatsRow extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
      child: Row(
        children: [
          Expanded(
            child: _StatCard(
              label: 'This Month',
              value: '₹4.82L',
              icon: Icons.account_balance_wallet_rounded,
              iconBg: AppTheme.blueLight,
              iconColor: AppTheme.blue,
              valueColor: AppTheme.blue,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _StatCard(
              label: 'Pending',
              value: '₹1.24L',
              icon: Icons.schedule_rounded,
              iconBg: const Color(0xFFFFF7ED),
              iconColor: AppTheme.amber,
              valueColor: AppTheme.amber,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _StatCard(
              label: 'Collected',
              value: '₹3.58L',
              icon: Icons.check_circle_rounded,
              iconBg: AppTheme.greenLight,
              iconColor: AppTheme.green,
              valueColor: AppTheme.green,
            ),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color iconBg;
  final Color iconColor;
  final Color valueColor;

  const _StatCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.iconBg,
    required this.iconColor,
    required this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: iconBg,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, size: 17, color: iconColor),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontFamily: 'Sora',
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: valueColor,
            ),
          ),
          const SizedBox(height: 2),
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
    );
  }
}

// ── Filter Tab Bar ─────────────────────────────────────────────────────────────

class _FilterTabBar extends StatelessWidget {
  final int filterIndex;
  final WidgetRef ref;

  const _FilterTabBar({
    required this.filterIndex,
    required this.ref,
  });

  @override
  Widget build(BuildContext context) {
    final counts = [
      _allInvoices.length,
      _allInvoices.where((i) => i.status == InvoiceStatus.paid).length,
      _allInvoices.where((i) => i.status == InvoiceStatus.pending).length,
      _allInvoices.where((i) => i.status == InvoiceStatus.overdue).length,
    ];

    final tabs = ['All', 'Paid', 'Pending', 'Overdue'];
    final colors = [
      AppTheme.blue,
      AppTheme.green,
      AppTheme.amber,
      AppTheme.red
    ];

    return Container(
      color: Colors.white,
      padding: const EdgeInsets.only(left: 16, bottom: 12),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: tabs.asMap().entries.map((entry) {
            final idx = entry.key;
            final label = entry.value;
            final isSelected = filterIndex == idx;
            final color = colors[idx];

            return GestureDetector(
              onTap: () => ref
                  .read(_billingFilterProvider.notifier)
                  .state = idx,
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 150),
                margin: const EdgeInsets.only(right: 8),
                padding: const EdgeInsets.symmetric(
                    horizontal: 14, vertical: 7),
                decoration: BoxDecoration(
                  color:
                      isSelected ? color : Colors.white,
                  borderRadius: BorderRadius.circular(100),
                  border: Border.all(
                    color: isSelected
                        ? color
                        : const Color(0xFFE5E7EB),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      label,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color:
                            isSelected ? Colors.white : const Color(0xFF374151),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: isSelected
                            ? Colors.white.withOpacity(0.25)
                            : const Color(0xFFF3F4F6),
                        borderRadius: BorderRadius.circular(100),
                      ),
                      child: Text(
                        '${counts[idx]}',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                          color: isSelected
                              ? Colors.white
                              : const Color(0xFF6B7280),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          }).toList(),
        ),
      ),
    );
  }
}

// ── Invoice Tile ───────────────────────────────────────────────────────────────

class _InvoiceTile extends ConsumerWidget {
  final Invoice invoice;

  const _InvoiceTile({required this.invoice});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final expandedId = ref.watch(_expandedInvoiceProvider);
    final isExpanded = expandedId == invoice.id;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        children: [
          // Main tile content
          InkWell(
            onTap: () {
              ref.read(_expandedInvoiceProvider.notifier).state =
                  isExpanded ? null : invoice.id;
            },
            borderRadius: isExpanded
                ? const BorderRadius.vertical(top: Radius.circular(12))
                : BorderRadius.circular(12),
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      // Invoice number + patient
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Text(
                                  invoice.invoiceNumber,
                                  style: const TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF6B7280),
                                    fontFamily: 'Inter',
                                  ),
                                ),
                                const SizedBox(width: 8),
                                _StatusBadge(status: invoice.status),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              invoice.patientName,
                              style: const TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
                                color: AppTheme.dark,
                                fontFamily: 'Sora',
                              ),
                            ),
                          ],
                        ),
                      ),
                      // Amount
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            _formatRupees(invoice.grandTotal),
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                              color: AppTheme.dark,
                              fontFamily: 'Sora',
                            ),
                          ),
                          Text(
                            invoice.date,
                            style: const TextStyle(
                              fontSize: 11,
                              color: Color(0xFF9CA3AF),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.medical_services_outlined,
                          size: 13, color: Color(0xFF9CA3AF)),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          invoice.services,
                          style: const TextStyle(
                            fontSize: 12,
                            color: Color(0xFF6B7280),
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Icon(
                        isExpanded
                            ? Icons.keyboard_arrow_up_rounded
                            : Icons.keyboard_arrow_down_rounded,
                        size: 18,
                        color: const Color(0xFF9CA3AF),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          // Expandable GST breakdown
          AnimatedSize(
            duration: const Duration(milliseconds: 200),
            curve: Curves.easeInOut,
            child: isExpanded
                ? _GstBreakdown(invoice: invoice)
                : const SizedBox.shrink(),
          ),
          // Action row
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: const BoxDecoration(
              color: Color(0xFFFAFAFA),
              borderRadius:
                  BorderRadius.vertical(bottom: Radius.circular(12)),
              border:
                  Border(top: BorderSide(color: Color(0xFFE5E7EB))),
            ),
            child: Row(
              children: [
                if (invoice.paymentMethod != null) ...[
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: AppTheme.greenLight,
                      borderRadius: BorderRadius.circular(100),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.check_circle_rounded,
                            size: 10, color: AppTheme.green),
                        const SizedBox(width: 4),
                        Text(
                          invoice.paymentMethod!,
                          style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            color: AppTheme.green,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 6),
                ],
                const Spacer(),
                // Download PDF
                _ActionIconButton(
                  icon: Icons.download_rounded,
                  label: 'PDF',
                  onTap: () {},
                ),
                const SizedBox(width: 8),
                // Send WhatsApp
                _ActionIconButton(
                  icon: Icons.chat_rounded,
                  label: 'Share',
                  color: const Color(0xFF16A34A),
                  onTap: () {},
                ),
                const SizedBox(width: 8),
                // Collect Payment (only for pending/overdue)
                if (invoice.status != InvoiceStatus.paid)
                  _ActionIconButton(
                    icon: Icons.payment_rounded,
                    label: 'Collect',
                    color: AppTheme.blue,
                    filled: true,
                    onTap: () => _showCollectPaymentSheet(context, invoice),
                  ),
                // View Invoice
                if (invoice.status == InvoiceStatus.paid)
                  _ActionIconButton(
                    icon: Icons.receipt_long_rounded,
                    label: 'View',
                    color: AppTheme.blue,
                    filled: true,
                    onTap: () => context.push('/billing/${invoice.id}'),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showCollectPaymentSheet(BuildContext context, Invoice invoice) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
          borderRadius:
              BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => _CollectPaymentSheet(invoice: invoice),
    );
  }

  String _formatRupees(double amount) {
    if (amount >= 100000) {
      return '₹${(amount / 100000).toStringAsFixed(2)}L';
    } else if (amount >= 1000) {
      final k = amount / 1000;
      return k == k.truncateToDouble()
          ? '₹${k.toInt()}K'
          : '₹${k.toStringAsFixed(1)}K';
    }
    return '₹${amount.toStringAsFixed(0)}';
  }
}

class _GstBreakdown extends StatelessWidget {
  final Invoice invoice;

  const _GstBreakdown({required this.invoice});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
      decoration: const BoxDecoration(
        color: Color(0xFFFAFAFB),
        border:
            Border(top: BorderSide(color: Color(0xFFE5E7EB))),
      ),
      child: Column(
        children: [
          _BreakdownRow(
              label: 'Subtotal', value: invoice.subtotal),
          const SizedBox(height: 4),
          _BreakdownRow(
              label:
                  'CGST (${(invoice.gstPercent / 2).toStringAsFixed(0)}%)',
              value: invoice.cgst),
          const SizedBox(height: 4),
          _BreakdownRow(
              label:
                  'SGST (${(invoice.gstPercent / 2).toStringAsFixed(0)}%)',
              value: invoice.sgst),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 6),
            child: Divider(height: 1),
          ),
          _BreakdownRow(
            label: 'Grand Total',
            value: invoice.grandTotal,
            isBold: true,
          ),
        ],
      ),
    );
  }
}

class _BreakdownRow extends StatelessWidget {
  final String label;
  final double value;
  final bool isBold;

  const _BreakdownRow({
    required this.label,
    required this.value,
    this.isBold = false,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight:
                isBold ? FontWeight.w700 : FontWeight.w400,
            color:
                isBold ? AppTheme.dark : const Color(0xFF6B7280),
          ),
        ),
        Text(
          '₹${value.toStringAsFixed(2)}',
          style: TextStyle(
            fontSize: 12,
            fontWeight:
                isBold ? FontWeight.w700 : FontWeight.w500,
            color:
                isBold ? AppTheme.dark : const Color(0xFF374151),
          ),
        ),
      ],
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final InvoiceStatus status;

  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final (label, bgColor, textColor) = switch (status) {
      InvoiceStatus.paid => ('Paid', AppTheme.greenLight, AppTheme.green),
      InvoiceStatus.pending =>
        ('Pending', const Color(0xFFFFF7ED), AppTheme.amber),
      InvoiceStatus.overdue =>
        ('Overdue', const Color(0xFFFEF2F2), AppTheme.red),
    };

    return Container(
      padding:
          const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(100),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: textColor,
        ),
      ),
    );
  }
}

class _ActionIconButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final bool filled;
  final VoidCallback onTap;

  const _ActionIconButton({
    required this.icon,
    required this.label,
    this.color = const Color(0xFF6B7280),
    this.filled = false,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: filled ? color : color.withOpacity(0.08),
          borderRadius: BorderRadius.circular(7),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon,
                size: 14,
                color: filled ? Colors.white : color),
            const SizedBox(width: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: filled ? Colors.white : color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Collect Payment Sheet ──────────────────────────────────────────────────────

class _CollectPaymentSheet extends StatefulWidget {
  final Invoice invoice;

  const _CollectPaymentSheet({required this.invoice});

  @override
  State<_CollectPaymentSheet> createState() =>
      _CollectPaymentSheetState();
}

class _CollectPaymentSheetState
    extends State<_CollectPaymentSheet> {
  String _selectedMethod = 'UPI';
  static const _methods = ['UPI', 'Card', 'Cash', 'Cheque'];

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
                'Collect Payment',
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
          const SizedBox(height: 8),
          Text(
            widget.invoice.patientName,
            style: const TextStyle(
                fontSize: 14, color: Color(0xFF6B7280)),
          ),
          const SizedBox(height: 20),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: AppTheme.blueLight,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Amount Due',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.blue,
                  ),
                ),
                Text(
                  '₹${widget.invoice.grandTotal.toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontFamily: 'Sora',
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: AppTheme.blue,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          const Text(
            'Payment Method',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: Color(0xFF6B7280),
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: _methods.map((m) {
              final isSelected = _selectedMethod == m;
              return Expanded(
                child: GestureDetector(
                  onTap: () =>
                      setState(() => _selectedMethod = m),
                  child: Container(
                    margin: EdgeInsets.only(
                        right: m != _methods.last ? 8 : 0),
                    padding:
                        const EdgeInsets.symmetric(vertical: 10),
                    decoration: BoxDecoration(
                      color: isSelected
                          ? AppTheme.blue
                          : Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: isSelected
                            ? AppTheme.blue
                            : const Color(0xFFE5E7EB),
                      ),
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      m,
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
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () {
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    backgroundColor: AppTheme.green,
                    content: Text(
                      'Payment of ₹${widget.invoice.grandTotal.toStringAsFixed(2)} collected via $_selectedMethod',
                      style: const TextStyle(color: Colors.white),
                    ),
                    behavior: SnackBarBehavior.floating,
                    margin: const EdgeInsets.all(16),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10)),
                  ),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.green,
                padding:
                    const EdgeInsets.symmetric(vertical: 14),
              ),
              icon: const Icon(Icons.check_circle_rounded,
                  size: 18),
              label: const Text(
                'Confirm Payment',
                style: TextStyle(
                    fontWeight: FontWeight.w700, fontSize: 15),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Empty State ────────────────────────────────────────────────────────────────

class _EmptyState extends StatelessWidget {
  final int filterIndex;

  const _EmptyState({required this.filterIndex});

  @override
  Widget build(BuildContext context) {
    const labels = ['invoices', 'paid invoices', 'pending invoices', 'overdue invoices'];
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: AppTheme.surface,
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Icons.receipt_long_rounded,
                size: 28, color: Color(0xFFD1D5DB)),
          ),
          const SizedBox(height: 12),
          Text(
            'No ${labels[filterIndex]}',
            style: const TextStyle(
              fontFamily: 'Sora',
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: AppTheme.dark,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Invoices will appear here once created.',
            style: TextStyle(fontSize: 13, color: Colors.grey[500]),
          ),
        ],
      ),
    );
  }
}

// ── Summary Bar ────────────────────────────────────────────────────────────────

class _SummaryBar extends StatelessWidget {
  final double total;
  final int count;
  final int filterIndex;

  const _SummaryBar({
    required this.total,
    required this.count,
    required this.filterIndex,
  });

  @override
  Widget build(BuildContext context) {
    const filterLabels = ['Total', 'Paid', 'Pending', 'Overdue'];
    const filterColors = [
      AppTheme.blue,
      AppTheme.green,
      AppTheme.amber,
      AppTheme.red
    ];

    final label = filterLabels[filterIndex];
    final color = filterColors[filterIndex];
    final bottom = MediaQuery.of(context).padding.bottom;

    return Container(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 14,
        bottom: bottom + 14,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Color(0xFFE5E7EB))),
      ),
      child: Row(
        children: [
          Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '$count invoice${count != 1 ? 's' : ''}',
                style: const TextStyle(
                  fontSize: 12,
                  color: Color(0xFF9CA3AF),
                  fontWeight: FontWeight.w500,
                ),
              ),
              Text(
                '$label Amount',
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: AppTheme.dark,
                ),
              ),
            ],
          ),
          const Spacer(),
          Container(
            padding: const EdgeInsets.symmetric(
                horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Text(
              '₹${total.toStringAsFixed(2)}',
              style: TextStyle(
                fontFamily: 'Sora',
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: color,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
