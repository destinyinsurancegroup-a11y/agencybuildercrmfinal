<?php

namespace App\Enums\Gideon;

enum MiniCloseStatus: string
{
    case EARNED = 'earned';
    case ASSUMED = 'assumed';
    case MISSED = 'missed';
    case DISQUALIFIED = 'disqualified';
    case FAILED = 'failed'; // used when Intro gate fails or Close fails
    case EXIT = 'exit';     // ethical non-close
}
