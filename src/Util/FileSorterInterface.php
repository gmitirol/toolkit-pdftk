<?php
/**
 * Interface for file sorters.
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Util;

use SplFileInfo;

/**
 * Interface for file sorters.
 */
interface FileSorterInterface
{
    /**
     * Sort files.
     *
     * @param SplFileInfo[] $fileInfos
     *
     * @return SplFileInfo[]
     */
    public function sort(array $fileInfos);
}
