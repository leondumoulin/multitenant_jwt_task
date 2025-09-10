<?php

namespace App\Services;

use App\Models\Tenant\Activity;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Deal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ActivityService
{
    /**
     * Get all activities for the authenticated user.
     */
    public function getAllActivities(): Collection
    {
        return Activity::with(['user', 'contact', 'deal'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific activity by ID for the authenticated user.
     */
    public function getActivityById(int $id): ?Activity
    {
        return Activity::with(['user', 'contact', 'deal'])
            ->where('id', $id)
            ->where('user_id', Auth::guard('tenant')->id())
            ->first();
    }

    /**
     * Create a new activity.
     */
    public function createActivity(array $data): Activity
    {
        // Validate contact ownership if contact_id is provided
        if (isset($data['contact_id'])) {
            $this->validateContactOwnership($data['contact_id']);
        }

        // Validate deal ownership if deal_id is provided
        if (isset($data['deal_id'])) {
            $this->validateDealOwnership($data['deal_id']);
        }

        $activityData = array_merge($data, [
            'user_id' => Auth::guard('tenant')->id(),
        ]);

        $activity = Activity::create($activityData);

        return $activity->load(['user', 'contact', 'deal']);
    }

    /**
     * Update an existing activity.
     */
    public function updateActivity(Activity $activity, array $data): Activity
    {
        $this->ensureActivityOwnership($activity);

        // Validate contact ownership if contact_id is being updated
        if (isset($data['contact_id'])) {
            $this->validateContactOwnership($data['contact_id']);
        }

        // Validate deal ownership if deal_id is being updated
        if (isset($data['deal_id'])) {
            $this->validateDealOwnership($data['deal_id']);
        }

        $activity->update($data);

        return $activity->fresh(['user', 'contact', 'deal']);
    }

    /**
     * Delete an activity.
     */
    public function deleteActivity(Activity $activity): bool
    {
        $this->ensureActivityOwnership($activity);

        return $activity->delete();
    }

    /**
     * Search activities by query.
     */
    public function searchActivities(string $query): Collection
    {
        return Activity::with(['user', 'contact', 'deal'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('type', 'like', "%{$query}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($query) {
                        $contactQuery->where('name', 'like', "%{$query}%");
                    })
                    ->orWhereHas('deal', function ($dealQuery) use ($query) {
                        $dealQuery->where('title', 'like', "%{$query}%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activities by type.
     */
    public function getActivitiesByType(string $type): Collection
    {
        return Activity::with(['user', 'contact', 'deal'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activities by status.
     */
    public function getActivitiesByStatus(string $status): Collection
    {
        return Activity::with(['user', 'contact', 'deal'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activities by contact.
     */
    public function getActivitiesByContact(int $contactId): Collection
    {
        $this->validateContactOwnership($contactId);

        return Activity::with(['user', 'contact', 'deal'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where('contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activities by deal.
     */
    public function getActivitiesByDeal(int $dealId): Collection
    {
        $this->validateDealOwnership($dealId);

        return Activity::with(['user', 'contact', 'deal'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where('deal_id', $dealId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get upcoming activities.
     */
    public function getUpcomingActivities(): Collection
    {
        return Activity::with(['user', 'contact', 'deal'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where('scheduled_at', '>', now())
            ->where('status', 'pending')
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Get overdue activities.
     */
    public function getOverdueActivities(): Collection
    {
        return Activity::with(['user', 'contact', 'deal'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where('scheduled_at', '<', now())
            ->where('status', 'pending')
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Get activities statistics.
     */
    public function getActivityStats(): array
    {
        $userId = Auth::guard('tenant')->id();

        return [
            'total' => Activity::where('user_id', $userId)->count(),
            'pending' => Activity::where('user_id', $userId)->where('status', 'pending')->count(),
            'completed' => Activity::where('user_id', $userId)->where('status', 'completed')->count(),
            'cancelled' => Activity::where('user_id', $userId)->where('status', 'cancelled')->count(),
            'upcoming' => Activity::where('user_id', $userId)
                ->where('scheduled_at', '>', now())
                ->where('status', 'pending')
                ->count(),
            'overdue' => Activity::where('user_id', $userId)
                ->where('scheduled_at', '<', now())
                ->where('status', 'pending')
                ->count(),
            'by_type' => [
                'call' => Activity::where('user_id', $userId)->where('type', 'call')->count(),
                'email' => Activity::where('user_id', $userId)->where('type', 'email')->count(),
                'meeting' => Activity::where('user_id', $userId)->where('type', 'meeting')->count(),
                'task' => Activity::where('user_id', $userId)->where('type', 'task')->count(),
                'note' => Activity::where('user_id', $userId)->where('type', 'note')->count(),
                'other' => Activity::where('user_id', $userId)->where('type', 'other')->count(),
            ],
        ];
    }

    /**
     * Mark activity as completed.
     */
    public function markAsCompleted(Activity $activity): Activity
    {
        $this->ensureActivityOwnership($activity);

        $activity->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $activity->fresh(['user', 'contact', 'deal']);
    }

    /**
     * Ensure the activity belongs to the authenticated user.
     */
    private function ensureActivityOwnership(Activity $activity): void
    {
        if ($activity->user_id !== Auth::guard('tenant')->id()) {
            throw new \Exception('Activity not found or access denied.');
        }
    }

    /**
     * Validate that the contact belongs to the authenticated user.
     */
    private function validateContactOwnership(int $contactId): void
    {
        $contact = Contact::where('id', $contactId)
            ->where('user_id', Auth::guard('tenant')->id())
            ->first();

        if (!$contact) {
            throw new \Exception('Contact not found or access denied.');
        }
    }

    /**
     * Validate that the deal belongs to the authenticated user.
     */
    private function validateDealOwnership(int $dealId): void
    {
        $deal = Deal::where('id', $dealId)
            ->where('user_id', Auth::guard('tenant')->id())
            ->first();

        if (!$deal) {
            throw new \Exception('Deal not found or access denied.');
        }
    }
}
