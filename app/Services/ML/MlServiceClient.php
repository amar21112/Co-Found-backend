<?php

namespace App\Services\ML;

use App\DTOs\ML\MatchPairDTO;
use App\DTOs\ML\MlMatchResultDTO;
use App\Exceptions\ML\MlServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * HTTP client for the Co-Found ML (FastAPI) service.
 *
 * Single responsibility: speak to the ML API.
 * Does not touch the database. Does not build features. Does not dispatch jobs.
 *
 * Callers pass typed MatchPairDTOs; this class returns typed MlMatchResultDTOs.
 * All JSON ↔ DTO mapping is encapsulated here.
 *
 * Usage:
 *   $results = app(MlServiceClient::class)->predictBatch($pairs);
 *   // $results is Collection<MlMatchResultDTO> — only relevant pairs included
 */
class MlServiceClient
{
    private const TIMEOUT_SECONDS = 30;
    private const BATCH_SIZE      = 500;

    private readonly string $baseUrl;
    private readonly string $secret;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ml.base_url'), '/');
        $this->secret  = (string) config('services.ml.secret');
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Score a collection of match pairs in batches of BATCH_SIZE.
     *
     * Handles chunking internally — callers pass the full set.
     * Returns only pairs the model considers relevant (is_relevant = true).
     *
     * @param  Collection<int, MatchPairDTO>  $pairs
     * @return Collection<int, MlMatchResultDTO>
     * @throws MlServiceException on non-retryable ML errors
     */
    public function predictBatch(Collection $pairs): Collection
    {
        if ($pairs->isEmpty()) {
            return collect();
        }

        return $pairs
            ->chunk(self::BATCH_SIZE)
            ->flatMap(fn (Collection $chunk) => $this->scoreChunk($chunk));
    }

    /**
     * Health-check the ML service.
     *
     * @return array{status: string, model: string, n_features: int}
     * @throws MlServiceException
     */
    public function health(): array
    {
        return $this->get('/health');
    }

    // =========================================================================
    // Private — chunk scoring
    // =========================================================================

    /**
     * Send one chunk to /predict/batch and map results to MlMatchResultDTOs.
     * Filters out non-relevant predictions before returning.
     *
     * @param  Collection<int, MatchPairDTO>  $chunk
     * @return array<int, MlMatchResultDTO>
     * @throws MlServiceException (retryable → bubble; non-retryable → log + return [])
     */
    private function scoreChunk(Collection $chunk): array
    {
        $payload = ['pairs' => $chunk->map(fn (MatchPairDTO $p) => $p->toMlPayload())->values()->all()];

        try {
            $response = $this->post('/predict/batch', $payload);
        } catch (MlServiceException $e) {
            // Retryable (network/5xx): bubble up so the job retries.
            // Non-retryable HTTP errors (401/422): log and skip this chunk.
            if ($e->isRetryable()) {
                throw $e;
            }
            Log::warning('MlServiceClient: non-retryable HTTP error, skipping chunk', [
                'http_status' => $e->getHttpStatus(),
                'error'       => $e->getMessage(),
            ]);
            return [];
        }

        // Response arrived but payload shape is wrong — always bubble this up,
        // regardless of HTTP status, so callers know something is fundamentally broken.
        $rawResults = $response['data'] ?? null;

        if (! is_array($rawResults)) {
            throw new MlServiceException('ML /predict/batch returned unexpected payload shape.', 200);
        }

        $pairList = $chunk->values();

        return collect($rawResults)
            ->map(fn (array $result, int $i) =>
            MlMatchResultDTO::fromPairAndResponse($pairList->get($i), $result)
            )
            ->filter(fn (MlMatchResultDTO $r) => $r->isRelevant)
            ->values()
            ->all();
    }

    // =========================================================================
    // Private — HTTP helpers
    // =========================================================================

    /** @return array<string, mixed> */
    private function post(string $path, array $payload): array
    {
        return $this->request('POST', $path, $payload);
    }

    /** @return array<string, mixed> */
    private function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /** @return array<string, mixed> */
    private function request(string $method, string $path, array $payload = []): array
    {
        $url = $this->baseUrl . $path;

        try {
            $pending  = Http::withToken($this->secret)->timeout(self::TIMEOUT_SECONDS)->acceptJson();
            $response = match ($method) {
                'POST'  => $pending->post($url, $payload),
                'GET'   => $pending->get($url),
                default => throw new InvalidArgumentException("Unsupported method: $method"),
            };
        } catch (ConnectionException $e) {
            Log::error('MlServiceClient: connection failed', ['url' => $url, 'error' => $e->getMessage()]);
            throw new MlServiceException("ML service unreachable at $url: {$e->getMessage()}", 0, $e);
        }

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            Log::error('MlServiceClient: HTTP error', ['url' => $url, 'status' => $response->status(), 'body' => $detail]);
            throw new MlServiceException(
                "ML service returned HTTP {$response->status()} for $method $path.",
                $response->status(),
            );
        }

        return $response->json() ?? [];
    }
}
