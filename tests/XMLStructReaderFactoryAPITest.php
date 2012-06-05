<?php

require_once 'XMLStructReaderTest.inc.php';

/**
 * Reader factory test.
 */
class XMLStructReaderFactoryAPITest extends XMLStructReaderTestCase {
  public function testCreateFactory() {
    $factory = $this->getMockReaderFactory();
    $this->assertInstanceOf('XMLStructReaderFactory', $factory);
  }

  /**
   * @expectedException InvalidArgumentException
   * @expectedExceptionMessage Owner is not a valid object.
   */
  public function testCreateFactoryInvalidOwner() {
    /** @noinspection PhpParamsInspection */
    new DefaultXMLStructReaderFactory('invalid value');
  }

  /**
   * @expectedException InvalidArgumentException
   * @expectedExceptionMessage Context is not a valid object.
   */
  public function testCreateFactoryInvalidContext() {
    /** @noinspection PhpParamsInspection */
    new DefaultXMLStructReaderFactory(NULL, 'invalid value');
  }

  /**
   * @depends testCreateFactory
   * @dataProvider fileProvider
   */
  public function testCreateReader($file) {
    $factory = $this->getMockReaderFactory();
    $reader = $factory->createReader($file);
    $this->assertInstanceOf('XMLStructReader', $reader, 'Basic reader can be created from factory.');
  }

  /**
   * @depends testCreateFactory
   * @dataProvider pathProvider
   */
  public function testCreateReaderFromPath($path) {
    $factory = $this->getMockReaderFactory();
    $reader = $factory->createReader($path);
    $this->assertInstanceOf('XMLStructReader', $reader, 'Reader can be created from a path.');
  }

  /**
   * @depends testCreateReader
   * @dataProvider fileProvider
   */
  public function testCreateFactoryWithOwner($file) {
    $factory = $this->getMockReaderFactory();
    $reader = $factory->createReader($file);
    $subFactory = $this->getMockReaderFactory($reader);
    $this->assertInstanceOf('XMLStructReaderFactory', $subFactory);
    $this->assertInstanceOf('XMLStructReader', $subFactory->getOwner(), 'Basic factory can be created with an owner.');
  }

  /**
   * @depends testCreateFactoryWithOwner
   * @dataProvider fileProvider
   */
  public function testCreateFactoryWithContext($file) {
    $factory = $this->getMockReaderFactory();
    $reader = $factory->createReader($file);
    $subFactory = $this->getMockReaderFactory($reader, $reader->getContext());
    $this->assertInstanceOf('XMLStructReaderFactory', $subFactory);
    $this->assertInstanceOf('XMLStructReaderContext', $subFactory->getContext(), 'Basic factory can be created with a context.');
  }

  protected function getXMLPath() {
    $xml = '<root/>';
    return $this->createXMLPath($xml);
  }

  public function fileProvider() {
    return array(array(new SplFileObject($this->getXMLPath())));
  }

  public function pathProvider() {
    return array(array($this->getXMLPath()));
  }
}
