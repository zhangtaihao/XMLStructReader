<?php

require_once 'XMLStructReaderTest.inc.php';
require_once 'XMLStructReader.php';

/**
 * Test default reader.
 */
class XMLStructReaderTest extends XMLStructReaderTestCase {
  public function testCreateFactory() {
    $factory = new DefaultXMLStructReaderFactory();
    $this->assertTrue(is_object($factory), 'Default factory can be created.');
  }

  /**
   * @depends testCreateFactory
   * @dataProvider fileProvider
   */
  public function testCreateReader($file) {
    $factory = new DefaultXMLStructReaderFactory();
    $reader = $factory->createReader($file);
    $this->assertTrue(is_object($reader), 'Default reader can be created');
  }

  /**
   * @dataProvider fileProvider
   */
  public function testReadBasic($file) {
    // TODO
  }

  /**
   * @dataProvider delegateProvider
   * @expectedException XMLStructReaderException
   * @expectedExceptionMessage No matching element interpreter is found.
   */
  public function testReadNullInterpreter($delegate) {
    $reader = new TestNullXMLStructReader($delegate);
    $reader->read();
  }

  /**
   * @dataProvider delegateProvider
   * @expectedException XMLStructReaderException
   * @expectedExceptionMessage No matching attribute interpreter is found.
   */
  public function testReadNullAttributeInterpreter($delegate) {
    $reader = new TestNullAttributeXMLStructReader($delegate);
    $reader->read();
  }
}

/**
 * Test reader without interpreters.
 */
class TestNullXMLStructReader extends DefaultXMLStructReader {
  protected function setUpInterpreters() {
    // Skip interpreter setup.
  }
}

/**
 * Test reader without attribute interpreters.
 */
class TestNullAttributeXMLStructReader extends DefaultXMLStructReader {
  protected function setUpInterpreters() {
    // @codeCoverageIgnoreStart
    $this->registerElementInterpreterFactory(new NullElementInterpreterFactory());
    // @codeCoverageIgnoreEnd
  }
}

/**
 * Element interpreter that does nothing.
 */
class NullElementInterpreterFactory implements XMLStructReader_ElementInterpreterFactory {
  public function getNamespace() {
    return '*';
  }
  public function getElementName() {
    return '*';
  }
  public function createElementInterpreter($name, $context, $reader, $parent = NULL) {
    return new NullElementInterpreter();
  }
}

/**
 * Element interpreter that does nothing.
 */
class NullElementInterpreter implements XMLStructReader_ElementInterpreter {
  public function addData($key, $data) {}
  public function addAttribute($name, $value) {}
  public function addCharacterData($data) {}
  public function processElement() {}
  public function getData() {}
}
