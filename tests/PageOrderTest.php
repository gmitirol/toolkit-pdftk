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

use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Exception\ReorderException;
use Gmi\Toolkit\Pdftk\Page;
use Gmi\Toolkit\Pdftk\Pages;
use Gmi\Toolkit\Pdftk\PageOrder;
use Gmi\Toolkit\Pdftk\PdftkWrapper;

class PageOrderTest extends TestCase
{
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
}
