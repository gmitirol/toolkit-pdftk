<?php
/**
 * FileChecker.
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk\Util;

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;

/**
 * Checks whether a file exists.
 */
class FileChecker
{
    /**
     * Checks whether a file exists.
     */
    public function checkFileExists(string $file, string $message = 'File "%s" not found!'): void
    {
        $exceptionMessage = (false !== strpos($message, '%s')) ? sprintf($message, $file) : $message;

        if (!file_exists($file)) {
            throw new FileNotFoundException($exceptionMessage);
        }
    }

    /**
     * Checks whether a PDF file exists.
     */
    public function checkPdfFileExists(string $file): void
    {
        $this->checkFileExists($file, 'PDF "%s" not found!');
    }
}
