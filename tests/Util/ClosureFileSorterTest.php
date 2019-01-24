<?php

namespace Gmi\Toolkit\Pdftk\Tests\Util;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\Util\ClosureFileSorter;

use SplFileInfo;

class ClosureFileSorterTest extends TestCase
{
    public function testSort()
    {
        $file1 = $this->createMock(SplFileInfo::class);
        $file1->expects($this->any())
              ->method('getSize')
              ->willReturn(42);

        $file2 = $this->createMock(SplFileInfo::class);
        $file2->expects($this->any())
              ->method('getSize')
              ->willReturn(67881);

        $file3 = $this->createMock(SplFileInfo::class);
        $file3->expects($this->any())
               ->method('getSize')
               ->willReturn(2354);

        $file4 = $this->createMock(SplFileInfo::class);
        $file4->expects($this->any())
               ->method('getSize')
               ->willReturn(32568);

        $files = [$file1, $file2, $file3, $file4];

        $sizeSorter = function (SplFileInfo $a, SplFileInfo $b) {
            return ($a->getSize() < $b->getSize());
        };

        $sorter = new ClosureFileSorter($sizeSorter);
        $this->assertSame($sizeSorter, $sorter->getClosure());
        $result = $sorter->sort($files);

        $expected = [$file2, $file4, $file3, $file1];
        $this->assertSame($expected, $result);
    }
}
