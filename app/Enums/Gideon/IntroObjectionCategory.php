<?php

namespace App\Enums\Gideon;

enum IntroObjectionCategory: string
{
    case TIME_DEFENSE = 'time_defense';
    case LEGITIMACY_CHALLENGE = 'legitimacy_challenge';
    case CONTROL_ATTEMPT = 'control_attempt';
    case SKEPTICISM_DEFENSE = 'skepticism_defense';
    case COMMITMENT_DISSONANCE = 'commitment_dissonance';
    case HARD_REJECTION = 'hard_rejection';
    case NONE = 'none';
}
