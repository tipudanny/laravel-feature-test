<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
          'name' => 'Tipu',
          'email' => 'tipu@gmail.com',
          'password' => bcrypt('password')
        ];
    }
    /**
     *
     * @test
     */
    public function only_authenticated_user_can_add_new_user()
    {
        // check user authentication
        // create new user
        // show error

        $path = 'api/user-create';
        $data = [
          'name' => 'tipu',
          //'email' => 'tipu@tedfo.com'
        ];

        $this->postJson($path,$data)
            ->assertStatus(401);

        $user = User::factory()->create();
        $this->actingAs($user,'api');

        //$this->withoutExceptionHandling();
        $res = $this->postJson($path,$data)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'email' => 'The email field is required.'
            ]);

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

}
