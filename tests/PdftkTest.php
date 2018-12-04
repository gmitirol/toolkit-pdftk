<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Martin Pircher <martin.pircher@i-med.ac.at>
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */
namespace Gmi\Toolkit\Pdftk\Tests;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\Bookmark;
use Gmi\Toolkit\Pdftk\Joiner;
use Gmi\Toolkit\Pdftk\Pdftk;
use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Pdftk\Splitter;
use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;

class PdftkTest extends TestCase
{
    public function testCustomBinaryNotFound()
    {
        $binary = __DIR__ . '/Fixtures/missing.sh';

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('setBinary')
                    ->with($binary)
                    ->will($this->throwException(new FileNotFoundException('Binary not found!')));

        $this->expectException(FileNotFoundException::Class);
        new Pdftk(['binary' => $binary], $mockWrapper);
    }

    public function testCustomBinary()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockWrapper = $this->createMock(PdftkWrapper::class);
        $mockWrapper->expects($this->once())
                    ->method('setBinary')
                    ->with($binary)
                    ->willReturn($mockWrapper);

        new Pdftk(['binary' => $binary], $mockWrapper);
    }

    public function testImport()
    {
        $file = __DIR__ . '/Fixtures/example2.pdf';
        $pdftk = new Pdftk();
        $pdftk->import($file);

        $this->assertSame(1, count($pdftk->pages()->all()));

        $this->assertSame(210, $pdftk->pages()->all()[0]->getWidthMm());

        $this->assertSame(3, count($pdftk->bookmarks()->all()));

        $this->assertSame(1, $pdftk->bookmarks()->all()[0]->getPageNumber());
        $this->assertSame(1, $pdftk->bookmarks()->all()[0]->getLevel());
        $this->assertSame('Awesome PDF', $pdftk->bookmarks()->all()[0]->getTitle());

        $this->assertSame(1, $pdftk->bookmarks()->all()[1]->getPageNumber());
        $this->assertSame(2, $pdftk->bookmarks()->all()[1]->getLevel());
        $this->assertSame('Section 1', $pdftk->bookmarks()->all()[1]->getTitle());

        $this->assertSame(1, $pdftk->bookmarks()->all()[2]->getPageNumber());
        $this->assertSame(2, $pdftk->bookmarks()->all()[2]->getLevel());
        $this->assertSame('Section 2', $pdftk->bookmarks()->all()[2]->getTitle());

        $this->assertSame('Jane Doe', $pdftk->metadata()->get('Author'));
        $this->assertSame('Example Producer', $pdftk->metadata()->get('Producer'));
        $this->assertSame('Example Creator', $pdftk->metadata()->get('Creator'));
    }

    public function testApply()
    {
        $file = __DIR__ . '/Fixtures/example2.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $pdftk = new Pdftk();
        $pdftk->import($file);

        $exampleBookmark = new Bookmark();
        $exampleBookmark
            ->setPageNumber(1)
            ->setLevel(2)
            ->setTitle('Section 3')
        ;

        $pdftk->bookmarks()->add($exampleBookmark);
        $pdftk->metadata()->set('Author', 'Jane Doe & Erika Mustermann');

        $pdftk->apply($file, $target);

        $pdftkGet = new Pdftk();
        $pdftkGet->import($target);

        $this->assertSame(4, count($pdftkGet->bookmarks()->all()));
        $this->assertSame(1, $pdftkGet->bookmarks()->all()[3]->getPageNumber());
        $this->assertSame(2, $pdftkGet->bookmarks()->all()[3]->getLevel());
        $this->assertSame('Section 3', $pdftkGet->bookmarks()->all()[3]->getTitle());

        unlink($target);
    }

    public function testGetJoiner()
    {
        $pdftk = new Pdftk();
        $joiner = $pdftk->joiner();

        $this->assertInstanceOf(Joiner::class, $joiner);
        $this->assertSame($pdftk->getJoiner(), $joiner);
    }

    public function testGetSplitter()
    {
        $pdftk = new Pdftk();
        $splitter = $pdftk->splitter();

        $this->assertInstanceOf(Splitter::class, $splitter);
        $this->assertSame($pdftk->getSplitter(), $splitter);
    }
}
