<?php
/**
 * Sorts files.
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Nikola Vrlazic <nikola.vrlazic@i-med.ac.at>
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Util;

use Closure;
use SplFileInfo;

/**
 * Sort different types of files from array.
 */
class FileSorter
{
    /**
     * @var Closure
     */
    private $naturalSorter;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->naturalSorter = function (SplFileInfo $a, SplFileInfo $b) {
            return strnatcasecmp($a->getRealPath(), $b->getRealPath());
        };
    }

    /**
     * Sort files using natural order.
     *
     * @param SplFileInfo[] $fileInfos
     *
     * @return SplFileInfo[]
     */
    public function sortNaturally(array $fileInfos)
    {
        uasort($fileInfos, $this->naturalSorter);

        return $fileInfos;
    }

    /**
     * Returns natural sorter.
     *
     * @return Closure
     */
    public function getNaturalSorter()
    {
        return $this->naturalSorter;
    }
}
