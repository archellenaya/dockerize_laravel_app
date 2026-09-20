# Security: vulnerability classes and how this app handles them

This documents the vulnerability classes we tested against this app, what
Laravel does for each by default, what this specific codebase does on top
of that, and where the regression test lives. Read it alongside
`tests/Feature/Security/` — every claim below has a test backing it, and
every test has a comment pointing back to the relevant section here.

| Vulnerability | Laravel's default protection | Verified by |
|---|---|---|
| SQL Injection | Query builder parameter binding | `SqlInjectionTest.php` |
| XSS | Blade `{{ }}` auto-escaping | `XssTest.php` |
| CSRF | `PreventRequestForgery` middleware + `@csrf` | `CsrfProtectionTest.php` |
| Mass Assignment | `$fillable` allow-lists + FormRequest validation | `MassAssignmentTest.php` |
| IDOR | Not automatic — see below | `IdorTest.php` |
| Auth bypass | `auth`/`guest` route middleware | `AuthorizationTest.php` |

Run the whole set with `php artisan test --filter=Security` (inside the
`laravel-app` container). None of it touches the real MySQL database —
`phpunit.xml` forces an in-memory SQLite connection for every test run.

---

## SQL Injection

**What it is:** attacker input changes the structure of a SQL query
instead of just supplying a value — e.g. a search box that lets you
inject `' OR '1'='1` and have it evaluated as SQL rather than treated as
the literal string you typed.

**Laravel's default protection:** Eloquent and the query builder send
every value through PDO prepared statements. `where('title', 'like',
$value)` compiles to `where title like ?` with `$value` sent to the
database driver as a bound parameter — never spliced into the SQL text.
This is automatic for every fluent query-builder method (`where`,
`whereHas`, `whereDate`, `whereIn`, …). It stops being automatic the
moment raw SQL fragments are built with string interpolation via
`whereRaw()`, `DB::raw()`, `DB::statement()`, `DB::select()`,
`selectRaw()`, or `orderByRaw()`.

**What this app does:** `grep -rn "whereRaw\|DB::raw\|DB::statement\|DB::select\|selectRaw\|orderByRaw" app/`
returns nothing — there is no raw SQL anywhere in the codebase. The one
place that *looks* risky, `EloquentArticleRepository::filtered()`,
builds the search filter as:

```php
->where('title', 'like', "%{$search}%")
```

The `"%{$search}%"` interpolation only builds the *value* (the wildcard
pattern); it's still passed to `where()` as a normal bound parameter, so
the interpolation never touches the SQL template itself.

**Verified:**
- Ran a UNION-based payload (`nonexistent%' UNION SELECT ... FROM users -- `)
  through the live search filter via HTTP and via the repository directly;
  inspected the query log and confirmed the compiled SQL used `?`
  placeholders with the entire payload sitting inside the bound value.
- Ran an `OR '1'='1'` payload through the category filter; confirmed it
  did not bypass filtering (returned zero rows, since no category has
  that literal string as its slug).
- Built an isolated demo (in-memory SQLite, fake data, no connection to
  this app's real database) contrasting the vulnerable version of that
  same line (string-concatenated into raw SQL — leaked another table's
  data) against the parameterized version (payload neutralized).

**What would break this:** introducing `whereRaw()`/`DB::raw()` with
string-interpolated user input anywhere, for "performance" or
convenience. Don't concatenate request data into raw SQL fragments —
that's the entire rule.

---

## XSS (Cross-Site Scripting)

**What it is:** attacker-controlled text gets rendered as executable
HTML/JS in another visitor's browser — e.g. a `<script>` tag in a
display name that runs when someone else views a page containing it.

**Laravel's default protection:** Blade's `{{ $value }}` syntax runs the
value through `htmlspecialchars()` before output, turning `<script>`
into `&lt;script&gt;`. The unescaped equivalent, `{!! $value !!}`, exists
specifically for trusted, pre-sanitized HTML and should never be used on
anything that came from a user or an external API.

**What this app does:** `grep -rn "{!!" resources/views/` returns
nothing — every view in this app uses escaped output exclusively. That
matters here more than in a typical CRUD app, because two of this app's
main data sources are *external and untrusted*: article titles/
descriptions come from NewsAPI (a compromised or malicious upstream feed
could inject a script tag into a title), and category/source names are
either seeded or also import-derived.

**Verified:**
- A `<script>` tag as a registration name renders escaped in the header
  (`{{ auth()->user()->name }}`).
- A payload designed to break out of an HTML attribute
  (`"><script>alert(1)</script>`) survives `old()` re-population on the
  registration form's `value="..."` attribute without breaking out or
  executing — Blade escapes quotes too, not just angle brackets.
- A `<script>` tag in a simulated malicious article title/description
  renders escaped on both the article list and detail pages.
- A `<script>` tag in a category name renders escaped in the filter
  dropdown.

**What would break this:** adding `{!! !!}` anywhere that touches user
input, an article field, or anything sourced externally. If this app
ever renders article content as Markdown/rich HTML (it currently
displays `content` as plain escaped text), that renderer would need its
own sanitization step (e.g. an allow-list-based HTML purifier) — `{{ }}`
alone can't help once you deliberately opt into unescaped output.

---

## CSRF (Cross-Site Request Forgery)

**What it is:** a malicious site tricks a logged-in visitor's browser
into submitting a state-changing request to this app (e.g. an auto-
submitting form pointed at `/logout` or `/articles/5/bookmark`) using
the visitor's own session cookie, without their knowledge.

**Laravel's default protection:** every route in the `web` middleware
group runs `PreventRequestForgery` (the current name for what used to be
`VerifyCsrfToken`), which rejects any non-GET request unless it carries
a token matching the one tied to the visitor's session. The `@csrf`
Blade directive embeds that token as a hidden field in a form.

**What this app does:** every POST/DELETE form
(login, register, logout, bookmark save/unsave, category follow/
unfollow) includes `@csrf` (and `@method('DELETE')` where needed), and
none of the routes opt out of the `web` middleware group.

**Verified — and why this needed two different techniques:**
Laravel's `PreventRequestForgery::handle()` contains an explicit bypass:
it skips the token check entirely when `$app->runningUnitTests()` is
true. That's *why* every other test in this suite can `$this->post(...)`
a form with no token and have it succeed — and it means a normal
in-process feature test literally cannot observe a 419 rejection here.
So this was verified two ways:
1. **Structural test** (`CsrfProtectionTest.php`): confirms
   `PreventRequestForgery` is actually present in the `web` middleware
   group, and that every state-changing route runs that group.
2. **Live proof**, run once by hand against the real `php artisan serve`
   process (not the test runner, so no bypass applies):
   ```sh
   curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8000/login \
     -d "email=x@example.com&password=x"
   # → 419
   ```

**What would break this:** adding a route outside the `web` group, or
adding `PreventRequestForgery::except([...])` for a path that shouldn't
be exempt. If this app grows a JSON/API surface later, that surface
needs its own protection story (e.g. Sanctum tokens) rather than
disabling CSRF outright.

---

## Mass Assignment

**What it is:** an attacker adds extra fields to a form submission
hoping the framework blindly saves them — e.g. submitting
`email_verified_at=<future date>` or `id=1` alongside a normal
registration form, hoping it overwrites a column they shouldn't control.

**Laravel's default protection:** two independent layers, both used
here:
1. Every Eloquent model must declare either `$fillable` (an allow-list —
   what this app uses everywhere) or `$guarded` (a deny-list, or
   `$guarded = []` to disable protection entirely). Attributes not on
   the `$fillable` list are silently dropped during `create()`/`fill()`/
   `update()`.
2. `FormRequest::validated()` only returns keys that have an explicit
   validation rule — anything else submitted in the request never even
   reaches the controller/service layer.

**What this app does:** every model (`User`, `Article`, `Category`,
`Source`, `ArticleBookmark`, `CategoryFollow`) declares an explicit
`$fillable` array; `grep -rn "guarded" app/Models/` confirms none use
`$guarded = []`. No controller calls `Model::create($request->all())` —
`RegisteredUserController` passes `$request->validated()`
(name/email/password only, per `RegisterUserRequest::rules()`), and
every other write goes through a repository method with explicit,
named parameters.

**Verified:** submitted registration with extra fields
(`email_verified_at`, `remember_token`, `id`) that aren't in
`RegisterUserRequest::rules()`; confirmed none were persisted. Separately
confirmed at the model level that `Article::create([...,'id' => 555555])`
and `User::create([...,'id' => 424242])` ignore the unlisted `id` key.

**What would break this:** adding `$guarded = []` to a model, or a
controller calling `Model::create($request->all())` directly instead of
going through validation.

---

## IDOR (Insecure Direct Object Reference)

**What it is:** an attacker changes an ID in a request to read or modify
a resource that belongs to someone else — e.g. calling
`DELETE /articles/5/bookmark` while logged in as Bob, where article `5`
is something only Alice bookmarked, hoping it deletes Alice's row.

**Laravel's default protection:** none, automatically — this is a
query-scoping/architecture discipline, not a framework feature. (Laravel
does offer Policies/Gates for per-resource authorization checks; this
app doesn't need them because of how the routes are shaped — see next.)

**What this app does:** every bookmark/follow route is keyed by the
*target* resource (the article or category), never by the row a client
is trying to affect, and every query is explicitly scoped to
`auth()->id()`:

```php
// BookmarkController::destroy
$this->bookmarks->unsave($request->user()->id, $article);

// EloquentBookmarkRepository::remove
ArticleBookmark::where('user_id', $userId)->where('article_id', $articleId)->delete();
```

There is no route shaped like `DELETE /bookmarks/{bookmarkId}` where a
client supplies a row's own primary key — so there's no ID an attacker
could substitute to reach a row that isn't already scoped to their own
`user_id`. Worst case, targeting someone else's article/category ID is a
no-op (the `WHERE` clause matches zero rows), not a leak or a mutation.

**Verified:** Bob calls `bookmarks.destroy`/`categories.unfollow`
targeting the *exact* article/category Alice already saved/followed —
Alice's row survives every time. Also confirmed Bob's dashboard never
shows Alice's saved articles.

**What would break this:** adding a route or query that accepts a
"which user" ID from the client instead of always deriving it from
`auth()->id()` — e.g. an admin-style `/users/{id}/bookmarks` endpoint
without an authorization check on `{id}`.

---

## Authorization / Auth bypass

**What it is:** an unauthenticated visitor reaches a page or action that
should require login (or a logged-in user reaches a guest-only page like
the login form).

**Laravel's default protection:** the `auth` middleware redirects
unauthenticated requests to the route named `login`; the `guest`
middleware redirects already-authenticated users away from login/
register.

**What this app does** (`routes/web.php`): the dashboard, bookmark
store/destroy, and category follow/unfollow routes all sit inside
`Route::middleware('auth')->group(...)`; login/register sit inside
`Route::middleware('guest')->group(...)` (`routes/auth.php`).

**Verified:** a guest hitting the dashboard, bookmark routes, or follow
routes gets redirected to `login` and leaves no database row behind; an
authenticated user hitting `/login` or `/register` gets redirected away.

**What would break this:** forgetting to nest a new sensitive route
inside the `auth` middleware group when adding it to `routes/web.php`.

---

## Keeping this true over time

- `php artisan test --filter=Security` — run before merging anything
  that touches routes, models, or Blade views.
- `./vendor/bin/pint --test` — catches nothing security-specific, but
  keeps the codebase consistent enough that a raw-SQL or unescaped-output
  addition is easy to spot in review.
- The single highest-leverage habit: `grep -rn "whereRaw\|DB::raw\|{!!\|guarded = \[\]" app/ resources/views/`
  before merging. A clean result on that grep is most of what keeps
  three of these six categories true.
