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
   * Namespace URI.
   */
  const NS = 'http://xml.zth.me/XMLStructReader/';

  /**
   * Reader default options.
   */
  protected $defaultOptions = array(
    // Specify default options as documented.
    XML_STRUCT_READER_OPTION_INCLUDED_LOAD => FALSE,
    XML_STRUCT_READER_OPTION_INCLUDED_PATH => NULL,
    XML_STRUCT_READER_OPTION_INCLUDED_READER_FACTORY => 'XMLStructReaderFactory',
    XML_STRUCT_READER_OPTION_INCLUDED_SAME_OPTIONS => TRUE,
  );

  /**
   * File delegate to parse.
   * @var XMLStructReader_StreamDelegate
   */
  protected $fileDelegate;

  /**
   * Reader options.
   * @var array
   */
  protected $options;

  /**
   * Reader context when parsing.
   * @var array
   */
  protected $context;

  /**
   * XML parser handle.
   * @var resource
   */
  protected $parser;

  /**
   * Creates a reader with options.
   *
   * @param XMLStructReader_StreamDelegate $fileDelegate
   *   Delegate object for a file to parse.
   * @param array $options
   *   Options for the reader. For possible option keys, see self::setOption().
   * @param array $context
   *   Parse context for the reader. Used internally to specify metadata about
   *   the base context to use when parsing with the created reader.
   */
  public function __construct($fileDelegate, array $options = array(), array $context = array()) {
    $this->fileDelegate = $fileDelegate;
    $this->options = $this->defaultOptions;
    $this->setOptions($options);
    $this->setContext($context);
    $this->setUp();
  }

  /**
   * Frees resources.
   */
  public function __destruct() {
    $this->cleanUp();
  }

  /**
   * Sets up the created parser.
   */
  protected function setUp() {
    // Set up parser.
    $this->parser = $this->createParser();
  }

  /**
   * Cleans up the object.
   */
  protected function cleanUp() {
    if (isset($this->parser)) {
      @xml_parser_free($this->parser);
      $this->parser = NULL;
    }
  }

  /**
   * Sets an option on the reader.
   *
   * @param $option
   *   An option name. Possible values:
   *   - XML_STRUCT_READER_OPTION_INCLUDED_LOAD
   *   - XML_STRUCT_READER_OPTION_INCLUDED_PATH
   *   - XML_STRUCT_READER_OPTION_INCLUDED_READER_FACTORY
   *   - XML_STRUCT_READER_OPTION_INCLUDED_SAME_OPTIONS
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
   * Resets all options to default.
   */
  public function resetOptions() {
    $this->setOptions($this->defaultOptions);
  }

  /**
   * Creates a new parser for use with this object.
   *
   * @return resource
   *   Handle to the parser.
   */
  protected function createParser() {
    $parser = xml_parser_create_ns();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);
    xml_set_object($parser, $this);
    xml_set_element_handler($parser, 'startElement', 'endElement');
    xml_set_character_data_handler($parser, 'characterData');
    return $parser;
  }

  /**
   * Sets the reader context.
   *
   * @param array $context
   *   Context to use.
   */
  public function setContext(array $context) {
    $this->context = $context;
  }

  /**
   * Reads an array from the stream.
   *
   * @return array
   *   Structured array.
   * @throws RuntimeException
   *   If no data could be read.
   */
  public function read() {
    // Check data can be read.
    if (!isset($this->parser)) {
      throw new RuntimeException('Data could not be read.');
    }

    // Read data.
    while (!$this->fileDelegate->isEOF()) {
      $line = $this->fileDelegate->readLine();
      if (!xml_parse($this->parser, $line)) {
        throw new XMLStructReaderException('Error while parsing: ' . xml_error_string(xml_get_error_code($this->parser)));
      }
    }
    $this->cleanUp();

    // TODO
    return array();
  }

  /**
   * Handles element start.
   */
  public function startElement($parser, $name, array $attributes) {
    // TODO
  }

  /**
   * Handles character data.
   */
  public function characterData($parser, $data) {
    // TODO
  }

  /**
   * Handles element end.
   */
  public function endElement($parser, $name) {
    // TODO
  }
}

/**
 * Interface for a specific behavior in the reader responsible for interpreting
 * annotations on the XML tree.
 */
interface XMLStructReaderInterpreter {
  // TODO
}

/**
 * Factory class for creating a reader.
 */
class XMLStructReaderFactory {
  /**
   * Parsing context to include with created readers.
   * @var array
   */
  protected $context;

  /**
   * Reader that owns this factory.
   * @var XMLStructReader
   */
  protected $owner;

  /**
   * Constructs a reader factory.
   *
   * @param XMLStructReader $owner
   *   Owner object creating this factory.
   * @param array $context
   *   Parse context for the reader. Used internally to specify metadata about
   *   the base context to use when parsing with the created reader.
   */
  public function __construct($owner = NULL, array $context = array()) {
    if (isset($owner)) {
      if (!is_object($owner) || !$owner instanceof XMLStructReader) {
        throw new InvalidArgumentException('Owner is not a valid object.');
      }
      $this->owner = $owner;
    }
    $this->context = $context;
  }

  /**
   * Creates a reader with options. See XMLStructReader::setOption() for a list
   * of options to initialize with.
   *
   * @param mixed $file
   *   Path to an XML file, a stream resource, or an SplFileObject instance.
   * @param array $options
   *   Options for the reader.
   * @return XMLStructReader
   *   Created reader.
   */
  public function createReader($file, array $options = array()) {
    $delegate = $this->createStreamDelegate($file);
    $reader = new XMLStructReader($delegate, $options, $this->context);
    return $reader;
  }

  /**
   * Creates a stream delegate.
   *
   * @param mixed $file
   *   Path to an XML file, a stream resource, or an SplFileObject instance.
   * @return XMLStructReader_StreamDelegate
   *   Stream delegate for the given parameter.
   */
  protected function createStreamDelegate($file) {
    if (is_string($file) && file_exists($file)) {
      // Transform file path into file object.
      $file = new SplFileObject($file);
    }
    // Create the delegate.
    $delegate = new XMLStructReader_StreamDelegate($file);
    return $delegate;
  }
}

/**
 * Generic exception during typical reader usage.
 */
class XMLStructReaderException extends Exception {}

/**
 * Delegate for handling stream operations uniformly across a resource handle
 * and a SplFileObject instance.
 *
 * @subpackage  Utility
 */
class XMLStructReader_StreamDelegate {
  /**
   * Stream resource handle.
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
    elseif (is_object($file) && $file instanceof SplFileObject) {
      $this->object = $file;
    }
    else {
      throw new InvalidArgumentException('File parameter is not recognized.');
    }
  }

  /**
   * Determines whether a resource is wrapped.
   */
  public function isResource() {
    return isset($this->resource);
  }

  /**
   * Determines whether a file object is wrapped.
   */
  public function isObject() {
    return isset($this->object);
  }

  /**
   * Determines whether end of file has been reached.
   * @return boolean
   */
  public function isEOF() {
    return isset($this->object) ? $this->object->eof() : feof($this->resource);
  }

  /**
   * Reads a line from the stream.
   *
   * @return string
   *   The line read from the file, or FALSE on error.
   */
  public function readLine() {
    return isset($this->object) ? $this->object->fgets() : fgets($this->resource);
  }
}
