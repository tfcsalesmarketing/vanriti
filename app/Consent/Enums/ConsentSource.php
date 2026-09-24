<?php

namespace App\Consent\Enums;

/**
 * Controlled vocabulary for how a consent decision was made.
 *
 * The ConsentService only accepts these values; arbitrary strings from the
 * browser must be mapped onto the enum before being persisted.
 */
enum ConsentSource: string
{
    case Banner = 'banner';

    case Settings = 'settings';

    case Migration = 'migration';

    case System = 'system';

    case Api = 'api';

    case CarriedFromAnonymous = 'carried_from_anonymous';
}
