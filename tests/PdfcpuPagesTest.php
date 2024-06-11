<?php
/**
 * pdfcpu wrapper pages test
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
use Gmi\Toolkit\Pdftk\Pages;
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdfcpuPagesTest extends TestCase
{
    public function testImportPagesException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $pdfErrorMessage = 'PDF error message';
        $pdfOutputMessage = 'PDF output message';

        $exception = new Exception('failure');

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

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with(sprintf('\'%s\' info -pages 1- -j \'%s\'', $binary, $pdf))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $pages = new Pages();

        try {
            $pdfcpu->importPages($pages, $pdf);
        } catch (PdfException $e) {
            $this->assertSame(
                sprintf('Failed to read pages data from "%s"! Error: %s', $pdf, $exception->getMessage()),
                $e->getMessage()
            );
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
            $this->assertSame($pdfOutputMessage, $e->getPdfOutput());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testImportPages()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $pagesJson = <<<EOT
{
	"header": {
		"version": "pdfcpu v0.8.0 dev",
		"creation": "2024-04-30 12:09:03 CEST"
	},
	"infos": [
		{
			"pageCount": 1,
			"pageBoundaries": {
				"1": {
					"mediaBox": {
						"rect": {
							"ll": {
								"x": 0,
								"y": 0
							},
							"ur": {
								"x": 595.32,
								"y": 841.92
							}
						}
					},
					"rot": 0,
					"orient": "portrait"
				}
			}
		}
	]
}
EOT;

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn($pagesJson);

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with(sprintf('\'%s\' info -pages 1- -j \'%s\'', $binary, $pdf))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $pages = new Pages();

        $pdfcpu->importPages($pages, $pdf);
        $this->assertSame(1, count($pages->all()));
    }

    /**
     * @todo Remove when pdfcpu does not emit the extra pages line before JSON anymore
     */
    public function testImportPagesExtraLineBeforeJsonIsIgnored()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $pagesJson = <<<EOT
pages: 1,2
{
	"header": {
		"version": "pdfcpu v0.8.0 dev",
		"creation": "2024-04-30 12:09:03 CEST"
	},
	"infos": [
		{
			"pageCount": 2,
			"pageBoundaries": {
				"1": {
					"mediaBox": {
						"rect": {
							"ll": {
								"x": 0,
								"y": 0
							},
							"ur": {
								"x": 595.32,
								"y": 841.92
							}
						}
					},
					"rot": 0,
					"orient": "portrait"
				},
                                "2": {
					"mediaBox": {
						"rect": {
							"ll": {
								"x": 0,
								"y": 0
							},
							"ur": {
								"x": 595.32,
								"y": 841.92
							}
						}
					},
					"rot": 0,
					"orient": "portrait"
				}
			}
		}
	]
}
EOT;

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn($pagesJson);

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with(sprintf('\'%s\' info -pages 1- -j \'%s\'', $binary, $pdf))
                           ->willReturn($mockProcess);

        $pdfcpu = new PdfcpuWrapper($binary, $mockProcessFactory);
        $pages = new Pages();

        $pdfcpu->importPages($pages, $pdf);
        $this->assertSame(2, count($pages->all()));
    }
}
