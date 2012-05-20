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
 * Option: whether to load included file.
 *
 * Possible values:
 * - TRUE
 * - FALSE (default)
 */
define('XML_STRUCT_READER_OPTION_INCLUDED_LOAD', 'includedLoad');

/**
 * Default base path for included file names.
 *
 * Possible values:
 * - Any valid directory path string. Default: . (current working directory)
 */
define('XML_STRUCT_READER_OPTION_INCLUDED_PATH', 'includedPath');

/**
 * Factory class to create reader for included files.
 *
 * Possible values:
 * - Any valid findable class name. Default: XMLStructReaderFactory
 */
define('XML_STRUCT_READER_OPTION_INCLUDED_READER_FACTORY', 'includedReaderFactory');

/**
 * Option: whether to use the same set of options for the reader of an included
 * XML file.
 *
 * Possible values:
 * - TRUE (default)
 * - FALSE
 */
define('XML_STRUCT_READER_OPTION_INCLUDED_SAME_OPTIONS', 'includedUseSameOptions');

/**
 * Main implementation of XML structured array parser.
 *
 * XMLStructReader can be used to read an XML into an array, optionally with
 * annotations on the document itself to adjust the resulting structure.
 */
class XMLStructReader {
  /**
   * Reader default options.
   */
  protected $defaultOptions = array(
    // Specify default options as documented.
    XML_STRUCT_READER_OPTION_INCLUDED_LOAD => FALSE,
    XML_STRUCT_READER_OPTION_INCLUDED_PATH => '.',
    XML_STRUCT_READER_OPTION_INCLUDED_READER_FACTORY => 'XMLStructReaderFactory',
    XML_STRUCT_READER_OPTION_INCLUDED_SAME_OPTIONS => TRUE,
  );

  /**
   * Reader options.
   * @var array
   */
  protected $options;

  /**
   * XML parser handle.
   * @var resource
   */
  protected $parser;

  /**
   * Creates a reader with options.
   *
   * @param array $options
   *   Options for the reader. For possible option keys, see self::setOption().
   */
  public function __construct(array $options = array()) {
    $this->options = $this->defaultOptions;
    $this->setOptions($options);
    $this->setUp();
  }

  /**
   * Sets an option on the reader.
   *
   * @param $option
   *   An option name. Possible values:
   *   - XML_STRUCT_READER_OPTION_INCLUDED_LOAD
   *   - XML_STRUCT_READER_OPTION_INCLUDED_PATH
   *   - XML_STRUCT_READER_OPTION_INCLUDED_READER_FACTORY
   * @param mixed $value
   *   - Option value.
   */
  public function setOption($option, $value) {
    if (array_key_exists($option, $this->defaultOptions)) {
      $this->options[$option] = $value;
    }
  }

  /**
   * Sets a number of option on the reader.
   *
   * @param array $options
   *   Options to set. For possible option keys, see self::setOption().
   */
  public function setOptions(array $options) {
    foreach ($options as $option => $value) {
      $this->setOption($option, $value);
    }
  }

  /**
   * Sets up the created parser.
   */
  protected function setUp() {
    $this->parser = xml_parser_create_ns();
    xml_set_object($this->parser, $this);
  }
}

/**
 * Factory class for creating a reader.
 */
class XMLStructReaderFactory {
  /**
   * Creates a reader with options. See XMLStructReader::__construct() for a
   * list of options to initialize with.
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
