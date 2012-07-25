<?php
/**
 * XML structured array parser.
 *
 * XMLStructReader can be used to read an XML into an array, optionally with
 * annotations on the document itself to adjust the resulting structure.
 *
 * @package  XMLStructReader
 * @author   Taihao Zhang <jason@zth.me>
 * @license  GNU General Public License v3.0
 * @license  LICENSE.txt
 */

/**
 * What to do when keys conflict.
 *
 * Possible values:
 * - XML_STRUCT_READER_CONFLICT_REPLACE (default)
 * - XML_STRUCT_READER_CONFLICT_MERGE
 */
define('XML_STRUCT_READER_OPTION_KEY_CONFLICT', 'keyConflict');

/**
 * Whether to trim whitespace from ends of contiguous chunks of element text
 * (i.e. not interrupted by an element).
 *
 * Possible values:
 * - TRUE (default)
 * - FALSE
 */
define('XML_STRUCT_READER_OPTION_TEXT_TRIM', 'textTrim');

/**
 * Whether to join all text values in an element.
 *
 * Possible values:
 * - TRUE (default)
 * - FALSE
 */
define('XML_STRUCT_READER_OPTION_TEXT_JOIN', 'textJoin');

/**
 * Whether to skip empty chunks of text within elements. The text is measured
 * in its trimmed form.
 *
 * Possible values:
 * - TRUE (default)
 * - FALSE
 */
define('XML_STRUCT_READER_OPTION_TEXT_SKIP_EMPTY', 'textSkipEmpty');

/**
 * Default base path for included file names.
 *
 * Possible values:
 * - Any valid directory path string. Default: . (current working directory)
 */
define('XML_STRUCT_READER_OPTION_INCLUDE_PATH', 'includePath');

/**
 * Factory class to create reader for included files.
 *
 * Possible values:
 * - Any valid findable class name. Default: DefaultXMLStructReaderFactory
 */
define('XML_STRUCT_READER_OPTION_INCLUDE_READER_FACTORY', 'includeReaderFactory');

/**
 * Key conflict option value representing 'merge'.
 */
define('XML_STRUCT_READER_CONFLICT_MERGE', 'merge');

/**
 * Key conflict option value representing 'replace'.
 */
define('XML_STRUCT_READER_CONFLICT_REPLACE', 'replace');

/**
 * Base implementation of XML structured array parser.
 */
abstract class XMLStructReader {
  /**
   * Namespace URI.
   */
  const NS = 'http://xml.zth.me/XMLStructReader';

  /**
   * Namespace separator.
   */
  const NS_SEPARATOR = ':';

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
   * Interpreter factory registry.
   * @var XMLStructReader_InterpreterFactory[][]
   */
  protected $interpreterFactoryRegistry = array();

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
    // Return nothing by default.
    return NULL;
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
   * Gets all options for the reader.
   *
   * @return array
   *   An array of option values.
   */
  public function getOptions() {
    $options = array();
    foreach (array_keys($this->options) as $key) {
      $options[$key] = $this->getOption($key);
    }
    return $options;
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
   * @return XMLStructReaderContext
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
    $parser = xml_parser_create_ns(NULL, self::NS_SEPARATOR);
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);
    xml_set_object($parser, $this);
    xml_set_element_handler($parser, 'startElement', 'endElement');
    xml_set_character_data_handler($parser, 'characterData');
    return $parser;
  }

  /**
   * Registers an interpreter factory.
   *
   * @param string $type
   *   Interpreter type, as a primary classifier.
   * @param string $id
   *   Interpreter identifier.
   * @param XMLStructReader_InterpreterFactory $factory
   *   Factory object.
   */
  protected function registerInterpreterFactory($type, $id, $factory) {
    $this->interpreterFactoryRegistry[$type][$id] = $factory;
  }

  /**
   * Looks up an interpreter factory.
   *
   * @param string $type
   *   Interpreter type, as a primary classifier.
   * @param string[] $candidateIds
   *   An array of candidate factory identifiers to look up.
   * @return XMLStructReader_InterpreterFactory
   *   Interpreter factory object.
   */
  protected function getInterpreterFactory($type, array $candidateIds) {
    foreach ($candidateIds as $id) {
      if (isset($this->interpreterFactoryRegistry[$type][$id])) {
        return $this->interpreterFactoryRegistry[$type][$id];
      }
    }
    // Return nothing by default.
    return NULL;
  }

  /**
   * Reads an array from the stream.
   *
   * @return array
   *   Structured array.
   * @throws RuntimeException
   *   If no data could be read.
   * @throws XMLStructReaderException
   *   If an error occurred while parsing.
   */
  public function read() {
    // Check data can be read.
    if (!isset($this->parser)) {
      throw new RuntimeException('Data could not be read.');
    }

    // Read data.
    while (!$this->fileDelegate->isEOF()) {
      $line = $this->fileDelegate->readLine();
      if (!xml_parse($this->parser, $line, $this->fileDelegate->isEOF())) {
        throw new XMLStructReaderException(sprintf('Error while parsing line %d: %s', xml_get_current_line_number($this->parser), xml_error_string(xml_get_error_code($this->parser))));
      }
    }
    $this->cleanUp();

    return $this->getData();
  }

  /**
   * Splits a qualified name into the short name and its namespace.
   *
   * @param string $qualifiedName
   *   Fully qualified XML name.
   * @return array
   *   List of namespace and name. The namespace may be NULL.
   */
  protected function resolveQualifiedName($qualifiedName) {
    $namespace = NULL;
    $name = $qualifiedName;
    if (FALSE !== $separatorPos = strrpos($qualifiedName, self::NS_SEPARATOR)) {
      $namespace = substr($qualifiedName, 0, $separatorPos);
      $name = substr($qualifiedName, $separatorPos + 1);
    }
    return array($namespace, $name);
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
   * @return array
   *   Data array, or NULL if there was an error.
   * @throws XMLStructReaderException
   *   If data cannot be returned, e.g. data has not been read.
   */
  abstract public function getData();
}

/**
 * Default reader implementation.
 *
 * This reader supports the following options:
 * - XML_STRUCT_READER_OPTION_INCLUDE_PATH
 * - XML_STRUCT_READER_OPTION_INCLUDE_READER_FACTORY
 */
class DefaultXMLStructReader extends XMLStructReader {
  /**
   * Element interpreter type.
   */
  const INTERPRETER_ELEMENT = 'element';

  /**
   * Attribute interpreter type.
   */
  const INTERPRETER_ATTRIBUTE = 'attribute';

  /**
   * Context stack.
   * @var XMLStructReaderContext[]
   */
  protected $contextStack = array();

  /**
   * Root element container.
   * @var XMLStructReader_DefaultRootContainer
   */
  protected $rootContainer;

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
      XML_STRUCT_READER_OPTION_KEY_CONFLICT => XML_STRUCT_READER_CONFLICT_REPLACE,
      XML_STRUCT_READER_OPTION_TEXT_TRIM => TRUE,
      XML_STRUCT_READER_OPTION_TEXT_JOIN => TRUE,
      XML_STRUCT_READER_OPTION_TEXT_SKIP_EMPTY => TRUE,
      XML_STRUCT_READER_OPTION_INCLUDE_PATH => NULL,
      XML_STRUCT_READER_OPTION_INCLUDE_READER_FACTORY => 'DefaultXMLStructReaderFactory',
    ) + parent::getDefaultOptions();
  }

  /**
   * Sets up the default reader.
   */
  protected function setUp() {
    parent::setUp();
    // Set up interpreters.
    $this->setUpInterpreters();
  }

  /**
   * Registers interpreters for this reader.
   */
  protected function setUpInterpreters() {
    // Add default interpreters.
    $this->registerElementInterpreterFactory(new XMLStructReader_DefaultElementFactory());
    $this->registerElementInterpreterFactory(new XMLStructReader_StructIncludeFactory());
    $this->registerAttributeInterpreterFactory(new XMLStructReader_DefaultWildcardAttributeFactory());
    $this->registerAttributeInterpreterFactory(new XMLStructReader_DefaultAttributeFactory());
    $this->registerAttributeInterpreterFactory(new XMLStructReader_StructAttributeFactory());
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
   * Registers a named interpreter factory.
   *
   * @param string $type
   *   Interpreter type, as a primary classifier.
   * @param string|null $namespace
   *   Namespace URI, or NULL if no specific namespace.
   * @param string $name
   *   XML name.
   * @param object
   *   Interpreter factory object.
   */
  protected function registerNamedInterpreterFactory($type, $namespace, $name, $factory) {
    // Register on the fully qualified name (with wildcards).
    $id = "$namespace:$name";
    $this->registerInterpreterFactory($type, $id, $factory);
  }

  /**
   * Looks up a named interpreter factory.
   *
   * @param string $type
   *   Interpreter type, as a primary classifier.
   * @param string|null $namespace
   *   Namespace URI, or NULL if no specific namespace.
   * @param string $name
   *   XML name.
   * @return object
   *   Interpreter factory object.
   */
  protected function getNamedInterpreterFactory($type, $namespace, $name) {
    // Populate factory candidates.
    $candidates = array();
    $candidateName = $name;
    $candidateNamespace = $namespace;
    // Add qualified candidate.
    $candidates[] = "$candidateNamespace:$candidateName";
    // Add unqualified candidate.
    $candidates[] = "*:$candidateName";
    // Add unnamed qualified candidate.
    $candidates[] = "$candidateNamespace:*";
    // Add universal candidate.
    $candidates[] = '*:*';
    // Look up factory.
    return $this->getInterpreterFactory($type, $candidates);
  }

  /**
   * Registers an element interpreter factory.
   *
   * @param XMLStructReader_ElementInterpreterFactory $factory
   *   Element interpreter factory.
   */
  protected function registerElementInterpreterFactory($factory) {
    $namespace = $factory->getNamespace();
    $name = $factory->getElementName();
    $this->registerNamedInterpreterFactory(self::INTERPRETER_ELEMENT, $namespace, $name, $factory);
  }

  /**
   * Registers an attribute interpreter factory.
   *
   * @param XMLStructReader_AttributeInterpreterFactory $factory
   *   Attribute interpreter factory.
   */
  protected function registerAttributeInterpreterFactory($factory) {
    $namespace = $factory->getNamespace();
    $name = $factory->getAttributeName();
    $this->registerNamedInterpreterFactory(self::INTERPRETER_ATTRIBUTE, $namespace, $name, $factory);
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
    return $this->getNamedInterpreterFactory(self::INTERPRETER_ELEMENT, $namespace, $name);
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
    return $this->getNamedInterpreterFactory(self::INTERPRETER_ATTRIBUTE, $namespace, $name);
  }

  /**
   * Reads an array from the stream.
   *
   * @return array
   *   Structured array.
   * @throws RuntimeException
   *   If no data could be read.
   * @throws XMLStructReaderException
   *   If an error occurred while parsing.
   */
  public function read() {
    // Set up root container.
    $this->rootContainer = new XMLStructReader_DefaultRootContainer($this->context, $this);
    $this->pushElement($this->rootContainer);
    // Read data.
    $data = parent::read();
    $this->popElement();
    return $data;
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

    // Look up element interpreter factory.
    list($elementNamespace, $elementName) = $this->resolveQualifiedName($name);
    if (!$factory = $this->getElementInterpreterFactory($elementName, $elementNamespace)) {
      throw new XMLStructReaderException('No matching element interpreter is found.');
    }

    // Create interpreter with parent.
    $element = $factory->createElementInterpreter($elementName, $context, $this, $this->getElement());
    $this->pushElement($element);

    // Process attributes.
    foreach ($attributes as $attrName => $attrValue) {
      // Look up attribute interpreter factory.
      list($attributeNamespace, $attributeName) = $this->resolveQualifiedName($attrName);
      if (!$factory = $this->getAttributeInterpreterFactory($attributeName, $attributeNamespace)) {
        throw new XMLStructReaderException('No matching attribute interpreter is found.');
      }

      // Create attribute interpreter for element.
      $attribute = $factory->createAttributeInterpreter($attributeName, $context, $this, $element);

      // Process attribute.
      $attribute->processAttribute($attrValue);
    }
  }

  /**
   * Handles character data.
   */
  public function characterData($parser, $data) {
    // Handle element data.
    if ($element = $this->getElement()) {
      $element->addCharacterData($data);
    }
  }

  /**
   * Handles element end.
   */
  public function endElement($parser, $name) {
    // Pop and handle element.
    $this->popElement()->processElement();
    // Pop context off.
    $this->popContext();
  }

  /**
   * Returns the read data array.
   *
   * @return array
   *   Data array, or NULL if there was an error.
   * @throws XMLStructReaderException
   *   If data cannot be returned, e.g. data has not been read.
   */
  public function getData() {
    if (isset($this->rootContainer)) {
      return $this->rootContainer->getData();
    }
    // Fail by default.
    throw new XMLStructReaderException('Data has not been read.');
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
   * @throws InvalidArgumentException
   *   If either owner or context is not a valid object.
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
   * Gets the factory owner.
   *
   * @return XMLStructReader
   *   Reader that owns this factory.
   */
  public function getOwner() {
    return $this->owner;
  }

  /**
   * Gets the factory context.
   *
   * @return XMLStructReaderContext
   *   Context object.
   */
  public function getContext() {
    return $this->context;
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

  public function offsetExists($index) {
    // Check for NULL value for consistency with isset().
    return parent::offsetExists($index) && !is_null($this[$index]);
  }
}

/**
 * Generic exception during typical reader usage.
 */
class XMLStructReaderException extends RuntimeException {}

/**
 * Base interpreter factory for the default namespace.
 */
interface XMLStructReader_InterpreterFactory {
  /**
   * Returns the namespace to interpret in.
   *
   * @return string|null
   *   URI of the namespace, NULL if no namespace, or '*' if all other
   *   unprocessed namespaces.
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
   *   Name of the XML element, or '*' if all elements not interpreted by other
   *   interpreters are processed.
   */
  public function getElementName();

  /**
   * Creates an element interpreter.
   *
   * @param string $name
   *   Element name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $parent
   *   Parent element interpreter.
   * @return XMLStructReader_ElementInterpreter
   *   Element interpreter object.
   */
  public function createElementInterpreter($name, $context, $reader, $parent = NULL);
}

/**
 * Factory interface for creating an interpreter for processing an attribute.
 */
interface XMLStructReader_AttributeInterpreterFactory extends XMLStructReader_InterpreterFactory {
  /**
   * Returns the name of the attribute to interpret.
   *
   * @return string
   *   Name of the XML attribute, or '*' if all attributes not interpreted by
   *   other interpreters are processed.
   */
  public function getAttributeName();

  /**
   * Creates an attribute interpreter.
   *
   * @param string $name
   *   Attribute name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $element
   *   Containing element interpreter.
   * @return XMLStructReader_AttributeInterpreter
   *   Attribute interpreter object.
   */
  public function createAttributeInterpreter($name, $context, $reader, $element);
}

/**
 * Element interpreter.
 */
interface XMLStructReader_ElementInterpreter {
  /**
   * Adds data for the element.
   *
   * @param string $key
   *   Data key, e.g. child element name.
   * @param mixed $data
   *   Data to add.
   */
  public function addElementData($key, $data);

  /**
   * Adds element attribute.
   *
   * @param string $name
   *   Attribute name.
   * @param string $value
   *   Attribute value.
   */
  public function addAttribute($name, $value);

  /**
   * Adds character data for the element.
   *
   * @param string $data
   *   Raw character data from XML parser.
   */
  public function addCharacterData($data);

  /**
   * Handles the element (complete with data) as it ends.
   */
  public function processElement();

  /**
   * Gets the element data.
   *
   * @return mixed
   *   Element data.
   */
  public function getData();
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
  public function processAttribute($value);
}

/**
 * Default element interpreter factory.
 */
class XMLStructReader_DefaultElementFactory implements XMLStructReader_ElementInterpreterFactory {
  /**
   * Returns the namespace to interpret in.
   *
   * @return string|null
   *   URI of the namespace, NULL if no namespace, or '*' if all other
   *   unprocessed namespaces.
   */
  public function getNamespace() {
    return '*';
  }

  /**
   * Returns the name of the element to interpret.
   *
   * @return string
   *   Name of the XML element, or '*' if all elements not interpreted by other
   *   interpreters are processed.
   */
  public function getElementName() {
    return '*';
  }

  /**
   * Creates an element interpreter.
   *
   * @param string $name
   *   Element name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $parent
   *   Parent element interpreter.
   * @return XMLStructReader_ElementInterpreter
   *   Element interpreter object.
   */
  public function createElementInterpreter($name, $context, $reader, $parent = NULL) {
    return new XMLStructReader_DefaultElement($name, $context, $reader, $parent);
  }
}

/**
 * Default element interpreter.
 */
class XMLStructReader_DefaultElement implements XMLStructReader_ElementInterpreter {
  /**
   * Element name.
   * @var $name
   */
  protected $name;

  /**
   * Element context.
   * @var XMLStructReaderContext
   */
  protected $context;

  /**
   * Associated reader
   * @var XMLStructReader
   */
  protected $reader;

  /**
   * Parent element.
   * @var XMLStructReader_ElementInterpreter
   */
  protected $parent;

  /**
   * Attribute values.
   * @var array
   */
  protected $attributes = array();

  /**
   * Element text values.
   * @var string[]
   */
  protected $textValues = array();

  /**
   * Current element text value (by reference).
   * @var string
   */
  protected $textValue;

  /**
   * Element children data.
   * @var array
   */
  protected $data = array();

  /**
   * Creates an element interpreter.
   *
   * @param string $name
   *   Element name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $parent
   *   Parent element interpreter.
   */
  public function __construct($name, $context, $reader, $parent = NULL) {
    $this->name = $name;
    $this->context = $context;
    $this->reader = $reader;
    $this->parent = $parent;
    // Initialize context for default element.
    $this->initializeContext($context);
  }

  /**
   * Initializes values for the context.
   *
   * @param XMLStructReaderContext $context
   *   Reader context.
   */
  protected function initializeContext($context) {
    if (!$this->isRoot()) {
      // Remove list element context for single use.
      if (isset($context['listElement'])) {
        unset($context['listElement']);
      }
      // Remove text key context for single use.
      if (isset($context['textKey'])) {
        unset($context['textKey']);
      }
      // Remove substitute key context for single use.
      if (isset($context['asKey'])) {
        unset($context['asKey']);
      }
    }
  }

  /**
   * Determines whether this element is root.
   * @return bool
   */
  public function isRoot() {
    return !is_object($this->parent) || $this->parent instanceof XMLStructReader_DefaultRootContainer;
  }

  /**
   * Adds data to element.
   */
  protected function addData($key, $data) {
    $this->data[] = array(
      'key' => $key,
      'data' => $data,
    );
  }

  /**
   * Adds data for the element.
   *
   * @param string $key
   *   Data key, e.g. child element name.
   * @param mixed $data
   *   Data to add, NULL if element is empty.
   */
  public function addElementData($key, $data) {
    // Mark the end of a contiguous textual chunk.
    $this->endCharacterData();
    // Add element data.
    if (isset($data)) {
      $this->addData($key, $data);
    }
  }

  /**
   * Adds element attribute.
   *
   * @param string $name
   *   Attribute name.
   * @param string $value
   *   Attribute value.
   */
  public function addAttribute($name, $value) {
    // Add attribute value.
    $this->attributes[$name] = $value;
    // Collect attribute as data.
    $this->addData($name, $value);
  }

  /**
   * Adds character data for the element.
   *
   * @param string $data
   *   Raw character data from XML parser.
   */
  public function addCharacterData($data) {
    $optionTextSkipEmpty = $this->reader->getOption(XML_STRUCT_READER_OPTION_TEXT_SKIP_EMPTY);
    // Skip empty text.
    if (!empty($optionTextSkipEmpty) && strlen(trim($data)) == 0) {
      return;
    }
    // Append text value.
    if (!isset($this->textValue)) {
      // Push to the next textual value.
      $this->textValue = &$this->textValues[];
      $this->textValue = '';
    }
    $this->textValue .= $data;
  }

  /**
   * Finishes a chunk of character data.
   */
  protected function endCharacterData() {
    if (isset($this->textValue)) {
      // Collect keyed, unjoined element value.
      if (isset($this->context['textKey']) && !$this->reader->getOption(XML_STRUCT_READER_OPTION_TEXT_JOIN)) {
        $data = $this->prepareTextValue(array($this->textValue));
        $this->addData($this->context['textKey'], $data);
      }
      // Empty current text value reference.
      unset($this->textValue);
    }
  }

  /**
   * Handles the element (complete with data) as it ends.
   */
  public function processElement() {
    // Mark the end of a contiguous textual chunk.
    $this->endCharacterData();
    // Add data to parent.
    if (isset($this->parent)) {
      $key = isset($this->context['asKey']) ? $this->context['asKey'] : $this->name;
      $this->parent->addElementData($key, $this->getData());
    }
  }

  /**
   * Gets the element data.
   *
   * @return mixed
   *   Element data.
   */
  public function getData() {
    $dataValues = $this->getElementData();

    // Prepare data to return.
    $dataValues = $this->prepareData($dataValues);

    return $dataValues;
  }

  /**
   * Prepares built data for return.
   *
   * @param mixed $dataValues
   *   Built element data.
   * @return mixed
   *   Data to return as value.
   */
  protected function prepareData($dataValues) {
    $data = NULL;
    if (is_array($dataValues) && NULL !== $preparedData = $this->prepareArrayData($dataValues)) {
      $data = $preparedData;
    }
    elseif (is_string($dataValues) && NULL !== $preparedData = $this->prepareStringData($dataValues)) {
      // Use element value as data.
      $data = $preparedData;
    }
    return $data;
  }

  /**
   * Prepares array data.
   *
   * @param array $dataValues
   * @return mixed
   */
  protected function prepareArrayData(array $dataValues) {
    $data = array();

    // Use collected values as data.
    $mergedValues = array();
    $listElement = isset($this->context['listElement']) ? $this->context['listElement'] : NULL;
    foreach ($dataValues as $item) {
      // Add as list item.
      if ($listElement == '*' || $listElement === $item['key']) {
        $data[] = $item['data'];
      }
      // Add as struct value.
      else {
        switch ($this->reader->getOption(XML_STRUCT_READER_OPTION_KEY_CONFLICT)) {
          case XML_STRUCT_READER_CONFLICT_REPLACE:
            // Set value.
            $data[$item['key']] = $item['data'];
            break;

          case XML_STRUCT_READER_CONFLICT_MERGE:
            // Set value if first.
            if (!isset($data[$item['key']])) {
              $data[$item['key']] = $item['data'];
            }
            // Append to merged values.
            else {
              if (!isset($mergedValues[$item['key']])) {
                // Push first value to merged values.
                $mergedValues[$item['key']] = array($data[$item['key']]);
              }
              $mergedValues[$item['key']][] = $item['data'];
            }
            break;
        }
      }
    }
    // Push merged values back in.
    foreach ($mergedValues as $key => $values) {
      $data[$key] = $values;
    }

    return $data;
  }

  /**
   * Prepares text data.
   *
   * @param array $dataValue
   * @return mixed
   */
  protected function prepareStringData($dataValue) {
    $data = NULL;
    if ($dataValue !== '' || !$this->reader->getOption(XML_STRUCT_READER_OPTION_TEXT_SKIP_EMPTY)) {
      // Use element value as data.
      $data = $dataValue;
    }
    return $data;
  }

  /**
   * Gets unprocessed element data values.
   *
   * @return array|string
   *   An array of value sets (each with a pair of 'key' and 'data'), or a text
   *   value if element value is flat.
   */
  protected function getElementData() {
    $dataValues = $this->data;
    $elementValue = $this->getElementValue();

    // Use keyed text as data value.
    if (isset($this->context['textKey']) && isset($elementValue)) {
      $dataValues[] = array(
        'key' => $this->context['textKey'],
        'data' => $elementValue,
      );
    }

    // Return data as value sets.
    if (!empty($dataValues)) {
      return $dataValues;
    }
    // Return data as flat value.
    else {
      return $elementValue;
    }
  }

  /**
   * Gets element value.
   *
   * @return string|null
   *   Element text value, or NULL if none applicable (e.g. text is not joined).
   */
  protected function getElementValue() {
    $elementValue = NULL;
    if ($this->reader->getOption(XML_STRUCT_READER_OPTION_TEXT_JOIN)) {
      // Collect text values.
      $elementValue = $this->prepareTextValue($this->textValues);
    }
    return $elementValue;
  }

  /**
   * Processes and returns text value.
   */
  protected function prepareTextValue(array $values) {
    $result = '';
    foreach ($values as $value) {
      // Trim value.
      if ($this->reader->getOption(XML_STRUCT_READER_OPTION_TEXT_TRIM)) {
        $value = trim($value);
      }
      // Append value.
      $result .= $value;
    }
    return $result;
  }
}

/**
 * Default element interpreter factory.
 */
class XMLStructReader_StructIncludeFactory implements XMLStructReader_ElementInterpreterFactory {
  /**
   * Returns the namespace to interpret in.
   *
   * @return string|null
   *   URI of the namespace, NULL if no namespace, or '*' if all other
   *   unprocessed namespaces.
   */
  public function getNamespace() {
    return XMLStructReader::NS;
  }

  /**
   * Returns the name of the element to interpret.
   *
   * @return string
   *   Name of the XML element, or '*' if all elements not interpreted by other
   *   interpreters are processed.
   */
  public function getElementName() {
    return 'include';
  }

  /**
   * Creates an element interpreter.
   *
   * @param string $name
   *   Element name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $parent
   *   Parent element interpreter.
   * @return XMLStructReader_ElementInterpreter
   *   Element interpreter object.
   */
  public function createElementInterpreter($name, $context, $reader, $parent = NULL) {
    return new XMLStructReader_StructInclude($context, $reader, $parent);
  }
}

/**
 * Include element interpreter.
 */
class XMLStructReader_StructInclude implements XMLStructReader_ElementInterpreter {
  /**
   * Element context.
   * @var XMLStructReaderContext
   */
  protected $context;

  /**
   * Associated reader
   * @var XMLStructReader
   */
  protected $reader;

  /**
   * Parent element.
   * @var XMLStructReader_ElementInterpreter
   */
  protected $parent;

  /**
   * Element metadata.
   * @var array
   */
  protected $metadata = array();

  /**
   * Included data.
   * @var mixed
   */
  protected $data;

  /**
   * Creates an element interpreter.
   *
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $parent
   *   Parent element interpreter.
   */
  public function __construct($context, $reader, $parent = NULL) {
    $this->context = $context;
    $this->reader = $reader;
    $this->parent = $parent;
  }

  /**
   * Adds data for the element.
   *
   * @param string $key
   *   Data key, e.g. child element name.
   * @param mixed $data
   *   Data to add.
   */
  public function addElementData($key, $data) {
    $this->metadata[$key] = $data;
  }

  /**
   * Adds element attribute.
   *
   * @param string $name
   *   Attribute name.
   * @param string $value
   *   Attribute value.
   */
  public function addAttribute($name, $value) {
    // Add attribute value as data.
    $this->metadata[$name] = $value;
  }

  /**
   * Adds character data for the element.
   *
   * @param string $data
   *   Raw character data from XML parser.
   */
  public function addCharacterData($data) {
    // Do nothing.
  }

  /**
   * Handles the element (complete with data) as it ends.
   */
  public function processElement() {
    // Add data to parent.
    if (isset($this->parent) && NULL !== $data = $this->getData()) {
      // Add included data (using the included root element) to parent.
      $includedData = reset($data);
      $key = key($data);
      $this->parent->addElementData($key, $includedData);
    }
  }

  /**
   * Gets the element data.
   *
   * @return mixed
   *   Element data.
   */
  public function getData() {
    // Build data.
    if (!isset($this->data)) {
      $factoryClass = $this->reader->getOption(XML_STRUCT_READER_OPTION_INCLUDE_READER_FACTORY);
      if (class_exists($factoryClass) && isset($this->metadata['file'])) {
        // Get file path.
        $file = $this->metadata['file'];
        $file = preg_replace_callback('!\$\{([^}]*)\}!', array($this, 'pregReplaceCallback'), $file);
        // Derive include path.
        $path = $this->reader->getOption(XML_STRUCT_READER_OPTION_INCLUDE_PATH);
        $path = isset($path) ? rtrim($path, DIRECTORY_SEPARATOR) : NULL;
        // Look up full path.
        $fileObject = NULL;
        if (isset($path) && $obj = $this->openFile($path . DIRECTORY_SEPARATOR . $file)) {
          $fileObject = $obj;
        }
        elseif ($obj = $this->openFile($file)) {
          $fileObject = $obj;
        }
        // Read file.
        if (isset($fileObject)) {
          /** @var $factory XMLStructReaderFactory */
          $factory = new $factoryClass($this->reader, $this->context);
          $reader = $factory->createReader($fileObject, $this->reader->getOptions());
          // Cache read data.
          $this->data = $reader->read();
        }
      }
    }
    return $this->data;
  }

  /**
   * Creates a file object.
   *
   * @param string $path
   *   File path or URL.
   * @return SplFileObject|null
   *   File object, or NULL if invalid path.
   */
  protected function openFile($path) {
    // Try to open file without errors.
    try {
      return new SplFileObject($path);
    }
    catch (RuntimeException $ex) {
      return NULL;
    }
  }

  /**
   * Callback for preg_replace_callback().
   */
  public function pregReplaceCallback($matches) {
    $name = $matches[1];
    // Replace constant.
    if (defined($name)) {
      return constant($name);
    }
    // Return empty replacement.
    return '';
  }
}

/**
 * Default reader root container element.
 */
class XMLStructReader_DefaultRootContainer extends XMLStructReader_DefaultElement {
  /**
   * Constructs a basic root element.
   *
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   */
  public function __construct($context, $reader) {
    parent::__construct(NULL, $context, $reader);
  }

  /**
   * Initializes values for the context.
   *
   * @param XMLStructReaderContext $context
   *   Reader context.
   */
  protected function initializeContext($context) {
    // Skip context preparation.
  }

  /**
   * Gets the element data.
   *
   * @return mixed
   *   Element data.
   */
  public function getData() {
    // Ignore list element when processing root.
    if (isset($this->context['listElement'])) {
      unset($this->context['listElement']);
    }

    return parent::getData();
  }
}

/**
 * Factory for the empty attribute interpreter for a default reader.
 */
class XMLStructReader_DefaultWildcardAttributeFactory implements XMLStructReader_AttributeInterpreterFactory {
  /**
   * Returns the namespace to interpret in.
   *
   * @return string|null
   *   URI of the namespace, NULL if no namespace, or '*' if all other
   *   unprocessed namespaces.
   */
  public function getNamespace() {
    return '*';
  }

  /**
   * Returns the name of the attribute to interpret.
   *
   * @return string
   *   Name of the XML attribute, or '*' if all attributes not interpreted by
   *   other interpreters are processed.
   */
  public function getAttributeName() {
    return '*';
  }

  /**
   * Creates an attribute interpreter.
   *
   * @param string $name
   *   Attribute name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $element
   *   Containing element interpreter.
   * @return XMLStructReader_AttributeInterpreter
   *   Attribute interpreter object.
   */
  public function createAttributeInterpreter($name, $context, $reader, $element) {
    return new XMLStructReader_EmptyAttribute();
  }
}

/**
 * Attribute interpreter that does nothing.
 */
class XMLStructReader_EmptyAttribute implements XMLStructReader_AttributeInterpreter {
  /**
   * Handles the element attribute.
   *
   * @param string $value
   *   Attribute value.
   */
  public function processAttribute($value) {
    // Do nothing.
  }
}

/**
 * Default attribute interpreter factory.
 */
class XMLStructReader_DefaultAttributeFactory implements XMLStructReader_AttributeInterpreterFactory {
  /**
   * Returns the namespace to interpret in.
   *
   * @return string|null
   *   URI of the namespace, NULL if no namespace, or '*' if all other
   *   unprocessed namespaces.
   */
  public function getNamespace() {
    // Only handle attributes with no namespace.
    return NULL;
  }

  /**
   * Returns the name of the attribute to interpret.
   *
   * @return string
   *   Name of the XML attribute, or '*' if all attributes not interpreted by
   *   other interpreters are processed.
   */
  public function getAttributeName() {
    return '*';
  }

  /**
   * Creates an attribute interpreter.
   *
   * @param string $name
   *   Attribute name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $element
   *   Containing element interpreter.
   * @return XMLStructReader_AttributeInterpreter
   *   Attribute interpreter object.
   */
  public function createAttributeInterpreter($name, $context, $reader, $element) {
    return new XMLStructReader_DefaultAttribute($name, $context, $reader, $element);
  }
}

/**
 * Default attribute interpreter.
 */
class XMLStructReader_DefaultAttribute implements XMLStructReader_AttributeInterpreter {
  /**
   * Element name.
   * @var $name.
   */
  protected $name;

  /**
   * Element context.
   * @var XMLStructReaderContext
   */
  protected $context;

  /**
   * Associated reader.
   * @var XMLStructReader
   */
  protected $reader;

  /**
   * Containing element.
   * @var XMLStructReader_ElementInterpreter
   */
  protected $element;

  /**
   * Creates an element interpreter.
   *
   * @param string $name
   *   Attribute name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $element
   *   Containing element interpreter.
   */
  public function __construct($name, $context, $reader, $element = NULL) {
    $this->name = $name;
    $this->context = $context;
    $this->reader = $reader;
    $this->element = $element;
  }

  /**
   * Handles the element attribute.
   *
   * @param string $value
   *   Attribute value.
   */
  public function processAttribute($value) {
    $this->element->addAttribute($this->name, $value);
  }
}

/**
 * XMLStructReader-namespaced attribute interpreter factory.
 */
class XMLStructReader_StructAttributeFactory implements XMLStructReader_AttributeInterpreterFactory {
  /**
   * Returns the namespace to interpret in.
   *
   * @return string
   *   XMLStructReader namespaces.
   */
  public function getNamespace() {
    return XMLStructReader::NS;
  }

  /**
   * Returns the name of the attribute to interpret.
   *
   * @return string
   *   Name of the XML attribute, or '*' if all elements not interpreted by
   *   other interpreters are processed.
   */
  public function getAttributeName() {
    return '*';
  }

  /**
   * Creates an attribute interpreter.
   *
   * @param string $name
   *   Attribute name.
   * @param XMLStructReaderContext $context
   *   Reader context.
   * @param XMLStructReader $reader
   *   Associated reader.
   * @param XMLStructReader_ElementInterpreter $element
   *   Containing element interpreter.
   * @return XMLStructReader_AttributeInterpreter
   *   Attribute interpreter object.
   */
  public function createAttributeInterpreter($name, $context, $reader, $element) {
    return new XMLStructReader_StructAttribute($name, $context, $reader, $element);
  }
}

/**
 * XMLStructReader-namespaced attribute interpreter.
 */
class XMLStructReader_StructAttribute extends XMLStructReader_DefaultAttribute {
  /**
   * Handles the element attribute.
   *
   * @param string $value
   *   Attribute value.
   */
  public function processAttribute($value) {
    // Use NULL value.
    if ($value == 'php:null') {
      $value = NULL;
    }
    // Use TRUE value.
    elseif ($value == 'php:true') {
      $value = TRUE;
    }
    // Use FALSE value.
    elseif ($value == 'php:false') {
      $value = FALSE;
    }
    // Set context.
    $this->context[$this->name] = $value;
  }
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
   * @throws InvalidArgumentException
   *   If the file parameter is invalid.
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
