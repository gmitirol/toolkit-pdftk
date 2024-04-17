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
        setlocale(LC_CTYPE, 'C');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No supported UTF-8 locale found!');
        $escaper = new Escaper(['XXX.UTF-8']);
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
