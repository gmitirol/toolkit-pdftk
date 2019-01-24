<?php
/**
 * Sorts files naturally.
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

use SplFileInfo;

/**
 * Sort different types of files from array (natural sorting, case insensitive).
 */
class NaturalFileSorter implements FileSorterInterface
{
    /**
     * @var ClosureFileSorter;
     */
    private $sorter;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $naturalSorter = function (SplFileInfo $a, SplFileInfo $b) {
            return strnatcasecmp($a->getRealPath(), $b->getRealPath());
        };

        $this->sorter = new ClosureFileSorter($naturalSorter);
    }

    /**
     * Sort files using natural order.
     *
     * @param SplFileInfo[] $fileInfos
     *
     * @return SplFileInfo[]
     */
    public function sort(array $fileInfos)
    {
        return $this->sorter->sort($fileInfos);
    }
}
