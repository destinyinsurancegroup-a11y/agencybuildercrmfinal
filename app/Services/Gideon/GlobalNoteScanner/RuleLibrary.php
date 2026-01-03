<?php

namespace App\Services\Gideon\GlobalNoteScanner;

class RuleLibrary
{
    /**
     * Each rule tells Gideon:
     * - what phrase to look for
     * - how important it is (precedenceRank)
     * - what message to show the agent
     */
    public static function rules(): array
    {
        return [

            // =========================
            // SAVE OPPORTUNITIES (highest priority)
            // =========================
            [
                'ruleCode' => 'SAVE-S1',
                'ruleGroup' => 'save',
                'precedenceRank' => 10,
                'requiresDueAt' => false,
                'patterns' => [
                    '/\blapsed policy\b/',
                    '/\breinstate\b/',
                    '/\bback on the book\b/',
                    '/\bpayment missed\b/',
                ],
                'title' => 'Reinstate coverage opportunity',
                'why'   => 'This prevents an immediate loss of business.',
                'next'  => 'Contact the client and reinstate the policy.',
            ],

            // =========================
            // FOLLOW-UP DUE
            // =========================
            [
                'ruleCode' => 'FUP-F1',
                'ruleGroup' => 'followup',
                'precedenceRank' => 20,
                'requiresDueAt' => true,
                'patterns' => [
                    '/\bcall tomorrow\b/',
                    '/\bnext week\b/',
                    '/\bin \d+ weeks?\b/',
                    '/\bfollow up\b/',
                    '/\bcall back\b/',
                ],
                'title' => 'Follow-up is due',
                'why'   => 'Missed follow-ups lose trust and deals.',
                'next'  => 'Reach out now and schedule the follow-up.',
            ],

            // =========================
            // QUOTE / INFO REQUESTED
            // =========================
            [
                'ruleCode' => 'QUO-Q1',
                'ruleGroup' => 'quote',
                'precedenceRank' => 30,
                'requiresDueAt' => false,
                'patterns' => [
                    '/\bneeds quote\b/',
                    '/\basked for pricing\b/',
                    '/\bsend (the )?app\b/',
                    '/\bneeds bank draft\b/',
                ],
                'title' => 'Client requested information',
                'why'   => 'Unfulfilled requests stall buying momentum.',
                'next'  => 'Send the requested information and follow up.',
            ],

            // =========================
            // QUOTE SENT — NO FOLLOW-UP
            // =========================
            [
                'ruleCode' => 'QUO-Q2',
                'ruleGroup' => 'quote',
                'precedenceRank' => 35,
                'requiresDueAt' => false,
                'patterns' => [
                    '/\bquote sent\b/',
                    '/\bpricing sent\b/',
                    '/\bproposal sent\b/',
                ],
                'title' => 'Quote sent with no follow-up',
                'why'   => 'Most sales close on follow-up, not the quote.',
                'next'  => 'Call to review the quote and move forward.',
            ],

            // =========================
            // COVERAGE EXPANSION
            // =========================
            [
                'ruleCode' => 'XPN-X1',
                'ruleGroup' => 'expansion',
                'precedenceRank' => 40,
                'requiresDueAt' => false,
                'patterns' => [
                    '/\bget (my )?(husband|wife) covered\b/',
                    '/\badd (spouse|daughter|son|child)\b/',
                ],
                'title' => 'Coverage expansion opportunity',
                'why'   => 'Client already expressed interest in more coverage.',
                'next'  => 'Call and discuss adding coverage.',
            ],

            // =========================
            // REFERRAL SOURCE
            // =========================
            [
                'ruleCode' => 'REF-R1',
                'ruleGroup' => 'referral',
                'precedenceRank' => 50,
                'requiresDueAt' => false,
                'patterns' => [
                    '/\bp&c\b/',
                    '/\bproperty and casualty\b/',
                    '/\bdenial leads\b/',
                ],
                'title' => 'Referral source follow-up',
                'why'   => 'Warm referral sources lose momentum if ignored.',
                'next'  => 'Follow up and secure referral leads.',
            ],
        ];
    }

    /**
     * If any of these appear, Gideon shuts up.
     */
    public static function hardExclusions(): array
    {
        return [
            '/\bnot interested\b/',
            '/\bdo not call\b/',
            '/\bwrong number\b/',
            '/\bduplicate\b/',
            '/\bdeceased\b/',
        ];
    }
}
