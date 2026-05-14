<?php

namespace App\Http\Controllers\Api\V1\ML;

use App\Http\Controllers\Controller;
use App\Http\Requests\ML\ExportDatasetRequest;
use App\Http\Requests\ML\GenerateDatasetRequest;
use App\Http\Requests\ML\IngestMatchesRequest;
use App\Services\MatchService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MLController extends Controller
{
    public function __construct(
        private readonly MatchService $matchService,
    ) {}

    public function stats(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $this->matchService->datasetStats(),
        ]);
    }

    public function generate(GenerateDatasetRequest $request): JsonResponse
    {
        $result = $this->matchService->generateDataset($request->getDto());

        return response()->json([
            'status'  => 'success',
            'message' => 'Training dataset generated successfully.',
            'data'    => $result,
        ], 201);
    }

    public function export(ExportDatasetRequest $request): JsonResponse|StreamedResponse
    {
        $dto  = $request->getDto();
        $rows = $this->matchService->exportTrainingData($dto);

        if ($rows->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No records found for the given filters.',
            ], 404);
        }

        if ($dto->format === 'csv') {
            return $this->streamCsv($rows, $dto->type);
        }

        return response()->json([
            'status' => 'success',
            'meta'   => [
                'total'              => $rows->count(),
                'type'               => $dto->type ?? 'all',
                'min_score'          => $dto->minScore,
                'with_feedback_only' => $dto->withFeedbackOnly,
            ],
            'data' => $rows->values(),
        ]);
    }

    public function ingest(IngestMatchesRequest $request): JsonResponse
    {
        $result = $this->matchService->ingestBatch($request->getDto());

        return response()->json([
            'status'  => 'success',
            'message' => 'Matches ingested successfully.',
            'data'    => $result,
        ], 201);
    }

    private function streamCsv($rows, ?string $type): StreamedResponse
    {
        $filename = 'matches_' . ($type ?? 'all') . '_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($rows->first()));
            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}
