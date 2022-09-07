<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2022 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Tests;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Process\Process;

use Gmi\Toolkit\Pdftk\Exception\ReorderException;
use Gmi\Toolkit\Pdftk\PageOrder;
use Gmi\Toolkit\Pdftk\PdftkWrapper;

use Exception;

class PdfOrderTest extends TestCase
{
    public function testReorderDifferentNumberOfPages()
    {
        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getPdfDataDump')
                    ->with('example.pdf')
                    ->willReturn("PageMediaBegin\n");
        $mockWrapper->expects($this->never())
                    ->method('createProcess');

        $pageOrder = new PageOrder($mockWrapper);

        $this->expectException(ReorderException::class);
        $this->expectExceptionMessage('Invalid number of pages!');

        $pageOrder->reorder('example.pdf', [2, 1], 'output.pdf');
    }

    public function testReorderIncorrectPageNumbers()
    {
        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getPdfDataDump')
                    ->with('example.pdf')
                    ->willReturn("PageMediaBegin\nPageMediaBegin\nPageMediaBegin\n");
        $mockWrapper->expects($this->never())
                    ->method('createProcess');

        $pageOrder = new PageOrder($mockWrapper);

        $this->expectException(ReorderException::class);
        $this->expectExceptionMessage('Invalid page order!');

        $pageOrder->reorder('example.pdf', [17, 2, 1], 'output.pdf');
    }

    public function testReorder()
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->willReturn($mockProcess);

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getBinary')
                    ->willReturn('/usr/bin/pdftk');
        $mockWrapper->expects($this->once())
                    ->method('getPdfDataDump')
                    ->with('example.pdf')
                    ->willReturn("PageMediaBegin\nPageMediaBegin\nPageMediaBegin\n");
        $mockWrapper->expects($this->once())
                    ->method('createProcess')
                    ->with('/usr/bin/pdftk \'example.pdf\' cat 3 1 2 output \'output.pdf\'')
                    ->willReturn($mockProcess);

        $pageOrder = new PageOrder($mockWrapper);

        $pageOrder->reorder('example.pdf', [3, 1, 2], 'output.pdf');
    }

    public function testReorderOverwriteInputFile()
    {
        copy(__DIR__ . '/Fixtures/example-multipage.pdf', '/tmp/example-multipage.pdf');
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->willReturn($mockProcess);

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getBinary')
                    ->willReturn('/usr/bin/pdftk');
        $mockWrapper->expects($this->once())
                    ->method('getPdfDataDump')
                    ->with('/tmp/example-multipage.pdf')
                    ->willReturn("PageMediaBegin\nPageMediaBegin\nPageMediaBegin\nnPageMediaBegin\n");
        $mockWrapper->expects($this->once())
                    ->method('createProcess')
                    ->with($this->stringContains('/usr/bin/pdftk \'/tmp/example-multipage.pdf\' cat 3 4 1 2 output '))
                    ->willReturn($mockProcess);

        $pageOrder = new PageOrder($mockWrapper);

        $pageOrder->reorder('/tmp/example-multipage.pdf', [3, 4, 1, 2], null);
    }

    public function testReorderOverwriteInputFileFailure()
    {
        copy(__DIR__ . '/Fixtures/example-multipage.pdf', '/tmp/example-multipage.pdf');
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException(new Exception('fail')));

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getBinary')
                    ->willReturn('/usr/bin/pdftk');
        $mockWrapper->expects($this->once())
                    ->method('getPdfDataDump')
                    ->with('/tmp/example-multipage.pdf')
                    ->willReturn("PageMediaBegin\nPageMediaBegin\nPageMediaBegin\nnPageMediaBegin\n");
        $mockWrapper->expects($this->once())
                    ->method('createProcess')
                    ->with($this->stringContains('/usr/bin/pdftk \'/tmp/example-multipage.pdf\' cat 3 4 1 2 output '))
                    ->willReturn($mockProcess);

        $pageOrder = new PageOrder($mockWrapper);
        
        try {
            $pageOrder->reorder('/tmp/example-multipage.pdf', [3, 4, 1, 2], null);
        } catch (ReorderException $e) {
            // exception is expected, see below
        }

        $this->assertNotNull($e);
        $this->assertSame('Failed to reorder PDF "/tmp/example-multipage.pdf"! Error: fail', $e->getMessage());
        $this->assertSame(
            file_get_contents(__DIR__ . '/Fixtures/example-multipage.pdf'),
            file_get_contents('/tmp/example-multipage.pdf'),
            'The document in the temp file should still be the same is at was (a copy of the fixture)'
        );
    }
}
