<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CertificateIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isActive(), 403);

        return view('student.certificates.index', [
            'certificates' => Certificate::query()
                ->where('user_id', $request->user()->getKey())
                ->whereNull('revoked_at')
                ->whereHas('course')
                ->with('course')
                ->orderByDesc('issued_at')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }
}
