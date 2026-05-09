<?php

namespace Tests\Feature\Profile;

use App\Models\PortfolioItem;
use App\Models\SkillEndorsement;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge(
            ['password' => Hash::make('Secret123')],
            $overrides
        ));
    }

    private function makeSkill(User $user, array $overrides = []): UserSkill
    {
        return UserSkill::factory()->create(array_merge(
            ['user_id' => $user->id, 'skill_name' => 'PHP'],
            $overrides
        ));
    }

    private function makePortfolioItem(User $user, array $overrides = []): PortfolioItem
    {
        return PortfolioItem::factory()->create(array_merge(
            ['user_id' => $user->id, 'visibility' => 'public'],
            $overrides
        ));
    }

    // =========================================================================
    // GET /api/v1/users  (user directory)
    // =========================================================================

    /** @test */
    public function authenticated_user_can_browse_user_directory(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->makeUser();
        $this->makeUser();

        $this->getJson('/api/v1/users')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    /** @test */
    public function user_directory_only_shows_active_users(): void
    {
        $viewer = $this->makeUser();
        Sanctum::actingAs($viewer);

        $this->makeUser(); // active
        User::factory()->suspended()->create();
        User::factory()->unverified()->create();

        $this->getJson('/api/v1/users')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data'); // viewer + one active
    }

    /** @test */
    public function user_directory_can_be_searched(): void
    {
        Sanctum::actingAs($this->makeUser());

        User::factory()->create(['full_name' => 'Alice Unique Name']);
        User::factory()->create(['full_name' => 'Bob Other Person']);

        $this->getJson('/api/v1/users?search=Unique')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function guest_sees_email_stripped_from_directory(): void
    {
        $this->makeUser(['email' => 'visible@example.com']);
        Sanctum::actingAs(User::factory()->guest()->create());

        $response = $this->getJson('/api/v1/users')
            ->assertStatus(200);

        $first = collect($response->json('data'))->firstWhere('email', 'visible@example.com');
        $this->assertNull($first);

        foreach ($response->json('data') as $u) {
            $this->assertNull($u['email']);
        }
    }

    /** @test */
    public function guest_is_page_capped_on_user_directory(): void
    {
        Sanctum::actingAs(User::factory()->guest()->create());

        $this->getJson('/api/v1/users?page=3')
            ->assertStatus(403)
            ->assertJsonPath('code', 'GUEST_PAGE_LIMIT_REACHED');
    }

    /** @test */
    public function unauthenticated_user_cannot_browse_directory(): void
    {
        $this->getJson('/api/v1/users')->assertStatus(401);
    }

    // =========================================================================
    // GET /api/v1/users/{user}  (public profile)
    // =========================================================================

    /** @test */
    public function authenticated_user_can_view_public_profile(): void
    {
        $target = $this->makeUser(['full_name' => 'Jane Doe']);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/users/$target->id")
            ->assertStatus(200)
            ->assertJsonPath('data.full_name', 'Jane Doe')
            ->assertJsonPath('data.id', $target->id);
    }

    /** @test */
    public function verified_viewer_sees_contact_links(): void
    {
        $target = $this->makeUser(['github_url' => 'https://github.com/janedoe']);
        Sanctum::actingAs($this->makeUser());

        $response = $this->getJson("/api/v1/users/$target->id")
            ->assertStatus(200);

        $this->assertEquals('https://github.com/janedoe', $response->json('data.github_url'));
    }

    /** @test */
    public function guest_cannot_see_contact_links_on_profile(): void
    {
        $target = $this->makeUser([
            'email'      => 'secret@example.com',
            'github_url' => 'https://github.com/janedoe',
        ]);
        Sanctum::actingAs(User::factory()->guest()->create());

        $response = $this->getJson("/api/v1/users/$target->id")
            ->assertStatus(200);

        $this->assertNull($response->json('data.email'));
        $this->assertNull($response->json('data.github_url'));
        $this->assertTrue($response->json('data.guest_restricted'));
    }

    // =========================================================================
    // GET /api/v1/profile  (own profile)
    // =========================================================================

    /** @test */
    public function user_can_view_own_profile(): void
    {
        $user = $this->makeUser(['full_name' => 'John Smith']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.full_name', 'John Smith')
            ->assertJsonPath('data.id', $user->id);
    }

    /** @test */
    public function own_profile_shows_email(): void
    {
        $user = $this->makeUser(['email' => 'john@example.com']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/profile')->assertStatus(200);

        $this->assertEquals('john@example.com', $response->json('data.email'));
    }

    /** @test */
    public function unauthenticated_user_cannot_view_own_profile(): void
    {
        $this->getJson('/api/v1/profile')->assertStatus(401);
    }

    // =========================================================================
    // PUT /api/v1/profile  (update profile)
    // =========================================================================

    /** @test */
    public function user_can_update_their_profile(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'full_name' => 'Updated Name',
            'bio'       => 'My new bio.',
            'location'  => 'Cairo, Egypt',
        ])->assertStatus(200)
            ->assertJsonPath('data.full_name', 'Updated Name')
            ->assertJsonPath('data.bio', 'My new bio.');

        $this->assertDatabaseHas('users', [
            'id'        => $user->id,
            'full_name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function username_must_be_unique(): void
    {
        $this->makeUser(['username' => 'taken_name']);
        $user  = $this->makeUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['username' => 'taken_name'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /** @test */
    public function user_can_keep_their_own_username(): void
    {
        $user = $this->makeUser(['username' => 'my_username']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['username' => 'my_username'])
            ->assertStatus(200);
    }

    /** @test */
    public function website_url_must_be_valid(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['website_url' => 'not-a-url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['website_url']);
    }

    // =========================================================================
    // POST /api/v1/profile/change-password
    // =========================================================================

    /** @test */
    public function user_can_change_password_with_correct_current_password(): void
    {
        $user = $this->makeUser(['password' => Hash::make('OldPass123')]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/change-password', [
            'current_password'          => 'OldPass123',
            'password'                  => 'NewPass456',
            'password_confirmation'     => 'NewPass456',
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('NewPass456', $user->fresh()->password));
    }

    /** @test */
    public function wrong_current_password_returns_422(): void
    {
        $user = $this->makeUser(['password' => Hash::make('OldPass123')]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/change-password', [
            'current_password'          => 'WrongPass',
            'password'                  => 'NewPass456',
            'password_confirmation'     => 'NewPass456',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /** @test */
    public function password_confirmation_must_match(): void
    {
        $user = $this->makeUser(['password' => Hash::make('OldPass123')]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/change-password', [
            'current_password'          => 'OldPass123',
            'password'                  => 'NewPass456',
            'password_confirmation'     => 'DifferentPass',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // =========================================================================
    // Skills — GET, POST, PUT, DELETE
    // =========================================================================

    /** @test */
    public function user_can_list_own_skills(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeSkill($user, ['skill_name' => 'PHP']);
        $this->makeSkill($user, ['skill_name' => 'Laravel']);

        $this->getJson('/api/v1/profile/skills')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_add_a_skill(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/skills', [
            'skill_name'        => 'React',
            'proficiency_level' => 4,
            'years_experience'  => 3.5,
        ])->assertStatus(201)
            ->assertJsonPath('data.skill_name', 'React');

        $this->assertDatabaseHas('user_skills', [
            'user_id'    => $user->id,
            'skill_name' => 'React',
        ]);
    }

    /** @test */
    public function cannot_add_duplicate_skill(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makeSkill($user, ['skill_name' => 'PHP']);

        $this->postJson('/api/v1/profile/skills', [
            'skill_name'        => 'PHP',
            'proficiency_level' => 3,
        ])->assertStatus(409);
    }

    /** @test */
    public function proficiency_level_must_be_between_1_and_5(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/skills', [
            'skill_name'        => 'Python',
            'proficiency_level' => 6,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['proficiency_level']);
    }

    /** @test */
    public function user_can_update_their_skill(): void
    {
        $user  = $this->makeUser();
        $skill = $this->makeSkill($user, ['proficiency_level' => 2]);
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/profile/skills/$skill->id", [
            'proficiency_level' => 5,
            'years_experience'  => 6.0,
        ])->assertStatus(200)
            ->assertJsonPath('data.proficiency_level', 5);
    }

    /** @test */
    public function user_cannot_update_another_users_skill(): void
    {
        $other = $this->makeUser();
        $skill = $this->makeSkill($other);
        Sanctum::actingAs($this->makeUser());

        $this->putJson("/api/v1/profile/skills/$skill->id", [
            'proficiency_level' => 5,
        ])->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_their_skill(): void
    {
        $user  = $this->makeUser();
        $skill = $this->makeSkill($user);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/profile/skills/$skill->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('user_skills', ['id' => $skill->id]);
    }

    /** @test */
    public function user_cannot_delete_another_users_skill(): void
    {
        $other = $this->makeUser();
        $skill = $this->makeSkill($other);
        Sanctum::actingAs($this->makeUser());

        $this->deleteJson("/api/v1/profile/skills/$skill->id")
            ->assertStatus(403);
    }

    // =========================================================================
    // Skill Endorsements — POST, DELETE
    // =========================================================================

    /** @test */
    public function user_can_endorse_another_users_skill(): void
    {
        $owner     = $this->makeUser();
        $endorser  = $this->makeUser();
        $skill     = $this->makeSkill($owner);
        Sanctum::actingAs($endorser);

        $this->postJson("/api/v1/skills/$skill->id/endorse")
            ->assertStatus(201);

        $this->assertDatabaseHas('skill_endorsements', [
            'user_skill_id'       => $skill->id,
            'endorsed_by_user_id' => $endorser->id,
        ]);
    }

    /** @test */
    public function cannot_endorse_own_skill(): void
    {
        $user  = $this->makeUser();
        $skill = $this->makeSkill($user);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/skills/$skill->id/endorse")
            ->assertStatus(422);
    }

    /** @test */
    public function cannot_endorse_a_skill_twice(): void
    {
        $owner    = $this->makeUser();
        $endorser = $this->makeUser();
        $skill    = $this->makeSkill($owner);
        Sanctum::actingAs($endorser);

        SkillEndorsement::factory()->create([
            'user_skill_id'       => $skill->id,
            'endorsed_by_user_id' => $endorser->id,
        ]);

        $this->postJson("/api/v1/skills/$skill->id/endorse")
            ->assertStatus(409);
    }

    /** @test */
    public function user_can_remove_their_endorsement(): void
    {
        $owner    = $this->makeUser();
        $endorser = $this->makeUser();
        $skill    = $this->makeSkill($owner);
        Sanctum::actingAs($endorser);

        SkillEndorsement::factory()->create([
            'user_skill_id'       => $skill->id,
            'endorsed_by_user_id' => $endorser->id,
        ]);

        $this->deleteJson("/api/v1/skills/$skill->id/endorse")
            ->assertStatus(200);

        $this->assertDatabaseMissing('skill_endorsements', [
            'user_user_skill_id'  => $skill->id,
            'endorsed_by_user_id' => $endorser->id,
        ]);
    }

    /** @test */
    public function removing_non_existent_endorsement_returns_404(): void
    {
        $owner    = $this->makeUser();
        $skill    = $this->makeSkill($owner);
        Sanctum::actingAs($this->makeUser());

        $this->deleteJson("/api/v1/skills/$skill->id/endorse")
            ->assertStatus(404);
    }

    // =========================================================================
    // Portfolio — GET, POST, PUT, DELETE, public view
    // =========================================================================

    /** @test */
    public function user_can_list_own_portfolio(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->makePortfolioItem($user);
        $this->makePortfolioItem($user);

        $this->getJson('/api/v1/profile/portfolio')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_add_a_portfolio_item(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile/portfolio', [
            'title'       => 'My Website',
            'description' => 'A personal website.',
            'item_type'   => 'link',
            'external_url'=> 'https://mywebsite.com',
            'visibility'  => 'public',
        ])->assertStatus(201)
            ->assertJsonPath('data.title', 'My Website');

        $this->assertDatabaseHas('portfolio_items', [
            'user_id' => $user->id,
            'title'   => 'My Website',
        ]);
    }

    /** @test */
    public function portfolio_item_type_must_be_valid(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/profile/portfolio', [
            'title'     => 'Bad Type',
            'item_type' => 'banana',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['item_type']);
    }

    /** @test */
    public function user_can_update_portfolio_item(): void
    {
        $user = $this->makeUser();
        $item = $this->makePortfolioItem($user, ['title' => 'Old Title']);
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/profile/portfolio/$item->id", [
            'title'    => 'New Title',
            'item_type'=> 'document',
        ])->assertStatus(200)
            ->assertJsonPath('data.title', 'New Title');
    }

    /** @test */
    public function user_cannot_update_another_users_portfolio_item(): void
    {
        $other = $this->makeUser();
        $item  = $this->makePortfolioItem($other);
        Sanctum::actingAs($this->makeUser());

        $this->putJson("/api/v1/profile/portfolio/$item->id", [
            'title'     => 'Hacked',
            'item_type' => 'link',
        ])->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_portfolio_item(): void
    {
        $user = $this->makeUser();
        $item = $this->makePortfolioItem($user);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/profile/portfolio/$item->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('portfolio_items', ['id' => $item->id]);
    }

    /** @test */
    public function authenticated_user_can_view_public_portfolio(): void
    {
        $owner = $this->makeUser();
        $this->makePortfolioItem($owner, ['visibility' => 'public']);
        $this->makePortfolioItem($owner, ['visibility' => 'public']);
        $this->makePortfolioItem($owner, ['visibility' => 'private']);
        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/v1/users/$owner->id/portfolio")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function guest_cannot_view_user_portfolio(): void
    {
        $owner = $this->makeUser();
        Sanctum::actingAs(User::factory()->guest()->create());

        $this->getJson("/api/v1/users/$owner->id/portfolio")
            ->assertStatus(403);
    }
}
