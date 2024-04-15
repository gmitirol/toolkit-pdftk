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

use Symfony\Component\Process\Process;

use Gmi\Toolkit\Pdftk\Exception\SplitException;
use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Pdftk\Splitter;

use Exception;

class PdfSplitterTest extends TestCase
{
    public function testSplitException()
    {
        $inputFile = __DIR__ . '/test/dummy.pdf';
        $mapping = ['dummy-1.pdf' => [1]];
        $outputFolder = __DIR__ . '/test';

        $exception = $this->getTestException();

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->once())
                    ->method('createProcess')
                    ->with('/my/pdftk \'' . $inputFile . '\' cat 1 output \'' . $outputFolder . '/dummy-1.pdf\'')
                    ->willReturn($mockProcess);

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

        $pdfErrorMessage = 'PDf error message';
        $pdfOutputMessage = 'PDf output message';

        $exception = $this->getTestException();

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

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->once())
                    ->method('createProcess')
                    ->with('/my/pdftk \'' . $inputFile . '\' cat 1 output \'' . $outputFolder . '/dummy-1.pdf\'')
                    ->willReturn($mockProcess);

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

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->exactly(2))
                    ->method('mustRun');

        $commandLine1 = '/my/pdftk \'' . $inputFile . '\' cat 1 3 5 output \'' . $outputFolder . '/dummy-odd.pdf\'';
        $commandLine2 = '/my/pdftk \'' . $inputFile . '\' cat 2 4 6 output \'' . $outputFolder . '/dummy-even.pdf\'';

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->at(0))
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->at(1))
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->at(2))
                    ->method('createProcess')
                    ->with($commandLine1)
                    ->willReturn($mockProcess);
        $mockWrapper->expects($this->at(3))
                    ->method('createProcess')
                    ->with($commandLine2)
                    ->willReturn($mockProcess);

        $splitter = new Splitter($mockWrapper);

        $splitter->split($inputFile, $mapping, $outputFolder);
    }

    public function testSplitSuccessfulNoOutputFolder()
    {
        $inputFile = __DIR__ . '/test/dummy.pdf';
        $mapping = ['/foo/dummy-a.pdf' => [1, 2, 3], '/bar/dummy-b.pdf' => [4, 5, 6]];

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->exactly(2))
                    ->method('mustRun');

        $commandLine1 = '/my/pdftk \'' . $inputFile . '\' cat 1 2 3 output \'/foo/dummy-a.pdf\'';
        $commandLine2 = '/my/pdftk \'' . $inputFile . '\' cat 4 5 6 output \'/bar/dummy-b.pdf\'';

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->at(0))
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->at(1))
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->at(2))
                     ->method('createProcess')
                     ->with($commandLine1)
                     ->willReturn($mockProcess);
        $mockWrapper->expects($this->at(3))
                    ->method('createProcess')
                    ->with($commandLine2)
                    ->willReturn($mockProcess);

        $splitter = new Splitter($mockWrapper);

        $splitter->split($inputFile, $mapping, null);
    }

    private function getTestException()
    {
        return new Exception('pdftk exception message');
    }
}
