<?php
/**
 * Escaper.
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Util;

use Exception;

/**
 * Escaping helper class to fix shortcomings of the PHP escapeshellarg/escapeshellcmd functions.
 */
class Escaper
{
    /**
     *
     * @var string|true The best detected UTF-8 locale, or true if there was already a UTF-8 locale set.
     */
    private $locale = '';

    /**
     *
     * @param string[] $utf8Locales UTF-8 aware locales which should be used, in order of preference.
     *
     * @throws Exception if no
     */
    public function __construct(array $utf8Locales = ['de_AT.UTF-8', 'de_DE.UTF-8', 'en_US.UTF-8', 'C.UTF-8'])
    {
        $this->initializeUtf8Locale($utf8Locales);
    }

    /**
     * Escapes a shell argument.
     *
     * Wrapper around the PHP escapeshellarg() function which uses the correct locale for UTF-8 support.
     *
     * If the current locale does not support UTF-8, it switches to a supported UTF-8 locale
     * and back to the original one after the escape operation.
     */
    public function escapeshellarg(string $arg): string
    {
        $previousLocale = null;

        if (true !== $this->locale) {
            $previousLocale = setlocale(LC_CTYPE, 0);
            setlocale(LC_CTYPE, $this->locale);
        }

        $result = escapeshellarg($arg);

        if (true !== $this->locale) {
            setlocale(LC_CTYPE, $previousLocale);
        }

        return $result;
    }

    /**
     * Escapes a shell command, see Escaper::escapeshellarg().
     */
    public function escapeshellcmd(string $command): string
    {
        $previousLocale = '';

        if (true !== $this->locale) {
            $previousLocale = setlocale(LC_CTYPE, 0);
            setlocale(LC_CTYPE, $this->locale);
        }

        $result = escapeshellcmd($command);

        if (true !== $this->locale) {
            setlocale(LC_CTYPE, $previousLocale);
        }

        return $result;
    }

    /**
     * Alias for escapeshellarg().
     */
    public function shellArg(string $arg): string
    {
        return $this->escapeshellarg($arg);
    }

    /**
     * Alias for escapeshellcmd().
     */
    public function shellCmd(string $arg): string
    {
        return $this->escapeshellcmd($arg);
    }

    private function initializeUtf8Locale(array $utf8Locales): void
    {
        // with 0 as the second argument, setlocale() returns the current locale
        $previousLocale = setlocale(LC_CTYPE, 0);

        // keep the current locale, as it is already capable of UTF-8. Store true as sentinel value.
        if (false !== strpos($previousLocale, '.UTF-8')) {
            $this->locale = true;

            return;
        }

        $locale = false;
        foreach ($utf8Locales as $cur) {
            if (false !== @setlocale(LC_CTYPE, $cur)) {
                $locale = $cur;
                setlocale(LC_CTYPE, $previousLocale);
            }
        }

        if (!$locale) {
            throw new Exception('No supported UTF-8 locale found!');
        }

        $this->locale = $locale;
    }
}
