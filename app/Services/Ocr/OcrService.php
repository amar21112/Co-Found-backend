<?php

namespace App\Services\Ocr;

use App\Exceptions\Ocr\OcrServiceException;
use App\Exceptions\Ocr\OcrServiceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for the self-hosted Egypt ID OCR service.
 *
 * Endpoint map (new api.py):
 *   POST /scan          — image upload → full OCR pipeline → ScanResponse
 *   POST /decode        — NID string   → DecodedNID
 *   GET  /health        — liveness probe (unauthenticated)
 *
 * All authenticated endpoints require:
 *   Authorization: Bearer <OCR_SERVICE_SECRET>
 *
 * config/services.php:
 *   'ocr' => [
 *       'url'     => env('OCR_SERVICE_URL'),
 *       'secret'  => env('OCR_SERVICE_SECRET'),
 *       'timeout' => env('OCR_SERVICE_TIMEOUT', 60),
 *   ],
 */
class OcrService
{
    private readonly string $baseUrl;
    private readonly string $secret;
    private readonly int    $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ocr.url', ''), '/');
        $this->secret  = config('services.ocr.secret', '');
        $this->timeout = (int) config('services.ocr.timeout', 60);
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Run the full OCR pipeline on a front-side ID card image.
     *
     * Maps to: POST /scan
     *
     * @return array{
     *     success: bool,
     *     national_id: string|null,
     *     decoded: array{
     *         valid: bool,
     *         national_id: string,
     *         birth_date: string,
     *         gender: string,
     *         gender_en: string,
     *         governorate: string,
     *         nationality: string,
     *         expiry_date: string|null,
     *         expiry_is_permanent: bool|null,
     *         full_name_on_card: string|null,
     *     }|null,
     *     processing_time_ms: float,
     *     error: string|null,
     * }
     *
     * @throws OcrServiceUnavailableException
     * @throws OcrServiceException
     */
    public function recognizeIdCard(UploadedFile $image): array
    {
        $response = $this->client($this->timeout)
            ->attach('file', $image->getContent(), $image->getClientOriginalName())
            ->post($this->url('/scan'));

        return $this->parse($response, 'scan');
    }

    /**
     * Decode a raw 14-digit NID — no image needed.
     *
     * Maps to: POST /decode
     *
     * @return array{
     *     valid: bool,
     *     national_id: string|null,
     *     birth_date: string|null,
     *     gender: string|null,
     *     governorate: string|null,
     *     nationality: string,
     *     expiry_date: string|null,
     *     expiry_is_permanent: bool|null,
     *     error: string|null,
     * }
     *
     * @throws OcrServiceUnavailableException
     * @throws OcrServiceException
     */
    public function decodeNationalId(string $nationalId): array
    {
        $response = $this->client(10)
            ->post($this->url('/decode'), ['national_id' => $nationalId]);

        return $this->parse($response, 'decode');
    }

    /**
     * Liveness probe.
     *
     * Maps to: GET /health (unauthenticated on OCR side)
     *
     * @return array{ status: string, dependencies: array }
     *
     * @throws OcrServiceUnavailableException
     * @throws OcrServiceException
     */
    public function health(): array
    {
        $response = Http::timeout(5)->get($this->url('/health'));

        return $this->parse($response, 'health');
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function client(int $timeout): PendingRequest
    {
        if (! $this->baseUrl || ! $this->secret) {
            throw new OcrServiceException(
                'OCR service is not configured. Set OCR_SERVICE_URL and OCR_SERVICE_SECRET in your .env.'
            );
        }

        return Http::withToken($this->secret)
            ->timeout($timeout)
            ->withMiddleware($this->connectionErrorMiddleware());
    }

    private function connectionErrorMiddleware(): callable
    {
        return function (callable $handler) {
            return function ($request, array $options) use ($handler) {
                try {
                    return $handler($request, $options);
                } catch (ConnectionException $e) {
                    Log::error('OCR service unreachable', ['error' => $e->getMessage()]);
                    throw new OcrServiceUnavailableException(previous: $e);
                }
            };
        };
    }

    private function url(string $path): string
    {
        return $this->baseUrl . $path;
    }

    private function parse(Response $response, string $operation): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        $detail = $response->json('detail') ?? $response->body();

        Log::warning('OCR service error', [
            'operation' => $operation,
            'status'    => $response->status(),
            'detail'    => $detail,
        ]);

        throw new OcrServiceException(
            "OCR service returned HTTP {$response->status()} on '$operation': $detail",
            $response->status(),
        );
    }
}
