<?php
/**
 * pdfcpu wrapper metadata test
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */
namespace Gmi\Toolkit\Pdftk\Tests;

use Symfony\Component\Process\Process;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\Exception\NotImplementedException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Metadata;
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdfcpuMetadataTest extends TestCase
{
    public function testImportMetadataException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $pdfErrorMessage = 'PDF error message';
        $pdfOutputMessage = 'PDF output message';

        $exception = new Exception('failure');

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn($pdfErrorMessage);
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn($pdfOutputMessage);

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with(sprintf('\'%s\' info -j \'%s\'', $binary, $pdf))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $metadata = new Metadata();

        try {
            $pdfcpu->importMetadata($metadata, $pdf);
        } catch (PdfException $e) {
            $this->assertSame(
                sprintf('Failed to read metadata data from "%s"! Error: %s', $pdf, $exception->getMessage()),
                $e->getMessage()
            );
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
            $this->assertSame($pdfOutputMessage, $e->getPdfOutput());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testImportMetadata()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn('{"infos": [{"title": ""}]}');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with(sprintf('\'%s\' info -j \'%s\'', $binary, $pdf))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $metadata = new Metadata();

        $pdfcpu->importMetadata($metadata, $pdf);
    }

    /**
     * @todo Remove when pdfcpu supports setting metadata
     */
    public function testMetadataApplyNotImplementedInPdfcpu()
    {
        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $randomText = microtime(false);

        $metaSet = new Metadata(new PdfcpuWrapper());

        $this->expectException(NotImplementedException::class);
        $metaSet->set('Creator', $randomText . 'C')
                ->set('Producer', $randomText . 'P')
                ->apply($source, $target);
    }
}
