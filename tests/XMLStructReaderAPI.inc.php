<?php
/**
 * Test reader implementations.
 */

require_once 'XMLStructReader.php';

/**
 * Test reader.
 */
class TestXMLStructReader extends XMLStructReader {
  public $parser;
  public $data = array();

  protected function getDefaultOptions() {
    return array(
      'test1' => 'default',
      'test2' => 'default',
    ) + parent::getDefaultOptions();
  }

  public function startElement($parser, $name, array $attributes) {
    $this->data['start'] = $name;
    $this->data['attributes'] = $attributes;
  }

  public function characterData($parser, $data) {
    $this->data['content'] = $data;
  }

  public function endElement($parser, $name) {
    $this->data['end'] = $name;
  }

  public function getData() {
    return $this->data;
  }
}

/**
 * Test invalid reader without parser.
 */
class TestInvalidParserXMLStructReader extends TestXMLStructReader {
  protected function setUp() {
    // Skip setting up the parser.
  }
}

/**
 * Test factory producing test reader.
 */
class TestXMLStructReaderFactory extends XMLStructReaderFactory {
  // Expose owner.
  public $owner;

  protected function createReaderObject($delegate, array $options = array(), $context = NULL) {
    return new TestXMLStructReader($delegate, $options, $context);
  }
}
