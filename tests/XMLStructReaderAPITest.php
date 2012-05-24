<?php

require_once 'XMLStructReaderAPI.inc.php';

/**
 * Reader and factory tests.
 */
class XMLStructReaderAPITest extends PHPUnit_Framework_TestCase {
  /**
   * @dataProvider delegateProvider
   */
  public function testObject($delegate) {
    $reader = new TestXMLStructReader($delegate);
    $this->assertTrue(is_object($reader), 'Reader can be constructed.');
    // Simulate destructor.
    $reader->__destruct();
    $this->assertTrue(TRUE, is_object($reader), 'Reader can be destructed.');
  }

  /**
   * @depends testObject
   * @dataProvider delegateProvider
   */
  public function testOptions($delegate) {
    $reader = new TestXMLStructReader($delegate, array('test1' => 'custom'));
    $this->assertEquals('default', $reader->getOption('test2'), 'A default option can be retrieved.');
    $this->assertEquals('custom', $reader->getOption('test1'), 'A custom option can be retrieved.');
    $reader->resetOptions();
    $this->assertEquals('default', $reader->getOption('test1'), 'A custom option can be reset.');
    $this->assertNull($reader->getOption('invalid'), 'An invalid option is NULL.');
  }

  /**
   * @depends testObject
   * @dataProvider delegateProvider
   */
  public function testContext($delegate) {
    $context = new XMLStructReaderContext(array('key' => 'value'));
    $this->assertTrue(is_object($context), 'A context can be created.');
    $reader = new TestXMLStructReader($delegate, array(), $context);
    $context = $reader->getContext();
    $this->assertObjectHasAttribute('key', $context, 'Reader context value can be retrieved.');
  }

  /**
   * @depends testObject
   * @dataProvider delegateProvider
   */
  public function testRead($delegate) {
    $reader = new TestXMLStructReader($delegate);
    $data = $reader->read();
    $expectedData = array(
      'start' => 'test',
      'attributes' => array('attr' => 'value'),
      'content' => 'content',
      'end' => 'test',
    );
    $this->assertSame($expectedData, $data, 'Data is correctly read from a reader.');
  }

  /**
   * @depends testObject
   * @dataProvider delegateProvider
   * @expectedException RuntimeException
   * @expectedExceptionMessage Data could not be read.
   */
  public function testReadInvalidParser($delegate) {
    $reader = new TestInvalidParserXMLStructReader($delegate);
    $reader->read();
  }

  /**
   * @depends testObject
   * @dataProvider invalidDelegateProvider
   * @expectedException XMLStructReaderException
   */
  public function testReadInvalidXML($delegate) {
    $reader = new TestXMLStructReader($delegate);
    $reader->read();
  }

  protected function getDataPath() {
    $xml = '<test attr="value">content</test>';
    return 'data://text/plain,' . $xml;
  }

  protected function createDelegate() {
    // @codeCoverageIgnoreStart
    $delegate = new XMLStructReader_StreamDelegate(new SplFileObject($this->getDataPath()));
    // @codeCoverageIgnoreEnd
    return $delegate;
  }

  public function delegateProvider() {
    return array(array($this->createDelegate()));
  }

  protected function createInvalidDelegate() {
    // @codeCoverageIgnoreStart
    $xmlPath = 'data://text/plain,<invalid></test>';
    $delegate = new XMLStructReader_StreamDelegate(new SplFileObject($xmlPath));
    // @codeCoverageIgnoreEnd
    return $delegate;
  }

  public function invalidDelegateProvider() {
    return array(array($this->createInvalidDelegate()));
  }
}
