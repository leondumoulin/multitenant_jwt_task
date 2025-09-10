<?php

namespace App\Services;

use App\Models\Tenant\Deal;
use App\Models\Tenant\Contact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class DealService
{
    /**
     * Get all deals for the authenticated user.
     */
    public function getAllDeals(): Collection
    {
        return Deal::with(['user', 'contact'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific deal by ID for the authenticated user.
     */
    public function getDealById(int $id): ?Deal
    {
        return Deal::with(['user', 'contact'])
            ->where('id', $id)
            ->where('user_id', Auth::guard('tenant')->id())
            ->first();
    }

    /**
     * Create a new deal.
     */
    public function createDeal(array $data): Deal
    {
        // Validate contact ownership if contact_id is provided
        if (isset($data['contact_id'])) {
            $this->validateContactOwnership($data['contact_id']);
        }

        $dealData = array_merge($data, [
            'user_id' => Auth::guard('tenant')->id(),
        ]);

        $deal = Deal::create($dealData);

        return $deal->load(['user', 'contact']);
    }

    /**
     * Update an existing deal.
     */
    public function updateDeal(Deal $deal, array $data): Deal
    {
        $this->ensureDealOwnership($deal);

        // Validate contact ownership if contact_id is being updated
        if (isset($data['contact_id'])) {
            $this->validateContactOwnership($data['contact_id']);
        }

        $deal->update($data);

        return $deal->fresh(['user', 'contact']);
    }

    /**
     * Delete a deal.
     */
    public function deleteDeal(Deal $deal): bool
    {
        $this->ensureDealOwnership($deal);

        return $deal->delete();
    }

    /**
     * Search deals by query.
     */
    public function searchDeals(string $query): Collection
    {
        return Deal::with(['user', 'contact'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($query) {
                        $contactQuery->where('name', 'like', "%{$query}%")
                            ->orWhere('company', 'like', "%{$query}%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get deals by status.
     */
    public function getDealsByStatus(string $status): Collection
    {
        return Deal::with(['user', 'contact'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get deals by contact.
     */
    public function getDealsByContact(int $contactId): Collection
    {
        $this->validateContactOwnership($contactId);

        return Deal::with(['user', 'contact'])
            ->where('user_id', Auth::guard('tenant')->id())
            ->where('contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get deals statistics.
     */
    public function getDealStats(): array
    {
        $userId = Auth::guard('tenant')->id();

        $totalValue = Deal::where('user_id', $userId)->sum('value');
        $wonValue = Deal::where('user_id', $userId)->where('status', 'won')->sum('value');
        $openValue = Deal::where('user_id', $userId)->whereIn('status', ['open', 'negotiation', 'proposal'])->sum('value');

        return [
            'total' => Deal::where('user_id', $userId)->count(),
            'open' => Deal::where('user_id', $userId)->where('status', 'open')->count(),
            'won' => Deal::where('user_id', $userId)->where('status', 'won')->count(),
            'lost' => Deal::where('user_id', $userId)->where('status', 'lost')->count(),
            'closed' => Deal::where('user_id', $userId)->where('status', 'closed')->count(),
            'negotiation' => Deal::where('user_id', $userId)->where('status', 'negotiation')->count(),
            'proposal' => Deal::where('user_id', $userId)->where('status', 'proposal')->count(),
            'total_value' => $totalValue,
            'won_value' => $wonValue,
            'open_value' => $openValue,
            'win_rate' => $totalValue > 0 ? round(($wonValue / $totalValue) * 100, 2) : 0,
        ];
    }

    /**
     * Get deals pipeline data.
     */
    public function getDealsPipeline(): array
    {
        $userId = Auth::guard('tenant')->id();

        return [
            'prospects' => Deal::where('user_id', $userId)->where('status', 'open')->sum('value'),
            'negotiation' => Deal::where('user_id', $userId)->where('status', 'negotiation')->sum('value'),
            'proposal' => Deal::where('user_id', $userId)->where('status', 'proposal')->sum('value'),
            'closed_won' => Deal::where('user_id', $userId)->where('status', 'won')->sum('value'),
        ];
    }

    /**
     * Ensure the deal belongs to the authenticated user.
     */
    private function ensureDealOwnership(Deal $deal): void
    {
        if ($deal->user_id !== Auth::guard('tenant')->id()) {
            throw new \Exception('Deal not found or access denied.');
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
}
