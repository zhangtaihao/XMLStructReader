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
  public function testGetOption($delegate) {
    $reader = new TestXMLStructReader($delegate);
    $this->assertEquals('default', $reader->getOption('test1'), 'A default option can be retrieved.');
    $this->assertNull($reader->getOption('invalid'), 'An invalid option is NULL.');
  }

  /**
   * @depends testGetOption
   * @dataProvider delegateProvider
   */
  public function testSetOption($delegate) {
    $reader = new TestXMLStructReader($delegate);
    $reader->setOption('test1', 'custom1');
    $this->assertEquals('custom1', $reader->getOption('test1'), 'A custom option can be set.');
  }

  /**
   * @depends testSetOption
   * @dataProvider delegateProvider
   */
  public function testSetOptions($delegate) {
    $reader = new TestXMLStructReader($delegate, array('test1' => 'custom1'));
    $this->assertEquals('custom1', $reader->getOption('test1'), 'Custom options can be set via the constructor.');
    $reader->setOptions(array('test2' => 'custom2'));
    $this->assertEquals('custom2', $reader->getOption('test2'), 'Custom options can be set via the method.');
  }

  /**
   * @depends testSetOptions
   * @dataProvider delegateProvider
   */
  public function testResetOptions($delegate) {
    $reader = new TestXMLStructReader($delegate, array('test1' => 'custom1'));
    $reader->resetOptions();
    $this->assertEquals('default', $reader->getOption('test1'), 'A custom option can be reset.');
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
