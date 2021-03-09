<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;


class UserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $path;
    protected $data;

    public function setUp():void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->path = 'api/user-create';

        $this->data =[
          'name' => $this->faker->name,
          'email' => $this->faker->email,
          'password' => bcrypt('password')
        ];
    }

    /**
     *
     * @test
     */
    public function user_get_email_on_registration()
    {
        $this->actingAs($this->user,'api');
        Mail::fake();
        Mail::assertNothingSent();
        $this->postJson('api/user-create',$this->data)
            ->assertOk()
            ->assertJson([
                "message"=> "The user has been notify"
            ]);
        //Mail::assertSent(NotifyUserMail::class);
        //$mail = new NotifyUserMail();
        //dump($mail->email);
    }
    /**
     *
     * @test
     */
    public function user_can_login_if_email_verified()
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);
        $attr = [
            'email' => $user->email ,
            'password' => 'password'
        ];
        $this->postJson('api/login-verify',$attr)
            ->assertStatus(401)
            ->assertJson([
                'message' => 'you are not verified yet.'
            ]);
    }

    /**
     *
     * @test
     */
    public function user_can_login()
    {
        //$this->withoutExceptionHandling();
        $res = $this->postJson('api/login',[
            'email'    => $this->user->email,
            'password' => 'password',
        ])
            ->assertStatus(200);
        $res->dump();
    }
    /**
     *
     * @test
     */
    public function only_authenticated_user_can_add_new_user()
    {
        $this->postJson($this->path,$this->data)
            ->assertStatus(401);

        $user = User::factory()->create();
        $this->actingAs($user,'api');
        $this->postJson($this->path,$this->data)
            ->assertStatus(201);
    }
    /**
     *
     * @test
     */
    public function user_name_cannot_be_null()
    {
        $data['name'] = null;
        $user = User::factory()->create();
        $this->actingAs($user,'api');

        $this->postJson('api/user-create',$data)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name'
            ]);
    }

    /**
     *
     * @test
     * */
    public function check_user_email_address_is_unique()
    {
        $this->data['email'] = $this->user->email;

        $this->actingAs($this->user,'api');

        $this->postJson($this->path,$this->data)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'email' => 'The email has already been taken.'
            ]);
    }

    /**
     *
     * @test
     */
    public function show_all_users()
    {
        User::factory()->count(10)->create();
        $this->actingAs($this->user,'api');

        $this->getJson($this->path)
            ->assertStatus(200)
            ->assertJson([
                'users'=>User::all()->except($this->user->id)->toArray()
            ]);
    }
    /**
     *
     * @test
     */
    public function only_authenticated_user_can_see_single_user()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->user,'api');
        $this->getJson($this->path.'/'.$this->user->id)
            ->assertStatus(200)
            ->assertJson([
                'user' => User::findOrFail($this->user->id)->value('name')
            ])
            ->assertSee($this->user['name']);
    }

}
