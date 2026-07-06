<?php
/**
 * pdfcpu wrapper facade test
 *
 * @copyright 2014-2026 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */
namespace Gmi\Toolkit\Pdftk\Tests;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\BinaryPathAwareInterface;
use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Metadata;
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\WrapperInterface;

class PdfcpuWrapperTest extends TestCase
{
    public function testImplementsExpectedInterfaces()
    {
        $wrapper = new PdfcpuWrapper();

        $this->assertInstanceOf(WrapperInterface::class, $wrapper);
        $this->assertInstanceOf(BinaryPathAwareInterface::class, $wrapper);
    }

    public function testBinaryNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        new PdfcpuWrapper(__DIR__ . '/Fixtures/missing.sh');
    }

    public function testGetBinaryReflectsConstructedPath()
    {
        $binary = '/usr/local/bin/pdfcpu_0.11.1';
        if (!is_executable($binary)) {
            $this->markTestSkipped(sprintf('pdfcpu v0.11 binary not found at %s', $binary));
        }

        $wrapper = new PdfcpuWrapper($binary);

        $this->assertSame($binary, $wrapper->getBinary(false));
    }

    public function testSetBinarySwapsToOtherVersion()
    {
        $v11 = '/usr/local/bin/pdfcpu_0.11.1';
        $v12 = '/usr/local/bin/pdfcpu_0.12.1';
        if (!is_executable($v11) || !is_executable($v12)) {
            $this->markTestSkipped('both pdfcpu v0.11 and v0.12 binaries are required for this test');
        }

        $wrapper = new PdfcpuWrapper($v11);
        $this->assertSame($v11, $wrapper->getBinary(false));

        $wrapper->setBinary($v12);
        $this->assertSame($v12, $wrapper->getBinary(false));
    }

    /**
     * @dataProvider provideBinaries
     */
    public function testDelegatesMetadataRoundTrip(string $binary)
    {
        if (!is_executable($binary)) {
            $this->markTestSkipped(sprintf('pdfcpu binary not found at %s', $binary));
        }

        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

        $wrapper = new PdfcpuWrapper($binary);
        $metaSet = new Metadata($wrapper);
        $metaSet->set('Title', 'Facade Test');

        $wrapper->applyMetadata($metaSet, $source, $target);

        $metaGet = new Metadata($wrapper);
        $wrapper->importMetadata($metaGet, $target);
        $this->assertSame('Facade Test', $metaGet->get('Title'));

        @unlink($target);
    }

    public function provideBinaries(): array
    {
        return [
            'v0.11' => ['/usr/local/bin/pdfcpu_0.11.1'],
            'v0.12' => ['/usr/local/bin/pdfcpu_0.12.1'],
        ];
    }
}
