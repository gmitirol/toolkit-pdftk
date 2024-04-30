<?php
/**
 * PDFtk wrapper bookmarks test
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

use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Bookmark;
use Gmi\Toolkit\Pdftk\Bookmarks;
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdfcpuBookmarksTest extends TestCase
{
    public function testImportBookmarksException()
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
        $mockProcess->expects($this->atLeastOnce())
                    ->method('getErrorOutput')
                    ->willReturn($pdfErrorMessage);
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn($pdfOutputMessage);

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->stringStartsWith(sprintf('\'%s\' bookmarks export \'%s\'', $binary, $pdf)))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $bookmarks = new Bookmarks();

        try {
            $pdfcpu->importBookmarks($bookmarks, $pdf);
        } catch (PdfException $e) {
            $this->assertSame(
                sprintf('Failed to read bookmarks data from "%s"! Error: %s', $pdf, $exception->getMessage()),
                $e->getMessage()
            );
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
            $this->assertSame($pdfOutputMessage, $e->getPdfOutput());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testImportBookmarks()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->stringStartsWith(sprintf('\'%s\' bookmarks export \'%s\'', $binary, $pdf)))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $bookmarks = new Bookmarks();

        $pdfcpu->importBookmarks($bookmarks, $pdf);
    }

    public function testImportBookmarksFileWithoutBookmarks()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $exception = new Exception('failure');

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));
        $mockProcess->expects($this->atLeastOnce())
                    ->method('getErrorOutput')
                    ->willReturn('no outlines available');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->stringStartsWith(sprintf('\'%s\' bookmarks export \'%s\'', $binary, $pdf)))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $bookmarks = new Bookmarks();

        $pdfcpu->importBookmarks($bookmarks, $pdf);
    }

    public function testApplyBookmarksException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $outfile = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

        $pdfErrorMessage = 'PDF error message';
        $pdfOutputMessage = 'PDF output message';

        $exception = new Exception('failure');

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));
        $mockProcess->expects($this->atLeastOnce())
                    ->method('getErrorOutput')
                    ->willReturn($pdfErrorMessage);
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn($pdfOutputMessage);

        $regex = sprintf("|^'%s' bookmarks import '%s' '[a-zA-Z0-9/]+\.json' '%s'$|", $binary, $pdf, $outfile);
        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->matchesRegularExpression($regex))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $bookmarks = new Bookmarks();

        try {
            $pdfcpu->applyBookmarks($bookmarks, $pdf, $outfile);
        } catch (PdfException $e) {
            $this->assertSame(
                sprintf('Failed to write PDF bookmarks to "%s"! Error: %s', $outfile, $exception->getMessage()),
                $e->getMessage()
            );
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
            $this->assertSame($pdfOutputMessage, $e->getPdfOutput());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testApplyBookmarks()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $outfile = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $regex = sprintf("|^'%s' bookmarks import '%s' '[a-zA-Z0-9/]+\.json' '%s'$|", $binary, $pdf, $outfile);

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->matchesRegularExpression($regex))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $bookmarks = new Bookmarks();

        $pdfcpu->applyBookmarks($bookmarks, $pdf, $outfile);
    }

    /**
     * @group FunctionalTest
     */
    public function testApplyBookmarksRealPdf()
    {
        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

        $wrapper = new PdfcpuWrapper();

        $bookmark = new Bookmark();
        $bookmark
            ->setTitle('Example Bookmark')
            ->setPageNumber(1)
        ;


        $bmSet = new Bookmarks($wrapper);

        $bmSet->add($bookmark);
        $wrapper->applyBookmarks($bmSet, $source, $target);

        $bmGet = new Bookmarks($wrapper);
        $wrapper->importBookmarks($bmGet, $target);

        $this->assertSame('Example Bookmark', $bmGet->all()[0]->getTitle());
        $this->assertSame(1, $bmGet->all()[0]->getPageNumber());
        $this->assertSame(1, $bmGet->all()[0]->getLevel());

        unlink($target);
    }

    /**
     * @group FunctionalTest
     */
    public function testApplyBookmarksRealPdfOutputFileSameAsInputFile()
    {
        $source = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';
        copy(__DIR__ . '/Fixtures/empty.pdf', $source);

        $wrapper = new PdfcpuWrapper();

        $bookmark = new Bookmark();
        $bookmark
            ->setTitle('Example Bookmark')
            ->setPageNumber(1)
        ;

        $bmSet = new Bookmarks($wrapper);

        $bmSet->add($bookmark);
        $wrapper->applyBookmarks($bmSet, $source);

        $bmGet = new Bookmarks($wrapper);
        $wrapper->importBookmarks($bmGet, $source);

        $this->assertSame('Example Bookmark', $bmGet->all()[0]->getTitle());
        $this->assertSame(1, $bmGet->all()[0]->getPageNumber());
        $this->assertSame(1, $bmGet->all()[0]->getLevel());

        unlink($source);
    }
}
