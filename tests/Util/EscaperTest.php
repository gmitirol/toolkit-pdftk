<?php

namespace Gmi\Toolkit\Pdftk\Tests\Util;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\Util\Escaper;

use Exception;

class EscaperTest extends TestCase
{
    private $currentLocale;

    public function setUp()
    {
        $this->currentLocale = @setlocale(LC_CTYPE, 0);
    }

    public function tearDown()
    {
        @setlocale(LC_CTYPE, $this->currentLocale);
    }

    public function testNoSupportedUtf8LocaleFound()
    {
        if (false !== @setlocale(LC_CTYPE, 'XXX.UTF-8')) {
            /**
             * @see https://www.php.net/manual/en/function.setlocale.php
             */
            $msg = 'PHPs setlocale() should return false if the specified locale does not exist.' .
                   'However, this is not the case on this system!';

            $this->markTestSkipped($msg);
        }

        setlocale(LC_CTYPE, 'C');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No supported UTF-8 locale found!');
        new Escaper(['XXX.UTF-8']);
    }

    public function testNoSupportedUtf8LocaleFoundButMisreported()
    {
        $locale = @setlocale(LC_CTYPE, 'XXX.UTF-8');
        if (false === $locale) {
            $this->assertFalse($locale);

            /**
             * PHPs setlocale() works as intended on this system, so just return.
             * Marking the test as skipped would cause PHPUnit warnings, but the behaviour of setlocale is okay here.
             *
             * @see testNoSupportedUtf8LocaleFound()
             */
            return;
        }

        setlocale(LC_CTYPE, 'C');
        new Escaper(['XXX.UTF-8']);
    }

    public function testEscapeshellargsAlreadyUtf8Locale()
    {
        setlocale(LC_CTYPE, 'C.UTF-8');
        $input = 'exäm;ple';

        $escaper = new Escaper([]); // only consider current UTF-8 locale, no change allowed
        $this->assertSame("'$input'", $escaper->escapeshellarg($input));
        $this->assertSame("'$input'", $escaper->shellArg($input));
    }

    public function testEscapeshellargsCUtf8Locale()
    {
        setlocale(LC_CTYPE, 'C');
        $input = 'exäm;ple';

        $escaper = new Escaper(['C.UTF-8']);
        $this->assertSame("'$input'", $escaper->escapeshellarg($input));
        $this->assertSame("'$input'", $escaper->shellArg($input));
    }

    public function testEscapeshellcmdAlreadyUtf8Locale()
    {
        setlocale(LC_CTYPE, 'C.UTF-8');
        $input = 'exäm;ple > foo';
        $expected = 'exäm\;ple \> foo';

        $escaper = new Escaper([]); // only consider current UTF-8 locale, no change allowed
        $this->assertSame($expected, $escaper->escapeshellcmd($input));
        $this->assertSame($expected, $escaper->shellCmd($input));
    }

    public function testEscapeshellcmdCUtf8Locale()
    {
        setlocale(LC_CTYPE, 'C');
        $input = 'exäm;ple > foo';
        $expected = 'exäm\;ple \> foo';

        $escaper = new Escaper(['C.UTF-8']);
        $this->assertSame($expected, $escaper->escapeshellcmd($input));
        $this->assertSame($expected, $escaper->shellCmd($input));
    }
}
