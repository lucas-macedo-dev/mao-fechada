<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use Illuminate\Http\Request;

trait AuthorizesOwnership
{
    private function ensureOwnership(Request $request, int $ownerUserId): void
    {
        if ((int) $request->user()->id !== $ownerUserId) {
            abort(403, __('messages.ownership_denied'));
        }
    }
}
