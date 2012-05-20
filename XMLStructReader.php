<?php
/**
 * Default included file reader class.
 */
define('XML_STRUCT_READER_INCLUDED_READER_CLASS', 'XMLStructReader');

/**
 * XML structured array parser.
 *
 * XMLStructReader can be used to read an XML into an array, optionally with
 * annotations on the document itself to adjust the resulting structure.
 *
 * @package  XMLStructArray
 * @author   Taihao Zhang <jason@zth.me>
 */
class XMLStructReader {
  // TODO
}

/**
 * File delegate for handling operations across a file resource and a
 * SplFileObject instance.
 *
 * @package     XMLStructArray
 * @subpackage  Utility
 * @author      Taihao Zhang <jason@zth.me>
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
