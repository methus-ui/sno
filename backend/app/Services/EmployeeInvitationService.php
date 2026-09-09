<?php

namespace App\Services;

use App\Models\EmployeeInvitation;

class EmployeeInvitationService
{
    /**
     * Create a new invitation
     *
     * @param array $data
     * @return EmployeeInvitation
     */
    public function createInvitation(array $data): EmployeeInvitation
    {
        return EmployeeInvitation::create([
            'email' => $data['email'],
            'employee_type' => $data['employee_type'],
            'role_id' => $data['role_id'] ?? null,
            'zone_id' => $data['zone_id'] ?? null,
            'store_id' => $data['store_id'] ?? null,
            'created_by' => $data['created_by'],
            'expires_at' => now()->addDays($data['expiry_days'] ?? 7),
        ]);
    }

    /**
     * Validate an invitation token
     *
     * @param string $token
     * @return EmployeeInvitation|null
     */
    public function validateInvitation(string $token): ?EmployeeInvitation
    {
        return EmployeeInvitation::where('token', $token)
            ->valid()
            ->first();
    }

    /**
     * Get invitation data for pre-filling form
     *
     * @param EmployeeInvitation $invitation
     * @return array
     */
    public function getInvitationData(EmployeeInvitation $invitation): array
    {
        return [
            'email' => $invitation->email,
            'employee_type' => $invitation->employee_type,
            'role_id' => $invitation->role_id,
            'zone_id' => $invitation->zone_id,
            'store_id' => $invitation->store_id,
        ];
    }

    /**
     * Clean up expired invitations
     *
     * @return int Number of deleted invitations
     */
    public function cleanupExpiredInvitations(): int
    {
        return EmployeeInvitation::expired()->delete();
    }
}
