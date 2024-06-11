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

use Symfony\Component\Finder\Finder;

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\JoinException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Joiner;
use Gmi\Toolkit\Pdftk\Pages;
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Sorter\FileSorterInterface;

use ArrayObject;
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
                    ->method('join');

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

        $exception = new PdfException('Error');

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('join')
                    ->with([$inputFolder . '/Sp185998.pdf'], 'DummyOutput.pdf')
                    ->will($this->throwException($exception));

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

        $pdfErrorMessage = 'PDF error message';
        $pdfOutputMessage = 'PDF output message';

        $exception = new PdfException('Error', 0, null, $pdfErrorMessage, $pdfOutputMessage);

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('join')
                    ->with([$inputFolder . '/Sp178945.pdf'], 'DummyOutput.pdf')
                    ->will($this->throwException($exception));

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

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('join')
                    ->with([$inputFolder . '/Sp123456.pdf'], 'DummyJoin.pdf');

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

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('join')
                    ->with([$filePath1, $filePath2, $filePath3], 'DummyJoin2Files.pdf');

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

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('join')
                    ->with([$filePath1, $filePath2, $filePath3], 'DummyJoin2Files.pdf');

        $joiner = new Joiner($mockWrapper, $mockFinder, $mockSorter);

        $joiner->joinByPattern($inputFolder, $pattern, $output);
    }

    /**
     * @group FunctionalTest
     * @dataProvider getWrapperImplementations
     */
    public function testJoinRealPdfs($wrapper)
    {
        $file1 = __DIR__ . '/Fixtures/example2.pdf';
        $file2 = __DIR__ . '/Fixtures/example.pdf';

        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

        $wrapper->join([$file1, $file2], $target);

        $this->assertFileExists($target);

        $pages = new Pages();
        $wrapper->importPages($pages, $target);

        $this->assertSame(4, count($pages->all()));

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
