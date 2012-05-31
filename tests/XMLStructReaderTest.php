<?php

require_once 'XMLStructReader.php';

/**
 * Test default reader.
 */
class XMLStructReaderTest extends PHPUnit_Framework_TestCase {
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
   * @expectedException RuntimeException
   * @expectedExceptionMessage No matching element interpreter is found.
   */
  public function testReadNullInterpreter($delegate) {
    $reader = new TestNullXMLStructReader($delegate);
    $reader->read();
  }

  /**
   * @dataProvider delegateProvider
   * @expectedException RuntimeException
   * @expectedExceptionMessage No matching attribute interpreter is found.
   */
  public function testReadNullAttributeInterpreter($delegate) {
    $reader = new TestNullAttributeXMLStructReader($delegate);
    $reader->read();
  }

  public function dataBasic() {
    return new SplFileObject('data://text/plain,<root>
      <element test="value">test</element>
    </root>');
  }

  public function dataNamespace() {
    return new SplFileObject('data://text/plain,<root xmlns="http://example.com/" xmlns:x="http://example.com/attr/">
      <element x:test="value">test</element>
    </root>');
  }

  public function dataSets() {
    return array('dataBasic', 'dataNamespace');
  }

  protected function createFiles(array $dataMethods) {
    $data = array();
    foreach ($dataMethods as $dataMethod) {
      if (method_exists($this, $dataMethod)) {
        $data[] = $this->$dataMethod();
      }
    }
    return $data;
  }

  public function fileProvider() {
    $data = array();
    foreach ($this->createFiles($this->dataSets()) as $file) {
      $data[] = array($file);
    }
    return $data;
  }

  public function delegateProvider() {
    $data = array();
    foreach ($this->createFiles($this->dataSets()) as $file) {
      // @codeCoverageIgnoreStart
      $data[] = array(new XMLStructReader_StreamDelegate($file));
      // @codeCoverageIgnoreEnd
    }
    return $data;
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
  public function getNamespace() {}
  public function getElementName() {}
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
