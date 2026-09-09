<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface EmployeeApplicationRepositoryInterface
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
    ): LengthAwarePaginator;

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
    ): LengthAwarePaginator;

    /**
     * Get application by application ID token
     *
     * @param string $applicationId
     * @param string $type
     * @return object|null
     */
    public function getApplicationByToken(string $applicationId, string $type): ?object;

    /**
     * Approve an application
     *
     * @param object $application
     * @param array $data
     * @return bool
     */
    public function approveApplication(object $application, array $data): bool;

    /**
     * Deny an application
     *
     * @param object $application
     * @param array $data
     * @return bool
     */
    public function denyApplication(object $application, array $data): bool;

    /**
     * Get application statistics
     *
     * @return array
     */
    public function getApplicationStats(): array;
}
