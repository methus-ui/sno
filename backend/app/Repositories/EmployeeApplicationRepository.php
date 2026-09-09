<?php

namespace App\Repositories;

use App\Contracts\Repositories\EmployeeApplicationRepositoryInterface;
use App\Models\Admin;
use App\Models\VendorEmployee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeApplicationRepository implements EmployeeApplicationRepositoryInterface
{
    /**
     * Get pending admin employee applications
     *
     * @param string|null $searchValue
     * @param int|null $zoneId
     * @param int $dataLimit
     * @return LengthAwarePaginator
     */
    public function getPendingAdminApplications(
        ?string $searchValue = null,
        ?int $zoneId = null,
        int $dataLimit = 25
    ): LengthAwarePaginator {
        $query = Admin::pending()
            ->with(['role', 'zones'])
            ->where('role_id', '!=', 1); // Exclude super admin

        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        if ($searchValue) {
            $keys = explode(' ', $searchValue);
            $query->where(function ($q) use ($keys) {
                foreach ($keys as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('application_id', 'like', "%{$value}%");
                }
            });
        }

        return $query->latest('applied_at')->paginate($dataLimit);
    }

    /**
     * Get pending vendor employee applications
     *
     * @param string|null $searchValue
     * @param int|null $storeId
     * @param int $dataLimit
     * @return LengthAwarePaginator
     */
    public function getPendingVendorApplications(
        ?string $searchValue = null,
        ?int $storeId = null,
        int $dataLimit = 25
    ): LengthAwarePaginator {
        $query = VendorEmployee::pending()
            ->with(['role', 'store', 'vendor']);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($searchValue) {
            $keys = explode(' ', $searchValue);
            $query->where(function ($q) use ($keys) {
                foreach ($keys as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('application_id', 'like', "%{$value}%");
                }
            });
        }

        return $query->latest('applied_at')->paginate($dataLimit);
    }

    /**
     * Get application by application ID token
     *
     * @param string $applicationId
     * @param string $type
     * @return object|null
     */
    public function getApplicationByToken(string $applicationId, string $type): ?object
    {
        $model = $type === 'admin' ? Admin::class : VendorEmployee::class;
        return $model::where('application_id', $applicationId)->first();
    }

    /**
     * Approve an application
     *
     * @param object $application
     * @param array $data
     * @return bool
     */
    public function approveApplication(object $application, array $data): bool
    {
        // For Admin model
        if ($application instanceof Admin) {
            return $application->update([
                'status' => 1,
                'approved_by' => $data['approved_by'],
                'approved_at' => now(),
                'rejection_reason' => null,
                'role_id' => $data['role_id'] ?? $application->role_id,
                'zone_id' => $data['zone_id'] ?? $application->zone_id,
            ]);
        }

        // For VendorEmployee model
        if ($application instanceof VendorEmployee) {
            return $application->update([
                'application_status' => 1,
                'approved_by' => $data['approved_by'],
                'approved_at' => now(),
                'rejection_reason' => null,
                'employee_role_id' => $data['role_id'] ?? $application->employee_role_id,
            ]);
        }

        return false;
    }

    /**
     * Deny an application
     *
     * @param object $application
     * @param array $data
     * @return bool
     */
    public function denyApplication(object $application, array $data): bool
    {
        // For Admin model
        if ($application instanceof Admin) {
            return $application->update([
                'status' => 0,
                'approved_by' => $data['approved_by'],
                'approved_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);
        }

        // For VendorEmployee model
        if ($application instanceof VendorEmployee) {
            return $application->update([
                'application_status' => 0,
                'approved_by' => $data['approved_by'],
                'approved_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);
        }

        return false;
    }

    /**
     * Get application statistics
     *
     * @return array
     */
    public function getApplicationStats(): array
    {
        return [
            'pending_admin' => Admin::pending()->where('role_id', '!=', 1)->count(),
            'pending_vendor' => VendorEmployee::pending()->count(),
            'approved_today_admin' => Admin::approved()
                ->whereDate('approved_at', today())->count(),
            'approved_today_vendor' => VendorEmployee::approved()
                ->whereDate('approved_at', today())->count(),
        ];
    }
}
