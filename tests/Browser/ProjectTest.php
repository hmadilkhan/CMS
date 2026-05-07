<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Project;
use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProjectTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_can_view_projects_list()
    {
        $user = User::factory()->create();
        Project::factory()->count(3)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/projects')
                    ->assertSee('Projects');
        });
    }

    public function test_can_create_project()
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $customer) {
            $browser->loginAs($user)
                    ->visit('/projects/create')
                    ->type('name', 'New Solar Project')
                    ->select('customer_id', $customer->id)
                    ->press('Save')
                    ->waitForLocation('/projects')
                    ->assertSee('New Solar Project');
        });
    }

    public function test_can_edit_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Old Project']);

        $this->browse(function (Browser $browser) use ($user, $project) {
            $browser->loginAs($user)
                    ->visit("/projects/{$project->id}/edit")
                    ->clear('name')
                    ->type('name', 'Updated Project')
                    ->press('Update')
                    ->waitForLocation('/projects')
                    ->assertSee('Updated Project');
        });
    }

    public function test_can_track_project_publicly()
    {
        $project = Project::factory()->create([
            'tracking_code' => 'TEST123'
        ]);

        $this->browse(function (Browser $browser) use ($project) {
            $browser->visit('/track-your-project')
                    ->type('code', 'TEST123')
                    ->type('email', $project->customer->email)
                    ->press('Track')
                    ->pause(2000)
                    ->assertSee('Project Status');
        });
    }

    public function test_can_move_project_between_departments()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->browse(function (Browser $browser) use ($user, $project) {
            $browser->loginAs($user)
                    ->visit('/projects')
                    ->select('department', '2')
                    ->press('Move')
                    ->pause(1000)
                    ->assertSee('moved successfully');
        });
    }
}
