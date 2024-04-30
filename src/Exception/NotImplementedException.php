<?php
/**
 * Not implemented exception.
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Exception;

/**
 * This exception should be thrown if a PDF tool implementation does not support the requested operation.
 */
class NotImplementedException extends PdfException
{
}