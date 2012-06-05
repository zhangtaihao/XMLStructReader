<?php

require_once 'XMLStructReaderTest.inc.php';
require_once 'XMLStructReader.php';

/**
 * Test default reader.
 */
class XMLStructReaderTest extends XMLStructReaderTestCase {
  public function testCreateFactory() {
    $factory = new DefaultXMLStructReaderFactory();
    $this->assertInstanceOf('DefaultXMLStructReaderFactory', $factory, 'Default factory can be created.');
  }

  /**
   * @depends testCreateFactory
   * @dataProvider fileProvider
   */
  public function testCreateReader($file) {
    $factory = new DefaultXMLStructReaderFactory();
    $reader = $factory->createReader($file);
    $this->assertInstanceOf('DefaultXMLStructReader', $reader, 'Default reader can be created');
  }

  /**
   * @dataProvider delegateProvider
   * @expectedException XMLStructReaderException
   * @expectedExceptionMessage No matching element interpreter is found.
   */
  public function testReadNullInterpreter($delegate) {
    /** @var $reader PHPUnit_Framework_MockObject_MockObject */
    $reader = $this->getMockBuilder('DefaultXMLStructReader')
      ->disableOriginalConstructor()
      ->setMethods(array('setUpInterpreters'))
      ->getMock();
    $reader->expects($this->once())->method('setUpInterpreters');
    /** @var $reader XMLStructReader */
    $reader->__construct($delegate);
    $reader->read();
  }

  /**
   * @expectedException XMLStructReaderException
   * @expectedExceptionMessage No matching attribute interpreter is found.
   */
  public function testReadNullAttributeInterpreter() {
    $delegate = $this->createXMLDelegate('<element attribute="value"/>');
    $factory = $this->createElementInterpreterFactory('*', '*');
    $reader = $this->createElementReader($delegate, array($factory));
    $reader->read();
  }

  /**
   * @expectedException XMLStructReaderException
   * @expectedExceptionMessage Exact match.
   */
  public function testRegistryExact() {
    $this->createRegistryTest('<element/>')->read();
  }

  /**
   * @expectedException XMLStructReaderException
   * @expectedExceptionMessage Wildcard namespace.
   */
  public function testRegistryWildcardNamespace() {
    $this->createRegistryTest('<element xmlns="urn:x"/>')->read();
  }

  /**
   * @expectedException XMLStructReaderException
   * @expectedExceptionMessage Wildcard element.
   */
  public function testRegistryWildcardElement() {
    $this->createRegistryTest('<unmatched/>')->read();
  }

  /**
   * @expectedException XMLStructReaderException
   * @expectedExceptionMessage Wildcard match.
   */
  public function testRegistryWildcard() {
    $this->createRegistryTest('<unmatched xmlns="urn:x"/>')->read();
  }

  /**
   * @dataProvider readDataProvider
   */
  public function testReadData($xml, $expectedValue) {
    $delegate = $this->createXMLDelegate($xml);
    $reader = new DefaultXMLStructReader($delegate);
    $data = $reader->read();
    $this->assertSame($data, $expectedValue);
  }

  public function readDataProvider() {
    return array(
      // Check simple XML.
      array(
        '<element>value</element>',
        array('element' => 'value'),
      ),
      array(
        '<root><element>value</element></root>',
        array('root' => array('element' => 'value')),
      ),
    );
  }

  public function createRegistryTest($xml) {
    $factories = array();
    $factories[] = $this->createElementInterpreterFactory(NULL, 'element', 'Exact match.');
    $factories[] = $this->createElementInterpreterFactory('*', 'element', 'Wildcard namespace.');
    $factories[] = $this->createElementInterpreterFactory(NULL, '*', 'Wildcard element.');
    $factories[] = $this->createElementInterpreterFactory('*', '*', 'Wildcard match.');
    return $this->createElementReader($this->createXMLDelegate($xml), $factories);
  }

  /**
   * @return XMLStructReader_ElementInterpreterFactory
   */
  public function createElementInterpreterFactory($namespace, $name, $exceptionMessage = NULL) {
    $factory = $this->getMock('XMLStructReader_ElementInterpreterFactory');

    $factory->expects($this->any())
      ->method('getNamespace')
      ->will($this->returnValue($namespace));

    $factory->expects($this->any())
      ->method('getElementName')
      ->will($this->returnValue($name));

    $testCase = $this;
    $factory->expects($this->any())
      ->method('createElementInterpreter')
      ->will($this->returnCallback(
        function () use (&$testCase, $exceptionMessage) {
          /** @var $testCase XMLStructReaderTest */
          /** @var $mock PHPUnit_Framework_MockObject_MockObject */
          $mock = $testCase->getMock('XMLStructReader_ElementInterpreter');
          if (isset($exceptionMessage)) {
            $mock->expects($testCase->any())
              ->method('processElement')
              ->will($testCase->throwException(new XMLStructReaderException($exceptionMessage)));
          }
          return $mock;
        }));

    return $factory;
  }

  /**
   * @param XMLStructReader_StreamDelegate $delegate
   * @param XMLStructReader_ElementInterpreterFactory[] $factories
   * @return XMLStructReader
   */
  public function createElementReader($delegate, $factories) {
    /** @var $reader PHPUnit_Framework_MockObject_MockObject */
    $reader = $this->getMockBuilder('DefaultXMLStructReader')
      ->disableOriginalConstructor()
      ->setMethods(array('setUpInterpreters'))
      ->getMock();
    $method = new ReflectionMethod($reader, 'registerElementInterpreterFactory');
    $method->setAccessible(TRUE);
    $reader->expects($this->once())
      ->method('setUpInterpreters')
      ->will($this->returnCallback(
      function () use (&$reader, &$method, &$factories) {
        /** @var $method ReflectionMethod */
        foreach ($factories as $factory) {
          $method->invoke($reader, $factory);
        }
      }));

    // Test reader.
    /** @var $reader XMLStructReader */
    $reader->__construct($delegate);
    return $reader;
  }
}
