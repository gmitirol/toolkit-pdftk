<?php
/**
 * pdfcpu wrapper factory test
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

use Gmi\Toolkit\Pdftk\PdfcpuV11Wrapper;
use Gmi\Toolkit\Pdftk\PdfcpuV12Wrapper;
use Gmi\Toolkit\Pdftk\PdfcpuWrapperFactory;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdfcpuWrapperFactoryTest extends TestCase
{
    private const V11_OUTPUT = "pdfcpu: v0.11.1 dev\n"
        . "commit: c4b560d (2025-10-21T21:18:35Z)\n"
        . "base  : go1.25.3\n"
        . "config: /root/.config/pdfcpu/config.yml\n";

    private const V12_OUTPUT = "\n"
        . "**************************** WARNING ****************************\n"
        . "* Your configuration is not based on the current major version. *\n"
        . "*        Please backup and then reset your configuration:       *\n"
        . "*                     \$ pdfcpu config reset                     *\n"
        . "*****************************************************************\n"
        . "pdfcpu: v0.12.1 dev\n"
        . "commit: 148d18d4 (2026-05-11T13:14:47Z)\n"
        . "config: /root/.config/pdfcpu/config.yml\n"
        . "base  : go1.26.1\n";

    public function testCreateReturnsV11WrapperForV011()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $factory = new PdfcpuWrapperFactory();
        $wrapper = $factory::create($binary, $this->mockProcessFactory($binary, self::V11_OUTPUT));

        $this->assertInstanceOf(PdfcpuV11Wrapper::class, $wrapper);
    }

    public function testCreateReturnsV12WrapperForV012()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $wrapper = PdfcpuWrapperFactory::create($binary, $this->mockProcessFactory($binary, self::V12_OUTPUT));

        $this->assertInstanceOf(PdfcpuV12Wrapper::class, $wrapper);
    }

    public function testCreateReturnsV12WrapperForFutureMajorVersion()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $wrapper = PdfcpuWrapperFactory::create(
            $binary,
            $this->mockProcessFactory($binary, "pdfcpu: v1.0.0 dev\n")
        );

        $this->assertInstanceOf(PdfcpuV12Wrapper::class, $wrapper);
    }

    /**
     * @dataProvider provideVersionStrings
     */
    public function testDetectVersionParsesMajorMinor(string $output, int $major, int $minor)
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $version = PdfcpuWrapperFactory::detectVersion($binary, $this->mockProcessFactory($binary, $output));

        $this->assertSame($major, $version['major']);
        $this->assertSame($minor, $version['minor']);
    }

    public function provideVersionStrings(): array
    {
        return [
            'v0.11.1' => [self::V11_OUTPUT, 0, 11],
            'v0.12.1 with banner' => [self::V12_OUTPUT, 0, 12],
            'v0.10.0' => ["pdfcpu: v0.10.0\n", 0, 10],
            'v1.0.0' => ["pdfcpu: v1.0.0\n", 1, 0],
            'v0.12.0 no banner' => ["pdfcpu: v0.12.0 dev\ncommit: abc\n", 0, 12],
        ];
    }

    public function testDetectVersionThrowsWhenBinaryFails()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $exception = new Exception('command failed');

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn('stderr');
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn('stdout');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with(sprintf("'%s' version", $binary))
                           ->willReturn($mockProcess);

        try {
            PdfcpuWrapperFactory::detectVersion($binary, $mockProcessFactory);
            $this->fail('Expected PdfException was not thrown');
        } catch (PdfException $e) {
            $this->assertStringStartsWith(
                sprintf('Failed to detect pdfcpu version from "%s"!', $binary),
                $e->getMessage()
            );
            $this->assertSame($exception, $e->getPrevious());
            $this->assertSame('stderr', $e->getPdfError());
            $this->assertSame('stdout', $e->getPdfOutput());
        }
    }

    /**
     * @dataProvider provideUnparseableOutput
     */
    public function testDetectVersionThrowsWhenOutputCannotBeParsed(string $output)
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $this->expectException(PdfException::class);
        $this->expectExceptionMessage(sprintf('Failed to parse pdfcpu version from "%s"!', $binary));

        PdfcpuWrapperFactory::detectVersion($binary, $this->mockProcessFactory($binary, $output));
    }

    public function provideUnparseableOutput(): array
    {
        return [
            'empty' => [''],
            'unrelated text' => ["hello world\n"],
            'missing v prefix' => ["pdfcpu: 0.12.1\n"],
            'version not at line start' => ['the pdfcpu: v0.12.1 string'],
        ];
    }

    public function testCreateUsesRealBinaryWhenNoProcessFactoryGiven()
    {
        $binary = '/usr/local/bin/pdfcpu_0.11.1';
        if (!is_executable($binary)) {
            $this->markTestSkipped(sprintf('pdfcpu v0.11 binary not found at %s', $binary));
        }

        $wrapper = PdfcpuWrapperFactory::create($binary);
        $this->assertInstanceOf(PdfcpuV11Wrapper::class, $wrapper);
    }

    public function testCreateUsesRealBinaryV12()
    {
        $binary = '/usr/local/bin/pdfcpu_0.12.1';
        if (!is_executable($binary)) {
            $this->markTestSkipped(sprintf('pdfcpu v0.12 binary not found at %s', $binary));
        }

        $wrapper = PdfcpuWrapperFactory::create($binary);
        $this->assertInstanceOf(PdfcpuV12Wrapper::class, $wrapper);
    }

    private function mockProcessFactory(string $binary, string $output): ProcessFactory
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');
        $mockProcess->expects($this->any())
                    ->method('getOutput')
                    ->willReturn($output);

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with(sprintf("'%s' version", $binary))
                           ->willReturn($mockProcess);

        return $mockProcessFactory;
    }
}
