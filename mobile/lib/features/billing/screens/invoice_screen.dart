import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'dart:developer' as developer;

class InvoiceScreen extends ConsumerStatefulWidget {
  final int invoiceId;

  const InvoiceScreen({super.key, required this.invoiceId});

  @override
  ConsumerState<InvoiceScreen> createState() => _InvoiceScreenState();
}

class _InvoiceScreenState extends ConsumerState<InvoiceScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _invoice;

  @override
  void initState() {
    super.initState();
    developer.log('InvoiceScreen initialized for id: ${widget.invoiceId}', name: 'InvoiceScreen');
    _loadInvoice();
  }

  Future<void> _loadInvoice() async {
    developer.log('Loading invoice', name: 'InvoiceScreen');
    
    // TODO: Load from API
    await Future.delayed(const Duration(milliseconds: 500));
    
    // Mock data
    setState(() {
      _isLoading = false;
      _invoice = {
        'id': widget.invoiceId,
        'invoice_number': 'CLN001-2026-0042',
        'invoice_date': '2026-03-26',
        'patient': {
          'name': 'Rajesh Kumar',
          'phone': '9876543210',
        },
        'items': [
          {
            'description': 'Consultation',
            'sac_code': '999311',
            'gst_rate': 0.0,
            'unit_price': 500.0,
            'quantity': 1,
            'cgst': 0.0,
            'sgst': 0.0,
            'total': 500.0,
          },
          {
            'description': 'LASER Session - Q-Switch',
            'sac_code': '999312',
            'gst_rate': 18.0,
            'unit_price': 3500.0,
            'quantity': 1,
            'cgst': 315.0,
            'sgst': 315.0,
            'total': 4130.0,
          },
          {
            'description': 'Chemical Peel - Glycolic 30%',
            'sac_code': '999312',
            'gst_rate': 18.0,
            'unit_price': 2000.0,
            'quantity': 1,
            'cgst': 180.0,
            'sgst': 180.0,
            'total': 2360.0,
          },
        ],
        'subtotal': 6000.0,
        'cgst_amount': 495.0,
        'sgst_amount': 495.0,
        'discount_amount': 0.0,
        'total': 6990.0,
        'paid': 5000.0,
        'balance_due': 1990.0,
        'payment_status': 'partial',
      };
    });

    developer.log('Invoice loaded', name: 'InvoiceScreen');
  }

  void _collectPayment() {
    developer.log('Opening payment collection', name: 'InvoiceScreen');
    
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => _PaymentCollectionSheet(
        balanceDue: (_invoice?['balance_due'] as num?)?.toDouble() ?? 0,
        onPaymentCollected: (amount, method) {
          developer.log('Payment collected: ₹$amount via $method', name: 'InvoiceScreen');
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Payment of ₹$amount recorded')),
          );
          _loadInvoice(); // Refresh
        },
      ),
    );
  }

  void _shareInvoice() {
    developer.log('Sharing invoice via WhatsApp', name: 'InvoiceScreen');
    
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Sending payment link via WhatsApp...')),
    );
  }

  void _downloadPdf() {
    developer.log('Downloading PDF', name: 'InvoiceScreen');
    
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Generating PDF...')),
    );
  }

  @override
  Widget build(BuildContext context) {
    developer.log('Building InvoiceScreen', name: 'InvoiceScreen');
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
        title: Text(_invoice?['invoice_number'] ?? 'Invoice'),
        actions: [
          IconButton(
            icon: const Icon(Icons.picture_as_pdf_outlined),
            onPressed: _downloadPdf,
            tooltip: 'Download PDF',
          ),
          IconButton(
            icon: const Icon(Icons.share_outlined),
            onPressed: _shareInvoice,
            tooltip: 'Share via WhatsApp',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _buildInvoiceBody(theme),
      bottomNavigationBar: _invoice != null && (_invoice!['balance_due'] as num) > 0
          ? SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: FilledButton.icon(
                  onPressed: _collectPayment,
                  icon: const Icon(Icons.payments_outlined),
                  label: Text('Collect ₹${_invoice!['balance_due']}'),
                  style: FilledButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                ),
              ),
            )
          : null,
    );
  }

  Widget _buildInvoiceBody(ThemeData theme) {
    final invoice = _invoice!;
    final items = invoice['items'] as List;
    final patient = invoice['patient'] as Map;
    final status = invoice['payment_status'] as String;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Status chip
          Row(
            children: [
              _buildStatusChip(status, theme),
              const Spacer(),
              Text(
                invoice['invoice_date'],
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Patient info card
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  CircleAvatar(
                    backgroundColor: theme.colorScheme.primaryContainer,
                    child: Text(
                      (patient['name'] as String).substring(0, 1),
                      style: TextStyle(color: theme.colorScheme.onPrimaryContainer),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          patient['name'],
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        Text(
                          patient['phone'],
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // Items section
          Text(
            'Items',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 12),

          // Items list
          Card(
            child: Column(
              children: [
                for (var i = 0; i < items.length; i++) ...[
                  _buildItemRow(items[i], theme),
                  if (i < items.length - 1) const Divider(height: 1),
                ],
              ],
            ),
          ),
          const SizedBox(height: 24),

          // GST Breakdown
          Text(
            'Tax Breakdown',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 12),

          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  _buildSummaryRow('Subtotal', invoice['subtotal'], theme),
                  const SizedBox(height: 8),
                  _buildSummaryRow('CGST', invoice['cgst_amount'], theme),
                  _buildSummaryRow('SGST', invoice['sgst_amount'], theme),
                  if ((invoice['discount_amount'] as num) > 0) ...[
                    const SizedBox(height: 8),
                    _buildSummaryRow('Discount', -invoice['discount_amount'], theme),
                  ],
                  const Divider(height: 24),
                  _buildSummaryRow('Total', invoice['total'], theme, isBold: true),
                  const SizedBox(height: 8),
                  _buildSummaryRow('Paid', invoice['paid'], theme, color: Colors.green),
                  _buildSummaryRow('Balance Due', invoice['balance_due'], theme, color: Colors.red),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // GST Notes
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceContainerHighest,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.info_outline, size: 16, color: theme.colorScheme.onSurfaceVariant),
                    const SizedBox(width: 8),
                    Text(
                      'GST Information',
                      style: theme.textTheme.labelLarge?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  '• Clinical consultations (SAC 999311) are GST exempt\n'
                  '• Cosmetic procedures (SAC 999312) attract 18% GST\n'
                  '• Place of Supply: Maharashtra (27)',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusChip(String status, ThemeData theme) {
    Color color;
    String label;
    IconData icon;

    switch (status) {
      case 'paid':
        color = Colors.green;
        label = 'Paid';
        icon = Icons.check_circle;
        break;
      case 'partial':
        color = Colors.orange;
        label = 'Partial';
        icon = Icons.pending;
        break;
      default:
        color = Colors.red;
        label = 'Pending';
        icon = Icons.hourglass_empty;
    }

    return Chip(
      avatar: Icon(icon, size: 16, color: color),
      label: Text(label),
      backgroundColor: color.withOpacity(0.1),
      labelStyle: TextStyle(color: color, fontWeight: FontWeight.w600),
      side: BorderSide.none,
    );
  }

  Widget _buildItemRow(Map<String, dynamic> item, ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  item['description'],
                  style: theme.textTheme.bodyLarge?.copyWith(
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              Text(
                '₹${item['total']}',
                style: theme.textTheme.bodyLarge?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              Text(
                '₹${item['unit_price']} × ${item['quantity']}',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: theme.colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  'SAC ${item['sac_code']} | ${item['gst_rate']}% GST',
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(
    String label,
    num amount,
    ThemeData theme, {
    bool isBold = false,
    Color? color,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: isBold
              ? theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600)
              : theme.textTheme.bodyMedium,
        ),
        Text(
          '${amount < 0 ? '-' : ''}₹${amount.abs()}',
          style: (isBold
                  ? theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600)
                  : theme.textTheme.bodyMedium)
              ?.copyWith(color: color),
        ),
      ],
    );
  }
}

// Payment Collection Sheet
class _PaymentCollectionSheet extends StatefulWidget {
  final double balanceDue;
  final Function(double amount, String method) onPaymentCollected;

  const _PaymentCollectionSheet({
    required this.balanceDue,
    required this.onPaymentCollected,
  });

  @override
  State<_PaymentCollectionSheet> createState() => _PaymentCollectionSheetState();
}

class _PaymentCollectionSheetState extends State<_PaymentCollectionSheet> {
  final _amountController = TextEditingController();
  String _selectedMethod = 'upi';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _amountController.text = widget.balanceDue.toStringAsFixed(0);
    developer.log('PaymentCollectionSheet initialized', name: 'PaymentCollectionSheet');
  }

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  void _collectPayment() async {
    final amount = double.tryParse(_amountController.text) ?? 0;
    if (amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a valid amount')),
      );
      return;
    }

    if (amount > widget.balanceDue) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Amount exceeds balance due')),
      );
      return;
    }

    developer.log('Collecting payment: ₹$amount via $_selectedMethod', name: 'PaymentCollectionSheet');
    setState(() => _isLoading = true);

    // TODO: Call API
    await Future.delayed(const Duration(seconds: 1));

    widget.onPaymentCollected(amount, _selectedMethod);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 16,
        bottom: MediaQuery.of(context).viewInsets.bottom + 16,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Collect Payment',
            style: theme.textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 24),

          // Amount field
          TextField(
            controller: _amountController,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(
              labelText: 'Amount',
              prefixText: '₹ ',
              helperText: 'Balance due: ₹${widget.balanceDue}',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Payment method
          Text(
            'Payment Method',
            style: theme.textTheme.labelLarge,
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              _buildMethodChip('upi', 'UPI', Icons.qr_code),
              _buildMethodChip('card', 'Card', Icons.credit_card),
              _buildMethodChip('cash', 'Cash', Icons.payments),
              _buildMethodChip('netbanking', 'Net Banking', Icons.account_balance),
            ],
          ),
          const SizedBox(height: 24),

          // Collect button
          FilledButton(
            onPressed: _isLoading ? null : _collectPayment,
            style: FilledButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: _isLoading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Record Payment'),
          ),
        ],
      ),
    );
  }

  Widget _buildMethodChip(String value, String label, IconData icon) {
    final isSelected = _selectedMethod == value;

    return FilterChip(
      label: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16),
          const SizedBox(width: 4),
          Text(label),
        ],
      ),
      selected: isSelected,
      onSelected: (selected) {
        developer.log('Payment method selected: $value', name: 'PaymentCollectionSheet');
        setState(() => _selectedMethod = value);
      },
    );
  }
}
