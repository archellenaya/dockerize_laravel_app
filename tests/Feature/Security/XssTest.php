<?php

namespace Tests\Feature\Security;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirms user-controllable strings are HTML-escaped wherever they're
 * rendered, so a script tag in a name, a re-populated form field, or a
 * (simulated) imported article field can't execute in another visitor's
 * browser. Blade's {{ }} escapes by default; grep confirms the app has
 * zero unescaped {!! !!} output anywhere - these tests pin down the
 * user-facing effect of that so it can't regress.
 */
class XssTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_script_tag_in_a_registered_name_is_escaped_when_rendered_in_the_header(): void
    {
        $payload = '<script>alert(document.cookie)</script>';

        $response = $this->post(route('register'), [
            'name' => $payload,
            'email' => 'attacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $page = $this->get(route('dashboard'));
        $page->assertOk();
        $page->assertDontSee($payload, false);
        $page->assertSee('&lt;script&gt;alert(document.cookie)&lt;/script&gt;', false);
    }

    public function test_a_broken_out_attribute_payload_in_old_registration_input_is_escaped(): void
    {
        // register.blade.php re-populates name/email via old('name')/old('email')
        // inside a `value="..."` attribute after a validation failure - a
        // classic reflected-XSS vector if that value isn't escaped, since
        // a quote in the payload could close the attribute early.
        $payload = '"><script>alert(1)</script>';

        $response = $this->post(route('register'), [
            'name' => $payload,
            'email' => 'not-a-valid-email',
            'password' => 'password',
            'password_confirmation' => 'different',
        ]);

        $response->assertInvalid(['email', 'password']);

        $page = $this->get(route('register'));
        $page->assertOk();
        $page->assertDontSee('<script>alert(1)</script>', false);
        $page->assertSee('value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', false);
    }

    public function test_a_script_tag_in_an_imported_articles_title_is_escaped_on_the_listing_and_detail_pages(): void
    {
        // Simulates a compromised or malicious upstream feed - the import
        // pipeline doesn't strip HTML from the title, so the display layer
        // (Blade escaping) is what has to hold here.
        $article = Article::factory()->create([
            'title' => '<script>alert(1)</script> Breaking News',
            'description' => '<img src=x onerror=alert(2)>',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(2)>', false);

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(2)>', false);
    }

    public function test_a_script_tag_in_a_category_name_is_escaped_in_the_filter_dropdown(): void
    {
        $category = Category::factory()->create(['name' => '<script>alert(3)</script>']);
        Article::factory()->create(['category_id' => $category->id]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<script>alert(3)</script>', false);
    }
}
