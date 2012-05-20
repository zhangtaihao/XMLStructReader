<?php
/**
 * XML structured array parser.
 *
 * @package  XMLStructArray
 * @author   Taihao Zhang <jason@zth.me>
 * @license  GNU General Public License v3.0
 * @license  LICENSE.txt
 */

/**
 * Default base path for included file names.
 */
define('XML_STRUCT_READER_INCLUDED_PATH', '.');
/**
 * Default included file reader class.
 */
define('XML_STRUCT_READER_INCLUDED_READER_CLASS', 'XMLStructReader');

/**
 * Main implementation of XML structured array parser.
 *
 * XMLStructReader can be used to read an XML into an array, optionally with
 * annotations on the document itself to adjust the resulting structure.
 */
class XMLStructReader {
  // TODO
}

/**
 * File delegate for handling operations across a file resource and a
 * SplFileObject instance.
 *
 * @subpackage  Utility
 */
class XMLStructReader_FileDelegate {
  /**
   * File resource handle.
   * @var resource
   */
  protected $resource;

  /**
   * File object.
   * @var SplFileObject
   */
  protected $object;

  /**
   * Creates a delegate given a handle or object.
   *
   * @param mixed $file
   *   File handle or SplFileObject.
   */
  public function __construct($file) {
    if (is_resource($file) && get_resource_type($file) == 'stream') {
      $this->resource = $file;
    }
    elseif (class_exists('SplFileObject') && is_object($file) && $file
        instanceof SplFileObject) {
      $this->object = $file;
    }
  }
}
