<?php

require_once 'XMLStructReaderTest.inc.php';
require_once 'XMLStructReaderAPI.inc.php';

/**
 * Reader and factory tests.
 */
class XMLStructReaderAPITest extends XMLStructReaderTestCase {
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
   * @depends testGetOption
   * @dataProvider delegateProvider
   */
  public function testGetOptions($delegate) {
    $reader = new TestXMLStructReader($delegate);
    $options = $reader->getOptions();
    $this->assertTrue(!empty($options['test1']), 'The entire option values set can be retrieved.');
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
  public function testSetXMLOption($delegate) {
    $reader = new TestXMLStructReader($delegate);
    // Reset XML option for testing.
    xml_parser_set_option($reader->parser, XML_OPTION_CASE_FOLDING, TRUE);

    $result = $reader->setXMLOption(XML_OPTION_CASE_FOLDING, FALSE);
    $this->assertTrue($result, 'XML option can be set');
  }

  /**
   * @depends testSetXMLOption
   * @dataProvider delegateProvider
   */
  public function testGetXMLOption($delegate) {
    $reader = new TestXMLStructReader($delegate);
    // Reset XML option for testing.
    xml_parser_set_option($reader->parser, XML_OPTION_CASE_FOLDING, TRUE);

    $reader->setXMLOption(XML_OPTION_CASE_FOLDING, FALSE);
    $value = $reader->getXMLOption(XML_OPTION_CASE_FOLDING);
    $this->assertFalse((bool) $value, 'XML option can be retrieved.');
  }

  /**
   * @depends testSetXMLOption
   * @dataProvider delegateProvider
   * @expectedException RuntimeException
   * @expectedExceptionMessage XML parser does not exist.
   */
  public function testInvalidSetXMLOption($delegate) {
    $reader = new TestInvalidParserXMLStructReader($delegate);
    $reader->setXMLOption(XML_OPTION_CASE_FOLDING, FALSE);
  }

  /**
   * @depends testGetXMLOption
   * @dataProvider delegateProvider
   * @expectedException RuntimeException
   * @expectedExceptionMessage XML parser does not exist.
   */
  public function testInvalidGetXMLOption($delegate) {
    $reader = new TestInvalidParserXMLStructReader($delegate);
    $reader->getXMLOption(XML_OPTION_CASE_FOLDING);
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

  public function dataBasic() {
    return '<test attr="value">content</test>';
  }

  public function dataMultiline() {
    return '<test attr="value">
      content
    </test>';
  }

  public function dataSets() {
    return array('dataBasic', 'dataMultiline');
  }

  protected function dataInvalid() {
    return '<invalid></test>';
  }

  protected function dataInvalidEntity() {
    return '<test>&unknown;</test>';
  }

  public function invalidDelegateProvider() {
    return array(
      array($this->createXMLDelegate($this->dataInvalid())),
      array($this->createXMLDelegate($this->dataInvalidEntity())),
    );
  }
}
