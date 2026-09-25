<?php

namespace App\Enums;

enum UnlockPolicy: string
{
    use HasValues;

    /** Locked content can be opened and completed; the UI only warns. */
    case Advisory = 'ADVISORY';

    /** Progress actions on locked content are rejected. */
    case Strict = 'STRICT';
}
