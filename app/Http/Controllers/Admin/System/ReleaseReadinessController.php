<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\System;

use App\Services\System\ReleaseReadinessService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ReleaseReadinessController extends Controller
{
    public function __invoke(Request $request, ReleaseReadinessService $readiness): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $localChecks = $readiness->localChecks();

        return view('admin.system.release-readiness', [
            'localChecks' => $localChecks,
            'localPassed' => count(array_filter($localChecks, fn (array $item): bool => $item['state'] === 'PASS')),
            'externalGates' => $readiness->externalGates(),
            'moduleWorkstreams' => $readiness->moduleWorkstreams(),
        ]);
    }
}
