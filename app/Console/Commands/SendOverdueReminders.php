<?php

namespace App\Console\Commands;

use App\Models\AuditTrail;
use App\Models\User;
use App\Models\WorkshopEquipmentRegister;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendOverdueReminders extends Command
{
    protected $signature = 'ict:send-overdue-reminders';
    protected $description = 'Send email reminders for overdue workshop jobs';

    public function handle(): void
    {
        $overdueJobs = WorkshopEquipmentRegister::where('due_date', '<', now())
            ->whereNotIn('status', ['Completed', 'Collected'])
            ->with('technician')
            ->get();

        if ($overdueJobs->isEmpty()) {
            $this->info('No overdue jobs found.');
            return;
        }

        $hods = User::where('role', 'hod')->get();
        $admins = User::where('role', 'super_admin')->get();
        $recipients = $hods->merge($admins);

        $jobList = $overdueJobs->map(fn($j) => "- {$j->entry_job_number}: {$j->equipment_type} (Technician: " . ($j->technician?->firstname ?? 'Unassigned') . ", Due: " . $j->due_date?->format('d M Y') . ")")->implode("\n");

        foreach ($recipients as $recipient) {
            try {
                Mail::raw(
                    "Dear {$recipient->firstname},\n\nThe following workshop jobs are overdue:\n\n{$jobList}\n\nPlease take action.\n\nICT Register System",
                    fn($m) => $m->to($recipient->email)->subject('Overdue Workshop Jobs — ICT Register')
                );
            } catch (\Exception $e) {
                // Continue despite mail failure
            }
        }

        AuditTrail::create([
            'username'     => 'System',
            'action'       => 'overdue_reminder',
            'module'       => 'Scheduler',
            'description'  => "Overdue reminders sent for {$overdueJobs->count()} job(s)",
            'ip_address'   => '127.0.0.1',
            'badge_status' => 'warning',
        ]);

        $this->info("Reminders sent for {$overdueJobs->count()} overdue job(s).");
    }
}
