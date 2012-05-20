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
 * Whether to load included file by default.
 */
define('XML_STRUCT_READER_LOAD_INCLUDED', FALSE);

/**
 * Default base path for included file names.
 */
define('XML_STRUCT_READER_INCLUDED_PATH', '.');

/**
 * Default factory class to create reader for included files.
 */
define('XML_STRUCT_READER_INCLUDED_READER_FACTORY', 'XMLStructReaderFactory');

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
 * Factory class for creating a reader.
 */
class XMLStructReaderFactory {
  /**
   * Creates a reader with options. See README.txt for a complete list of
   * options to initialize with.
   *
   * @param array $options
   *   Options for the reader.
   * @param XMLStructReader $owner
   *   Owner object for the new reader instance.
   */
  public function createReader(array $options = array(), $owner = NULL) {
    // TODO
  }
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
