<?php
/**
 * PDFtk wrapper split test
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
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdfcpuSplitTest extends TestCase
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

        $expectedCmd1 = "'$binary' collect -pages 2 '/path/to/input.pdf' '/path/to/out1.pdf'";
        $expectedCmd2 = "'$binary' collect -pages 1,3 '/path/to/input.pdf' '/path/to/out2.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);

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

        $expectedCmd1 = "'$binary' collect -pages 1,3 '/path/to/input.pdf' '/path/to/odd.pdf'";
        $expectedCmd2 = "'$binary' collect -pages 2,4 '/path/to/input.pdf' '/path/to/even.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);

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

        $expectedCmd1 = "'$binary' collect -pages 1,3 '/path/to/input.pdf' '/out/odd.pdf'";
        $expectedCmd2 = "'$binary' collect -pages 2,4 '/path/to/input.pdf' '/out/even.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);

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

        $expectedCmd1 = "'$binary' collect -pages 1,3 '/path/to/input.pdf' '/path/to/odd 2.pdf'";
        $expectedCmd2 = "'$binary' collect -pages 2,4 '/path/to/input.pdf' '/path/to/even 2.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);


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

        $expectedCmd1 = "'$binary' collect -pages 4,3 '/path/to/inpüt.pdf' '/path/to/sämple&2.pdf'";
        $expectedCmd2 = "'$binary' collect -pages 2,1 '/path/to/inpüt.pdf' '/path/to/out\$put.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->at(0))
                           ->method('createProcess')
                           ->with($expectedCmd1)
                           ->willReturn($mockProcess1);
        $mockProcessFactory->expects($this->at(1))
                           ->method('createProcess')
                           ->with($expectedCmd2)
                           ->willReturn($mockProcess2);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);


        $wrapper->split('/path/to/inpüt.pdf', ['/path/to/sämple&2.pdf' => [4, 3], '/path/to/out$put.pdf' => [2, 1]]);
    }
}
