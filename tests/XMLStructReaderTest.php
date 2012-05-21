<?php

require_once 'XMLStructReader.php';

/**
 * Reader factory test.
 */
class XMLStructReaderFactoryTest extends PHPUnit_Framework_TestCase {
  public function testCreateFactory() {
    $factory = new XMLStructReaderFactory();
    $this->assertTrue(is_object($factory), 'Factory can be created.');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testCreateInvalidFactory() {
    new XMLStructReaderFactory('invalid value');
  }

  /**
   * @dataProvider fileProvider
   */
  public function testCreateFactoryWithOwner($file) {
    $factory = new XMLStructReaderFactory();
    $reader = $factory->createReader($file);
    $subFactory = new XMLStructReaderFactory($reader);
    $this->assertTrue(is_object($subFactory), 'Factory can be created with an owner.');
  }

  /**
   * @dataProvider fileProvider
   */
  public function testCreateReader(SplFileObject $file) {
    $factory = new XMLStructReaderFactory();
    $reader = $factory->createReader($file);
    $this->assertTrue(is_object($reader), 'Reader can be created from a file.');
  }

  /**
   * @dataProvider xmlPathProvider
   */
  public function testCreateReaderFromPath($path) {
    $factory = new XMLStructReaderFactory();
    $reader = $factory->createReader($path);
    $this->assertTrue(is_object($reader), 'Reader can be created from a path.');
  }

  public function fileProvider() {
    $xml = '<root><element>test</element></root>';
    return array(array(new SplFileObject('data://text/plain,' . $xml)));
  }

  public function xmlPathProvider() {
    return array(array('tests/basic.xml'));
  }
}
