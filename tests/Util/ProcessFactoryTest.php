<?php

namespace Gmi\Toolkit\Pdftk\Tests\Util;

use Symfony\Component\Process\Process;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

class ProcessFactoryTest extends TestCase
{
    public function testCreateProcess()
    {
        $factory = new ProcessFactory();

        $commandLine = 'ls -lsa';

        $process = $factory->createProcess($commandLine);

        $this->assertInstanceOf(Process::class, $process);
        $this->assertSame($commandLine, $process->getCommandLine());
    }
}
