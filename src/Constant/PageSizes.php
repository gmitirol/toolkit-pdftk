<?php
/**
 * PageSizes
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Constant;

/**
 * Page size constants for the portrait orientation of the most common formats, with height and width in millimeters.
 *
 * @see https://www.adobe.com/uk/creativecloud/design/discover/a3-format.html
 */
class PageSizes
{
    public const A0_HEIGHT = 1189;
    public const A0_WIDTH = 841;

    public const A1_HEIGHT = 841;
    public const A1_WIDTH = 594;

    public const A2_HEIGHT = 594;
    public const A2_WIDTH = 420;

    public const A3_HEIGHT = 420;
    public const A3_WIDTH = 297;

    public const A4_HEIGHT = 297;
    public const A4_WIDTH = 210;

    public const A5_HEIGHT = 210;
    public const A5_WIDTH = 148;

    public const A6_HEIGHT = 148;
    public const A6_WIDTH = 105;
}
