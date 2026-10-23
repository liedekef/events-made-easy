<?php

namespace SumUp;

/**
 * Provides metadata about the SDK.
 */
class SdkInfo
{
    const USER_AGENT_TEMPLATE = 'sumup-php/v%s';

    /**
     * Returns the SDK version embedded in the build.
     *
     * @return string
     */
    public static function getVersion()
    {
        return Version::CURRENT;
    }

    /**
     * Returns the formatted User-Agent string used in outbound requests.
     *
     * @return string
     */
    public static function getUserAgent()
    {
        return sprintf(self::USER_AGENT_TEMPLATE, self::getVersion());
    }

    /**
     * Returns the standard runtime headers used in outbound requests.
     *
     * @return array<string, string>
     */
    public static function getRuntimeHeaders()
    {
        static $headers = null;

        if ($headers === null) {
            $headers = [
                'X-Sumup-Api-Version' => ApiVersion::CURRENT,
                'X-Sumup-Lang' => 'php',
                'X-Sumup-Package-Version' => self::getVersion(),
                'X-Sumup-Os' => self::getOsName(),
                'X-Sumup-Arch' => self::getArch(),
                'X-Sumup-Runtime' => 'php',
                'X-Sumup-Runtime-Version' => PHP_VERSION,
            ];
        }

        return $headers;
    }

    /**
     * @return string
     */
    private static function getOsName()
    {
        $osRaw = php_uname('s');
        $os = strtolower($osRaw);
        if (strpos($os, 'win') === 0) {
            return 'windows';
        }
        if (strpos($os, 'linux') === 0) {
            return 'linux';
        }
        if (strpos($os, 'darwin') === 0) {
            return 'darwin';
        }
        return $os ? $os : 'unknown';
    }

    /**
     * @return string
     */
    private static function getArch()
    {
        $archRaw = php_uname('m');
        $arch = strtolower($archRaw);
        $map = [
            'x86_64' => 'x86_64',
            'x64' => 'x86_64',
            'amd64' => 'x86_64',
            'x86' => 'x86',
            'i386' => 'x86',
            'i686' => 'x86',
            'ia32' => 'x86',
            'x32' => 'x86',
            'aarch64' => 'arm64',
            'arm64' => 'arm64',
            'arm' => 'arm',
        ];
        if (isset($map[$arch])) {
            return $map[$arch];
        }
        return $arch ? $arch : 'unknown';
    }
}
