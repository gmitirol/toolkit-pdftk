<?php
/**
 * General PDF exception.
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Martin Pircher <martin.pircher@i-med.ac.at>
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Exception;

use Exception;

/**
 * This exception should be thrown if a PDF operation fails.
 */
class PdfException extends Exception
{
    /**
     * @var string
     */
    private $pdfError;

    /**
     * @var string
     */
    private $pdfOutput;

    /**
     * Constructor.
     *
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     * @param string    $pdfError
     * @param string    $pdfOutput
     */
    public function __construct($message, $code = 0, Exception $previous = null, $pdfError = null, $pdfOutput = null)
    {
        parent::__construct($message, $code, $previous);

        $this->pdfError = $pdfError;
        $this->pdfOutput = $pdfOutput;
    }

    /**
     * Returns the concrete error message of the underlying PDF library.
     */
    public function getPdfError()
    {
        return $this->pdfError;
    }

    /**
     * Returns the concrete output of the underlying PDF library.
     */
    public function getPdfOutput()
    {
        return $this->pdfOutput;
    }
}
