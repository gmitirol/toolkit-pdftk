<?php
/**
 * PDFtk wrapper
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

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\Bookmark;
use Gmi\Toolkit\Pdftk\Bookmarks;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;

class BookmarksTest extends TestCase
{
    public function testAddInvalidPageNumber()
    {
        $bookmark = new Bookmark();
        $bookmark
            ->setTitle('Example')
            ->setPageNumber(-1)
            ->setLevel(1)
        ;

        $bm = new Bookmarks();
        $this->expectException(PdfException::Class);
        $this->expectExceptionMessage('Invalid page number: -1');
        $bm->add($bookmark);
    }

    public function testAddBookmarkPageNumberZeroIgnored()
    {
        $bookmark = new Bookmark();
        $bookmark
            ->setTitle('Bookmark with target page number 0 - invalid and thus ignored')
            ->setPageNumber(0)
            ->setLevel(1)
        ;

        $bm = new Bookmarks();
        $this->assertEmpty($bm->all());
        $bm->add($bookmark);
        $this->assertEmpty($bm->all());
    }

    public function testAddOutofBoundsPageNumber()
    {
        $bookmark = new Bookmark();
        $bookmark
            ->setTitle('Example')
            ->setPageNumber(4)
            ->setLevel(1)
        ;

        $bm = new Bookmarks();
        $this->expectException(PdfException::Class);
        $this->expectExceptionMessage('Page number out of range!');
        $bm
            ->setMaxpage(3)
            ->add($bookmark)
        ;
    }

    public function testAddInvalidLevel()
    {
        $bookmark = new Bookmark();
        $bookmark
            ->setTitle('Example')
            ->setPageNumber(1)
            ->setLevel(0)
        ;

        $bm = new Bookmarks();
        $this->expectException(PdfException::Class);
        $this->expectExceptionMessage('Invalid bookmark level: 0');
        $bm->add($bookmark);
    }

    public function testSetterGetter()
    {
        $bm = new Bookmarks();

        $bookmark = new Bookmark();
        $bookmark
            ->setTitle('Example')
            ->setPageNumber(10)
            ->setLevel(1)
        ;

        $this->assertSame(
            [$bookmark],
            $bm->add($bookmark)
               ->all()
        );
    }

    public function testRemove()
    {
        $bm = new Bookmarks();

        $bookmark = new Bookmark();
        $bookmark
            ->setTitle('Example')
            ->setPageNumber(2)
            ->setLevel(3)
        ;

        $bm->add($bookmark);
        $this->assertSame([$bookmark], $bm->all());

        $bm->remove($bookmark);
        $this->assertSame([], $bm->all());
    }

    public function testRemoveByPage()
    {
        $bm = new Bookmarks();

        $bookmark1 = new Bookmark();
        $bookmark1
            ->setTitle('Example 1 - page 1')
            ->setPageNumber(1)
        ;

        $bookmark2 = new Bookmark();
        $bookmark2
            ->setTitle('Example 2 - page 2')
            ->setPageNumber(2)
        ;

        $bookmark3 = new Bookmark();
        $bookmark3
            ->setTitle('Example 3 - page 2')
            ->setPageNumber(2)
        ;

        $bookmark4 = new Bookmark();
        $bookmark4
            ->setTitle('Example 4 - page 3')
            ->setPageNumber(3)
        ;

        $bm
            ->add($bookmark1)
            ->add($bookmark2)
            ->add($bookmark3)
            ->add($bookmark4)
        ;
        $this->assertSame(4, count($bm->all()));

        $bm->removeByPageNumber(2);
        $this->assertSame([$bookmark1, $bookmark4], $bm->all());
    }

    public function testClear()
    {
        $bm = new Bookmarks();

        $bookmark1 = new Bookmark();
        $bookmark1
            ->setTitle('Example 1 - page 1')
            ->setPageNumber(1)
        ;

        $bookmark2 = new Bookmark();
        $bookmark2
            ->setTitle('Example 2 - page 2')
            ->setPageNumber(2)
        ;

        $bm
            ->add($bookmark1)
            ->add($bookmark2)
        ;
        $this->assertSame(2, count($bm->all()));

        $bm->clear();
        $this->assertSame([], $bm->all());
    }

    /**
     * @group FunctionalTest
     */
    public function testOutputFileNotFound()
    {
        $file = __DIR__ . '/Fixtures/missing.pdf';
        $this->expectException(FileNotFoundException::Class);

        $bm = new Bookmarks();
        $bm->apply($file);
    }

    /**
     * @group FunctionalTest
     */
    public function testFileSetGet()
    {
        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $bookmark1 = new Bookmark();
        $bookmark1
            ->setTitle('1. Example Bookmark')
            ->setPageNumber(1)
            // default level 1 doesn't need to be set explicitly
        ;
        $bookmark2 = new Bookmark();
        $bookmark2
            ->setTitle('1.1. Why PDF bookmarks are awesome')
            ->setPageNumber(1)
            ->setLevel(2)
        ;
        $bookmark3 = new Bookmark();
        $bookmark3
            ->setTitle('1.2 How to set bookmarks')
            ->setPageNumber(1)
            ->setLevel(2)
        ;

        $bmSet = new Bookmarks();
        $bmSet->add($bookmark1)
              ->add($bookmark2)
              ->add($bookmark3)
              ->apply($source, $target);

        $bmGet = new Bookmarks();
        $bmGet->import($target);

        $this->assertSame('1. Example Bookmark', $bmGet->all()[0]->getTitle());
        $this->assertSame(1, $bmGet->all()[0]->getPageNumber());
        $this->assertSame(1, $bmGet->all()[0]->getLevel());

        $this->assertSame('1.1. Why PDF bookmarks are awesome', $bmGet->all()[1]->getTitle());
        $this->assertSame(1, $bmGet->all()[1]->getPageNumber());
        $this->assertSame(2, $bmGet->all()[1]->getLevel());

        $this->assertSame('1.2 How to set bookmarks', $bmGet->all()[2]->getTitle());
        $this->assertSame(1, $bmGet->all()[2]->getPageNumber());
        $this->assertSame(2, $bmGet->all()[2]->getLevel());

        unlink($target);
    }

    /**
     * @group FunctionalTest
     */
    public function testImportNotFound()
    {
        $file = __DIR__ . '/Fixtures/missing.pdf';

        $this->expectException(FileNotFoundException::Class);
        $bm = new Bookmarks();
        $bm->import($file);
    }

    /**
     * @group FunctionalTest
     */
    public function testImport()
    {
        $file = __DIR__ . '/Fixtures/example.pdf';
        $bm = new Bookmarks();
        $bm->import($file);

        $bookmarks = $bm->all();
        $this->assertSame(3, count($bookmarks));
        $bookmark1 = $bookmarks[0];
        $bookmark2 = $bookmarks[1];
        $bookmark3 = $bookmarks[2];

        $this->assertSame('Page 1 - Level 1', $bookmark1->getTitle());
        $this->assertSame(1, $bookmark1->getPageNumber());
        $this->assertSame(1, $bookmark1->getLevel());

        $this->assertSame('Page 2 - Level 1', $bookmark2->getTitle());
        $this->assertSame(2, $bookmark2->getPageNumber());
        $this->assertSame(1, $bookmark2->getLevel());

        $this->assertSame('Page 3 - Level 2', $bookmark3->getTitle());
        $this->assertSame(3, $bookmark3->getPageNumber());
        $this->assertSame(2, $bookmark3->getLevel());
    }
}
