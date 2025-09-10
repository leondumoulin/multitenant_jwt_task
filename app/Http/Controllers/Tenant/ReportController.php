<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Deal;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function deals(): JsonResponse
    {
        $userId = Auth::guard('tenant')->id();

        $dealsSummary = Deal::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_deals,
                SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_deals,
                SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won_deals,
                SUM(CASE WHEN status = "lost" THEN 1 ELSE 0 END) as lost_deals,
                SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed_deals,
                SUM(value) as total_value,
                SUM(CASE WHEN status = "won" THEN value ELSE 0 END) as won_value,
                AVG(value) as average_deal_value
            ')
            ->first();

        $dealsByStatus = Deal::where('user_id', $userId)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(value) as total_value'))
            ->groupBy('status')
            ->get();

        $dealsByMonth = Deal::where('user_id', $userId)
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as count,
                SUM(value) as total_value
            ')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $dealsSummary,
                'by_status' => $dealsByStatus,
                'by_month' => $dealsByMonth
            ]
        ]);
    }

    public function contacts(): JsonResponse
    {
        $userId = Auth::guard('tenant')->id();

        $contactsSummary = Contact::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_contacts,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_contacts,
                SUM(CASE WHEN status = "inactive" THEN 1 ELSE 0 END) as inactive_contacts,
                SUM(CASE WHEN status = "lead" THEN 1 ELSE 0 END) as lead_contacts
            ')
            ->first();

        $contactsByStatus = Contact::where('user_id', $userId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $contactsByMonth = Contact::where('user_id', $userId)
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as count
            ')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $contactsSummary,
                'by_status' => $contactsByStatus,
                'by_month' => $contactsByMonth
            ]
        ]);
    }

    public function activities(): JsonResponse
    {
        $userId = Auth::guard('tenant')->id();

        $activitiesSummary = Activity::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_activities,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_activities,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_activities,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_activities
            ')
            ->first();

        $activitiesByType = Activity::where('user_id', $userId)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get();

        $activitiesByStatus = Activity::where('user_id', $userId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $activitiesByMonth = Activity::where('user_id', $userId)
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as count
            ')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $activitiesSummary,
                'by_type' => $activitiesByType,
                'by_status' => $activitiesByStatus,
                'by_month' => $activitiesByMonth
            ]
        ]);
    }
}
