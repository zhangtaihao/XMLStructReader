<?php
/**
 * XML structured array parser.
 *
 * XMLStructReader can be used to read an XML into an array, optionally with
 * annotations on the document itself to adjust the resulting structure.
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
 * Base implementation of XML structured array parser.
 */
abstract class XMLStructReader {
  /**
   * Namespace URI.
   */
  const NS = 'http://xml.zth.me/XMLStructReader/';

  /**
   * File delegate to parse.
   * @var XMLStructReader_StreamDelegate
   */
  protected $fileDelegate;

  /**
   * Default options cache.
   * @var array
   */
  protected $defaultOptions;

  /**
   * Reader options.
   * @var array
   */
  protected $options;

  /**
   * Reader context when parsing.
   * @var XMLStructReaderContext
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
   *   Options for the reader.
   * @param XMLStructReaderContext $context
   *   Parse context for the reader. Used internally to specify metadata about
   *   the base context to use when parsing with the created reader.
   */
  public function __construct($fileDelegate, array $options = array(), $context = NULL) {
    $this->fileDelegate = $fileDelegate;
    $this->options = $this->defaultOptions = $this->getDefaultOptions();
    $this->setOptions($options);
    if (!isset($context)) {
      $context = new XMLStructReaderContext();
    }
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
   * Gets default reader options.
   *
   * @return array
   *   Set of options.
   */
  protected function getDefaultOptions() {
    return array();
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
   * Gets an option for the reader.
   *
   * @param $option
   *   Option name.
   * @return mixed
   *   Option value.
   */
  public function getOption($option) {
    if (array_key_exists($option, $this->options)) {
      return $this->options[$option];
    }
  }

  /**
   * Sets an option on the reader.
   *
   * @param $option
   *   An option name.
   * @param mixed $value
   *   Option value.
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
   *   Options to set.
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
   * Gets XML parser options.
   *
   * @see xml_parser_get_option()
   */
  public function getXMLOption($option) {
    if (isset($this->parser)) {
      return xml_parser_get_option($this->parser, $option);
    }
    else {
      throw new RuntimeException('XML parser does not exist.');
    }
  }

  /**
   * Sets XML parser options.
   *
   * @see xml_parser_set_option()
   */
  public function setXMLOption($option, $value) {
    if (isset($this->parser)) {
      return xml_parser_set_option($this->parser, $option, $value);
    }
    else {
      throw new RuntimeException('XML parser does not exist.');
    }
  }

  /**
   * Gets the reader context.
   *
   * @param XMLStructReaderContext $context
   *   Context to use.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Sets the reader context.
   *
   * @param XMLStructReaderContext $context
   *   Context to use.
   */
  public function setContext($context) {
    $this->context = $context;
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
    $isFinal = FALSE;
    $line = NULL;
    if (!$this->fileDelegate->isEOF()) {
      $line = $this->fileDelegate->readLine();
    }
    while (isset($line)) {
      if (!xml_parse($this->parser, $line, $isFinal)) {
        throw new XMLStructReaderException('Error while parsing: ' . xml_error_string(xml_get_error_code($this->parser)));
      }
      // Finish.
      if ($isFinal) {
        break;
      }
      // Trigger entity error reporting at the end.
      elseif ($this->fileDelegate->isEOF()) {
        $isFinal = TRUE;
        $line = '';
      }
      // Read next line.
      else {
        $line = $this->fileDelegate->readLine();
      }
    }
    $this->cleanUp();

    return $this->getData();
  }

  /**
   * Handles element start.
   */
  abstract public function startElement($parser, $name, array $attributes);

  /**
   * Handles character data.
   */
  abstract public function characterData($parser, $data);

  /**
   * Handles element end.
   */
  abstract public function endElement($parser, $name);

  /**
   * Returns the read data array.
   *
   * @return array|null
   *   Data array, or NULL if nothing was read (i.e. not even empty).
   */
  abstract public function getData();
}

/**
 * Default reader implementation.
 *
 * This reader supports the following options:
 * - XML_STRUCT_READER_OPTION_INCLUDED_LOAD
 * - XML_STRUCT_READER_OPTION_INCLUDED_PATH
 * - XML_STRUCT_READER_OPTION_INCLUDED_READER_FACTORY
 * - XML_STRUCT_READER_OPTION_INCLUDED_SAME_OPTIONS
 */
class DefaultXMLStructReader extends XMLStructReader {
  /**
   * Context stack.
   * @var XMLStructReaderContext[]
   */
  protected $contextStack = array();

  /**
   * Element trail stack.
   * @var XMLStructReader_ElementInterpreter[]
   */
  protected $elementTrail = array();

  /**
   * Element interpreter factory registry.
   * @var XMLStructReader_ElementInterpreterFactory[]
   */
  protected $elementInterpreterFactories = array();

  /**
   * Attribute interpreter factory registry.
   * @var XMLStructReader_AttributeInterpreterFactory[]
   */
  protected $attributeInterpreterFactories = array();

  /**
   * Specifies default options as documented.
   *
   * @return array
   *   Default reader options.
   */
  protected function getDefaultOptions() {
    return array(
      XML_STRUCT_READER_OPTION_INCLUDED_LOAD => FALSE,
      XML_STRUCT_READER_OPTION_INCLUDED_PATH => NULL,
      XML_STRUCT_READER_OPTION_INCLUDED_READER_FACTORY => 'XMLStructReaderFactory',
      XML_STRUCT_READER_OPTION_INCLUDED_SAME_OPTIONS => TRUE,
    ) + parent::getDefaultOptions();
  }

  /**
   * Pushes a context onto the stack.
   *
   * @param XMLStructReaderContext $context
   *   Context object.
   */
  protected function pushContext($context) {
    array_push($this->contextStack, $context);
    $this->setContext($context);
  }

  /**
   * Pops a context off the stack.
   *
   * @return XMLStructReaderContext
   *   Removed context object.
   */
  protected function popContext() {
    $context = array_pop($this->contextStack);
    $this->setContext(end($this->contextStack));
    return $context;
  }

  /**
   * Registers an element interpreter factory.
   *
   * @param XMLStructReader_ElementInterpreterFactory $factory
   *   Element interpreter factory.
   */
  protected function registerElementInterpreterFactory($factory) {
    // TODO
  }

  /**
   * Registers an attribute interpreter factory.
   *
   * @param XMLStructReader_AttributeInterpreterFactory $factory
   *   Attribute interpreter factory.
   */
  protected function registerAttributeInterpreterFactory($factory) {
    // TODO
  }

  /**
   * Looks up an element interpreter factory.
   *
   * @param string $name
   *   Element name.
   * @param string|null $namespace
   *   Namespace URI, or NULL if the element has no namespace.
   * @return XMLStructReader_ElementInterpreterFactory
   *   Element interpreter factory.
   */
  protected function getElementInterpreterFactory($name, $namespace = NULL) {
    // TODO
    return NULL;
  }

  /**
   * Looks up an attribute interpreter factory.
   *
   * @param string $name
   *   Attribute name.
   * @param string|null $namespace
   *   Namespace URI, or NULL if the attribute has no namespace.
   * @return XMLStructReader_AttributeInterpreterFactory
   *   Attribute interpreter factory.
   */
  protected function getAttributeInterpreterFactory($name, $namespace = NULL) {
    // TODO
    return NULL;
  }

  /**
   * Pushes an element onto the trail.
   *
   * @param XMLStructReader_ElementInterpreter $element
   *   Element interpreter.
   */
  protected function pushElement($element) {
    array_push($this->elementTrail, $element);
  }

  /**
   * Pops an element off the trail.
   *
   * @return XMLStructReader_ElementInterpreter
   *   Removed element interpreter.
   */
  protected function popElement() {
    $element = array_pop($this->elementTrail);
    return $element;
  }

  /**
   * Gets the last element in the trail.
   *
   * @return XMLStructReader_ElementInterpreter
   *   Element interpreter, or NULL if no element.
   */
  protected function getElement() {
    $element = end($this->elementTrail);
    return $element ? $element : NULL;
  }

  /**
   * Handles element start.
   */
  public function startElement($parser, $name, array $attributes) {
    // Clone a new context.
    $context = clone $this->getContext();
    $this->pushContext($context);

    // Derive element namespace and name.
    $elementNamespace = NULL;
    $elementName = $name;
    if (FALSE !== $separatorPos = strrpos($elementName, ':')) {
      $elementNamespace = substr($elementName, 0, $separatorPos);
      $elementName = substr($elementName, $separatorPos + 1);
    }

    // Look up element interpreter factory.
    if (!$factory = $this->getElementInterpreterFactory($elementName, $elementNamespace)) {
      throw new RuntimeException('No matching element interpreter is found.');
    }

    // Create interpreter with parent.
    $element = $factory->createElementInterpreter($elementName, $context, $this->getElement());
    $this->pushElement($element);

    // Process attributes.
    foreach ($attributes as $attrName => $attrValue) {
      // Derive attribute namespace and name.
      $attributeNamespace = NULL;
      $attributeName = $attrName;
      if (FALSE !== $separatorPos = strrpos($attributeName, ':')) {
        $attributeNamespace = substr($attributeName, 0, $separatorPos);
        $attributeName = substr($attributeName, $separatorPos + 1);
      }

      // Look up attribute interpreter factory.
      if (!$factory = $this->getAttributeInterpreterFactory($attributeName, $attributeNamespace)) {
        throw new RuntimeException('No matching attribute interpreter is found.');
      }

      // Create attribute interpreter for element.
      $attribute = $factory->createAttributeInterpreter($attributeName, $context, $element);

      // Handle attribute.
      $attribute->handleAttribute($attrValue);
    }
  }

  /**
   * Handles character data.
   */
  public function characterData($parser, $data) {
    // Handle element data.
    if ($element = $this->getElement()) {
      $element->handleCData($data);
    }
  }

  /**
   * Handles element end.
   */
  public function endElement($parser, $name) {
    // Pop context off.
    $this->popContext();
    // Pop and handle element.
    $this->popElement()->handleElement();
  }

  /**
   * Returns the read data array.
   *
   * @return array|null
   *   Data array, or NULL if nothing was read (i.e. not even empty).
   */
  public function getData() {
    // TODO
    return NULL;
  }
}

/**
 * Base factory class for creating a reader.
 */
abstract class XMLStructReaderFactory {
  /**
   * Reader that owns this factory.
   * @var XMLStructReader
   */
  protected $owner;

  /**
   * Parsing context to include with created readers.
   * @var XMLStructReaderContext
   */
  protected $context;

  /**
   * Constructs a reader factory.
   *
   * @param XMLStructReader $owner
   *   Owner object creating this factory.
   * @param XMLStructReaderContext $context
   *   Parse context for the reader. Used internally to specify metadata about
   *   the base context to use when parsing with the created reader.
   */
  public function __construct($owner = NULL, $context = NULL) {
    // Check owner.
    if (isset($owner)) {
      if (!is_object($owner) || !$owner instanceof XMLStructReader) {
        throw new InvalidArgumentException('Owner is not a valid object.');
      }
      $this->owner = $owner;
    }
    // Check context.
    if (isset($context)) {
      if (!is_object($context) || !$context instanceof XMLStructReaderContext) {
        throw new InvalidArgumentException('Context is not a valid object.');
      }
    }
    elseif (isset($owner)) {
      $context = $owner->getContext();
    }
    $this->context = $context;
  }

  /**
   * Creates a reader with options. See a specific reader implementation for
   * supported options to initialize with.
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
    $reader = $this->createReaderObject($delegate, $options, $this->context);
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
    if (is_string($file)) {
      // Attempt to transform any string into file object.
      $file = new SplFileObject($file);
    }
    // Create the delegate.
    $delegate = new XMLStructReader_StreamDelegate($file);
    return $delegate;
  }

  /**
   * Creates a reader object.
   *
   * @param XMLStructReader_StreamDelegate $delegate
   *   Delegate object for a file to parse.
   * @param array $options
   *   Options for the reader.
   * @param XMLStructReaderContext $context
   *   Parse context for the reader.
   * @return XMLStructReader
   *   Created reader.
   */
  abstract protected function createReaderObject($delegate, array $options = array(), $context = NULL);
}

/**
 * Default reader factory. This factory creates a DefaultXMLStructReader.
 */
class DefaultXMLStructReaderFactory extends XMLStructReaderFactory {
  /**
   * Creates a reader from a delegate.
   *
   * @param XMLStructReader_StreamDelegate $delegate
   *   Delegate object for a file to parse.
   * @param array $options
   *   Options for the reader.
   * @param XMLStructReaderContext $context
   *   Parse context for the reader.
   * @return XMLStructReader
   *   Created reader.
   */
  protected function createReaderObject($delegate, array $options = array(), $context = NULL) {
    return new DefaultXMLStructReader($delegate, $options, $context);
  }
}

/**
 * Context object for accessing and storing reader state.
 */
class XMLStructReaderContext extends ArrayObject {
  /**
   * Creates a context object for access as properties.
   */
  public function __construct(array $data = array()) {
    parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
  }
}

/**
 * Generic exception during typical reader usage.
 */
class XMLStructReaderException extends Exception {}

/**
 * Base interpreter factory for the default namespace.
 */
interface XMLStructReader_InterpreterFactory {
  /**
   * Returns the namespace to interpret in.
   *
   * @return string|null
   *   URI of the namespace, or NULL if no specific namespace.
   */
  public function getNamespace();
}

/**
 * Factory interface for creating an interpreter for processing an element.
 */
interface XMLStructReader_ElementInterpreterFactory extends XMLStructReader_InterpreterFactory {
  /**
   * Returns the name of the element to interpret.
   *
   * @return string
   *   Name of the XML element, or an asterisk ('*') if all elements not
   *   interpreted by other interpreters are processed.
   */
  public function getElementName();

  /**
   * Creates an element interpreter.
   *
   * @param string $name
   *   Element name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader_ElementInterpreter $parent
   *   Parent element interpreter.
   * @return XMLStructReader_ElementInterpreter
   *   Element interpreter object.
   */
  public function createElementInterpreter($name, $context, $parent = NULL);
}

/**
 * Factory interface for creating an interpreter for processing an attribute.
 */
interface XMLStructReader_AttributeInterpreterFactory extends XMLStructReader_InterpreterFactory {
  /**
   * Returns the name of the attribute to interpret.
   *
   * @return string|null
   *   Name of the XML attribute, or an asterisk ('*') if all attributes not
   *   interpreted by other interpreters are processed.
   */
  public function getAttributeName();

  /**
   * Creates an attribute interpreter.
   *
   * @param string $name
   *   Attribute name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader_ElementInterpreter $element
   *   Containing element interpreter.
   * @return XMLStructReader_AttributeInterpreter
   *   Attribute interpreter object.
   */
  public function createAttributeInterpreter($name, $context, $element);
}

/**
 * Element interpreter.
 */
interface XMLStructReader_ElementInterpreter {
  /**
   * Adds data for the element.
   *
   * @param mixed $data
   *   Data to add.
   * @param string|null $key
   *   Data key, or NULL if none applicable, e.g. character data.
   */
  public function addData($data, $key = NULL);

  /**
   * Handles character data for the element. Where possible, use self::addData()
   * preferentially to populate element data.
   *
   * @param string $data
   *   Raw character data from XML parser.
   */
  public function handleCData($data);

  /**
   * Handles the element (complete with data) as it ends.
   */
  public function handleElement();
}

/**
 * Attribute interpreter.
 */
interface XMLStructReader_AttributeInterpreter {
  /**
   * Handles the element attribute.
   *
   * @param string $value
   *   Attribute value.
   */
  public function handleAttribute($value);
}

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
