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
   * @dataProvider delegateProvider
   * @expectedException XMLStructReaderException
   * @expectedExceptionMessage No matching attribute interpreter is found.
   */
  public function testReadNullAttributeInterpreter($delegate) {
    $testCase = $this;

    // Mock element factory.
    /** @var $reader PHPUnit_Framework_MockObject_MockObject */
    $factory = $this->getMock('XMLStructReader_ElementInterpreterFactory');
    $factory->expects($this->any())
      ->method('getNamespace')
      ->will($this->returnValue('*'));
    $factory->expects($this->any())
      ->method('getElementName')
      ->will($this->returnValue('*'));
    $factory->expects($this->any())
      ->method('createElementInterpreter')
      ->will($this->returnCallback(
        function () use (&$testCase) {
          /** @var $testCase XMLStructReaderTest */
          return $testCase->getMock('XMLStructReader_ElementInterpreter');
        }));

    // Mock reader.
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
        function () use (&$reader, &$method, &$factory) {
          /** @var $method ReflectionMethod */
          $method->invoke($reader, $factory);
        }));

    // Test reader.
    /** @var $reader XMLStructReader */
    $reader->__construct($delegate);
    $reader->read();
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
}
