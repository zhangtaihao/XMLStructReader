<?php

require_once 'XMLStructReader.php';

/**
 * Reader factory test.
 */
class XMLStructReaderFactoryAPITest extends PHPUnit_Framework_TestCase {
  public function testCreateFactory() {
    $factory = new TestXMLStructReaderFactory();
    $this->assertTrue(is_object($factory), 'Basic factory can be created.');
  }

  /**
   * @expectedException InvalidArgumentException
   * @expectedExceptionMessage Owner is not a valid object.
   */
  public function testCreateFactoryInvalidOwner() {
    new DefaultXMLStructReaderFactory('invalid value');
  }

  /**
   * @expectedException InvalidArgumentException
   * @expectedExceptionMessage Context is not a valid object.
   */
  public function testCreateFactoryInvalidContext() {
    new DefaultXMLStructReaderFactory(NULL, 'invalid value');
  }

  /**
   * @depends testCreateFactory
   * @dataProvider fileProvider
   */
  public function testCreateReader($file) {
    $factory = new TestXMLStructReaderFactory();
    $reader = $factory->createReader($file);
    $this->assertTrue(is_object($reader), 'Basic reader can be created from factory.');
  }

  /**
   * @depends testCreateFactory
   * @dataProvider pathProvider
   */
  public function testCreateReaderFromPath($path) {
    $factory = new TestXMLStructReaderFactory();
    $reader = $factory->createReader($path);
    $this->assertTrue(is_object($reader), 'Reader can be created from a path.');
  }

  /**
   * @depends testCreateReader
   * @dataProvider fileProvider
   */
  public function testCreateFactoryWithOwner($file) {
    $factory = new TestXMLStructReaderFactory();
    $reader = $factory->createReader($file);
    $subFactory = new TestXMLStructReaderFactory($reader);
    $this->assertTrue(is_object($subFactory) && is_object($subFactory->owner), 'Basic factory can be created with an owner.');
  }

  /**
   * @depends testCreateFactoryWithOwner
   * @dataProvider fileProvider
   */
  public function testCreateFactoryWithContext($file) {
    $factory = new TestXMLStructReaderFactory();
    $reader = $factory->createReader($file);
    $subFactory = new TestXMLStructReaderFactory($reader, $reader->getContext());
    $this->assertTrue(is_object($subFactory) && is_object($subFactory->owner), 'Basic factory can be created with a context.');
  }

  protected function getDataPath() {
    $xml = '<test attr="value">test</test>';
    return 'data://text/plain,' . $xml;
  }

  public function fileProvider() {
    return array(array(new SplFileObject($this->getDataPath())));
  }

  public function pathProvider() {
    return array(array($this->getDataPath()));
  }
}

/**
 * Test reader.
 */
class TestXMLStructReader extends XMLStructReader {
  public $data = array();

  protected function getDefaultOptions() {
    return array(
      'test1' => 'default',
      'test2' => 'default',
    ) + parent::getDefaultOptions();
  }

  public function startElement($parser, $name, array $attributes) {
    $data['start'] = $name;
    $data['attributes'] = $attributes;
  }

  public function characterData($parser, $data) {
    $data['content'] = $data;
  }

  public function endElement($parser, $name) {
    $data['end'] = $name;
  }

  public function getData() {
    return $this->data;
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
