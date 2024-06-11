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

use Gmi\Toolkit\Pdftk\Constant\PageSizes;
use Gmi\Toolkit\Pdftk\Exception\ReorderException;
use Gmi\Toolkit\Pdftk\Page;
use Gmi\Toolkit\Pdftk\Pages;
use Gmi\Toolkit\Pdftk\PageOrder;
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\PdftkWrapper;

class PageOrderTest extends TestCase
{

    public function testReorderEmptyPageNumbers()
    {
        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->never())
                    ->method('importPages');
        $mockWrapper->expects($this->never())
                    ->method('reorder');

        $pageOrder = new PageOrder($mockWrapper);

        $this->expectException(ReorderException::class);
        $this->expectExceptionMessage('Failed to reorder PDF "example.pdf"! Error: Empty page order!');

        $pageOrder->reorder('example.pdf', [], 'output.pdf');
    }

    public function testReorderIncorrectPageNumbers()
    {
        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->never())
                    ->method('importPages');
        $mockWrapper->expects($this->never())
                    ->method('reorder');

        $pageOrder = new PageOrder($mockWrapper);

        $this->expectException(ReorderException::class);
        $this->expectExceptionMessage('Failed to reorder PDF "example.pdf"! Error: Invalid page order!');

        $pageOrder->reorder('example.pdf', [17, 2, 1], 'output.pdf');
    }

    public function testReorderDifferentNumberOfPages()
    {
        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('importPages')
                    ->with($this->isInstanceOf(Pages::class), 'example.pdf');

        $pageOrder = new PageOrder($mockWrapper);

        $this->expectException(ReorderException::class);
        $this->expectExceptionMessage('Failed to reorder PDF "example.pdf"! Error: Invalid number of pages!');

        $pageOrder->reorder('example.pdf', [2, 1], 'output.pdf');
    }

    public function testReorder()
    {
        $pagesCallback = function (Pages $pages) {
            $pages->add(new Page());
            $pages->add(new Page());
            $pages->add(new Page());

            return true;
        };

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('importPages')
                    ->with($this->callback($pagesCallback), 'example.pdf');
        $mockWrapper->expects($this->once())
                    ->method('reorder')
                    ->with('example.pdf', [3, 1, 2], 'output.pdf');

        $pageOrder = new PageOrder($mockWrapper);

        $pageOrder->reorder('example.pdf', [3, 1, 2], 'output.pdf');
    }

    /**
     * @group FunctionalTest
     * @dataProvider getWrapperImplementations
     */
    public function testReorderRealPdf($wrapper)
    {
        $file = __DIR__ . '/Fixtures/pagesizes.pdf';

        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

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
     * @dataProvider getWrapperImplementations
     */
    public function testReorderOverwriteInputFile($wrapper)
    {
        $file = __DIR__ . '/Fixtures/a4.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

        copy($file, $target);

        $this->assertSame(sha1_file($file), sha1_file($target));

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

    public function getWrapperImplementations(): array
    {
        return [
            [new PdftkWrapper()],
            [new PdfcpuWrapper()],
        ];
    }
}
