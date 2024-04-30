<?php
/**
 * pdfcpu wrapper test
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */
namespace Gmi\Toolkit\Pdftk\Tests;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;

use Exception;

/**
 * This test class tests general methods of the pdfcpu wrapper.
 *
 * The features implemented from WrapperInterface are unit-tested in individual Pdfcpu*Test classes.
 */
class PdfcpuWrapperTest extends TestCase
{
    public function testGuessBinary()
    {
        $pdftk = new PdfcpuWrapper();
        $this->assertSame('/usr/bin/pdfcpu', $pdftk->guessBinary('Linux'));
        $this->assertSame('C:\\Program Files\\pdfcpu\\pdfcpu.exe', $pdftk->guessBinary('WINNT'));
        $this->assertSame('C:\\Program Files\\pdfcpu\\pdfcpu.exe', $pdftk->guessBinary('Windows'));
    }

    public function testBinarySetGet()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $pdftk = new PdfcpuWrapper($binary);

        $this->assertSame(
            $binary,
            $pdftk->getBinary(false)
        );

        $this->assertSame(
            escapeshellarg($binary),
            $pdftk->getBinary(true)
        );

        $this->assertSame(
            $binary,
            $pdftk->setBinary($binary)->getBinary(false)
        );
    }

    public function testBinaryNotFound()
    {
        $binary = __DIR__ . '/Fixtures/missing.sh';

        $this->expectException(FileNotFoundException::Class);
        new PdfcpuWrapper($binary);
    }
}
