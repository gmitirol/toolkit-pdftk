<?php
/**
 * pdfcpu wrapper for pdfcpu v0.12 and newer
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

/**
 * Wrapper for pdfcpu v0.12 and newer, which uses POSIX-style long flags (e.g. `--pages`, `--replace`).
 *
 * @internal Only the methods exposed by the interfaces should be accessed from outside.
 */
final class PdfcpuV12Wrapper extends AbstractPdfcpuWrapper
{
    protected const PAGES_FLAG = '--pages';
    protected const REPLACE_FLAG = '--replace';
}
