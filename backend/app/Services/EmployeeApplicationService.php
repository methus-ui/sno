<?php

namespace App\Services;

use App\Models\Store;
use App\Traits\FileManagerTrait;
use Illuminate\Support\Str;

class EmployeeApplicationService
{
    use FileManagerTrait;

    /**
     * Prepare admin employee application data
     *
     * @param object $request
     * @param string|null $invitationToken
     * @return array
     */
    public function prepareAdminApplicationData(object $request, ?string $invitationToken = null): array
    {
        $data = [
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'role_id' => $request->role_id,
            'zone_id' => ($request->zone_id && $request->zone_id !== 'all') ? $request->zone_id : null,
            'password' => bcrypt($request->password),
            'application_id' => Str::uuid()->toString(),
            'applied_at' => now(),
            'status' => null, // Pending
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $this->upload('admin/', 'png', $request->file('image'));
        }

        // Handle documents
        $data['documents'] = $this->handleDocumentUploads($request, 'admin', $data['application_id']);

        return $data;
    }

    /**
     * Prepare vendor employee application data
     *
     * @param object $request
     * @param string|null $invitationToken
     * @return array
     */
    public function prepareVendorApplicationData(object $request, ?string $invitationToken = null): array
    {
        $vendorId = $request->vendor_id;

        // If not provided, get from store
        if (!$vendorId && $request->store_id) {
            $store = Store::find($request->store_id);
            $vendorId = $store ? $store->vendor_id : null;
        }

        $data = [
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'employee_role_id' => $request->role_id,
            'store_id' => $request->store_id,
            'vendor_id' => $vendorId,
            'password' => bcrypt($request->password),
            'application_id' => Str::uuid()->toString(),
            'applied_at' => now(),
            'application_status' => null, // Pending
            'status' => 1, // Active status (different from application_status)
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $this->upload('vendor/', 'png', $request->file('image'));
        }

        // Handle documents
        $data['documents'] = $this->handleDocumentUploads($request, 'vendor', $data['application_id']);

        return $data;
    }

    /**
     * Handle document uploads
     *
     * @param object $request
     * @param string $type
     * @param string $applicationId
     * @return array
     */
    private function handleDocumentUploads(object $request, string $type, string $applicationId): array
    {
        $documents = [];
        $basePath = "employee-applications/{$type}/{$applicationId}";

        // Resume
        if ($request->hasFile('resume')) {
            $documents['resume'] = $this->upload($basePath, 'pdf', $request->file('resume'));
        }

        // ID Proof
        if ($request->hasFile('id_proof')) {
            $documents['id_proof'] = $this->upload($basePath, 'jpg', $request->file('id_proof'));
        }

        // Certificates (multiple files)
        if ($request->hasFile('certificates')) {
            $documents['certificates'] = [];
            foreach ($request->file('certificates') as $certificate) {
                $documents['certificates'][] = $this->upload($basePath, 'pdf', $certificate);
            }
        }

        return $documents;
    }

    /**
     * Get approval data
     *
     * @param object $request
     * @param int $approverId
     * @return array
     */
    public function getApprovalData(object $request, int $approverId): array
    {
        return [
            'approved_by' => $approverId,
            'role_id' => $request->role_id ?? null,
            'zone_id' => $request->zone_id ?? null,
        ];
    }

    /**
     * Get denial data
     *
     * @param object $request
     * @param int $approverId
     * @return array
     */
    public function getDenialData(object $request, int $approverId): array
    {
        return [
            'approved_by' => $approverId,
            'rejection_reason' => $request->rejection_reason,
        ];
    }
}
