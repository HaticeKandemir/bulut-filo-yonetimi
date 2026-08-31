<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_nested_tree_at_arbitrary_depth(): void
    {
        $level1 = Institution::create(['name' => 'PTT', 'code' => 'PTT']);
        $level2 = Institution::create(['name' => 'PTT E-AVM', 'code' => 'PTT-EAVM', 'parent_id' => $level1->id]);
        $level3 = Institution::create(['name' => 'PTTEM', 'code' => 'PTTEM', 'parent_id' => $level2->id]);
        $level4 = Institution::create(['name' => 'PTTEM ALT BIRIM', 'code' => 'PTTEM-ALT', 'parent_id' => $level3->id]);

        $response = $this->getJson('/api/v1/institutions');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $level1->id);
        $response->assertJsonPath('data.0.children.0.id', $level2->id);
        $response->assertJsonPath('data.0.children.0.children.0.id', $level3->id);
        $response->assertJsonPath('data.0.children.0.children.0.children.0.id', $level4->id);
        $response->assertJsonPath('data.0.children.0.children.0.children.0.children', []);
    }

    public function test_index_uses_a_single_query_regardless_of_tree_depth(): void
    {
        $parent = Institution::create(['name' => 'PTT', 'code' => 'PTT']);

        for ($i = 0; $i < 5; $i++) {
            $parent = Institution::create([
                'name' => "Level {$i}",
                'code' => "LEVEL-{$i}",
                'parent_id' => $parent->id,
            ]);
        }

        $this->expectsDatabaseQueryCount(1);

        $this->getJson('/api/v1/institutions')->assertOk();
    }
}
