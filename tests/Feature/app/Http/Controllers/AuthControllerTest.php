<?php

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Laravel\Lumen\Testing\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class AuthControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function createApplication()
    {
        return require './bootstrap/app.php';
    }

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testUserShouldNotAuthenticateWithWrongProvider()
    {
        $payload = [
            'email' => 'hey@danielheart.dev',
            'password' => 'secret123'
        ];

        $request = $this->post(route('authenticate', ['provider' => 'wrong provider']), $payload);

        $request->assertResponseStatus(422);
        $request->seeJson(['errors' => ['main' => 'Provider Not found']]);
    }


    public function testUserShouldBeDeniedIfNotRegistered()
    {
        $payload = [
            'email' => 'hey@danielheart.dev',
            'password' => 'secret123'
        ];

        $request = $this->post(route('authenticate', ['provider' => 'retailer']), $payload);
        $request->assertResponseStatus(401);
        $request->seeJson(['errors' => ['main' => 'Wrong credentials']]);
    }

    public function testUserShouldSendWrongPassword()
    {

        $user = User::factory()->create();
        $payload = [
            'email' => $user->email,
            'password' => 'fodase123'
        ];

        $request = $this->post(route('authenticate', ['provider' => 'user']), $payload);
        $request->assertResponseStatus(401);
        $request->seeJson(['errors' => ['main' => 'Wrong credentials']]);

    }

    public function testUserCanAuthenticate()
    {

        $this->artisan('passport:install');
        $user = User::factory()->create();

        $payload = [
            'email' => $user->email,
            'password' => 'secret'
        ];

        $request = $this->post(route('authenticate', ['provider' => 'user']), $payload);
        $request->assertResponseStatus(200);
        $request->seeJsonStructure(['access_token','expires_at', 'provider']);
    }
}
