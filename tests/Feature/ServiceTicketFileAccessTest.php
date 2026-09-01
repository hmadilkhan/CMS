<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketFile;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A ticket attachment is deleted by id, off disk and out of the database. These
 * tests keep that to the people the ticket belongs to — the same set addComment
 * already checks for.
 */
class ServiceTicketFileAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'Employee'): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $user->syncRoles([Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])]);

        return $user->fresh();
    }

    private function ticketFile(User $creator, ?User $uploader = null): ServiceTicketFile
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sales_partner_id' => SalesPartner::create(['name' => 'Ticket Partner'])->id,
        ]);
        $project = Project::create([
            'project_name' => 'Ticket Project',
            'code' => 'P-'.random_int(1000, 9999),
            'customer_id' => $customer->id,
            'department_id' => 1,
        ]);

        $ticket = ServiceTicket::create([
            'project_id' => $project->id,
            'user_id' => $creator->id,
            'subject' => 'Broken inverter',
            'priority' => 'High',
            'status' => 'Pending',
        ]);

        Storage::disk('public')->put('service-tickets/evidence.txt', 'photo');

        return ServiceTicketFile::create([
            'service_ticket_id' => $ticket->id,
            'file_name' => 'evidence.txt',
            'file_path' => 'service-tickets/evidence.txt',
            'file_type' => 'text/plain',
            'file_size' => 5,
            'uploaded_by' => ($uploader ?? $creator)->id,
        ]);
    }

    public function test_an_unrelated_user_cannot_delete_a_ticket_attachment(): void
    {
        $file = $this->ticketFile($this->user());

        $this->actingAs($this->user())
            ->delete(route('service-tickets.files.delete', $file->id))
            ->assertForbidden();

        $this->assertNotNull(ServiceTicketFile::find($file->id));
        Storage::disk('public')->assertExists('service-tickets/evidence.txt');
    }

    public function test_the_person_who_raised_the_ticket_can_delete_its_attachment(): void
    {
        $creator = $this->user();
        $file = $this->ticketFile($creator);

        $this->actingAs($creator)
            ->delete(route('service-tickets.files.delete', $file->id))
            ->assertRedirect();

        $this->assertNull(ServiceTicketFile::find($file->id));
    }

    public function test_whoever_uploaded_the_file_can_delete_it(): void
    {
        $uploader = $this->user();
        $file = $this->ticketFile($this->user(), $uploader);

        $this->actingAs($uploader)
            ->delete(route('service-tickets.files.delete', $file->id))
            ->assertRedirect();

        $this->assertNull(ServiceTicketFile::find($file->id));
    }

    /**
     * Resolving a ticket is the assigned Service Manager's act — the views only
     * offer the control to them, and only send the request for them.
     */
    public function test_only_the_assigned_service_manager_can_resolve_a_ticket(): void
    {
        // Resolving notifies the creator; this test is about who may resolve.
        Notification::fake();

        $creator = $this->user();
        $manager = $this->user('Service Manager');
        $ticket = $this->ticketFile($creator)->ticket;
        $ticket->update(['assigned_to' => $manager->id]);

        // The person who raised it cannot close it.
        $this->actingAs($creator)
            ->put(route('service-tickets.update', $ticket->id), ['status' => 'Resolved'])
            ->assertForbidden();

        // Nor can a Service Manager it is not assigned to.
        $this->actingAs($this->user('Service Manager'))
            ->put(route('service-tickets.update', $ticket->id), ['status' => 'Resolved'])
            ->assertForbidden();

        $this->assertSame('Pending', $ticket->refresh()->status);

        // The assigned one can.
        $this->actingAs($manager)
            ->put(route('service-tickets.update', $ticket->id), ['status' => 'Resolved'])
            ->assertRedirect();

        $this->assertSame('Resolved', $ticket->refresh()->status);
    }

    public function test_an_unrelated_user_cannot_open_a_ticket(): void
    {
        $ticket = $this->ticketFile($this->user())->ticket;

        $this->actingAs($this->user())
            ->get(route('service-tickets.details', $ticket->id))
            ->assertForbidden();

        $this->actingAs($this->user())
            ->get(route('service-tickets.admin-details', $ticket->id))
            ->assertForbidden();
    }

    public function test_the_creator_the_assignee_a_service_manager_and_a_super_admin_can_open_a_ticket(): void
    {
        $creator = $this->user();
        $assignee = $this->user();
        $ticket = $this->ticketFile($creator)->ticket;
        $ticket->update(['assigned_to' => $assignee->id]);

        foreach ([$creator, $assignee, $this->user('Service Manager'), $this->user('Super Admin')] as $viewer) {
            $this->actingAs($viewer)
                ->get(route('service-tickets.details', $ticket->id))
                ->assertOk();
        }
    }

    public function test_the_all_tickets_dashboard_is_not_open_to_everyone(): void
    {
        $this->actingAs($this->user())
            ->get(route('service.admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->user('Service Manager'))
            ->get(route('service.admin.dashboard'))
            ->assertOk();
    }
}
