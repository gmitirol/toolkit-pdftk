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
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\PdftkWrapper;

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
     * @dataProvider getWrapperImplementations
     */
    public function testOutputFileNotFound($wrapper)
    {
        $file = __DIR__ . '/Fixtures/missing.pdf';
        $this->expectException(FileNotFoundException::Class);

        $bm = new Bookmarks($wrapper);
        $bm->apply($file);
    }

    /**
     * @group FunctionalTest
     * @dataProvider getWrapperImplementations
     */
    public function testFileSetGet($wrapper)
    {
        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

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

        $bmSet = new Bookmarks($wrapper);
        $bmSet->add($bookmark1)
              ->add($bookmark2)
              ->add($bookmark3)
              ->apply($source, $target);

        $bmGet = new Bookmarks($wrapper);
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
     * @dataProvider getWrapperImplementations
     */
    public function testFileSetGetNestedBookmarks($wrapper)
    {
        $source = __DIR__ . '/Fixtures/three-pages.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';

        $bookmark1 = new Bookmark();
        $bookmark1
            ->setTitle('1. Lorem Ipsum')
            ->setPageNumber(1)
            // default level 1 doesn't need to be set explicitly
        ;
        $bookmark2 = new Bookmark();
        $bookmark2
            ->setTitle('1.1. Sed diam nonumy')
            ->setPageNumber(1)
            ->setLevel(2)
        ;
        $bookmark3 = new Bookmark();
        $bookmark3
            ->setTitle('1.1.1. Et justo duo dolores')
            ->setPageNumber(2)
            ->setLevel(3)
        ;

        $bookmark4 = new Bookmark();
        $bookmark4
            ->setTitle('1.1.2. Ullamcorper suscipit')
            ->setPageNumber(2)
            ->setLevel(3)
        ;
        $bookmark5 = new Bookmark();
        $bookmark5
            ->setTitle('1.2. Magna no rebum')
            ->setPageNumber(3)
            ->setLevel(2)
        ;
        $bookmark6 = new Bookmark();
        $bookmark6
            ->setTitle('2. Nam liber tempor')
            ->setPageNumber(3)
        ;

        $bmSet = new Bookmarks($wrapper);
        $bmSet->add($bookmark1)
              ->add($bookmark2)
              ->add($bookmark3)
              ->add($bookmark4)
              ->add($bookmark5)
              ->add($bookmark6)
              ->apply($source, $target);

        $bmGet = new Bookmarks($wrapper);
        $bmGet->import($target);

        $this->assertCount(6, $bmGet->all());

        $this->assertSame('1. Lorem Ipsum', $bmGet->all()[0]->getTitle());
        $this->assertSame(1, $bmGet->all()[0]->getPageNumber());
        $this->assertSame(1, $bmGet->all()[0]->getLevel());

        $this->assertSame('1.1. Sed diam nonumy', $bmGet->all()[1]->getTitle());
        $this->assertSame(1, $bmGet->all()[1]->getPageNumber());
        $this->assertSame(2, $bmGet->all()[1]->getLevel());

        $this->assertSame('1.1.1. Et justo duo dolores', $bmGet->all()[2]->getTitle());
        $this->assertSame(2, $bmGet->all()[2]->getPageNumber());
        $this->assertSame(3, $bmGet->all()[2]->getLevel());

        $this->assertSame('1.1.2. Ullamcorper suscipit', $bmGet->all()[3]->getTitle());
        $this->assertSame(2, $bmGet->all()[3]->getPageNumber());
        $this->assertSame(3, $bmGet->all()[3]->getLevel());

        $this->assertSame('1.2. Magna no rebum', $bmGet->all()[4]->getTitle());
        $this->assertSame(3, $bmGet->all()[4]->getPageNumber());
        $this->assertSame(2, $bmGet->all()[4]->getLevel());

        $this->assertSame('2. Nam liber tempor', $bmGet->all()[5]->getTitle());
        $this->assertSame(3, $bmGet->all()[5]->getPageNumber());
        $this->assertSame(1, $bmGet->all()[5]->getLevel());

        unlink($target);
    }

    /**
     * @group FunctionalTest
     * @dataProvider getWrapperImplementations
     */
    public function testImportNotFound($wrapper)
    {
        $file = __DIR__ . '/Fixtures/missing.pdf';

        $this->expectException(FileNotFoundException::Class);
        $bm = new Bookmarks($wrapper);
        $bm->import($file);
    }

    /**
     * @group FunctionalTest
     * @dataProvider getWrapperImplementations
     */
    public function testImportFileWithoutBookmarks($wrapper)
    {
        $file = __DIR__ . '/Fixtures/empty.pdf';
        $bm = new Bookmarks($wrapper);
        $bm->import($file);

        $bookmarks = $bm->all();
        $this->assertSame(0, count($bookmarks));
        $this->assertSame([], $bm->all());
    }

    /**
     * @group FunctionalTest
     * @dataProvider getWrapperImplementations
     */
    public function testImport($wrapper)
    {
        $file = __DIR__ . '/Fixtures/example.pdf';
        $bm = new Bookmarks($wrapper);
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

    /**
     * @group FunctionalTest
     * @dataProvider getWrapperImplementations
     */
    public function testImportNestedBookmarks($wrapper)
    {
        $file = __DIR__ . '/Fixtures/nested-bookmarks.pdf';
        $bm = new Bookmarks($wrapper);
        $bm->import($file);

        $bookmarks = $bm->all();
        $this->assertSame(12, count($bookmarks));
        $bookmark1 = $bookmarks[0];
        $bookmark2 = $bookmarks[1];
        $bookmark3 = $bookmarks[2];
        $bookmark4 = $bookmarks[3];
        $bookmark5 = $bookmarks[4];
        $bookmark6 = $bookmarks[5];
        $bookmark7 = $bookmarks[6];
        $bookmark8 = $bookmarks[7];
        $bookmark9 = $bookmarks[8];
        $bookmark10 = $bookmarks[9];
        $bookmark11 = $bookmarks[10];
        $bookmark12 = $bookmarks[11];

        $this->assertSame('1 Lorem ipsum dolor sit amet', $bookmark1->getTitle());
        $this->assertSame(1, $bookmark1->getPageNumber());
        $this->assertSame(1, $bookmark1->getLevel());

        $this->assertSame('2 Duis autem', $bookmark2->getTitle());
        $this->assertSame(1, $bookmark2->getPageNumber());
        $this->assertSame(1, $bookmark2->getLevel());

        $this->assertSame('2.1 Ut wisi', $bookmark3->getTitle());
        $this->assertSame(1, $bookmark3->getPageNumber());
        $this->assertSame(2, $bookmark3->getLevel());

        $this->assertSame('2.1.1 Nam liber', $bookmark4->getTitle());
        $this->assertSame(1, $bookmark4->getPageNumber());
        $this->assertSame(3, $bookmark4->getLevel());

        $this->assertSame('2.1.2 Duis autem', $bookmark5->getTitle());
        $this->assertSame(2, $bookmark5->getPageNumber());
        $this->assertSame(3, $bookmark5->getLevel());

        $this->assertSame('2.1.3 At vero eos', $bookmark6->getTitle());
        $this->assertSame(2, $bookmark6->getPageNumber());
        $this->assertSame(3, $bookmark6->getLevel());

        $this->assertSame('2.2 At accusam', $bookmark7->getTitle());
        $this->assertSame(2, $bookmark7->getPageNumber());
        $this->assertSame(2, $bookmark7->getLevel());

        $this->assertSame('3 Consetetur sadipscing elitr', $bookmark8->getTitle());
        $this->assertSame(2, $bookmark8->getPageNumber());
        $this->assertSame(1, $bookmark8->getLevel());

        $this->assertSame('3.1 Duis autem vel eum', $bookmark9->getTitle());
        $this->assertSame(2, $bookmark9->getPageNumber());
        $this->assertSame(2, $bookmark9->getLevel());

        $this->assertSame('3.1.1 Ut wisi enim', $bookmark10->getTitle());
        $this->assertSame(3, $bookmark10->getPageNumber());
        $this->assertSame(3, $bookmark10->getLevel());

        $this->assertSame('3.1.2 Duis autem vel eum', $bookmark11->getTitle());
        $this->assertSame(3, $bookmark11->getPageNumber());
        $this->assertSame(3, $bookmark11->getLevel());

        $this->assertSame('4 Consetetur sadipscing elitr', $bookmark12->getTitle());
        $this->assertSame(3, $bookmark12->getPageNumber());
        $this->assertSame(1, $bookmark12->getLevel());
    }

    public function getWrapperImplementations(): array
    {
        return [
            [new PdftkWrapper()],
            [new PdfcpuWrapper()],
        ];
    }
}
