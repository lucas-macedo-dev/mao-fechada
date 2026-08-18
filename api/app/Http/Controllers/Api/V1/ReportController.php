<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GenerateReportRequest;
use App\Http\Requests\Api\V1\ListReportsRequest;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ListReportsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = $request->user()->reports()->orderByDesc('created_at');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = (int) ($validated['per_page'] ?? 20);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::data($paginator->items(), meta: [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ]);
    }

    public function store(GenerateReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $report = $request->user()->reports()->create([
            'format'  => $validated['format'],
            'status'  => 'pending',
            'filters' => Arr::except($validated, ['format']),
        ]);

        GenerateReportJob::dispatch($report->id);

        return ApiResponse::data($report, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $report = Report::query()->findOrFail($id);
        $this->ensureOwnership($request, $report->user_id);

        return ApiResponse::data($report);
    }

    public function download(Request $request, int $id): StreamedResponse
    {
        $report = Report::query()->findOrFail($id);
        $this->ensureOwnership($request, $report->user_id);

        if ($report->status !== 'completed' || ! $report->file_path || ! Storage::disk('local')->exists($report->file_path)) {
            abort(Response::HTTP_CONFLICT, __('messages.report_not_ready'));
        }

        return Storage::disk('local')->download($report->file_path, "report-{$report->id}.{$report->format}");
    }

    private function ensureOwnership(Request $request, int $ownerUserId): void
    {
        if ((int) $request->user()->id !== $ownerUserId) {
            abort(403, __('messages.ownership_denied'));
        }
    }
}
