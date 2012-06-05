<?php

require_once 'XMLStructReader.php';

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

  /**
   * @param $delegate XMLStructReader_StreamDelegate
   * @return XMLStructReader
   */
  public function getMockReader($delegate) {
    // Create a mock object with custom initialization mechanisms.
    $methods = array('getDefaultOptions', 'startElement', 'characterData', 'endElement', 'getData');
    $reader = $this->getMockBuilder('XMLStructReader')
      ->setMethods($methods)
      ->disableOriginalConstructor()
      ->getMock();
    $reader->expects($this->atLeastOnce())
      ->method('getDefaultOptions')
      ->will($this->returnValue(array('test1' => 'default', 'test2' => 'default')));

    // Initialize reader.
    $arguments = func_get_args();
    call_user_func_array(array($reader, '__construct'), $arguments);
    return $reader;
  }

  /**
   * @return XMLStructReader
   */
  public function getMockReaderWithoutParser() {
    // Mock a reader that does not initialize the XML parser.
    $methods = array('createParser', 'startElement', 'characterData', 'endElement', 'getData');
    $arguments = func_get_args();
    return $this->getMock('XMLStructReader', $methods, $arguments);
  }

  /**
   * @return XMLStructReaderFactory
   */
  public function getMockReaderFactory() {
    $testCase = $this;

    $arguments = func_get_args();
    $factory = $this->getMockForAbstractClass('XMLStructReaderFactory', $arguments);
    $factory->expects($this->any())
      ->method('createReaderObject')
      ->will($this->returnCallback(
      function ($delegate, array $options = array(), $context = NULL) use (&$testCase) {
        /** @var $testCase XMLStructReaderTestCase */
        return $testCase->getMockReader($delegate, $options, $context);
      }));

    return $factory;
  }
}
