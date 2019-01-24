<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Tests;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\JoinException;
use Gmi\Toolkit\Pdftk\Joiner;
use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Pdftk\Util\FileSorterInterface;

use ArrayObject;
use Exception;
use SplFileInfo;

class JoinerTest extends TestCase
{
    public function testMissingFiles()
    {
        $inputFolder = __DIR__ . '/DummyFolder';
        $pattern = '/^[a-zA-Z]{2}[0-9]{6}\.pdf$/';
        $output = 'DummyOutput.pdf';

        $mockFinder = $this->createMock(Finder::class);
        $mockFinder->expects($this->once())
                   ->method('files')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('name')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('in')
                   ->willReturn($mockFinder);
        $mockFinder->expects($this->once())
                   ->method('getIterator')
                   ->willReturn(new ArrayObject());

        $mockSorter = $this->createMock(FileSorterInterface::class);
        $mockSorter->expects($this->never())
                   ->method('sort');

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->never())
                    ->method('createProcess');

        $joiner = new Joiner($mockWrapper, $mockFinder, $mockSorter);

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                'No files in "%s" are matching to the pattern "%s"',
                $inputFolder,
                $pattern
            )
        );

        $joiner->joinByPattern($inputFolder, $pattern, $output);
    }

    public function testJoinException()
    {
        $inputFolder = __DIR__ . '/DummyFolder';
        $pattern = '/^[a-zA-Z]{2}[0-9]{6}\.pdf$/';
        $output = 'DummyOutput.pdf';

        $mockSplFileInfo = $this->createMock(SplFileInfo::class);
        $mockSplFileInfo->expects($this->once())
                        ->method('getPathname')
                        ->willReturn($inputFolder . '/Sp185998.pdf');

        $mockFinder = $this->createMock(Finder::class);
        $mockFinder->expects($this->once())
                   ->method('files')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('name')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('in')
                   ->willReturn($mockFinder);
        $mockFinder->expects($this->once())
                   ->method('getIterator')
                   ->willReturn(new ArrayObject([$mockSplFileInfo]));

        $mockSorter = $this->createMock(FileSorterInterface::class);
        $mockSorter->expects($this->once())
                   ->method('sort')
                   ->with([$mockSplFileInfo])
                   ->willReturn(new ArrayObject([$mockSplFileInfo]));

        $exception = $this->getTestException();

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));
        $mockProcess->expects($this->once())
                    ->method('getOutput');

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->once())
                    ->method('createProcess')
                    ->with('/my/pdftk \'' . $inputFolder . '/Sp185998.pdf\' cat output \'DummyOutput.pdf\'')
                    ->willReturn($mockProcess);

        $joiner = new Joiner($mockWrapper, $mockFinder, $mockSorter);

        $this->expectException(JoinException::class);
        $this->expectExceptionMessage(
            sprintf('Failed to join PDF "%s"! Error: %s', $output, $exception->getMessage())
        );
        $joiner->joinByPattern($inputFolder, $pattern, $output);
    }

    public function testJoinExceptionHasErrorMessageAndOutput()
    {
        $inputFolder = __DIR__ . '/DummyFolder';
        $pattern = '/^[a-zA-Z]{2}[0-9]{6}\.pdf$/';
        $output = 'DummyOutput.pdf';

        $mockSplFileInfo = $this->createMock(SplFileInfo::class);
        $mockSplFileInfo->expects($this->once())
                        ->method('getPathname')
                        ->willReturn($inputFolder . '/Sp178945.pdf');

        $mockFinder = $this->createMock(Finder::class);
        $mockFinder->expects($this->once())
                   ->method('files')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('name')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('in')
                   ->willReturn($mockFinder);
        $mockFinder->expects($this->once())
                   ->method('getIterator')
                   ->willReturn(new ArrayObject([$mockSplFileInfo]));

        $mockSorter = $this->createMock(FileSorterInterface::class);
        $mockSorter->expects($this->once())
                   ->method('sort')
                   ->with([$mockSplFileInfo])
                   ->willReturn(new ArrayObject([$mockSplFileInfo]));

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
                    ->with('/my/pdftk \'' . $inputFolder . '/Sp178945.pdf\' cat output \'DummyOutput.pdf\'')
                    ->willReturn($mockProcess);

        $joiner = new Joiner($mockWrapper, $mockFinder, $mockSorter);

        try {
            $joiner->joinByPattern($inputFolder, $pattern, $output);
        } catch (JoinException $e) {
            $this->assertSame(
                sprintf('Failed to join PDF "%s"! Error: %s', $output, $exception->getMessage()),
                $e->getMessage()
            );
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
            $this->assertSame($pdfOutputMessage, $e->getPdfOutput());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testJoinSuccessful()
    {
        $inputFolder = __DIR__ . '/DummyFolder';
        $pattern = '/^[a-zA-Z]{2}[0-9]{6}\.pdf$/';
        $output = 'DummyJoin.pdf';

        $mockSplFileInfo = $this->createMock(SplFileInfo::class);
        $mockSplFileInfo->expects($this->once())
                        ->method('getPathname')
                        ->willReturn($inputFolder . '/Sp123456.pdf');

        $mockFinder = $this->createMock(Finder::class);
        $mockFinder->expects($this->once())
                   ->method('files')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('name')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('in')
                   ->willReturn($mockFinder);
        $mockFinder->expects($this->once())
                   ->method('getIterator')
                   ->willReturn(new ArrayObject([$mockSplFileInfo]));

        $mockSorter = $this->createMock(FileSorterInterface::class);
        $mockSorter->expects($this->once())
                   ->method('sort')
                   ->with([$mockSplFileInfo])
                   ->willReturn(new ArrayObject([$mockSplFileInfo]));

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');
        $mockProcess->expects($this->once())
                    ->method('getOutput');

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->once())
                    ->method('createProcess')
                    ->with('/my/pdftk \'' . $inputFolder . '/Sp123456.pdf\' cat output \'DummyJoin.pdf\'')
                    ->willReturn($mockProcess);

        $joiner = new Joiner($mockWrapper, $mockFinder, $mockSorter);

        $joiner->joinByPattern($inputFolder, $pattern, $output);
    }

    public function testJoinSuccessful3Pdfs()
    {
        $inputFolder = __DIR__ . '/DummyFolder';
        $pattern = '/^[a-zA-Z]{2}[0-9]{6}\.pdf$/';
        $output = 'DummyJoin2Files.pdf';

        $filePath1 = $inputFolder . '/Sp111111.pdf';
        $filePath2 = $inputFolder . '/Sp222222.pdf';
        $filePath3 = $inputFolder . '/Sp333333.pdf';

        $mockSplFileInfo1 = $this->createMock(SplFileInfo::class);
        $mockSplFileInfo1->expects($this->once())
                         ->method('getPathname')
                         ->willReturn($filePath1);

        $mockSplFileInfo2 = $this->createMock(SplFileInfo::class);
        $mockSplFileInfo2->expects($this->once())
                         ->method('getPathname')
                         ->willReturn($filePath2);
        $mockSplFileInfo3 = $this->createMock(SplFileInfo::class);
        $mockSplFileInfo3->expects($this->once())
                         ->method('getPathname')
                         ->willReturn($filePath3);

        $mockFinder = $this->createMock(Finder::class);
        $mockFinder->expects($this->once())
                   ->method('files')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('name')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('in')
                   ->willReturn($mockFinder);
        $mockFinder->expects($this->once())
                   ->method('getIterator')
                   ->willReturn(new ArrayObject([$mockSplFileInfo3, $mockSplFileInfo2, $mockSplFileInfo1]));

        $mockSorter = $this->createMock(FileSorterInterface::class);
        $mockSorter->expects($this->once())
                   ->method('sort')
                   ->with([$mockSplFileInfo3, $mockSplFileInfo2, $mockSplFileInfo1])
                   ->willReturn(new ArrayObject([$mockSplFileInfo1, $mockSplFileInfo2, $mockSplFileInfo3]));

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');
        $mockProcess->expects($this->once())
                    ->method('getOutput');

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->once())
                    ->method('createProcess')
                    // @codingStandardsIgnoreStart
                    ->with('/my/pdftk \'' . $filePath1 . '\' \'' . $filePath2 . '\' \'' . $filePath3 .'\' cat output \'DummyJoin2Files.pdf\'')
                    // @codingStandardsIgnoreEnd
                    ->willReturn($mockProcess);

        $joiner = new Joiner($mockWrapper, $mockFinder, $mockSorter);

        $joiner->joinByPattern($inputFolder, $pattern, $output);
    }

    public function testJoinSuccessful3PdfsWithSpaces()
    {
        $inputFolder = __DIR__ . '/DummyFolder';
        $pattern = '/^[a-zA-Z]{2}[0-9]{6}\.pdf$/';
        $output = 'DummyJoin2Files.pdf';

        $filePath1 = $inputFolder . '/Sp 11 11 11.pdf';
        $filePath2 = $inputFolder . '/Sp222222 .pdf';
        $filePath3 = $inputFolder . '/Sp 333333  .pdf';

        $mockSplFileInfo1 = $this->createMock(SplFileInfo::class);
        $mockSplFileInfo1->expects($this->once())
                         ->method('getPathname')
                         ->willReturn($filePath1);

        $mockSplFileInfo2 = $this->createMock(SplFileInfo::class);
        $mockSplFileInfo2->expects($this->once())
                         ->method('getPathname')
                         ->willReturn($filePath2);
        $mockSplFileInfo3 = $this->createMock(SplFileInfo::class);
        $mockSplFileInfo3->expects($this->once())
                         ->method('getPathname')
                         ->willReturn($filePath3);

        $mockFinder = $this->createMock(Finder::class);
        $mockFinder->expects($this->once())
                   ->method('files')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('name')
                   ->will($this->returnSelf());
        $mockFinder->expects($this->once())
                   ->method('in')
                   ->willReturn($mockFinder);
        $mockFinder->expects($this->once())
                   ->method('getIterator')
                   ->willReturn(new ArrayObject([$mockSplFileInfo3, $mockSplFileInfo2, $mockSplFileInfo1]));

        $mockSorter = $this->createMock(FileSorterInterface::class);
        $mockSorter->expects($this->once())
                   ->method('sort')
                   ->with([$mockSplFileInfo3, $mockSplFileInfo2, $mockSplFileInfo1])
                   ->willReturn(new ArrayObject([$mockSplFileInfo1, $mockSplFileInfo2, $mockSplFileInfo3]));

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');
        $mockProcess->expects($this->once())
                    ->method('getOutput');

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('getBinary')
                    ->willReturn('/my/pdftk');
        $mockWrapper->expects($this->once())
                    ->method('createProcess')
                    // @codingStandardsIgnoreStart
                    ->with('/my/pdftk \'' . $filePath1 . '\' \'' . $filePath2 . '\' \'' .$filePath3. '\' cat output \'DummyJoin2Files.pdf\'')
                    // @codingStandardsIgnoreEnd
                    ->willReturn($mockProcess);

        $joiner = new Joiner($mockWrapper, $mockFinder, $mockSorter);

        $joiner->joinByPattern($inputFolder, $pattern, $output);
    }

    private function getTestException()
    {
        return new Exception('pdftk exception message');
    }
}
