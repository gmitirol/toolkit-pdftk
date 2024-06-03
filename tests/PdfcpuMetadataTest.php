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

    public function testApplyMetadataInputIsCopiedToOutput()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';



        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with(sprintf('\'%s\' properties add \'%s\' Title=\'Foo\'', $binary, $target))
                           ->willReturn($mockProcess);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);
        $metadata = new Metadata($wrapper);
        $metadata->set('Title', 'Foo');
        $wrapper->applyMetadata($metadata, $source, $target);
        $this->assertSame(file_get_contents($source), file_get_contents($target));
        @unlink($target);
    }

    public function testApplyMetadataException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';



        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException(new Exception('ERR')));

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with(sprintf('\'%s\' properties add \'%s\' Title=\'Foo\'', $binary, $target))
                           ->willReturn($mockProcess);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);
        $metadata = new Metadata($wrapper);
        $metadata->set('Title', 'Foo');
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage(sprintf('Failed to write PDF metadata to "%s"! Error: ERR', $target));
        $wrapper->applyMetadata($metadata, $source, $target);
        @unlink($target);
    }

    public function testApplyMetadataRealPdf()
    {
        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

        $wrapper = new PdfcpuWrapper();
        $metaSet = new Metadata($wrapper);

        $metaSet->set('Creator', 'Awesome PDF creator 1.0')
                ->set('Subject', 'E=mc²')
                ->set('Title', 'Relativity: The Special and General Theory')
                ->set('Author', 'Älbert €instein');

        $wrapper->applyMetadata($metaSet, $source, $target);

        $metaGet = new Metadata($wrapper);
        $wrapper->importMetadata($metaGet, $target);
        $this->assertSame('Awesome PDF creator 1.0', $metaGet->get('Creator'));
        $this->assertSame('E=mc²', $metaGet->get('Subject'));
        $this->assertSame('Relativity: The Special and General Theory', $metaGet->get('Title'));
        $this->assertSame('Älbert €instein', $metaGet->get('Author'));

        @unlink($target);
    }
}
