<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GideonCoreSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        /*
        |--------------------------------------------------------------------------
        | 1. STAGES – Full Science-of-Sales Flow
        |--------------------------------------------------------------------------
        */

        $stages = [
            [
                'name' => 'Setting the Stage',
                'code' => 'stage_setting_the_stage',
                'description' => 'Open the call, lower pressure, confirm time, agenda, and that yes or no are both okay.',
                'entry_conditions' => [],
                'success_criteria' => [
                    'time_confirmed' => true,
                    'agenda_confirmed' => true,
                    'yes_no_framed' => true,
                ],
            ],
            [
                'name' => 'Motivation & Urgency',
                'code' => 'stage_motivation_urgency',
                'description' => 'Uncover problems, impact, and picture-perfect future. Create an emotional and logical reason to change.',
                'entry_conditions' => [],
                'success_criteria' => [
                    'problem_found' => true,
                    'impact_explored' => true,
                    'future_picture_described' => true,
                ],
            ],
            [
                'name' => 'Deal Killers',
                'code' => 'stage_deal_killers',
                'description' => 'Surface time, money, risk, relationships, and other options that could quietly kill the deal.',
                'entry_conditions' => [],
                'success_criteria' => [
                    'time_discussed' => true,
                    'money_discussed' => true,
                    'risk_discussed' => true,
                    'relationships_discussed' => true,
                    'alternatives_discussed' => true,
                ],
            ],
            [
                'name' => 'Transition to Close',
                'code' => 'stage_transition_to_close',
                'description' => 'Confirm that your presentation covers what they need and that they’ll make a yes/no decision.',
                'entry_conditions' => [],
                'success_criteria' => [
                    'decision_timeframe_confirmed' => true,
                    'yes_no_commitment_made' => true,
                ],
            ],
            [
                'name' => 'Presentation',
                'code' => 'stage_presentation',
                'description' => 'Connect their pains and goals to your solution, addressing known deal killers.',
                'entry_conditions' => [],
                'success_criteria' => [
                    'pain_linked_to_solution' => true,
                    'deal_killers_addressed' => true,
                ],
            ],
            [
                'name' => 'Close',
                'code' => 'stage_close',
                'description' => 'Ask what they want to do and treat anything other than a clear yes as a no that must be understood.',
                'entry_conditions' => [],
                'success_criteria' => [
                    'clean_closing_question_asked' => true,
                    'decision_obtained' => true,
                ],
            ],
            [
                'name' => 'Stress Test',
                'code' => 'stage_stress_test',
                'description' => 'Lock in expectations, prepare them for doubts and outside opinions after saying yes.',
                'entry_conditions' => [],
                'success_criteria' => [
                    'decision_reinforced' => true,
                    'future_issues_anticipated' => true,
                ],
            ],
        ];

        foreach ($stages as $stage) {
            DB::table('gideon_stages')->updateOrInsert(
                ['code' => $stage['code']],
                [
                    'name' => $stage['name'],
                    'description' => $stage['description'],
                    'entry_conditions' => json_encode($stage['entry_conditions']),
                    'success_criteria' => json_encode($stage['success_criteria']),
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $stageIds = DB::table('gideon_stages')->pluck('id', 'code');

        /*
        |--------------------------------------------------------------------------
        | 2. TACTICS – Core Sales Tools
        |--------------------------------------------------------------------------
        */

        $tactics = [
            [
                'name' => 'Socratic Question',
                'code' => 'tactic_socratic_question',
                'description' => 'Answer with a clarifying question to uncover intent, impact, or missing context.',
                'example_uses' => [
                    'Prospect asks about price too early.',
                    'Prospect makes a vague or loaded statement.',
                ],
                'sparring_examples' => [
                    'When you say that, what exactly do you mean?',
                    'You’re telling me that for a reason—what’s behind it?',
                ],
                'coaching_notes' => 'Use this when you are not sure what they really mean or why they said something.',
            ],
            [
                'name' => 'Going Negative',
                'code' => 'tactic_going_negative',
                'description' => 'Lean slightly toward a no so they feel safe leaning toward the truth or a yes.',
                'example_uses' => [
                    'Call feels stalled or drifting toward a quiet no.',
                    'Prospect sounds neutral and non-committal.',
                ],
                'sparring_examples' => [
                    'It kind of sounds like this might not really be a fit for you right now, and that’s okay. Is that where your head is at?',
                ],
                'coaching_notes' => 'Anything other than a clear yes is basically a no. This tactic forces clarity without pushing.',
            ],
            [
                'name' => 'Takeaway',
                'code' => 'tactic_takeaway',
                'description' => 'Assume the answer is less positive than you want and let them correct you, protecting their ego.',
                'example_uses' => [
                    'Asking about money or sensitive topics.',
                    'When a direct question might create resistance.',
                ],
                'sparring_examples' => [
                    'I’m guessing you probably haven’t had a chance to think through what you’d actually be comfortable investing here.',
                ],
                'coaching_notes' => 'Use when a blunt question could make them defensive. You guess low so they feel safe correcting you.',
            ],
            [
                'name' => 'Instant Influence',
                'code' => 'tactic_instant_influence',
                'description' => 'Ask why they might change instead of why they are resisting, to awaken their internal reasons.',
                'example_uses' => [
                    'Prospect is lukewarm about moving forward.',
                    'They are not sharing why they would ever change.',
                ],
                'sparring_examples' => [
                    'If you ever did decide to change this, what do you think would be the main reason?',
                ],
                'coaching_notes' => 'You are not fighting their resistance—you are helping them find their own reasons.',
            ],
            [
                'name' => 'Empathy Reset (Okay / Not Okay)',
                'code' => 'tactic_empathy_ok_not_ok',
                'description' => 'You surface your own discomfort so they do not have to carry all the tension.',
                'example_uses' => [
                    'They sound tense, offended, or pulled back.',
                    'You feel the temperature spike during the call.',
                ],
                'sparring_examples' => [
                    'Can we pause for a second? I’m honestly a bit uncomfortable—I get the sense I pushed this the wrong way, and that’s on me.',
                ],
                'coaching_notes' => 'You go “not okay” first so they can relax. You name the tension instead of pretending it is not there.',
            ],
        ];

        foreach ($tactics as $tactic) {
            DB::table('gideon_tactics')->updateOrInsert(
                ['code' => $tactic['code']],
                [
                    'name' => $tactic['name'],
                    'description' => $tactic['description'],
                    'example_uses' => json_encode($tactic['example_uses']),
                    'sparring_examples' => json_encode($tactic['sparring_examples']),
                    'coaching_notes' => $tactic['coaching_notes'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. OBJECTION TYPES – Core Families
        |--------------------------------------------------------------------------
        */

        $objections = [
            [
                'name' => 'Money / Price',
                'code' => 'obj_money',
                'description' => 'Concerns about cost, affordability, or whether it is worth the price.',
                'common_phrases' => [
                    'That’s too expensive.',
                    'We do not have the budget.',
                    'We were hoping to spend less.',
                ],
                'recommended_tactic_codes' => [
                    'tactic_socratic_question',
                    'tactic_takeaway',
                ],
            ],
            [
                'name' => 'Think It Over / No Decision',
                'code' => 'obj_think_it_over',
                'description' => 'They delay making a decision with vague “later” or “maybe” language.',
                'common_phrases' => [
                    'I need to think about it.',
                    'Let me get back to you.',
                    'We will see.',
                ],
                'recommended_tactic_codes' => [
                    'tactic_going_negative',
                    'tactic_socratic_question',
                ],
            ],
            [
                'name' => 'Spouse / Third Party',
                'code' => 'obj_spouse',
                'description' => 'They claim someone else must approve before a decision can be made.',
                'common_phrases' => [
                    'I need to talk to my spouse.',
                    'I need to run this by my partner.',
                    'My boss has to sign off.',
                ],
                'recommended_tactic_codes' => [
                    'tactic_socratic_question',
                    'tactic_instant_influence',
                ],
            ],
            [
                'name' => 'Competition / Already Have Someone',
                'code' => 'obj_competition',
                'description' => 'They already work with someone or are strongly considering another option.',
                'common_phrases' => [
                    'We already have a guy.',
                    'We are happy with our current provider.',
                    'We are looking at a few other options.',
                ],
                'recommended_tactic_codes' => [
                    'tactic_socratic_question',
                    'tactic_going_negative',
                ],
            ],
            [
                'name' => 'No Urgency / Not a Priority',
                'code' => 'obj_no_urgency',
                'description' => 'They see the value but do not feel any pressure to act now.',
                'common_phrases' => [
                    'Let’s revisit this later.',
                    'This is not a priority right now.',
                    'Maybe down the road.',
                ],
                'recommended_tactic_codes' => [
                    'tactic_instant_influence',
                    'tactic_going_negative',
                ],
            ],
        ];

        foreach ($objections as $obj) {
            DB::table('gideon_objection_types')->updateOrInsert(
                ['code' => $obj['code']],
                [
                    'name' => $obj['name'],
                    'description' => $obj['description'],
                    'common_phrases' => json_encode($obj['common_phrases']),
                    'recommended_tactic_codes' => json_encode($obj['recommended_tactic_codes']),
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. LANGUAGE PATTERNS – Softeners, Clarifiers, Triggers
        |--------------------------------------------------------------------------
        */

        $patterns = [
            // Softeners
            [
                'type' => 'softener',
                'code' => 'softener_thats_fair',
                'pattern_text' => 'That’s a fair point.',
                'tags' => ['rapport'],
            ],
            [
                'type' => 'softener',
                'code' => 'softener_i_get_that',
                'pattern_text' => 'I get where you’re coming from.',
                'tags' => ['rapport'],
            ],
            // Clarifiers
            [
                'type' => 'clarifier',
                'code' => 'clarifier_what_do_you_mean',
                'pattern_text' => 'When you say that, what do you really mean?',
                'tags' => ['clarify', 'happy_ears'],
            ],
            [
                'type' => 'clarifier',
                'code' => 'clarifier_whats_making_you_hesitate',
                'pattern_text' => 'It sounds like you are not fully sure—what is making you hesitate?',
                'tags' => ['clarify'],
            ],
            [
                'type' => 'clarifier',
                'code' => 'clarifier_more_toward_yes_or_no',
                'pattern_text' => 'Right now, are you leaning more toward yes or more toward no?',
                'tags' => ['clarify', 'decision'],
            ],
            // Non-committal triggers
            [
                'type' => 'noncommittal_trigger',
                'code' => 'trigger_maybe',
                'pattern_text' => 'maybe',
                'tags' => ['happy_ears'],
            ],
            [
                'type' => 'noncommittal_trigger',
                'code' => 'trigger_i_think',
                'pattern_text' => 'I think',
                'tags' => ['happy_ears'],
            ],
            [
                'type' => 'noncommittal_trigger',
                'code' => 'trigger_probably',
                'pattern_text' => 'probably',
                'tags' => ['happy_ears'],
            ],
            [
                'type' => 'noncommittal_trigger',
                'code' => 'trigger_let_me_think',
                'pattern_text' => 'let me think about it',
                'tags' => ['happy_ears'],
            ],
        ];

        foreach ($patterns as $pat) {
            DB::table('gideon_language_patterns')->updateOrInsert(
                ['type' => $pat['type'], 'code' => $pat['code']],
                [
                    'pattern_text' => $pat['pattern_text'],
                    'tags' => json_encode($pat['tags']),
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. IF/THEN RULES – Key Behavioral Logic
        |--------------------------------------------------------------------------
        */

        $rules = [
            [
                'name' => 'Problem discovered → Ask impact question',
                'code' => 'rule_problem_to_impact',
                'if_conditions' => [
                    'event' => 'prospect_expresses_problem',
                    'stage_code' => 'stage_motivation_urgency',
                ],
                'then_actions' => [
                    'tactic_code' => 'tactic_socratic_question',
                    'goal' => 'deepen_impact',
                    'sparring_prompt_templates' => [
                        'When you say that is an issue, what does it actually look like day to day for you?',
                        'How is that situation affecting you personally right now?',
                    ],
                ],
                'coaching_explanation' => 'Any time you hear a problem, your next move is to explore how it hits them emotionally and practically.',
                'priority' => 10,
            ],
            [
                'name' => 'Non-committal close → Going negative',
                'code' => 'rule_noncommittal_to_going_negative',
                'if_conditions' => [
                    'event' => 'prospect_gives_noncommittal_answer',
                    'stage_code' => 'stage_close',
                    'signals' => ['noncommittal_language' => true],
                ],
                'then_actions' => [
                    'tactic_code' => 'tactic_going_negative',
                    'goal' => 'force_clarity',
                    'sparring_prompt_templates' => [
                        'It kind of sounds like, if we are being honest, this might really be a no for you right now, and that is okay—should we call it that?',
                    ],
                ],
                'coaching_explanation' => 'Anything other than a clear yes is treated as a no so the real objection can surface.',
                'priority' => 20,
            ],
            [
                'name' => 'Early price question → Socratic question + takeaway',
                'code' => 'rule_early_price_question',
                'if_conditions' => [
                    'event' => 'prospect_asks_price_early',
                    'stage_code' => 'stage_motivation_urgency',
                ],
                'then_actions' => [
                    'tactic_code' => 'tactic_socratic_question',
                    'goal' => 'clarify_price_concern',
                    'sparring_prompt_templates' => [
                        'That is a fair question. Before we talk exact numbers, what are you hoping this will actually do for you?',
                    ],
                ],
                'coaching_explanation' => 'Do not quote numbers until you understand what problem you are solving for them.',
                'priority' => 30,
            ],
        ];

        foreach ($rules as $rule) {
            DB::table('gideon_if_then_rules')->updateOrInsert(
                ['code' => $rule['code']],
                [
                    'name' => $rule['name'],
                    'if_conditions' => json_encode($rule['if_conditions']),
                    'then_actions' => json_encode($rule['then_actions']),
                    'coaching_explanation' => $rule['coaching_explanation'],
                    'is_active' => true,
                    'priority' => $rule['priority'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 6. SCENARIOS – 5 Core V1 Sparring Scenarios
        |--------------------------------------------------------------------------
        */

        $scenarioData = [
            [
                'name' => 'Price Objection – Too Expensive',
                'code' => 'scenario_price_too_expensive',
                'product_type' => 'generic_insurance',
                'starting_stage_code' => 'stage_close',
                'description' => 'Prospect likes the idea but pushes back hard on price at the close.',
                'prospect_profile' => [
                    'persona' => 'cost_sensitive',
                    'motivation_level' => 'medium',
                    'money_sensitivity' => 'high',
                    'relationship_influence' => 'medium',
                ],
                'script_engine' => [
                    'objection_type_code' => 'obj_money',
                    'opening_line' => 'This all sounds good, but honestly, that price is higher than I expected.',
                    'behavior' => [
                        'will_negotiate' => true,
                        'will_concede_if_value_clearly_shown' => true,
                        'becomes_defensive_if_pushed' => true,
                    ],
                    'ideal_agent_behaviors' => [
                        'do_not_defend_price immediately',
                        'ask impact and value questions',
                        'link value to earlier pain',
                        'use takeaway gently if needed',
                    ],
                ],
            ],
            [
                'name' => 'Think It Over – Soft No',
                'code' => 'scenario_think_it_over',
                'product_type' => 'generic_insurance',
                'starting_stage_code' => 'stage_close',
                'description' => 'Prospect appears positive but gives a vague “I need to think about it” at the end.',
                'prospect_profile' => [
                    'persona' => 'conflict_avoidant',
                    'motivation_level' => 'medium',
                    'money_sensitivity' => 'medium',
                    'relationship_influence' => 'medium',
                ],
                'script_engine' => [
                    'objection_type_code' => 'obj_think_it_over',
                    'opening_line' => 'Yeah, this all sounds pretty good. I just want to think about it for a bit.',
                    'behavior' => [
                        'avoids_direct_no' => true,
                        'uses_noncommittal_language' => true,
                        'will_share_real_objection_if_safe' => true,
                    ],
                    'ideal_agent_behaviors' => [
                        'spot_noncommittal_language',
                        'treat_maybe_as_no',
                        'use going_negative or clarifier',
                        'surface_real_objection',
                    ],
                ],
            ],
            [
                'name' => 'Spouse Objection – Needs to Talk to Partner',
                'code' => 'scenario_spouse_objection',
                'product_type' => 'generic_insurance',
                'starting_stage_code' => 'stage_close',
                'description' => 'Prospect says they must speak with a spouse or partner before deciding.',
                'prospect_profile' => [
                    'persona' => 'collaborative_decider',
                    'motivation_level' => 'medium_high',
                    'money_sensitivity' => 'medium',
                    'relationship_influence' => 'high',
                ],
                'script_engine' => [
                    'objection_type_code' => 'obj_spouse',
                    'opening_line' => 'I like this, but I really need to talk to my spouse before we do anything.',
                    'behavior' => [
                        'will_not_commit_without_partner' => true,
                        'open_to_help_structuring_spouse_conversation' => true,
                    ],
                    'ideal_agent_behaviors' => [
                        'clarify_if_spouse_is_true_decision_maker',
                        'explore_spouses_likely_concerns',
                        'set_clear_next_step_with_spouse_involved',
                    ],
                ],
            ],
            [
                'name' => 'Competition – Already Have Someone',
                'code' => 'scenario_competition_already_have_someone',
                'product_type' => 'generic_insurance',
                'starting_stage_code' => 'stage_motivation_urgency',
                'description' => 'Prospect already has a provider and is somewhat loyal, but curious.',
                'prospect_profile' => [
                    'persona' => 'loyal_but_open',
                    'motivation_level' => 'low_medium',
                    'money_sensitivity' => 'medium',
                    'relationship_influence' => 'high',
                ],
                'script_engine' => [
                    'objection_type_code' => 'obj_competition',
                    'opening_line' => 'We actually already have someone we work with for this.',
                    'behavior' => [
                        'loyal_to_current_provider' => true,
                        'will_switch_if_clear_benefit' => true,
                    ],
                    'ideal_agent_behaviors' => [
                        'use questions to uncover gaps in current setup',
                        'avoid trashing competitor',
                        'highlight specific advantages tied to their pain',
                    ],
                ],
            ],
            [
                'name' => 'No Urgency – Maybe Later',
                'code' => 'scenario_no_urgency_maybe_later',
                'product_type' => 'generic_insurance',
                'starting_stage_code' => 'stage_motivation_urgency',
                'description' => 'Prospect sees some value but feels no urgency to act now.',
                'prospect_profile' => [
                    'persona' => 'easygoing_delayer',
                    'motivation_level' => 'low',
                    'money_sensitivity' => 'medium',
                    'relationship_influence' => 'low',
                ],
                'script_engine' => [
                    'objection_type_code' => 'obj_no_urgency',
                    'opening_line' => 'This all makes sense, but it is not really urgent. Let’s circle back later.',
                    'behavior' => [
                        'does_not_feel_immediate_pain' => true,
                        'will_move_if_future_risk_is_made_real' => true,
                    ],
                    'ideal_agent_behaviors' => [
                        'revisit consequences_of_waiting',
                        'use instant_influence style questions',
                        'help_them_picture_future_if_nothing_changes',
                    ],
                ],
            ],
        ];

        foreach ($scenarioData as $scenario) {
            $startingStageId = $stageIds[$scenario['starting_stage_code']] ?? null;

            DB::table('gideon_scenarios')->updateOrInsert(
                ['code' => $scenario['code']],
                [
                    'name' => $scenario['name'],
                    'product_type' => $scenario['product_type'],
                    'starting_stage_id' => $startingStageId,
                    'description' => $scenario['description'],
                    'prospect_profile' => json_encode($scenario['prospect_profile']),
                    'script_engine' => json_encode($scenario['script_engine']),
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
