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
use Gmi\Toolkit\Pdftk\Constant\PageOrientations;
use Gmi\Toolkit\Pdftk\Constant\PageSizes;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdftkSplitTest extends TestCase
{
    public function testSplitException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $exception = new Exception('Error message');

        $mockProcess1 = $this->createMock(Process::class);
        $mockProcess1->expects($this->once())
                     ->method('mustRun');
        $mockProcess2 = $this->createMock(Process::class);
        $mockProcess2->expects($this->once())
                     ->method('mustRun')
                     ->will($this->throwException($exception));
        $mockProcess2->expects($this->once())
                     ->method('getErrorOutput')
                     ->willReturn('Error');
        $mockProcess2->expects($this->once())
                     ->method('getOutput')
                     ->willReturn('Output');

        $expectedCmd1 = "'$binary' '/path/to/input.pdf' cat 2 output '/path/to/out1.pdf'";
        $expectedCmd2 = "'$binary' '/path/to/input.pdf' cat 1 3 output '/path/to/out2.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);

        try {
            $wrapper->split('/path/to/input.pdf', ['/path/to/out1.pdf' => [2], '/path/to/out2.pdf' => [1, 3]]);
        } catch (PdfException $e) {
            $this->assertSame('Error message', $e->getMessage());
            $this->assertSame($exception, $e->getPrevious());
            $this->assertSame('Error', $e->getPdfError());
            $this->assertSame('Output', $e->getPdfOutput());
        }
    }

    public function testSplit()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess1 = $this->createMock(Process::class);
        $mockProcess1->expects($this->once())
                     ->method('mustRun');
        $mockProcess2 = $this->createMock(Process::class);
        $mockProcess2->expects($this->once())
                     ->method('mustRun');

        $expectedCmd1 = "'$binary' '/path/to/input.pdf' cat 1 3 output '/path/to/odd.pdf'";
        $expectedCmd2 = "'$binary' '/path/to/input.pdf' cat 2 4 output '/path/to/even.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);

        $wrapper->split('/path/to/input.pdf', ['/path/to/odd.pdf' => [1, 3], '/path/to/even.pdf' => [2, 4]]);
    }

    public function testSplitOutputFolder()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess1 = $this->createMock(Process::class);
        $mockProcess1->expects($this->once())
                     ->method('mustRun');
        $mockProcess2 = $this->createMock(Process::class);
        $mockProcess2->expects($this->once())
                     ->method('mustRun');

        $expectedCmd1 = "'$binary' '/path/to/input.pdf' cat 1 3 output '/out/odd.pdf'";
        $expectedCmd2 = "'$binary' '/path/to/input.pdf' cat 2 4 output '/out/even.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);

        $wrapper->split('/path/to/input.pdf', ['odd.pdf' => [1, 3], 'even.pdf' => [2, 4]], '/out');
    }

    public function testSplitFilenamesWithSpaces()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess1 = $this->createMock(Process::class);
        $mockProcess1->expects($this->once())
                     ->method('mustRun');
        $mockProcess2 = $this->createMock(Process::class);
        $mockProcess2->expects($this->once())
                     ->method('mustRun');

        $expectedCmd1 = "'$binary' '/path/to/input.pdf' cat 1 3 output '/path/to/odd 2.pdf'";
        $expectedCmd2 = "'$binary' '/path/to/input.pdf' cat 2 4 output '/path/to/even 2.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);


        $wrapper->split('/path/to/input.pdf', ['/path/to/odd 2.pdf' => [1, 3], '/path/to/even 2.pdf' => [2, 4]]);
    }

    public function testSplitFilenamesWithSpecialCharacters()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess1 = $this->createMock(Process::class);
        $mockProcess1->expects($this->once())
                     ->method('mustRun');
        $mockProcess2 = $this->createMock(Process::class);
        $mockProcess2->expects($this->once())
                     ->method('mustRun');

        $expectedCmd1 = "'$binary' '/path/to/inpüt.pdf' cat 4 3 output '/path/to/sämple&2.pdf'";
        $expectedCmd2 = "'$binary' '/path/to/inpüt.pdf' cat 2 1 output '/path/to/out\$put.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);


        $wrapper->split('/path/to/inpüt.pdf', ['/path/to/sämple&2.pdf' => [4, 3], '/path/to/out$put.pdf' => [2, 1]]);
    }

    /**
     * @group FunctionalTest
     */
    public function testSplitRealPdf()
    {
        $file = __DIR__ . '/Fixtures/pages.pdf';

        $targetDir = sys_get_temp_dir() . uniqid('/pdf-split', true);
        mkdir($targetDir);

        $splitMapping = ['a4-variants.pdf' => [1, 2, 3], 'a3-variants.pdf' => [4, 5, 6]];

        $wrapper = new PdftkWrapper();
        $wrapper->split($file, $splitMapping, $targetDir);

        // verify the page info to ensure the pages are split correctly
        $pagesPdf1 = new Pages();
        $pagesPdf2 = new Pages();
        $wrapper->importPages($pagesPdf1, $targetDir . '/a4-variants.pdf');
        $wrapper->importPages($pagesPdf2, $targetDir . '/a3-variants.pdf');

        $pagesA4 = $pagesPdf1->all();
        $this->assertSame(3, count($pagesA4));

        $this->assertSame(PageSizes::A4_HEIGHT, $pagesA4[0]->getHeightMm());
        $this->assertSame(PageSizes::A4_WIDTH, $pagesA4[0]->getWidthMm());
        $this->assertSame(PageOrientations::UP, $pagesA4[0]->getRotation());

        $this->assertSame(PageSizes::A4_HEIGHT, $pagesA4[1]->getHeightMm());
        $this->assertSame(PageSizes::A4_WIDTH, $pagesA4[1]->getWidthMm());
        $this->assertSame(PageOrientations::LEFT, $pagesA4[1]->getRotation());

        $this->assertSame(PageSizes::A4_WIDTH, $pagesA4[2]->getHeightMm());
        $this->assertSame(PageSizes::A4_HEIGHT, $pagesA4[2]->getWidthMm());
        $this->assertSame(PageOrientations::UP, $pagesA4[2]->getRotation());

        $pagesA3 = $pagesPdf2->all();
        $this->assertSame(3, count($pagesA3));

        $this->assertSame(PageSizes::A3_HEIGHT, $pagesA3[0]->getHeightMm());
        $this->assertSame(PageSizes::A3_WIDTH, $pagesA3[0]->getWidthMm());
        $this->assertSame(PageOrientations::UP, $pagesA3[0]->getRotation());

        $this->assertSame(PageSizes::A3_HEIGHT, $pagesA3[1]->getHeightMm());
        $this->assertSame(PageSizes::A3_WIDTH, $pagesA3[1]->getWidthMm());
        $this->assertSame(PageOrientations::RIGHT, $pagesA3[1]->getRotation());

        $this->assertSame(PageSizes::A3_WIDTH, $pagesA3[2]->getHeightMm());
        $this->assertSame(PageSizes::A3_HEIGHT, $pagesA3[2]->getWidthMm());
        $this->assertSame(PageOrientations::UP, $pagesA3[2]->getRotation());

        foreach (array_keys($splitMapping) as $file) {
            unlink($targetDir . '/' . $file);
        }
        rmdir($targetDir);
    }
}
