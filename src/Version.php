<?php

declare(strict_types=1);

namespace VoiceTel\Sdk;

/**
 * Version & default constants for the VoiceTel PHP SDK.
 */
final class Version
{
    /** SDK release version. Bumped in lock-step with the API spec. */
    public const SDK_VERSION = '2.2.10';

    /** Default API endpoint. */
    public const DEFAULT_BASE_URL = 'https://api.voicetel.com';

    /** Default User-Agent sent on every request. */
    public const DEFAULT_USER_AGENT = 'voicetel-php-sdk/' . self::SDK_VERSION;
}
