<?php
/**
 * Process factory.
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
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
    /**
     * Creates a process from command line.
     *
     * @param string $commandLine
     *
     * @return Process
     */
    public function createProcess($commandLine)
    {
        return new Process($commandLine);
    }
}
