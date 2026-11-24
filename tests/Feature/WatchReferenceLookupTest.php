<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WatchReferenceLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_lookup_requires_an_admin(): void
    {
        $this->getJson($this->lookupUrl('T1374072103100'))->assertUnauthorized();

        $this->actingAs(User::factory()->create(['role' => 'investor']))
            ->getJson($this->lookupUrl('T1374072103100'))
            ->assertRedirect(route('investor.dashboard'));
    }

    public function test_tissot_sample_returns_verified_specs(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson($this->lookupUrl('t137.407.21.031.00'))
            ->assertOk()
            ->assertJsonPath('details.model_name', 'PRX Powermatic 80')
            ->assertJsonPath('details.case_size', '40 mm')
            ->assertJsonPath('source', 'Tissot specs table')
            ->assertJsonPath('verification_status', null);
    }

    public function test_inventory_match_only_returns_model_specs(): void
    {
        Watch::create([
            'brand' => 'Seiko',
            'model_name' => 'Speedtimer',
            'reference_number' => 'SSC-813',
            'slug' => 'seiko-speedtimer',
            'movement' => 'Solar',
            'serial_number' => 'private',
            'selling_price' => 20000,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson($this->lookupUrl('ssc813'))
            ->assertOk()
            ->assertJsonPath('details.movement', 'Solar')
            ->assertJsonPath('source', 'Existing inventory')
            ->assertJsonMissingPath('details.serial_number')
            ->assertJsonMissingPath('details.selling_price');
    }

    public function test_tissot_sku_and_dotted_reference_return_the_same_watch(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $sku = $this->getJson($this->lookupUrl('T1374101142100'))
            ->assertOk()
            ->assertJsonPath('details.model_name', 'PRX Quartz')
            ->assertJsonPath('details.movement', 'Swiss quartz')
            ->assertJsonPath('details.dial_color', 'Red');

        $this->getJson($this->lookupUrl('T137.410.11.421.00'))
            ->assertOk()
            ->assertExactJson($sku->json());
    }

    public function test_tissot_table_returns_the_catalogue_model_name_and_estimate_status(): void
    {
        Watch::create([
            'brand' => 'Tissot',
            'model_name' => 'Older inventory title',
            'reference_number' => 'T006.207.11.036.00',
            'slug' => 'older-tissot-entry',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson($this->lookupUrl('T0062071103600'))
            ->assertOk()
            ->assertJsonPath('details.brand', 'Tissot')
            ->assertJsonPath('details.model_name', 'Le Locle Automatic')
            ->assertJsonPath('details.case_size', '29 mm')
            ->assertJsonPath('verification_status', 'Family / SKU estimate – verify')
            ->assertJsonPath('source_url', 'https://www.tissotwatches.com/en-us/T0062071103600.html');

        $this->getJson($this->lookupUrl('T41.1.113.77'))
            ->assertOk()
            ->assertJsonPath('details.brand', 'Tissot');

        Http::assertNothingSent();
    }

    public function test_unknown_reference_falls_back_to_manual_entry(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson($this->lookupUrl('ABC123'))
            ->assertNotFound()
            ->assertJsonPath('message', 'No match in inventory or the Tissot specs table. Enter the specs manually.');

        Http::assertNothingSent();
    }

    private function lookupUrl(string $reference): string
    {
        return route('admin.watches.reference-lookup', ['reference' => $reference]);
    }
}
