<?php

use App\Models\AdviceDocument;
use App\Models\CategorieConseil;
use App\Models\Conseil;
use App\Models\User;
use Database\Seeders\ConseilSeeder;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
    $this->category = CategorieConseil::factory()->create();
});

function richAdviceSample(): array
{
    return ['ops' => [
        ['insert' => 'Before you start'], ['insert' => "\n", 'attributes' => ['header' => 2]],
        ['insert' => 'Keep this '], ['insert' => 'important', 'attributes' => ['bold' => true]],
        ['insert' => ' detail nearby. خطة 🙂'."\n"],
        ['insert' => 'First step'], ['insert' => "\n", 'attributes' => ['list' => 'bullet']],
        ['insert' => 'Second step'], ['insert' => "\n", 'attributes' => ['list' => 'bullet']],
        ['insert' => 'Check again'], ['insert' => "\n", 'attributes' => ['header' => 3]],
        ['insert' => 'A gentle reminder', 'attributes' => ['italic' => true]], ['insert' => "\n"],
        ['insert' => 'One'], ['insert' => "\n", 'attributes' => ['list' => 'ordered']],
        ['insert' => 'Two'], ['insert' => "\n", 'attributes' => ['list' => 'ordered']],
    ]];
}

function richAdvicePayload(int $categoryId, mixed $document): array
{
    return [
        'categorie_conseil_id' => $categoryId, 'titre' => 'Structured advice', 'resume' => 'A useful introduction.',
        'contenu' => 'Client text is replaced by the canonical document.', 'contenu_formate' => is_array($document) ? json_encode($document) : $document,
        'public_cible' => 'everyone', 'situation' => 'both', 'actif' => '1',
    ];
}

test('rich article JSON round trips through create edit and update without losing formatting or Unicode', function () {
    $document = richAdviceSample();
    $this->actingAs($this->admin)->post(route('admin.conseils.store'), richAdvicePayload($this->category->id, json_encode($document)))
        ->assertSessionHasNoErrors()->assertRedirect();
    $article = Conseil::firstOrFail();
    expect($article->contenu_formate)->toBe($document)->and($article->contenu)->toBe(AdviceDocument::text($document));
    $this->get(route('admin.conseils.edit', $article))->assertOk()->assertSee('data-editor-document', false)->assertSee('Section heading');
    $html = '<h2>Before you start</h2><p>Keep this <strong>important</strong> detail nearby. خطة 🙂</p><ul><li>First step</li><li>Second step</li></ul><h3>Check again</h3><p><em>A gentle reminder</em></p><ol><li>One</li><li>Two</li></ol>';
    $this->get(route('admin.conseils.show', $article))->assertOk()->assertSee($html, false);
    $document['ops'][0]['insert'] = 'Updated section';
    $this->put(route('admin.conseils.update', $article), richAdvicePayload($this->category->id, json_encode($document)))->assertSessionHasNoErrors();
    expect($article->fresh()->contenu_formate)->toBe($document)->and($article->fresh()->bodyHtml())->toContain('<h2>Updated section</h2>');
});

test('saving other article fields preserves the document and its searchable text', function () {
    $article = Conseil::factory()->create(['contenu_formate' => richAdviceSample()]);
    $original = $article->contenu_formate;
    $article->update(['actif' => true, 'contenu' => 'An inconsistent external text value.']);
    expect($article->fresh()->contenu_formate)->toBe($original)
        ->and($article->fresh()->contenu)->toBe(AdviceDocument::text($original));
});

test('the rich document only allows the limited toolbar formats and text', function () {
    $this->actingAs($this->admin);
    foreach ([
        ['ops' => [['insert' => ['image' => 'https://example.test/x.png']]]],
        ['ops' => [['insert' => "Text\n", 'attributes' => ['color' => 'red']]]],
        ['ops' => [['insert' => "Text\n", 'attributes' => ['link' => 'javascript:alert(1)']]]],
        ['ops' => [['insert' => "Text\n", 'attributes' => ['header' => 1]]]],
        ['ops' => [['insert' => "Text\n", 'attributes' => ['bold' => 'yes']]]],
        ['ops' => [['retain' => 5]]],
        ['ops' => [['insert' => "\n"]]],
        ['ops' => [['insert' => str_repeat('a', 15001)."\n"]]],
        '{broken json',
    ] as $document) {
        $this->post(route('admin.conseils.store'), richAdvicePayload($this->category->id, $document))
            ->assertSessionHasErrors('contenu_formate');
    }
    expect(Conseil::count())->toBe(0);
});

test('pasted HTML is literal text and never becomes executable article markup', function () {
    $document = ['ops' => [['insert' => '<script>alert("x")</script><img src=x onerror=alert(1)>'."\n"]]];
    $this->actingAs($this->admin)->post(route('admin.conseils.store'), richAdvicePayload($this->category->id, $document))->assertSessionHasNoErrors();
    $article = Conseil::firstOrFail();
    expect($article->bodyHtml())->toContain('&lt;script&gt;')->not->toContain('<script>')->not->toContain('<img');
});

test('validation retains the document and text when another form field is invalid', function () {
    $payload = richAdvicePayload($this->category->id, json_encode(richAdviceSample()));
    $payload['titre'] = '';
    $this->actingAs($this->admin)->from(route('admin.conseils.create'))->post(route('admin.conseils.store'), $payload)
        ->assertSessionHasErrors('titre')->assertSessionHasInput('contenu_formate', json_encode(richAdviceSample()));
    $this->get(route('admin.conseils.create'))->assertOk()->assertSee('Before you start')->assertSee('data-advice-editor', false);
});

test('legacy plain text remains escaped and can be upgraded to a formatted article', function () {
    $article = Conseil::factory()->create(['contenu' => "Legacy line.\n\nAnother line."]);
    expect($article->bodyHtml())->toBe('<p>Legacy line.</p><p>Another line.</p>');
    $this->actingAs($this->admin)->put(route('admin.conseils.update', $article), richAdvicePayload($this->category->id, richAdviceSample()))
        ->assertSessionHasNoErrors();
    expect($article->fresh()->contenu_formate)->toBe(richAdviceSample());
});

test('sample articles have headings and lists and reseeding never overwrites edited article bodies', function () {
    $this->seed(ConseilSeeder::class);
    $article = Conseil::where('titre', 'Make a simple plan for hot days')->firstOrFail();
    expect($article->bodyHtml())->toContain('<h2>')->toContain('<ul>')->toContain('<strong>Keep in mind:');
    $article->update(['contenu_formate' => null, 'contenu' => 'My own edited article.']);
    $this->seed(ConseilSeeder::class);
    expect($article->fresh()->contenu_formate)->toBeNull()->and($article->fresh()->contenu)->toBe('My own edited article.');
});
