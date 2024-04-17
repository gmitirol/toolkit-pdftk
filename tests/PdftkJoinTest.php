<?php
/**
 * PDFtk wrapper join test
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Martin Pircher <martin.pircher@i-med.ac.at>
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */
namespace Gmi\Toolkit\Pdftk\Tests;

use Symfony\Component\Process\Process;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\Bookmarks;
use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdftkJoinTest extends TestCase
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

        $expectedCmd = "'$binary' '/path/to/sample1.pdf' '/path/to/sample2.pdf' cat output '/path/to/output.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($expectedCmd)
                           ->willReturn($mockProcess);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);

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

        $expectedCmd = "'$binary' '/path/to/sample1.pdf' '/path/to/sample2.pdf' cat output '/path/to/output.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($expectedCmd)
                           ->willReturn($mockProcess);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);


        $wrapper->join(['/path/to/sample1.pdf', '/path/to/sample2.pdf'], '/path/to/output.pdf');
    }

    public function testJoinFilenamesWithSpaces()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $expectedCmd = "'$binary' '/path/to/sample 1.pdf' '/path/to/sample 2.pdf' cat output '/path/to/out put.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($expectedCmd)
                           ->willReturn($mockProcess);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);


        $wrapper->join(['/path/to/sample 1.pdf', '/path/to/sample 2.pdf'], '/path/to/out put.pdf');
    }

    public function testJoinFilenamesWithSpecialCharacters()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $expectedCmd = "'$binary' '/path/to/sam;ple.pdf' '/path/to/sämple&2.pdf' cat output '/path/to/out\$put.pdf'";

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($expectedCmd)
                           ->willReturn($mockProcess);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);
        $wrapper->join(['/path/to/sam;ple.pdf', '/path/to/sämple&2.pdf'], '/path/to/out$put.pdf');
    }

    /**
     * @group FunctionalTest
     */
    public function testJoinRealPdfs()
    {
        $file1 = __DIR__ . '/Fixtures/example2.pdf';
        $file2 = __DIR__ . '/Fixtures/example.pdf';
        
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $wrapper = new PdftkWrapper();
        $wrapper->join([$file1, $file2], $target);

        $this->assertFileExists($target);

        // verify the bookmarks of the joined file
        $bookmarks = new Bookmarks();
        $wrapper->importBookmarks($bookmarks, $target);

        /**
         * @var Bookmark[]
         */
        $bookmarksArray = $bookmarks->all();

        // Bookmarks of example2.pdf
        $this->assertSame('Awesome PDF', $bookmarksArray[0]->getTitle());
        $this->assertSame(1, $bookmarksArray[0]->getPageNumber());
        $this->assertSame(1, $bookmarksArray[0]->getLevel());

        $this->assertSame('Section 1', $bookmarksArray[1]->getTitle());
        $this->assertSame(1, $bookmarksArray[1]->getPageNumber());
        $this->assertSame(2, $bookmarksArray[1]->getLevel());

        $this->assertSame('Section 2', $bookmarksArray[2]->getTitle());
        $this->assertSame(1, $bookmarksArray[2]->getPageNumber());
        $this->assertSame(2, $bookmarksArray[2]->getLevel());

        // Bookmarks of example.pdf
        $this->assertSame('Page 1 - Level 1', $bookmarksArray[3]->getTitle());
        $this->assertSame(2, $bookmarksArray[3]->getPageNumber());
        $this->assertSame(1, $bookmarksArray[3]->getLevel());

        $this->assertSame('Page 2 - Level 1', $bookmarksArray[4]->getTitle());
        $this->assertSame(3, $bookmarksArray[4]->getPageNumber());
        $this->assertSame(1, $bookmarksArray[4]->getLevel());

        $this->assertSame('Page 3 - Level 2', $bookmarksArray[5]->getTitle());
        $this->assertSame(4, $bookmarksArray[5]->getPageNumber());
        $this->assertSame(2, $bookmarksArray[5]->getLevel());

        unlink($target);
    }
}
