<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PaymentProviderCheck;
use App\Models\User;
use App\Services\Commerce\StripeCheckoutGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PaymentProviderReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role): User
    {
        $user = User::query()->create([
            'name'=>ucfirst($role),'email'=>$role.'@example.test',
            'password'=>'SecurePass#123',
        ]);
        $user->forceFill(['role'=>$role,'status'=>'active'])->save();
        return $user;
    }

    public function test_admin_only_can_verify_real_test_endpoint_and_no_secrets_are_exposed(): void
    {
        $admin=$this->actor('admin');
        $student=$this->actor('student');
        $this->actingAs($student)->get(route('admin.commerce.gateway-readiness.index'))->assertForbidden();
        $this->post(route('admin.commerce.gateway-readiness.check'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.commerce.gateway-readiness.index'))
            ->assertOk()->assertSee('External payment readiness');
        $this->post(route('admin.commerce.gateway-readiness.check'))->assertStatus(422);

        config()->set('payments.stripe', [
            'enabled'=>false, 'mode'=>'test',
            'secret_key'=>'sk_test_123456789abcdef',
            'webhook_secret'=>'whsec_test_123456789abcdef',
            'live_approved'=>false,
        ]);
        Http::fake(['api.stripe.com/v1/account' => Http::response([
            'id'=>'acct_123456789', 'country'=>'SG',
            'charges_enabled'=>true,
        ],200)]);

        $this->post(route('admin.commerce.gateway-readiness.check'))->assertRedirect();
        $check=PaymentProviderCheck::query()->firstOrFail();
        $this->assertSame('connected',$check->result);
        $this->assertSame('SG',$check->country);
        $this->assertTrue($check->charges_enabled);
        $this->assertDatabaseCount('payment_provider_checks',1);
        $this->assertDatabaseCount('orders',0);
        $this->get(route('admin.commerce.gateway-readiness.index'))->assertOk()
            ->assertSee('connected',false)->assertDontSee('sk_test_123456789abcdef')
            ->assertDontSee('acct_123456789');
    }

    public function test_stripe_live_mode_never_activates_from_test_connectivity_alone(): void
    {
        config()->set('payments.stripe', [
            'enabled'=>true, 'mode'=>'live',
            'secret_key'=>'sk_live_123456789abcdef',
            'webhook_secret'=>'whsec_live_123456789abcdef',
            'live_approved'=>false,
        ]);
        $gateway=app(StripeCheckoutGateway::class);
        $this->assertFalse($gateway->configured());
        config()->set('payments.stripe.live_approved',true);
        $this->assertTrue($gateway->configured());
    }
}
