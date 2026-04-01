<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWhatsAppReminders extends Command
{
    protected $signature = 'whatsapp:send-reminders';

    protected $description = 'Send automated WhatsApp reminders (appointments, follow-ups, payments, birthdays)';

    public function handle(WhatsAppService $whatsApp): int
    {
        $this->info('Starting WhatsApp reminder run at ' . now()->toDateTimeString());
        Log::info('whatsapp:send-reminders started');

        $sent = 0;
        $errors = 0;

        // ── 1. 24-hour appointment reminders ────────────────────────────────
        $sent += $this->send24hReminders($whatsApp, $errors);

        // ── 2. 2-hour appointment reminders ─────────────────────────────────
        $sent += $this->send2hReminders($whatsApp, $errors);

        // ── 3. Follow-up reminders (visits where follow_up_date = today) ────
        $sent += $this->sendFollowUpReminders($whatsApp, $errors);

        // ── 4. Payment reminders (unpaid invoices > 3 days old) ─────────────
        $sent += $this->sendPaymentReminders($whatsApp, $errors);

        // ── 5. Birthday greetings ───────────────────────────────────────────
        $sent += $this->sendBirthdayGreetings($whatsApp, $errors);

        $this->info("Done. Sent: {$sent}, Errors: {$errors}");
        Log::info('whatsapp:send-reminders completed', ['sent' => $sent, 'errors' => $errors]);

        return self::SUCCESS;
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function send24hReminders(WhatsAppService $whatsApp, int &$errors): int
    {
        $tomorrow = now()->addDay()->format('Y-m-d');
        $sent = 0;

        $appointments = Appointment::with(['patient', 'doctor'])
            ->whereDate('scheduled_at', $tomorrow)
            ->whereIn('status', ['booked', 'confirmed'])
            ->whereNull('reminder_24h_sent_at')
            ->get();

        $this->info("24h reminders: {$appointments->count()} appointments found");

        foreach ($appointments as $appointment) {
            try {
                $patient = $appointment->patient;
                if (!$patient?->phone) {
                    continue;
                }

                $whatsApp->sendAppointmentReminder24h($patient, $appointment);

                $appointment->update(['reminder_24h_sent_at' => now()]);
                $sent++;

                $this->line("  -> 24h reminder sent to {$patient->name}");
            } catch (\Throwable $e) {
                $errors++;
                Log::error('24h reminder failed', [
                    'appointment_id' => $appointment->id,
                    'error'          => $e->getMessage(),
                ]);
                $this->error("  -> Failed for appointment #{$appointment->id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    private function send2hReminders(WhatsAppService $whatsApp, int &$errors): int
    {
        $windowStart = now()->addHours(2)->subMinutes(15);
        $windowEnd   = now()->addHours(2)->addMinutes(15);
        $sent = 0;

        $appointments = Appointment::with(['patient', 'doctor'])
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->whereIn('status', ['booked', 'confirmed'])
            ->whereNull('reminder_2h_sent_at')
            ->get();

        $this->info("2h reminders: {$appointments->count()} appointments found");

        foreach ($appointments as $appointment) {
            try {
                $patient = $appointment->patient;
                if (!$patient?->phone) {
                    continue;
                }

                $whatsApp->sendAppointmentReminder2h($patient, $appointment);

                $appointment->update(['reminder_2h_sent_at' => now()]);
                $sent++;

                $this->line("  -> 2h reminder sent to {$patient->name}");
            } catch (\Throwable $e) {
                $errors++;
                Log::error('2h reminder failed', [
                    'appointment_id' => $appointment->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    private function sendFollowUpReminders(WhatsAppService $whatsApp, int &$errors): int
    {
        $today = now()->toDateString();
        $sent = 0;

        $visits = Visit::with('patient')
            ->whereDate('followup_date', $today)
            ->where('status', 'finalised')
            ->get();

        $this->info("Follow-up reminders: {$visits->count()} visits found");

        foreach ($visits as $visit) {
            try {
                $patient = $visit->patient;
                if (!$patient?->phone) {
                    continue;
                }

                $whatsApp->sendFollowUpReminder($patient, $visit);
                $sent++;

                $this->line("  -> Follow-up reminder sent to {$patient->name}");
            } catch (\Throwable $e) {
                $errors++;
                Log::error('Follow-up reminder failed', [
                    'visit_id' => $visit->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    private function sendPaymentReminders(WhatsAppService $whatsApp, int &$errors): int
    {
        $cutoff = now()->subDays(3);
        $sent = 0;

        $invoices = Invoice::with('patient')
            ->whereIn('status', ['finalized', 'partially_paid'])
            ->where('created_at', '<=', $cutoff)
            ->whereNull('payment_reminder_sent_at')
            ->get();

        $this->info("Payment reminders: {$invoices->count()} invoices found");

        foreach ($invoices as $invoice) {
            try {
                $patient = $invoice->patient;
                if (!$patient?->phone) {
                    continue;
                }

                $paymentUrl = url("/pay/{$invoice->id}");
                $whatsApp->sendPaymentReminder($patient, $invoice, $paymentUrl);

                $invoice->update(['payment_reminder_sent_at' => now()]);
                $sent++;

                $this->line("  -> Payment reminder sent to {$patient->name}");
            } catch (\Throwable $e) {
                $errors++;
                Log::error('Payment reminder failed', [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    private function sendBirthdayGreetings(WhatsAppService $whatsApp, int &$errors): int
    {
        $today = now();
        $sent = 0;

        $patients = Patient::whereMonth('dob', $today->month)
            ->whereDay('dob', $today->day)
            ->whereNotNull('phone')
            ->get();

        $this->info("Birthday greetings: {$patients->count()} patients found");

        foreach ($patients as $patient) {
            try {
                $whatsApp->sendBirthdayGreeting($patient);
                $sent++;

                $this->line("  -> Birthday greeting sent to {$patient->name}");
            } catch (\Throwable $e) {
                $errors++;
                Log::error('Birthday greeting failed', [
                    'patient_id' => $patient->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
