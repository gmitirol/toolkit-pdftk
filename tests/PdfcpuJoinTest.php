<?php
/**
 * pdfcpu wrapper join test
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

use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdfcpuJoinTest extends TestCase
{
    public function testJoinException()
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

        $expectedCmd = "'$binary' merge '/path/to/output.pdf' '/path/to/sample1.pdf' '/path/to/sample2.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($expectedCmd)
                           ->willReturn($mockProcess);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);

        try {
            $wrapper->join(['/path/to/sample1.pdf', '/path/to/sample2.pdf'], '/path/to/output.pdf');
        } catch (PdfException $e) {
            $this->assertSame('Error message', $e->getMessage());
            $this->assertSame($exception, $e->getPrevious());
            $this->assertSame('Error', $e->getPdfError());
            $this->assertSame('Output', $e->getPdfOutput());
        }
    }

    public function testJoin()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $expectedCmd = "'$binary' merge '/path/to/output.pdf' '/path/to/sample1.pdf' '/path/to/sample2.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($expectedCmd)
                           ->willReturn($mockProcess);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);

        $wrapper->join(['/path/to/sample1.pdf', '/path/to/sample2.pdf'], '/path/to/output.pdf');
    }

    public function testJoinFilenamesWithSpaces()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $expectedCmd = "'$binary' merge '/path/to/out put.pdf' '/path/to/sample 1.pdf' '/path/to/sample 2.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($expectedCmd)
                           ->willReturn($mockProcess);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);


        $wrapper->join(['/path/to/sample 1.pdf', '/path/to/sample 2.pdf'], '/path/to/out put.pdf');
    }

    public function testJoinFilenamesWithSpecialCharacters()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $expectedCmd = "'$binary' merge '/path/to/out\$put.pdf' '/path/to/sam;ple.pdf' '/path/to/sämple&2.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($expectedCmd)
                           ->willReturn($mockProcess);

        $wrapper = new PdfcpuWrapper($binary, $mockProcessFactory);
        $wrapper->join(['/path/to/sam;ple.pdf', '/path/to/sämple&2.pdf'], '/path/to/out$put.pdf');
    }
}
