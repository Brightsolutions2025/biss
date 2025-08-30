<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        // Generate a fake but unique ticket number (e.g., TCK-000001)
        $ticketNumber = 'TCK-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT);

        return [
            'ticket_number'    => $ticketNumber,

            // Ownership
            'company_id'       => Company::factory(),
            'department_id'    => Department::factory(),
            'team_id'          => Team::factory(),

            // Type
            'ticket_type_id'   => TicketType::factory(),

            // Info
            'subject'          => $this->faker->sentence(6),
            'description'      => $this->faker->paragraph(),

            // SLA
            'due_at'           => $this->faker->optional()->dateTimeBetween('now', '+1 week'),
            'resolved_at'      => null,

            // Priority / Status
            'status'           => $this->faker->randomElement([
                'open', 'pending_approval', 'approved',
                'in_progress', 'resolved', 'closed', 'rejected'
            ]),
            'priority'         => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),

            // Assignment
            'created_by'       => User::factory(),
            'assigned_to'      => null,
            'assigned_by'      => null,
            'assigned_at'      => null,

            // Approval
            'requires_approval'=> $this->faker->boolean(20),
            'approved_by'      => null,
            'approved_at'      => null,

            // Attachments
            'attachments'      => null,

            // Timestamps
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
    }

    /**
     * Indicate the ticket is already assigned.
     */
    public function assigned(User $assignee = null, User $assigner = null): self
    {
        return $this->state(function () use ($assignee, $assigner) {
            $assignee = $assignee ?? User::factory()->create();
            $assigner = $assigner ?? User::factory()->create();

            return [
                'assigned_to' => $assignee->id,
                'assigned_by' => $assigner->id,
                'assigned_at' => now(),
                'status'      => 'in_progress',
            ];
        });
    }

    /**
     * Indicate the ticket is approved.
     */
    public function approved(User $approver = null): self
    {
        return $this->state(function () use ($approver) {
            $approver = $approver ?? User::factory()->create();

            return [
                'status'      => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ];
        });
    }
}
