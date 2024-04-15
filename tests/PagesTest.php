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

use Gmi\Toolkit\Pdftk\Pages;
use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;

class PagesTest extends TestCase
{
    const A4_HEIGHT = 297;
    const A4_WIDTH = 210;

    const A3_HEIGHT = 420;
    const A3_WIDTH = 297;

    public function testImportNotFound()
    {
        $file = __DIR__ . '/Fixtures/missing.pdf';

        $this->expectException(FileNotFoundException::Class);
        $p = new Pages();
        $p->import($file);
    }

    public function testImportA4()
    {
        $file = __DIR__ . '/Fixtures/a4.pdf';
        $p = new Pages();
        $p->import($file);

        $pages = $p->all();
        $this->assertSame(1, count($pages));
        $page1 = $pages[0];

        $this->assertSame(595.32, $page1->getWidth());
        $this->assertSame(841.92, $page1->getHeight());
        $this->assertSame(0, $page1->getRotation());
        $this->assertSame(1, $page1->getPageNumber());
    }

    public function testImport()
    {
        $file = __DIR__ . '/Fixtures/pages.pdf';
        $p = new Pages();
        $p->import($file);

        $pages = $p->all();
        $this->assertSame(6, count($pages));
        $page1 = $pages[0];
        $page2 = $pages[1];
        $page3 = $pages[2];
        $page4 = $pages[3];
        $page5 = $pages[4];
        $page6 = $pages[5];

        $this->assertSame(self::A4_WIDTH, $page1->getWidthMm());
        $this->assertSame(self::A4_HEIGHT, $page1->getHeightMm());
        $this->assertSame(0, $page1->getRotation());

        $this->assertSame(self::A4_WIDTH, $page2->getWidthMm());
        $this->assertSame(self::A4_HEIGHT, $page2->getHeightMm());
        $this->assertSame(270, $page2->getRotation());

        $this->assertSame(self::A4_HEIGHT, $page3->getWidthMm());
        $this->assertSame(self::A4_WIDTH, $page3->getHeightMm());
        $this->assertSame(0, $page3->getRotation());

        $this->assertSame(self::A3_WIDTH, $page4->getWidthMm());
        $this->assertSame(self::A3_HEIGHT, $page4->getHeightMm());
        $this->assertSame(0, $page4->getRotation());

        $this->assertSame(self::A3_WIDTH, $page5->getWidthMm());
        $this->assertSame(self::A3_HEIGHT, $page5->getHeightMm());
        $this->assertSame(90, $page5->getRotation());

        $this->assertSame(self::A3_HEIGHT, $page6->getWidthMm());
        $this->assertSame(self::A3_WIDTH, $page6->getHeightMm());
        $this->assertSame(0, $page6->getRotation());
    }

    public function testImportMixedHugePage()
    {
        $file = __DIR__ . '/Fixtures/mixed-hugepage.pdf';
        $p = new Pages();
        $p->import($file);

        $pages = $p->all();
        $this->assertSame(2, count($pages));

        $page1 = $pages[0];
        $this->assertSame(self::A4_WIDTH, $page1->getWidthMm());
        $this->assertSame(self::A4_HEIGHT, $page1->getHeightMm());
        $this->assertSame(0, $page1->getRotation());
        $this->assertSame(1, $page1->getPageNumber());

        $page2 = $pages[1];
        $this->assertSame(1189, $page2->getWidthMm());
        $this->assertSame(841, $page2->getHeightMm());
        $this->assertSame(0, $page2->getRotation());
        $this->assertSame(2, $page2->getPageNumber());
    }
}
