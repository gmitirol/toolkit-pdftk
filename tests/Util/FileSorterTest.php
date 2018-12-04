<?php

namespace Gmi\Toolkit\Pdftk\Tests\Util;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\Util\FileSorter;

use Closure;
use SplFileInfo;

class FileSorterTest extends TestCase
{
    public function testSortNaturally()
    {
        $file1 = $this->createMock(SplFileInfo::class);
        $file1->expects($this->any())
              ->method('getRealPath')
              ->willReturn('file3.txt');

        $file2 = $this->createMock(SplFileInfo::class);
        $file2->expects($this->any())
              ->method('getRealPath')
              ->willReturn('file1.txt');

        $file3 = $this->createMock(SplFileInfo::class);
        $file3->expects($this->any())
               ->method('getRealPath')
               ->willReturn('file2.txt');

        $file4 = $this->createMock(SplFileInfo::class);
        $file4->expects($this->any())
               ->method('getRealPath')
               ->willReturn('file10.txt');

        $file5 = $this->createMock(SplFileInfo::class);
        $file5->expects($this->any())
               ->method('getRealPath')
               ->willReturn('file6.txt');

        $files = [$file1, $file2, $file3, $file4, $file5];

        $sorter = new FileSorter();
        $result = $sorter->sortNaturally($files);

        $expected = [$file2, $file3, $file1, $file5, $file4];
        $this->assertEquals($expected, $result);
    }

    public function testGetNaturalSorter()
    {
        $sorter = new FileSorter();
        $this->assertInstanceOf(Closure::class, $sorter->getNaturalSorter());
    }
}
