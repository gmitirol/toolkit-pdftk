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

use Gmi\Toolkit\Pdftk\Metadata;
use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\PdfTk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\PdfcpuWrapper;
use Gmi\Toolkit\Pdftk\PdftkWrapper;

class PdftkMetadataTest extends TestCase
{
    public function testSetterGetter()
    {
        $meta = new Metadata();

        $this->assertSame(
            'mplx',
            $meta->set('Creator', 'mplx')
                 ->get('Creator')
        );

        $this->assertSame(
            false,
            $meta->set('Creator', 'mplx')
                 ->remove('Creator')
                 ->get('Creator')
        );

        $this->assertTrue(
            $meta->set('Creator', 'mplx')
                 ->has('Creator')
        );

        $this->assertContains(
            'Creator',
            $meta->set('Creator', 'mplx')
                 ->keys()
        );
    }

    public function testKeys()
    {
        $meta = new Metadata();
        $meta->set('Creator', 'mplx')
             ->set('Producer', 'example');

        $this->assertSame(['Creator', 'Producer'], $meta->keys());
    }

    public function testAll()
    {
        $meta = new Metadata();
        $meta->set('Creator', 'mplx')
             ->set('Producer', 'example');

        $this->assertSame(['Creator' => 'mplx', 'Producer' => 'example'], $meta->all());
    }

    public function testSetInvalidKey()
    {
        $meta = new Metadata();

        $this->expectException(PdfException::Class);
        $meta->set(0, 'mplx');
    }

    public function testGetInvalidKey()
    {
        $meta = new Metadata();

        $this->expectException(PdfException::Class);
        $meta->get(0);
    }

    public function testCheckInvalidKey()
    {
        $meta = new Metadata();

        $this->expectException(PdfException::Class);
        $meta->has(0);
    }

    public function testRemoveInvalidKey()
    {
        $meta = new Metadata();

        $this->expectException(PdfException::Class);
        $meta->remove(0);
    }

    public function testClear()
    {
        $meta = new Metadata();

        $this->assertSame(
            false,
            $meta->set('Producer', 'mplx')
                 ->clear()
                 ->get('Producer')
        );
    }

    /**
     * @group FunctionalTest
     * @todo Add data provider when pdfcpu supports setting metadata
     */
    public function testOutputFileNotFound()
    {
        $file = __DIR__ . '/Fixtures/missing.pdf';
        $this->expectException(FileNotFoundException::Class);

        $meta = new Metadata();
        $meta->set('Creator', 'mplx')
                ->apply($file);
    }

    /**
     * @group FunctionalTest
     * @todo Add data provider when pdfcpu supports setting metadata
     */
    public function testFileSetGet()
    {
        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $randomText = microtime(false);

        $metaSet = new Metadata();
        $metaSet->set('Creator', $randomText . 'C')
                ->set('Producer', $randomText . 'P')
                ->apply($source, $target);

        $metaGet = new Metadata();
        $metaGet->import($target);

        $this->assertSame($randomText . 'C', $metaGet->get('Creator'));
        $this->assertSame($randomText . 'P', $metaGet->get('Producer'));

        unlink($target);
    }

    /**
     * @group FunctionalTest
     * @todo Add data provider when pdfcpu supports setting metadata
     */
    public function testFileSetGetFilenameWithSpaces()
    {
        $source = __DIR__ . '/Fixtures/empty.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf with space');

        $randomText = microtime(false);

        $metaSet = new Metadata();
        $metaSet->set('Creator', $randomText . 'C')
                ->set('Producer', $randomText . 'P')
                ->apply($source, $target);

        $metaGet = new Metadata();
        $metaGet->import($target);

        $this->assertSame($randomText . 'C', $metaGet->get('Creator'));
        $this->assertSame($randomText . 'P', $metaGet->get('Producer'));

        unlink($target);
    }

    /**
     * @group FunctionalTest
     * @todo Add data provider when pdfcpu supports setting metadata
     */
    public function testFileSetGetSameFile()
    {
        $source = __DIR__ . '/Fixtures/example.pdf';

        $testPdf = $target = tempnam(sys_get_temp_dir(), 'pdf');

        copy($source, $testPdf);

        $randomText = microtime(false);

        $metaSet = new Metadata();
        $metaSet->set('Creator', $randomText . 'C')
                ->set('Producer', $randomText . 'P')
                ->apply($testPdf);

        $metaGet = new Metadata();
        $metaGet->import($testPdf);

        $this->assertSame($randomText . 'C', $metaGet->get('Creator'));
        $this->assertSame($randomText . 'P', $metaGet->get('Producer'));

        unlink($testPdf);
    }

    /**
     * @group FunctionalTest
     * @dataProvider getWrapperImplementations
     */
    public function testImport($wrapper)
    {
        $source = __DIR__ . '/Fixtures/example.pdf';

        $meta = new Metadata($wrapper);
        $meta->import($source);
        $this->assertSame('author', $meta->get('Author'));
        $this->assertSame('creator', $meta->get('Creator'));
        $this->assertSame('producer', $meta->get('Producer'));
    }

    public function getWrapperImplementations(): array
    {
        return [
            [new PdftkWrapper()],
            [new PdfcpuWrapper()],
        ];
    }
}
