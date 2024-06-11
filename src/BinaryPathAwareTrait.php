<?php
/**
 * BinaryPathAwareTrait
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
use Gmi\Toolkit\Pdftk\Util\FileChecker;

/**
 * @internal
 */
trait BinaryPathAwareTrait
{
    /**
     * @var string PDF tool binary including full path
     */
    private $binaryPath;

    /**
     * Set PDF tool binary to use.
     *
     * @throws FileNotFoundException
     */
    public function setBinary(string $binaryPath): self
    {
        $fileChecker = new FileChecker();
        $fileChecker->checkFileExists($binaryPath, 'Binary "%s" not found!');

        $this->binaryPath = $binaryPath;

        return $this;
    }

    /**
     * Get currently used PDF tool binary.
     *
     * @param bool $escaped Whether the binary path should be shell escaped
     */
    public function getBinary(bool $escaped = true): string
    {
        return $escaped ? escapeshellarg($this->binaryPath) : $this->binaryPath;
    }
}
