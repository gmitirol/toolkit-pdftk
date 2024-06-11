<?php
/**
 * Process factory.
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @author Nikola Vrlazic <nikola.vrlazic@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Util;

use Symfony\Component\Process\Process;

/**
 * Factory class for creating Symfony process instances.
 *
 * @see https://symfony.com/doc/2.8/components/process.html
 */
class ProcessFactory
{
    const PROCESS_DEFAULT_TIMEOUT = 300;

    /**
     * Creates a process from command line.
     *
     * @param string $commandLine Command line of the process
     * @param int    $timeout     Symfony process timeout
     */
    public function createProcess(string $commandLine, int $timeout = self::PROCESS_DEFAULT_TIMEOUT): Process
    {
        /**
         * Support old (Symfony 2.7 to 4.1) and new (Symfony 4.2+) syntax to build Symfony Process instances.
         * @psalm-suppress InvalidArgument Psalm can only analyze the file of the currently used Symfony version
         */
        $process = $this->useNewSyntax() ? Process::fromShellCommandline($commandLine) : new Process($commandLine);
        $process->setTimeout($timeout);

        return $process;
    }

    /**
     * Returns whether the new syntax to build Symfony Process instances from a command line should be used.
     *
     * @see https://github.com/symfony/symfony/pull/27821
     */
    private function useNewSyntax(): bool
    {
        return method_exists(Process::class, 'fromShellCommandline');
    }
}
