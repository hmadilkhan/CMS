<?php

namespace Tests\Feature;

use App\Livewire\Notifications;
use App\Models\User;
use App\Models\UserType;
use App\Support\NotificationSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The search box in the header's notifications dropdown and on the full
 * notifications page. Both read the text fields out of the `data` JSON.
 */
class NotificationSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        UserType::firstOrCreate(['name' => 'Admin']);
        $this->user = User::factory()->create(['user_type_id' => 1]);

        $this->notify(['note' => 'Please review the Permit packet', 'mentioned_by' => 'Susan Stauffer', 'project_name' => 'Yunjiao-Guan - 61st Ave', 'url' => '/projects/1']);
        $this->notify(['subject' => 'Inverter fault at site', 'priority' => 'High']);
        $this->notify(['email_subject' => 'Utility bill attached', 'email_from' => 'owner@example.com', 'url' => '/projects/2']);
    }

    private function notify(array $data): void
    {
        $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Test',
            'data' => $data,
        ]);
    }

    public function test_dropdown_matches_a_phrase_in_any_text_field_case_insensitively(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Notifications::class)
            ->set('search', 'review the permit')
            ->assertSeeText('Please review the Permit packet')
            ->assertDontSeeText('Inverter fault at site')
            ->set('search', 'YUNJIAO')
            ->assertSeeText('Please review the Permit packet')
            ->set('search', 'inverter')
            ->assertSeeText('Inverter fault at site')
            ->assertDontSeeText('Please review the Permit packet');
    }

    public function test_dropdown_does_not_match_json_key_names(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Notifications::class)
            ->set('search', 'url')
            ->assertSee('No notifications match "url"', false);
    }

    public function test_like_wildcards_are_matched_literally(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Notifications::class)
            ->set('search', '%')
            ->assertSee('No notifications match "%"', false);
    }

    public function test_dropdown_highlights_the_match(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Notifications::class)
            ->set('search', 'permit')
            ->assertSeeHtml('review the <mark class="search-hit">Permit</mark> packet');
    }

    public function test_highlight_escapes_the_text_and_never_marks_inside_an_entity(): void
    {
        $this->assertSame(
            'R&amp;D &lt;b&gt;<mark class="search-hit">Permit</mark>&lt;/b&gt;',
            (string) NotificationSearch::highlight('R&D <b>Permit</b>', 'permit')
        );
        $this->assertSame('R&amp;D', (string) NotificationSearch::highlight('R&D', 'amp'));
        $this->assertSame('a &lt;b&gt;', (string) NotificationSearch::highlight('a <b>', '  '));
    }

    // The page also renders the header dropdown, which lists every notification,
    // so the list is checked through the paginator rather than the HTML.
    public function test_index_page_filters_by_q_and_keeps_it_in_the_box(): void
    {
        $this->actingAs($this->user)
            ->get(route('notifications.index', ['q' => 'utility bill']))
            ->assertOk()
            ->assertSee('value="utility bill"', false)
            ->assertSee('1 result for')
            ->assertViewHas('notifications', fn ($page) => $page->total() === 1
                && $page->first()->data['email_subject'] === 'Utility bill attached'
                && str_contains($page->url(2), 'q=utility'));
    }

    public function test_index_page_without_a_search_lists_everything(): void
    {
        $this->actingAs($this->user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertViewHas('notifications', fn ($page) => $page->total() === 3);
    }
}
