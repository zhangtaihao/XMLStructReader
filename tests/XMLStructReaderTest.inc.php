<?php

abstract class XMLStructReaderTestCase extends PHPUnit_Framework_TestCase {
  public function dataBasic() {
    return '<root>
      <element test="value">test</element>
    </root>';
  }

  public function dataNamespace() {
    return '<root xmlns="http://example.com/" xmlns:x="http://example.com/attr/">
      <element x:test="value">test</element>
    </root>';
  }

  public function dataSets() {
    return array('dataBasic', 'dataNamespace');
  }

  public function fileProvider() {
    $data = array();
    foreach ($this->dataSets() as $dataMethod) {
      $data[] = array($this->createXMLFile($this->$dataMethod()));
    }
    return $data;
  }

  public function delegateProvider() {
    $data = array();
    foreach ($this->dataSets() as $dataMethod) {
      $data[] = array($this->createXMLDelegate($this->$dataMethod()));
    }
    return $data;
  }

  protected function createXMLPath($xml) {
    return 'data://text/plain,' . $xml;
  }

  protected function createXMLFile($xml) {
    return new SplFileObject($this->createXMLPath($xml));
  }

  protected function createXMLDelegate($xml) {
    // @codeCoverageIgnoreStart
    return new XMLStructReader_StreamDelegate($this->createXMLFile($xml));
    // @codeCoverageIgnoreEnd
  }
}
