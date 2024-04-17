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
use Gmi\Toolkit\Pdftk\Exception\SplitException;
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
}
