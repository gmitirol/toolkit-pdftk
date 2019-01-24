<?php
/**
 * Sorts files using a closure.
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
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
 * Sort different types of files using a closure for the actual sorting.
 */
class ClosureFileSorter implements FileSorterInterface
{
    /**
     * @var Closure
     */
    private $closure;

    /**
     * Constructor.
     *
     * @param Closure $closure Anoymous function which can be used by uasort().
     */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
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
        uasort($fileInfos, $this->closure);

        return array_values($fileInfos);
    }

    /**
     * Returns the closure.
     *
     * This method is not part of the FileSorterInterface, but can be used for testing/debugging.
     *
     * @return Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }
}
