<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Categories
        $categories = [
            [
                'name' => 'Hardware Support',
                'description' => 'Issues related to physical devices like laptops, monitors, keyboard, mouse, and desktop PCs.',
            ],
            [
                'name' => 'Software Support',
                'description' => 'Issues related to applications, operating systems, software installations, and license requests.',
            ],
            [
                'name' => 'Network & Internet',
                'description' => 'Issues with Wi-Fi, ethernet connection, VPN, internet speed, and IP phone connectivity.',
            ],
            [
                'name' => 'Access & Accounts',
                'description' => 'Issues regarding account lockouts, password resets, active directory permissions, and email access.',
            ],
            [
                'name' => 'General Inquiry',
                'description' => 'General questions, IT feedback, or miscellaneous IT requests.',
            ],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[] = Category::create($cat);
        }

        // 2. Seed Users
        // Admin
        $admin = User::create([
            'name' => 'IT Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Agents
        $agent1 = User::create([
            'name' => 'John Doe (IT Analyst)',
            'email' => 'agent1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
        ]);

        $agent2 = User::create([
            'name' => 'Jane Smith (Network Specialist)',
            'email' => 'agent2@example.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
        ]);

        $agents = [$agent1, $agent2];

        // Regular Users (Clients)
        $users = [];
        $userNames = [
            'Alice Johnson',
            'Bob Miller',
            'Charlie Davis',
            'Diana Evans',
            'Ethan Wilson',
        ];

        foreach ($userNames as $index => $name) {
            $users[] = User::create([
                'name' => $name,
                'email' => 'user' . ($index + 1) . '@example.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]);
        }

        // 3. Seed Tickets and Comments
        $ticketData = [
            [
                'title' => 'Laptop screen keeps flickering',
                'description' => 'My laptop screen has started flickering black every few seconds. It happens both on battery and when plugged in. I tried restarting but it did not help.',
                'category_index' => 0, // Hardware Support
                'user_index' => 0, // Alice (user1@example.com)
                'agent_index' => 0, // John Doe
                'status' => 'in_progress',
                'priority' => 'high',
                'comments' => [
                    [
                        'email' => 'user1@example.com',
                        'body' => 'I noticed it gets worse when I move the laptop hinge. Maybe it is a loose cable?',
                    ],
                    [
                        'email' => 'agent1@example.com',
                        'body' => 'Hi Alice, yes, that sounds like it could be a hardware connection issue. Could you bring the laptop over to the IT helpdesk in Room 302? I will take a quick look and run a diagnostics test.',
                    ],
                ]
            ],
            [
                'title' => 'Cannot connect to VPN from home',
                'description' => 'I am trying to connect to the corporate VPN using Cisco AnyConnect from home, but it keeps giving me a "Connection timed out" error. My home Wi-Fi is working fine.',
                'category_index' => 2, // Network & Internet
                'user_index' => 1, // Bob (user2@example.com)
                'agent_index' => 1, // Jane Smith
                'status' => 'open',
                'priority' => 'urgent',
                'comments' => [
                    [
                        'email' => 'agent2@example.com',
                        'body' => 'Hi Bob, please verify that you are connecting to the correct VPN gateway URL (vpn.company.com). Also, let me know if you have updated your Cisco client recently.',
                    ]
                ]
            ],
            [
                'title' => 'Requesting Microsoft Visio license',
                'description' => 'I need a Microsoft Visio license for mapping out some flowcharts for our upcoming project. My manager has approved this request.',
                'category_index' => 1, // Software Support
                'user_index' => 2, // Charlie (user3@example.com)
                'agent_index' => null,
                'status' => 'open',
                'priority' => 'low',
                'comments' => []
            ],
            [
                'title' => 'Account locked out after password change',
                'description' => 'I changed my domain password this morning, and now my account is locked out. I cannot log into my workstation or email.',
                'category_index' => 3, // Access & Accounts
                'user_index' => 3, // Diana (user4@example.com)
                'agent_index' => 0, // John Doe
                'status' => 'resolved',
                'priority' => 'high',
                'comments' => [
                    [
                        'email' => 'agent1@example.com',
                        'body' => 'Hi Diana, I checked the Active Directory logs and unlocked your account. You can now try logging in again with your new password.',
                    ],
                    [
                        'email' => 'user4@example.com',
                        'body' => 'Thank you John! I am able to log in successfully now.',
                    ]
                ]
            ],
            [
                'title' => 'Slow office Wi-Fi in building B cafeteria',
                'description' => 'The Wi-Fi in the building B cafeteria is extremely slow. Doing basic web browsing takes a long time, and I cannot attend online calls from there.',
                'category_index' => 2, // Network & Internet
                'user_index' => 4, // Ethan (user5@example.com)
                'agent_index' => null,
                'status' => 'open',
                'priority' => 'medium',
                'comments' => []
            ],
        ];

        foreach ($ticketData as $idx => $t) {
            $ticketNum = 'TIC-' . date('Ymd') . '-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT);
            
            $assignedAgentId = ($t['agent_index'] !== null) ? $agents[$t['agent_index']]->id : null;
            
            $ticket = Ticket::create([
                'ticket_number' => $ticketNum,
                'user_id' => $users[$t['user_index']]->id,
                'agent_id' => $assignedAgentId,
                'category_id' => $categoryModels[$t['category_index']]->id,
                'title' => $t['title'],
                'description' => $t['description'],
                'status' => $t['status'],
                'priority' => $t['priority'],
            ]);

            foreach ($t['comments'] as $c) {
                // Find user by email
                $commentUser = User::where('email', $c['email'])->first();
                
                if ($commentUser) {
                    Comment::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $commentUser->id,
                        'body' => $c['body'],
                    ]);
                }
            }
        }
    }
}
