<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\EquipmentDisposal;
use App\Models\EquipmentReceivingRegister;
use App\Models\WorkshopEquipmentRegister;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $workshopQuery = fn() => $user->isTechnician()
            ? WorkshopEquipmentRegister::where('technician_assigned', $user->id)
            : WorkshopEquipmentRegister::query();

        $stats = [
            'total_equipment_received' => EquipmentReceivingRegister::count(),
            'workshop_pending'         => $workshopQuery()->where('status', 'Pending')->count(),
            'workshop_in_progress'     => $workshopQuery()->where('status', 'In Progress')->count(),
            'workshop_completed'       => $workshopQuery()->where('status', 'Completed')->count(),
            'disposal_pending'         => EquipmentDisposal::whereIn('status', ['Requested', 'Pending Approval'])->count(),
            'disposal_approved'        => EquipmentDisposal::where('status', 'Approved')->count(),
            'total_users'              => User::count(),
        ];

        $overdueJobs = $workshopQuery()
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['Completed', 'Collected'])
            ->with('technician')
            ->latest()
            ->take(5)
            ->get();

        $recentAudit = AuditTrail::latest('created_at')->take(10)->get();

        $myJobs = null;
        if ($user->isTechnician()) {
            $myJobs = WorkshopEquipmentRegister::where('technician_assigned', $user->id)
                ->whereNotIn('status', ['Completed', 'Collected'])
                ->latest()
                ->take(10)
                ->get();
        }

        return view('dashboard', compact('stats', 'overdueJobs', 'recentAudit', 'myJobs'));
    }
}
