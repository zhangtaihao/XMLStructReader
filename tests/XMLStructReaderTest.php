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

  public function dataBasic() {
    return new SplFileObject('data://text/plain,<root>
      <element>test</element>
    </root>');
  }

  protected function createFiles($dataMethod) {
    $data = array();
    $dataMethods = func_get_args();
    foreach ($dataMethods as $dataMethod) {
      if (method_exists($this, $dataMethod)) {
        $data[] = array($this->$dataMethod());
      }
    }
    return $data;
  }

  public function fileProvider() {
    return $this->createFiles('dataBasic');
  }
}
