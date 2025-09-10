<?php

namespace App\Services;

use App\Models\Tenant\Contact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ContactService
{
    /**
     * Get all contacts for the authenticated user.
     */
    public function getAllContacts(): Collection
    {
        return Contact::with('user')
            ->where('user_id', Auth::guard('tenant')->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific contact by ID for the authenticated user.
     */
    public function getContactById(int $id): ?Contact
    {
        return Contact::with('user')
            ->where('id', $id)
            ->where('user_id', Auth::guard('tenant')->id())
            ->first();
    }

    /**
     * Create a new contact.
     */
    public function createContact(array $data): Contact
    {
        $contactData = array_merge($data, [
            'user_id' => Auth::guard('tenant')->id(),
        ]);

        return Contact::create($contactData);
    }

    /**
     * Update an existing contact.
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        $this->ensureContactOwnership($contact);

        $contact->update($data);

        return $contact->fresh(['user']);
    }

    /**
     * Delete a contact.
     */
    public function deleteContact(Contact $contact): bool
    {
        $this->ensureContactOwnership($contact);

        return $contact->delete();
    }

    /**
     * Search contacts by query.
     */
    public function searchContacts(string $query): Collection
    {
        return Contact::with('user')
            ->where('user_id', Auth::guard('tenant')->id())
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('company', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get contacts by status.
     */
    public function getContactsByStatus(string $status): Collection
    {
        return Contact::with('user')
            ->where('user_id', Auth::guard('tenant')->id())
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get contacts statistics.
     */
    public function getContactStats(): array
    {
        $userId = Auth::guard('tenant')->id();

        return [
            'total' => Contact::where('user_id', $userId)->count(),
            'active' => Contact::where('user_id', $userId)->where('status', 'active')->count(),
            'inactive' => Contact::where('user_id', $userId)->where('status', 'inactive')->count(),
            'leads' => Contact::where('user_id', $userId)->where('status', 'lead')->count(),
            'prospects' => Contact::where('user_id', $userId)->where('status', 'prospect')->count(),
        ];
    }

    /**
     * Ensure the contact belongs to the authenticated user.
     */
    private function ensureContactOwnership(Contact $contact): void
    {
        if ($contact->user_id !== Auth::guard('tenant')->id()) {
            throw new \Exception('Contact not found or access denied.');
        }
    }
}
