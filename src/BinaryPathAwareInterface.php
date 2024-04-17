<?php
/**
 * BinaryPathAwareInterface
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Martin Pircher <martin.pircher@i-med.ac.at>
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;

interface BinaryPathAwareInterface
{
    /**
     * Set tool binary to use.
     *
     * @throws FileNotFoundException
     */
    public function setBinary(string $binary);

    /**
     * Get current used tool binary.
     *
     * @param bool $escaped Whether the binary path should be shell escaped
     */
    public function getBinary(bool $escaped = true): string;
}
