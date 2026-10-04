<?php

use App\Models\CategorieConseil;
use App\Models\Conseil;
use App\Models\User;
use Database\Seeders\ConseilSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
});

function validAdviceArticle(int $categoryId, array $overrides = []): array
{
    return array_merge([
        'categorie_conseil_id' => $categoryId, 'titre' => 'Prepare your contact list',
        'resume' => 'Keep useful phone numbers nearby.', 'contenu' => "Write down key numbers.\n\nShare the plan.",
        'public_cible' => 'everyone', 'situation' => 'both', 'actif' => '0',
    ], $overrides);
}

test('advice administration requires an administrator for every action', function () {
    $category = CategorieConseil::factory()->create();
    $article = Conseil::factory()->for($category, 'categorieConseil')->create();
    $actions = [
        ['get', 'admin.categorie-conseils.index', []],
        ['get', 'admin.categorie-conseils.create', []],
        ['post', 'admin.categorie-conseils.store', []],
        ['get', 'admin.categorie-conseils.show', $category],
        ['get', 'admin.categorie-conseils.edit', $category],
        ['put', 'admin.categorie-conseils.update', $category],
        ['delete', 'admin.categorie-conseils.destroy', $category],
        ['get', 'admin.conseils.index', []],
        ['get', 'admin.conseils.create', []],
        ['post', 'admin.conseils.store', []],
        ['get', 'admin.conseils.show', $article],
        ['get', 'admin.conseils.edit', $article],
        ['put', 'admin.conseils.update', $article],
        ['patch', 'admin.conseils.publication', $article],
        ['delete', 'admin.conseils.destroy', $article],
    ];
    foreach ($actions as [$method, $routeName, $parameter]) {
        $this->$method(route($routeName, $parameter))->assertRedirect(route('login'));
    }
    $this->actingAs(User::factory()->create(['role' => 'USER']));
    foreach ($actions as [$method, $routeName, $parameter]) {
        $this->$method(route($routeName, $parameter))->assertForbidden();
    }
    expect($article->fresh())->not->toBeNull()->and($category->fresh())->not->toBeNull();
});

test('administrator can create read update and delete an empty advice category', function () {
    $this->actingAs($this->admin)->get(route('admin.categorie-conseils.create'))->assertOk();
    $this->post(route('admin.categorie-conseils.store'), ['nom' => '  Practical plans  ', 'icone' => 'sun'])->assertRedirect();
    $category = CategorieConseil::where('nom', 'Practical plans')->firstOrFail();
    $this->get(route('admin.categorie-conseils.index'))->assertOk()->assertSee('Practical plans');
    $this->get(route('admin.categorie-conseils.show', $category))->assertOk()->assertSee('No advice in this category yet');
    $this->get(route('admin.categorie-conseils.edit', $category))->assertOk()->assertSee('value="Practical plans"', false);
    $this->put(route('admin.categorie-conseils.update', $category), ['nom' => 'Practical plans', 'icone' => 'plug', 'description' => 'A useful topic.'])->assertSessionHasNoErrors();
    expect($category->fresh()->icone)->toBe('plug');
    $this->delete(route('admin.categorie-conseils.destroy', $category))->assertRedirect(route('admin.categorie-conseils.index'));
    $this->assertDatabaseMissing('categorie_conseils', ['id' => $category->id]);
});

test('category validation rejects duplicate blank overlong names and unsupported icons', function () {
    CategorieConseil::factory()->create(['nom' => 'Existing']);
    $this->actingAs($this->admin);
    foreach ([
        ['nom' => 'Existing', 'icone' => 'sun'],
        ['nom' => '   ', 'icone' => 'sun'],
        ['nom' => str_repeat('a', 101), 'icone' => 'sun'],
        ['nom' => 'New', 'icone' => 'unknown'],
        ['nom' => 'New', 'icone' => 'sun', 'description' => str_repeat('a', 1001)],
    ] as $payload) {
        $this->post(route('admin.categorie-conseils.store'), $payload)->assertSessionHasErrors();
    }
    expect(CategorieConseil::count())->toBe(1);
});

test('category search counts publications and prevents deletion even for a draft', function () {
    $category = CategorieConseil::factory()->create(['nom' => 'Family plans']);
    $other = CategorieConseil::factory()->create(['nom' => 'Equipment plans']);
    Conseil::factory()->for($category, 'categorieConseil')->published()->create();
    Conseil::factory()->for($category, 'categorieConseil')->create();
    $response = $this->actingAs($this->admin)->get(route('admin.categorie-conseils.index', ['q' => 'Family']))
        ->assertOk()->assertSee($category->nom)->assertDontSee($other->nom);
    $row = $response->viewData('categories')->first();
    expect($row->conseils_count)->toBe(2)->and($row->published_count)->toBe(1);
    $this->from(route('admin.categorie-conseils.show', $category))
        ->delete(route('admin.categorie-conseils.destroy', $category))->assertSessionHas('error');
    $category->conseils()->published()->delete();
    $this->delete(route('admin.categorie-conseils.destroy', $category))->assertSessionHas('error');
    expect($category->fresh())->not->toBeNull()->and($category->conseils()->count())->toBe(1);
});

test('database itself restricts category deletion and has a safe draft default', function () {
    $category = CategorieConseil::factory()->create();
    $article = Conseil::create(collect(validAdviceArticle($category->id))->except('actif')->all())->refresh();
    expect($article->actif)->toBeFalse()->and($article->categorieConseil->id)->toBe($category->id);
    expect(fn () => $category->delete())->toThrow(QueryException::class);
});

test('administrator can create review edit reassign publish and delete advice', function () {
    $category = CategorieConseil::factory()->create();
    $newCategory = CategorieConseil::factory()->create();
    $this->actingAs($this->admin)->get(route('admin.conseils.create', ['category' => $category->id]))->assertOk()
        ->assertSee('value="0" selected', false);
    $this->post(route('admin.conseils.store'), validAdviceArticle($category->id))->assertRedirect();
    $article = Conseil::firstOrFail();
    expect($article->actif)->toBeFalse();
    $this->get(route('admin.conseils.show', $article))->assertOk()->assertSee('Preview as resident')->assertSee('Publish advice');
    $this->get(route('admin.conseils.edit', $article))->assertOk()->assertSee('Prepare your contact list');
    $this->put(route('admin.conseils.update', $article), validAdviceArticle($newCategory->id, ['titre' => 'New title', 'actif' => '1']))
        ->assertRedirect(route('admin.conseils.show', $article));
    expect($article->fresh()->titre)->toBe('New title')->and($article->fresh()->actif)->toBeTrue()
        ->and($article->fresh()->categorie_conseil_id)->toBe($newCategory->id);
    $this->delete(route('admin.conseils.destroy', $article))->assertRedirect(route('admin.conseils.index'));
    $this->assertDatabaseMissing('conseils', ['id' => $article->id]);
    expect($newCategory->fresh())->not->toBeNull();
});

test('publication actions set explicit state and reject invalid input', function () {
    $article = Conseil::factory()->create();
    $this->actingAs($this->admin);
    for ($repeat = 0; $repeat < 2; $repeat++) {
        $this->patch(route('admin.conseils.publication', $article), ['actif' => '1'])->assertSessionHasNoErrors();
        expect($article->fresh()->actif)->toBeTrue();
    }
    $this->patch(route('admin.conseils.publication', $article), ['actif' => 'yes'])->assertSessionHasErrors('actif');
    $this->patch(route('admin.conseils.publication', $article), [])->assertSessionHasErrors('actif');
    $this->patch(route('admin.conseils.publication', $article), ['actif' => '0'])->assertSessionHasNoErrors();
    expect($article->fresh()->actif)->toBeFalse();
});

test('advice validation protects foreign keys required text enum choices and visibility', function () {
    $category = CategorieConseil::factory()->create();
    $this->actingAs($this->admin);
    foreach ([
        ['categorie_conseil_id' => 999999], ['titre' => '  '], ['titre' => str_repeat('a', 151)],
        ['resume' => str_repeat('a', 301)], ['contenu' => ' '], ['contenu' => str_repeat('a', 15001)],
        ['public_cible' => 'unknown'], ['situation' => 'unknown'], ['actif' => 'yes'],
    ] as $invalid) {
        $this->post(route('admin.conseils.store'), validAdviceArticle($category->id, $invalid))->assertSessionHasErrors(array_keys($invalid));
    }
    $this->post(route('admin.conseils.store'), collect(validAdviceArticle($category->id))->except('actif')->all())->assertSessionHasErrors('actif');
    expect(Conseil::count())->toBe(0);
});

test('validation preserves typed article values and gives a clear path when no category exists', function () {
    $this->actingAs($this->admin)->get(route('admin.conseils.create'))->assertOk()->assertSee('Start with a category');
    $category = CategorieConseil::factory()->create();
    $this->from(route('admin.conseils.create'))->post(route('admin.conseils.store'), validAdviceArticle($category->id, ['titre' => 'My typed title', 'resume' => '']))
        ->assertRedirect(route('admin.conseils.create'))->assertSessionHasErrors('resume');
    $this->get(route('admin.conseils.create'))->assertOk()->assertSee('value="My typed title"', false);
});

test('admin advice filters compose and preserve query parameters across pages', function () {
    $category = CategorieConseil::factory()->create();
    Conseil::factory()->count(11)->for($category, 'categorieConseil')->published()->create(['titre' => 'Contact planning']);
    Conseil::factory()->for($category, 'categorieConseil')->create(['titre' => 'Secret contact draft']);
    Conseil::factory()->published()->create(['titre' => 'Contact in another category']);
    $params = ['q' => 'Contact', 'category' => $category->id, 'status' => 'published'];
    $response = $this->actingAs($this->admin)->get(route('admin.conseils.index', $params))->assertOk()
        ->assertDontSee('Secret contact draft')->assertDontSee('Contact in another category');
    expect($response->viewData('conseils')->total())->toBe(11);
    foreach (['q=Contact', 'category='.$category->id, 'status=published', 'page=2'] as $query) {
        $response->assertSee($query, false);
    }
});

test('advice seeders are repeatable and retain existing content edits', function () {
    $this->seed(ConseilSeeder::class);
    $article = Conseil::firstOrFail();
    $article->update(['resume' => 'Edited by the team', 'actif' => false]);
    $this->seed(ConseilSeeder::class);
    expect(CategorieConseil::count())->toBe(4)->and(Conseil::count())->toBe(12)
        ->and($article->fresh()->resume)->toBe('Edited by the team')->and($article->fresh()->actif)->toBeFalse();
});

test('the shared seeder includes all modules with parent records available', function () {
    $this->seed(DatabaseSeeder::class);
    expect(CategorieConseil::count())->toBe(4)->and(Conseil::published()->count())->toBe(11);
    foreach (['quartiers', 'alerte_meteos', 'profiles', 'type_equipements', 'sensitive_equipments', 'coupures', 'signalements', 'type_points', 'point_fraicheurs'] as $table) {
        expect(\Illuminate\Support\Facades\DB::table($table)->count())->toBeGreaterThan(0);
    }
});

test('both advice migrations can roll back in child before parent order', function () {
    $this->artisan('migrate:rollback', ['--path' => [
        '../shared/database/migrations/2026_10_07_000002_create_conseils_table.php',
        '../shared/database/migrations/2026_10_07_000001_create_categorie_conseils_table.php',
    ], '--force' => true])->assertExitCode(0);
    expect(Schema::hasTable('conseils'))->toBeFalse()->and(Schema::hasTable('categorie_conseils'))->toBeFalse()
        ->and(Schema::hasTable('point_fraicheurs'))->toBeTrue();
    $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    expect(Schema::hasTable('conseils'))->toBeTrue()->and(Schema::hasTable('categorie_conseils'))->toBeTrue();
});
