<?php

namespace App\Services;

use App\Models\User;

class AiSafeQueryBuilderService
{
    public function __construct(private readonly AiSqlBuilderService $sqlBuilderService)
    {
    }

    public function build(array $plan, User $user): array
    {
        // Security: AI plans are metadata only. Laravel builds the SQL through Query Builder.
        return $this->sqlBuilderService->build($plan, $user);
    }
}
