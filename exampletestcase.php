<?php

namespace Tests\Feature;

use App\Permission;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SuperAdminFunctionality extends TestCase
{
    use RefreshDatabase,WithFaker;

    protected $user;
    protected $testUser;
    protected $attrs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
        $this->user->assignRole(Role::SUPER_ADMIN);

        $this->testUser = factory(User::class)->create();
        $this->testUser->assignRole(Role::ADMIN);

        $this->actingAs($this->user,'api');


        $this->attrs=[
            'name'=>$this->faker->name,
            'email'=>$this->faker->email,
            'permissions'=>[Permission::MANAGE_ACADEMY,Permission::MANAGE_USER],
            'password'=>bcrypt('password')
        ];
    }

    /**
     *Test case for TEDFO-298
     * @test
     */
    public function only_super_admin_can_add_admin_user()
    {
        $this->user->removeRole(Role::SUPER_ADMIN);
        $this->user->assignRole(Role::ADMIN);
        $this->user->givePermissionTo(Permission::MANAGE_ADMIN);

        $this->user->refresh();

        $this->postJson('v1/admins',$this->attrs)
            ->assertStatus(403)
            ->assertJson(['message'=>'Only super admin can add new admin']);

        $this->user->removeRole(Role::ADMIN);
        $this->user->assignRole(Role::SUPER_ADMIN);

        $this->user->refresh();

        $this->postJson('v1/admins',$this->attrs)
            ->assertStatus(201);
    }

    /**
     *
     * @test
     */
    public function name_field_required_on_admin_add()
    {
        $this->attrs['name']=null;

        $this->postJson('v1/admins',$this->attrs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name'=>'The name field is required.'
            ]);
    }
    /**
     *
     * @test
     */
    public function email_field_required_on_admin_add()
    {
        $this->attrs['email']=null;

        $this->postJson('v1/admins',$this->attrs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'email'=>'The email field is required.'
            ]);
    }
    /**
     *
     * @test
     */
    public function email_field_must_valid_email_on_admin_add()
    {
        $this->attrs['email']='fakeremail.com';

        $this->postJson('v1/admins',$this->attrs)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'email'=>'The email must be a valid email address.'
            ]);
    }
    /**
     *
     * @test
     */
    public function if_email_unique_then_create_new_else_assign_permissions()
    {
        $this->attrs['email'] = $this->testUser->email;

        $this->assertDatabaseHas('users', ['email' => $this->testUser->email]);

        $this->postJson('v1/admins',$this->attrs)
            ->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => $this->testUser->email]);

        $this->attrs['email'] = "newmail@mail.com";

        $this->assertDatabaseMissing('users', ['email' => 'newmail@mail.com']);

        $this->postJson('v1/admins',$this->attrs)
            ->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => $this->attrs['email']]);
    }
    /**
     *
     * @test
     */
    public function check_permission_is_exists_or_not()
    {
        $this->attrs['permissions'] = [Permission::MANAGE_USER,'Permission-Test'];

        $this->postJson('v1/admins',$this->attrs)
            ->assertJson([
                'admin'=>[
                    'name'=>$this->attrs['name'],
                    'email'=>$this->attrs['email'],
                    'profile'=>null,
                    'role'=>[
                        'admin'
                    ],
                    'permissions'=>[Permission::MANAGE_USER]
                ]
            ]);
    }
    /**
     *
     * @test
     */
    public function permissions_must_be_an_array()
    {
        $this->attrs['permissions'] = 'Permission-Test';

        $this->postJson('v1/admins',$this->attrs)
            ->assertJsonValidationErrors([
                'permissions'=>'The permissions must be an array.'
            ]);
    }

    /**
     *
     * @test
     */
    public function check_the_created_user_info_accurate()
    {
        $this->postJson('v1/admins',$this->attrs)
            ->assertStatus(201)
            ->assertJson([
                'admin'=>[
                    'name'=>$this->attrs['name'],
                    'email'=>$this->attrs['email'],
                    //'profile'=>null,
                    'role'=>[
                        'admin'
                    ],
                    'permissions'=>[Permission::MANAGE_ACADEMY,Permission::MANAGE_USER]
                ]
            ]);
    }

    /**
     * Test case for TEDFO-299
     * @test
     */
    public function only_can_super_admin_update_admin_permissions()
    {
        $this->user->removeRole(Role::SUPER_ADMIN);
        $this->user->assignRole(Role::ADMIN);
        $this->user->givePermissionTo(Permission::MANAGE_ADMIN);

        $this->user->refresh();

        $permissions = [Permission::MANAGE_USER,Permission::MANAGE_ACADEMY];

        $this->patchJson("v1/admins/{$this->testUser->id}",[
            'permissions' => $permissions
        ])
            ->assertJson(['message'=>'Only super admin can update admin']);


        $this->user->removeRole(Role::ADMIN);
        $this->user->assignRole(Role::SUPER_ADMIN);

        $this->user->refresh();

        $this->patchJson("v1/admins/{$this->testUser->id}",[
            'permissions' => $permissions
        ])
            ->assertJson([
                "admin"=> [
                    "name"=> $this->testUser->name,
                    "email"=> $this->testUser->email,
                    "role"=> [
                        "admin"
                    ],
                    "permissions"=> [
                        Permission::MANAGE_USER,Permission::MANAGE_ACADEMY
                    ]
                ]
            ]);

    }

    /**
     *
     * @test
     */
    public function permission_field_required_on_admin_permission_update()
    {
        $permissions = null;

        $this->patchJson("v1/admins/{$this->testUser->id}",[
            'permissions' => $permissions
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'permissions'=>'The permissions field is required.'
            ]);
    }
    /**
     *
     * @test
     */
    public function permission_field_must_be_array_on_admin_permissions_update()
    {
        $permissions ='permissions-1';

        $this->patchJson("v1/admins/{$this->testUser->id}",[
            'permissions' => $permissions
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'permissions'=>'The permissions must be an array.'
            ]);
    }

    /**
     *
     * @test
     */
    public function ignore_missing_permissions_from_database()
    {
        $permissions=[Permission::MANAGE_USER,Permission::MANAGE_ACADEMY,'permissions-1'];

        $this->patchJson("v1/admins/{$this->testUser->id}",[
            'permissions' => $permissions
        ])
            ->assertOk()
            ->assertJson([
                'admin'=>[
                    'permissions' => [Permission::MANAGE_USER,Permission::MANAGE_ACADEMY]
                ]
            ]);
    }
    /**
     *
     * @test
     */
    public function check_all_permissions_has_for_admin()
    {
        $this->testUser->givePermissionTo(Permission::MANAGE_USER);
        $this->testUser->givePermissionTo(Permission::MANAGE_SETTINGS);
        $this->testUser->givePermissionTo(Permission::MANAGE_BIZAID);

        $this->testUser->refresh();

        $this->assertContains(Permission::MANAGE_USER, $this->testUser->permissions->pluck('name'));
        $this->assertContains(Permission::MANAGE_SETTINGS, $this->testUser->permissions->pluck('name'));
        $this->assertContains(Permission::MANAGE_BIZAID, $this->testUser->permissions->pluck('name'));

        $permissions=[Permission::MANAGE_USER];

        $this->patchJson("v1/admins/{$this->testUser->id}",[
            'permissions' => $permissions
        ])
            ->assertOk()
            ->assertJson([
                'admin' => [
                    'permissions' => $permissions
                ]
            ]);

        $this->testUser->refresh();

        $this->assertContains(Permission::MANAGE_USER, $this->testUser->permissions->pluck('name'));
        $this->assertNotContains(Permission::MANAGE_SETTINGS, $this->testUser->permissions->pluck('name'));
        $this->assertNotContains(Permission::MANAGE_BIZAID, $this->testUser->permissions->pluck('name'));
    }

    /**
     *
     * @test
     */
    public function delete_admin_role_and_permissions()
    {
        $this->assertTrue($this->testUser->hasRole(Role::ADMIN));

        $this->testUser->givePermissionTo(Permission::MANAGE_USER);
        $this->testUser->givePermissionTo(Permission::MANAGE_SETTINGS);

        $this->testUser->refresh();

        $this->assertTrue($this->testUser->hasPermission(Permission::MANAGE_USER));
        $this->assertTrue($this->testUser->hasPermission(Permission::MANAGE_SETTINGS));

        $this->deleteJson("v1/admins/{$this->testUser->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'Admin removed successfully'
            ]);

        $this->testUser->refresh();

        $this->assertFalse($this->testUser->hasRole(Role::ADMIN));
        $this->assertFalse($this->testUser->hasPermission(Permission::MANAGE_USER));
        $this->assertFalse($this->testUser->hasPermission(Permission::MANAGE_SETTINGS));
    }


}
