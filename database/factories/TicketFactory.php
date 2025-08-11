<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketFactory extends Factory
{
    use HasFactory;

    protected $model = Ticket::class;

    public function definition(): array
    {
        static $ticketCounter = 1;
        $ticketNumber = 'TCK-' . str_pad($ticketCounter++, 6, '0', STR_PAD_LEFT);

        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $team = Team::factory()->create(['company_id' => $company->id]);
        $ticketType = TicketType::factory()->create(['company_id' => $company->id]);
        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        return [
            'ticket_number'     => $ticketNumber,
            'company_id'        => $company->id,
            'department_id'     => $department->id,
            'team_id'           => $team->id,
            'ticket_type_id'    => $ticketType->id,
            'subject'           => $this->faker->sentence(6),
            'description'       => $this->faker->paragraph(),
            'due_at'            => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'resolved_at'       => null,
            'status'            => $this->faker->randomElement([
                'open', 'pending_approval', 'approved', 
                'in_progress', 'resolved', 'closed', 'rejected'
            ]),
            'priority'          => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'created_by'        => $creator->id,
            'assigned_to'       => $assignee->id,
            'requires_approval' => $this->faker->boolean(30),
            'approved_by'       => null,
            'approved_at'       => null,
            'attachments'       => json_encode(
                $this->faker->optional()->randomElements(
                    ['file1.pdf', 'image.png', 'doc.docx'],
                    rand(1, 2)
                )
            ),
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }
}
