<?php

namespace Tests\Feature\Security;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirms an attacker can't smuggle extra fields into a form submission
 * to set attributes they shouldn't control. Two independent layers make
 * this safe in this app, and each is tested separately:
 *
 *  1. FormRequest::validated() only returns keys with a validation rule
 *     defined - RegisterUserRequest never rules 'email_verified_at' or
 *     'remember_token', so those never even reach the service layer.
 *  2. Every model declares an explicit $fillable array (grep confirms
 *     none use $guarded = []) - Eloquent silently drops any attribute
 *     not on that list when mass-assigning, as a second line of defense.
 */
class MassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_with_extra_unexpected_fields_does_not_set_them(): void
    {
        $inTheFuture = now()->addYear()->toDateTimeString();

        $this->post(route('register'), [
            'name' => 'Legit User',
            'email' => 'legit@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            // Not part of RegisterUserRequest::rules() - an attacker
            // hoping extra POST fields fall through to the database.
            'email_verified_at' => $inTheFuture,
            'remember_token' => 'attacker-supplied-token',
            'id' => 999999,
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'legit@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotSame(999999, $user->id);
        $this->assertNull($user->email_verified_at);
        $this->assertNotSame('attacker-supplied-token', $user->remember_token);
    }

    public function test_the_user_model_ignores_non_fillable_attributes_when_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'model-level@example.com',
            'password' => 'irrelevant-for-this-test',
            'id' => 424242,
        ]);

        $this->assertNotSame(424242, $user->id);
    }

    public function test_the_article_model_ignores_non_fillable_attributes_when_mass_assigned(): void
    {
        $article = Article::create([
            'title' => 'A title',
            'url' => 'https://example.com/a-title',
            // Not in Article::$fillable.
            'id' => 555555,
        ]);

        $this->assertNotSame(555555, $article->id);
    }
}
