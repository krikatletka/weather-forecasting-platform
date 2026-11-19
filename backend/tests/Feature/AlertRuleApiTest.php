<?php

namespace Tests\Feature;

use App\Models\AlertRule;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlertRuleApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(City $city, array $over = []): array
    {
        return array_merge([
            'city_id' => $city->id,
            'parameter' => 'temperature',
            'operator' => '<',
            'threshold_value' => 0,
        ], $over);
    }

    public function test_guest_gets_401(): void
    {
        $this->getJson('/api/alerts')->assertUnauthorized();
    }

    public function test_user_can_create_and_list_own_rules(): void
    {
        $user = User::factory()->create();
        $city = $this->makeCity(); 
        Sanctum::actingAs($user);

        $this->postJson('/api/alerts', $this->payload($city))->assertCreated();

        $this->getJson('/api/alerts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_does_not_see_other_users_rules(): void
    {
        $city = $this->makeCity(); 
        AlertRule::create($this->payload($city) + ['user_id' => User::factory()->create()->id]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/alerts')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_cannot_update_or_delete_other_users_rule(): void
    {
        $city = $this->makeCity(); 
        $rule = AlertRule::create($this->payload($city) + ['user_id' => User::factory()->create()->id]);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/alerts/{$rule->id}", ['is_active' => false])->assertNotFound();
        $this->deleteJson("/api/alerts/{$rule->id}")->assertNotFound();
    }

    public function test_user_can_toggle_and_delete_own_rule(): void
    {
        $user = User::factory()->create();
        $city = $this->makeCity(); 
        $rule = AlertRule::create($this->payload($city) + ['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->putJson("/api/alerts/{$rule->id}", ['is_active' => false])->assertOk();
        $this->assertFalse($rule->fresh()->is_active);

        $this->deleteJson("/api/alerts/{$rule->id}")->assertNoContent();
        $this->assertModelMissing($rule);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $city = $this->makeCity(); 
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/alerts', $this->payload($city, ['parameter' => 'foo']))->assertUnprocessable();
        $this->postJson('/api/alerts', $this->payload($city, ['operator' => '!=']))->assertUnprocessable();
        $this->postJson('/api/alerts', $this->payload($city, ['threshold_value' => 'abc']))->assertUnprocessable();
        $this->postJson('/api/alerts', $this->payload($city, ['city_id' => 99999]))->assertUnprocessable();
    }

    private function makeCity(string $name = 'Київ'): City
    {
        return City::forceCreate([
            'name' => $name,
            'latitude' => 50.45,
            'longitude' => 30.52,
            'timezone' => 'Europe/Kyiv',
        ]);
    } 
}