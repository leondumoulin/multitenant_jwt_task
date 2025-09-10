<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreContactRequest;
use App\Http\Requests\Tenant\UpdateContactRequest;
use App\Http\Resources\Tenant\ContactResource;
use App\Models\Tenant\Contact;
use App\Services\ContactService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    private ContactService $contactService;
    private AuditLogService $auditLogService;

    public function __construct(ContactService $contactService, AuditLogService $auditLogService)
    {
        $this->contactService = $contactService;
        $this->auditLogService = $auditLogService;
    }

    public function index(): JsonResponse
    {
        $contacts = $this->contactService->getAllContacts();

        return response()->json([
            'success' => true,
            'data' => ContactResource::collection($contacts)
        ]);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        try {
            $contact = $this->contactService->createContact($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Contact created successfully',
                'data' => new ContactResource($contact)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contact: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Contact $contact): JsonResponse
    {
        try {
            $contact = $this->contactService->getContactById($contact->id);

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact not found'
                ], 404);
            }

            // Log the view action
            $contact->logViewed();

            return response()->json([
                'success' => true,
                'data' => new ContactResource($contact)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contact: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        try {
            $contact = $this->contactService->updateContact($contact, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Contact updated successfully',
                'data' => new ContactResource($contact)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contact: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Contact $contact): JsonResponse
    {
        try {
            $this->contactService->deleteContact($contact);

            return response()->json([
                'success' => true,
                'message' => 'Contact deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contact: ' . $e->getMessage()
            ], 500);
        }
    }
}
