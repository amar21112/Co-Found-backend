<?php

namespace App\Services\Ocr;

use App\DTOs\Verification\EnrichedVerificationDTO;
use App\DTOs\Verification\SubmitVerificationDTO;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Calls the OCR service on a submitted ID card image and returns an
 * EnrichedVerificationDTO with all extractable card fields populated.
 *
 * Field sources (POST /scan → ScanResponse.decoded):
 *   decoded.national_id        → idCardNumber
 *   decoded.birth_date         → dateOfBirth  (DD/MM/YYYY → Y-m-d)
 *   decoded.full_name_on_card  → fullNameOnCard  (heuristic OCR)
 *   decoded.nationality        → nationality  (always "مصري")
 *   decoded.expiry_date        → expiryDate   (null when expiry_is_permanent)
 *
 * OCR is best-effort. Any failure returns an EnrichedVerificationDTO
 * with all card fields null so the submission is never blocked.
 */
class OcrEnricher
{
    public function __construct(
        private readonly OcrService $ocrService,
    ) {}

    public function enrich(SubmitVerificationDTO $dto): EnrichedVerificationDTO
    {
        try {
            $result = $this->ocrService->recognizeIdCard($dto->frontImage);

            if (! ($result['success'] ?? false) || ! ($result['national_id'] ?? null)) {
                Log::info('OCR enrichment: NID not detected', ['error' => $result['error'] ?? null]);

                return $this->fromSubmission($dto, ocrMeta: [
                    'attempted' => true,
                    'success'   => false,
                    'error'     => $result['error'] ?? 'no_nid_detected',
                ]);
            }

            $decoded = $result['decoded'] ?? [];

            $ocrMeta = [
                'attempted'     => true,
                'success'       => true,
                'national_id'   => $result['national_id'],
                'processing_ms' => $result['processing_time_ms'] ?? null,
            ];

            Log::info('OCR enrichment: NID extracted', [
                'national_id'  => $result['national_id'],
                'has_name'     => ! empty($decoded['full_name_on_card']),
                'is_permanent' => $decoded['expiry_is_permanent'] ?? false,
            ]);

            $expiryDate = ($decoded['expiry_is_permanent'] ?? false)
                ? null
                : $this->parseFieldDate($decoded['expiry_date'] ?? null);

            return new EnrichedVerificationDTO(
                frontImage:        $dto->frontImage,
                backImage:         $dto->backImage,
                submissionMethod:  $dto->submissionMethod,
                ipAddress:         $dto->ipAddress,
                idCardNumber:      $result['national_id'],
                fullNameOnCard:    $this->nullIfEmpty($decoded['full_name_on_card'] ?? null),
                dateOfBirth:       $this->parseBirthDate($decoded['birth_date'] ?? null),
                nationality:       $this->nullIfEmpty($decoded['nationality'] ?? null),
                expiryDate:        $expiryDate,
                livenessCheckData: ['ocr' => $ocrMeta],
            );

        } catch (Throwable $e) {
            Log::error('OCR enrichment failed', ['error' => $e->getMessage(), 'class' => $e::class]);

            return $this->fromSubmission($dto, ocrMeta: [
                'attempted' => true,
                'success'   => false,
                'error'     => 'service_error',
            ]);
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Build a bare EnrichedVerificationDTO from a SubmitVerificationDTO,
     * with no card fields and only the OCR metadata recorded.
     */
    private function fromSubmission(SubmitVerificationDTO $dto, array $ocrMeta): EnrichedVerificationDTO
    {
        return new EnrichedVerificationDTO(
            frontImage:        $dto->frontImage,
            backImage:         $dto->backImage,
            submissionMethod:  $dto->submissionMethod,
            ipAddress:         $dto->ipAddress,
            livenessCheckData: ['ocr' => $ocrMeta],
        );
    }

    /**
     * Convert NID-decoded "DD/MM/YYYY" → "YYYY-MM-DD".
     * Returns null if malformed or the date is not in the past.
     */
    private function parseBirthDate(?string $ocrDate): ?string
    {
        if (! $ocrDate) {
            return null;
        }

        $parts = explode('/', $ocrDate);

        if (count($parts) !== 3) {
            return null;
        }

        [$day, $month, $year] = $parts;
        $formatted = sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day);

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $formatted);

        if (! $date || $date >= new DateTimeImmutable('today')) {
            return null;
        }

        return $formatted;
    }

    /**
     * Parse an expiry date from the OCR service.
     * The API returns "DD/MM/YYYY" from _compute_expiry.
     */
    private function parseFieldDate(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, trim($raw));
            if ($date && $date->format($format) === trim($raw)) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function nullIfEmpty(?string $value): ?string
    {
        return ($value !== null && trim($value) !== '') ? trim($value) : null;
    }
}
