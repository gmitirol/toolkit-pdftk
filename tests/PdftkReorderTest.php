<?php
/**
 * PDFtk wrapper split test
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Martin Pircher <martin.pircher@i-med.ac.at>
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */
namespace Gmi\Toolkit\Pdftk\Tests;

use Symfony\Component\Process\Process;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\Pages;
use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Constant\PageSizes;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdftkReorderTest extends TestCase
{
    public function testReorderException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $exception = new Exception('Error message');

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn('Error');
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn('Output');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with("'$binary' '/path/to/input' cat 3 1 2 output '/path/to/output.pdf'")
                           ->willReturn($mockProcess);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);

        try {
            $wrapper->reorder('/path/to/input', [3, 1, 2], '/path/to/output.pdf');
        } catch (PdfException $e) {
            $msg = sprintf('Failed to reorder PDF "%s"! Error: Error message', '/path/to/input');
            $this->assertSame($msg, $e->getMessage());
            $this->assertSame($exception, $e->getPrevious());
            $this->assertSame('Error', $e->getPdfError());
            $this->assertSame('Output', $e->getPdfOutput());
        }
    }

    public function testReorder()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $exception = new Exception('Error message');

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with("'$binary' '/path/to/input' cat 3 1 2 output '/path/to/output.pdf'")
                           ->willReturn($mockProcess);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);

        $wrapper->reorder('/path/to/input', [3, 1, 2], '/path/to/output.pdf');
    }

    /**
     * @group FunctionalTest
     */
    public function testReorderRealPdf()
    {
        $file = __DIR__ . '/Fixtures/pagesizes.pdf';

        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $wrapper = new PdftkWrapper();
        $wrapper->reorder($file, [3, 1, 2], $target);

        // verify the page info to ensure the pages are split correctly
        $pages = new Pages();
        $wrapper->importPages($pages, $target);

        $pagesArray = $pages->all();
        $this->assertSame(3, count($pagesArray));

        $this->assertSame(PageSizes::A3_WIDTH, $pagesArray[0]->getHeightMm());
        $this->assertSame(PageSizes::A3_HEIGHT, $pagesArray[0]->getWidthMm());

        $this->assertSame(PageSizes::A4_HEIGHT, $pagesArray[1]->getHeightMm());
        $this->assertSame(PageSizes::A4_WIDTH, $pagesArray[1]->getWidthMm());

        $this->assertSame(PageSizes::A4_WIDTH, $pagesArray[2]->getHeightMm());
        $this->assertSame(PageSizes::A4_HEIGHT, $pagesArray[2]->getWidthMm());

        unlink($target);
    }

    /**
     * @group FunctionalTest
     */
    public function testReorderOverwriteInputFile()
    {
        $file = __DIR__ . '/Fixtures/a4.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        copy($file, $target);

        $this->assertSame(sha1_file($file), sha1_file($target));

        $wrapper = new PdftkWrapper();
        $wrapper->reorder($target, [1], $target);

        $this->assertNotSame(sha1_file($file), sha1_file($target));

        // verify the page info to ensure the pages are split correctly
        $pages = new Pages();
        $wrapper->importPages($pages, $target);

        $pagesArray = $pages->all();
        $this->assertSame(1, count($pagesArray));

        $this->assertSame(PageSizes::A4_HEIGHT, $pagesArray[0]->getHeightMm());
        $this->assertSame(PageSizes::A4_WIDTH, $pagesArray[0]->getWidthMm());

        unlink($target);
    }
}
