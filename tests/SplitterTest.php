<?php
/**
 * PDFtk wrapper
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

use Gmi\Toolkit\Pdftk\Constant\PageOrientations;
use Gmi\Toolkit\Pdftk\Constant\PageSizes;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Exception\SplitException;
use Gmi\Toolkit\Pdftk\Pages;
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Pdftk\Splitter;

class PdfSplitterTest extends TestCase
{
    public function testSplitException()
    {
        $inputFile = __DIR__ . '/test/dummy.pdf';
        $mapping = ['dummy-1.pdf' => [1]];
        $outputFolder = __DIR__ . '/test';

        $exception = new PdfException('Error');

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('split')
                    ->with($inputFile, $mapping)
                    ->will($this->throwException($exception));

        $splitter = new Splitter($mockWrapper);

        $this->expectException(SplitException::class);
        $this->expectExceptionMessage(
            sprintf('Failed to split PDF "%s"! Error: %s', $inputFile, $exception->getMessage())
        );
        $splitter->split($inputFile, $mapping, $outputFolder);
    }

    public function testSplitExceptionHasErrorMessageAndOutput()
    {
        $inputFile = __DIR__ . '/test/dummy.pdf';
        $mapping = ['dummy-1.pdf' => [1]];
        $outputFolder = __DIR__ . '/test';

        $pdfErrorMessage = 'PDF error message';
        $pdfOutputMessage = 'PDF output message';

        $exception = new PdfException('Error', 0, null, $pdfErrorMessage, $pdfOutputMessage);

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('split')
                    ->with($inputFile, $mapping, $outputFolder)
                    ->will($this->throwException($exception));

        $splitter = new Splitter($mockWrapper);

        try {
            $splitter->split($inputFile, $mapping, $outputFolder);
        } catch (SplitException $e) {
            $this->assertSame(
                sprintf('Failed to split PDF "%s"! Error: %s', $inputFile, $exception->getMessage()),
                $e->getMessage()
            );
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
            $this->assertSame($pdfOutputMessage, $e->getPdfOutput());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testSplitSuccessful()
    {
        $inputFile = __DIR__ . '/test/dummy.pdf';
        $mapping = ['dummy-odd.pdf' => [1, 3, 5], 'dummy-even.pdf' => [2, 4, 6]];
        $outputFolder = __DIR__ . '/test';

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('split')
                    ->with($inputFile, $mapping, $outputFolder);

        $splitter = new Splitter($mockWrapper);

        $splitter->split($inputFile, $mapping, $outputFolder);
    }

    /**
     * @group FunctionalTest
     * @dataProvider getWrapperImplementations
     */
    public function testSplitRealPdf($wrapper)
    {
        $file = __DIR__ . '/Fixtures/pages.pdf';

        $targetDir = sys_get_temp_dir() . uniqid('/pdf-split', true);
        mkdir($targetDir);

        $splitMapping = ['a4-variants.pdf' => [1, 2, 3], 'a3-variants.pdf' => [4, 5, 6]];

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

    public function getWrapperImplementations(): array
    {
        return [
            [new PdftkWrapper()],
            [new PdfcpuWrapper()],
        ];
    }
}
