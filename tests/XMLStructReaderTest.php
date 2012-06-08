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

  public function createRegistryTest($xml) {
    $testCase = $this;
    $elementMockException = function ($message) use (&$testCase) {
      /** @var $testCase XMLStructReaderTest */
      return $testCase->returnMockException('XMLStructReader_ElementInterpreter', array(), array('processElement'), $message);
    };

    $factories = array();
    $factories[] = $this->createElementInterpreterFactory(NULL, 'element', $elementMockException('Exact match.'));
    $factories[] = $this->createElementInterpreterFactory('*', 'element', $elementMockException('Wildcard namespace.'));
    $factories[] = $this->createElementInterpreterFactory(NULL, '*', $elementMockException('Wildcard element.'));
    $factories[] = $this->createElementInterpreterFactory('*', '*', $elementMockException('Wildcard match.'));
    return $this->createElementReader($this->createXMLDelegate($xml), $factories);
  }

  /**
   * @depends testReadNullInterpreter
   * @depends testReadNullAttributeInterpreter
   * @depends testRegistryExact
   * @depends testRegistryWildcardNamespace
   * @depends testRegistryWildcardElement
   * @depends testRegistryWildcard
   */
  public function testStructAttribute() {
    $testCase = $this;
    $capturedContext = NULL;
    $reader = NULL;
    $processElement = function () use (&$testCase, &$capturedContext, &$reader) {
      /** @var $testCase XMLStructReaderTest */
      /** @var $reader XMLStructReader */
      if ($context = $reader->getContext()) {
        $capturedContext = clone $context;
      }
    };

    // Mock element interpreter to capture context value.
    $createElementInterpreter = $this->returnMock('XMLStructReader_DefaultElement', $this->returnArguments(), array(
      'processElement' => $processElement,
    ));
    $elementFactory = $this->createElementInterpreterFactory('urn:test', 'element', $createElementInterpreter);

    // Prepare for testing.
    $attributeFactory = new XMLStructReader_StructAttributeFactory();
    $factories = array($elementFactory, $attributeFactory);
    /** @var $capturedContext XMLStructReaderContext */
    $contextDelegate = function ($value) use (&$testCase) {
      /** @var $testCase XMLStructReaderTest */
      return $testCase->createXMLDelegate(sprintf('<element x:test="%s" xmlns="urn:test" xmlns:x="%s"/>', $value, XMLStructReader::NS));
    };

    // Test normal value.
    $reader = $this->createElementReader($contextDelegate('context'), $factories);
    $reader->read();
    $this->assertSame('context', $capturedContext['test']);

    // Test NULL value.
    $reader = $this->createElementReader($contextDelegate('php:null'), $factories);
    $reader->read();
    $this->assertSame(NULL, $capturedContext['test']);

    // Test TRUE value.
    $reader = $this->createElementReader($contextDelegate('php:true'), $factories);
    $reader->read();
    $this->assertSame(TRUE, $capturedContext['test']);

    // Test FALSE value.
    $reader = $this->createElementReader($contextDelegate('php:false'), $factories);
    $reader->read();
    $this->assertSame(FALSE, $capturedContext['test']);
  }

  /**
   * @depends testStructAttribute
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

  /**
   * @param XMLStructReader_StreamDelegate $delegate
   * @param XMLStructReader_InterpreterFactory[] $factories
   * @return XMLStructReader
   */
  public function createElementReader($delegate, $factories) {
    /** @var $reader PHPUnit_Framework_MockObject_MockObject */
    $reader = $this->getMockBuilder('DefaultXMLStructReader')
      ->disableOriginalConstructor()
      ->setMethods(array('setUpInterpreters'))
      ->getMock();

    $elementMethod = new ReflectionMethod($reader, 'registerElementInterpreterFactory');
    $elementMethod->setAccessible(TRUE);
    $attributeMethod = new ReflectionMethod($reader, 'registerAttributeInterpreterFactory');
    $attributeMethod->setAccessible(TRUE);

    $reader->expects($this->once())
      ->method('setUpInterpreters')
      ->will($this->returnCallback(
      function () use (&$reader, &$elementMethod, &$attributeMethod, &$factories) {
        /** @var $elementMethod ReflectionMethod */
        /** @var $attributeMethod ReflectionMethod */
        foreach ($factories as $factory) {
          if ($factory instanceof XMLStructReader_ElementInterpreterFactory) {
            $elementMethod->invoke($reader, $factory);
          }
          elseif ($factory instanceof XMLStructReader_AttributeInterpreterFactory) {
            $attributeMethod->invoke($reader, $factory);
          }
        }
      }));

    // Test reader.
    /** @var $reader XMLStructReader */
    $reader->__construct($delegate);
    return $reader;
  }

  /**
   * @param $namespace
   * @param $name
   * @param PHPUnit_Framework_MockObject_Stub $createElementStub
   * @return XMLStructReader_ElementInterpreterFactory
   */
  public function createElementInterpreterFactory($namespace, $name, $createElementStub = NULL) {
    $factory = $this->getMock('XMLStructReader_ElementInterpreterFactory');

    $factory->expects($this->any())
      ->method('getNamespace')
      ->will($this->returnValue($namespace));

    $factory->expects($this->any())
      ->method('getElementName')
      ->will($this->returnValue($name));

    if (isset($createElementStub)) {
      $factory->expects($this->any())
        ->method('createElementInterpreter')
        ->will($createElementStub);
    }

    return $factory;
  }
}
