<?php

namespace App\Enums\Gideon;

enum TrainingMode: string
{
    case STAGES            = 'stages';
    case DISCOVERY_START   = 'discovery_start';
    case FULL_PRESENTATION = 'full_presentation';

    /**
     * Safe normalization helper.
     * Accepts:
     * - TrainingMode enum
     * - string (valid value)
     * - null (defaults)
     */
    public static function normalize(self|string|null $mode, self $default = self::DISCOVERY_START): self
    {
        if ($mode instanceof self) {
            return $mode;
        }

        if (is_string($mode)) {
            return self::tryFrom($mode) ?? $default;
        }

        return $default;
    }
}
