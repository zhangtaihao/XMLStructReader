<?php

require_once 'XMLStructReaderAPI.inc.php';

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
    $xml = '<root/>';
    return 'data://text/plain,' . $xml;
  }

  public function fileProvider() {
    return array(array(new SplFileObject($this->getDataPath())));
  }

  public function pathProvider() {
    return array(array($this->getDataPath()));
  }
}
