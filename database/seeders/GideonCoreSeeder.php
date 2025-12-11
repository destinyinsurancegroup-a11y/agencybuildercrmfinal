<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GideonCoreSeeder extends Seeder
{
    public function run(): void
    {
        // minimal stage + tactic + pattern examples
        DB::table('gideon_stages')->insertOrIgnore([
            [
                'name' => 'Setting the Stage',
                'code' => 'stage_setting_the_stage',
                'description' => 'Define time, agenda, and a yes/no outcome to lower pressure.',
                'entry_conditions' => json_encode([]),
                'success_criteria' => json_encode([
                    'time_confirmed' => true,
                    'agenda_confirmed' => true,
                    'yes_no_framed' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Motivation & Urgency',
                'code' => 'stage_motivation_urgency',
                'description' => 'Uncover problems, impact, and picture-perfect future.',
                'entry_conditions' => json_encode([]),
                'success_criteria' => json_encode([
                    'problem_found' => true,
                    'impact_explored' => true,
                    'future_picture_described' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('gideon_tactics')->insertOrIgnore([
            [
                'name' => 'Socratic Question',
                'code' => 'tactic_socratic_question',
                'description' => 'Answer with a clarifying question to uncover intent and impact.',
                'example_uses' => json_encode([
                    'Prospect asks an early price question.',
                    'Prospect makes a vague or loaded statement.',
                ]),
                'sparring_examples' => json_encode([
                    'When you say that, what exactly do you mean?',
                    'You’re telling me that for a reason—what’s behind it?',
                ]),
                'coaching_notes' => 'Use when you don’t fully understand why they said something.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('gideon_language_patterns')->insertOrIgnore([
            [
                'type' => 'softener',
                'code' => 'softener_thats_fair',
                'pattern_text' => 'That’s a fair point.',
                'tags' => json_encode(['rapport']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'clarifier',
                'code' => 'clarifier_what_do_you_mean',
                'pattern_text' => 'When you say that, what do you really mean?',
                'tags' => json_encode(['clarify', 'happy_ears']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'noncommittal_trigger',
                'code' => 'trigger_maybe',
                'pattern_text' => 'maybe',
                'tags' => json_encode(['happy_ears']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
