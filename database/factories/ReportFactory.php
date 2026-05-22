<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'under_review', 'resolved', 'dismissed']);

        return [
            'id'                    => $this->faker->uuid(),
            'reporter_id'           => User::factory(),
            'reported_user_id'      => User::factory(),
            'reported_content_type' => $this->faker->randomElement(['project', 'message', 'profile', 'portfolio_item', null]),
            'reported_content_id'   => $this->faker->uuid(),
            'report_type'           => $this->faker->randomElement(['harassment', 'spam', 'inappropriate', 'copyright', 'other']),
            'description'           => $this->faker->paragraph(),
            'evidence'              => json_encode(['screenshots' => [$this->faker->imageUrl()]]),
            'status'                => $status,
            'priority'              => $this->faker->randomElement(['low', 'medium', 'high']),
            'assigned_to'           => in_array($status, ['under_review', 'resolved']) ? User::factory()->moderator() : null,
            'resolved_by'           => $status === 'resolved' ? User::factory()->moderator() : null,
            'resolution_action'     => $status === 'resolved' ? $this->faker->randomElement(['warning_issued', 'content_removed', 'user_suspended', 'no_action']) : null,
            'resolution_notes'      => $status === 'resolved' ? $this->faker->sentence() : null,
            'resolved_at'           => $status === 'resolved' ? $this->faker->dateTimeBetween('-14 days') : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => [
            'status'      => 'pending',
            'assigned_to' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    /**
     * Both reporter and reported_user are drawn from the pool of users
     * that already have a fully verified identity_verification record.
     *
     * Requires IdentityVerificationSeeder to have run first.
     */
    public function withVerifiedUsers(): static
    {
        return $this->state(function () {
            // Fetch users that have a verified identity verification record.
            $verifiedUserIds = DB::table('identity_verifications')
                ->where('verification_status', 'verified')
                ->pluck('user_id')
                ->shuffle();

            if ($verifiedUserIds->count() < 2) {
                throw new RuntimeException(
                    'withVerifiedUsers() requires at least 2 verified users. ' .
                    'Make sure IdentityVerificationSeeder ran before AdministrationSeeder.'
                );
            }

            return [
                'reporter_id'      => $verifiedUserIds->get(0),
                'reported_user_id' => $verifiedUserIds->get(1),
            ];
        });
    }
}
