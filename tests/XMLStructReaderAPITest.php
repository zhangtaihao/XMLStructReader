<?php

require_once 'XMLStructReaderTest.inc.php';

/**
 * Reader and factory tests.
 */
class XMLStructReaderAPITest extends XMLStructReaderTestCase {
  /**
   * @dataProvider delegateProvider
   */
  public function testObject($delegate) {
    $reader = $this->getMockReader($delegate);
    $reader->__construct($delegate);
    $this->assertInstanceOf('XMLStructReader', $reader, 'Reader can be constructed.');
    // Simulate destructor.
    $reader->__destruct();
  }

  /**
   * @depends testObject
   * @dataProvider delegateProvider
   */
  public function testGetOption($delegate) {
    $reader = $this->getMockReader($delegate);
    $this->assertEquals('default', $reader->getOption('test1'), 'A default option can be retrieved.');
    $this->assertNull($reader->getOption('invalid'), 'An invalid option is NULL.');
  }

  /**
   * @depends testGetOption
   * @dataProvider delegateProvider
   */
  public function testSetOption($delegate) {
    $reader = $this->getMockReader($delegate);
    $reader->setOption('test1', 'custom1');
    $this->assertEquals('custom1', $reader->getOption('test1'), 'A custom option can be set.');
  }

  /**
   * @depends testGetOption
   * @dataProvider delegateProvider
   */
  public function testGetOptions($delegate) {
    $reader = $this->getMockReader($delegate);
    $options = $reader->getOptions();
    $this->assertTrue(!empty($options['test1']), 'The entire option values set can be retrieved.');
  }

  /**
   * @depends testSetOption
   * @dataProvider delegateProvider
   */
  public function testSetOptions($delegate) {
    $reader = $this->getMockReader($delegate, array('test1' => 'custom1'));
    $this->assertEquals('custom1', $reader->getOption('test1'), 'Custom options can be set via the constructor.');
    $reader->setOptions(array('test2' => 'custom2'));
    $this->assertEquals('custom2', $reader->getOption('test2'), 'Custom options can be set via the method.');
  }

  /**
   * @depends testSetOptions
   * @dataProvider delegateProvider
   */
  public function testResetOptions($delegate) {
    $reader = $this->getMockReader($delegate, array('test1' => 'custom1'));
    $reader->resetOptions();
    $this->assertEquals('default', $reader->getOption('test1'), 'A custom option can be reset.');
  }

  /**
   * @depends testObject
   * @dataProvider delegateProvider
   */
  public function testXMLOption($delegate) {
    $reader = $this->getMockReader($delegate);
    $reader->setXMLOption(XML_OPTION_CASE_FOLDING, TRUE);
    $result = $reader->setXMLOption(XML_OPTION_CASE_FOLDING, FALSE);
    $this->assertTrue($result, 'XML option can be set');
    $value = $reader->getXMLOption(XML_OPTION_CASE_FOLDING);
    $this->assertFalse((bool) $value, 'XML option can be retrieved.');
  }

  /**
   * @depends testXMLOption
   * @dataProvider delegateProvider
   * @expectedException RuntimeException
   * @expectedExceptionMessage XML parser does not exist.
   */
  public function testInvalidSetXMLOption($delegate) {
    $reader = $this->getMockReaderWithoutParser($delegate);
    $reader->setXMLOption(XML_OPTION_CASE_FOLDING, FALSE);
  }

  /**
   * @depends testXMLOption
   * @dataProvider delegateProvider
   * @expectedException RuntimeException
   * @expectedExceptionMessage XML parser does not exist.
   */
  public function testInvalidGetXMLOption($delegate) {
    $reader = $this->getMockReaderWithoutParser($delegate);
    $reader->getXMLOption(XML_OPTION_CASE_FOLDING);
  }

  /**
   * @depends testObject
   * @dataProvider delegateProvider
   */
  public function testContext($delegate) {
    $context = new XMLStructReaderContext(array('key' => 'value'));
    $this->assertInstanceOf('XMLStructReaderContext', $context, 'A context can be created.');
    $reader = $this->getMockReader($delegate, array(), $context);
    $context = $reader->getContext();
    $this->assertObjectHasAttribute('key', $context, 'Reader context value can be retrieved.');
  }

  /**
   * @depends testObject
   * @dataProvider delegateProvider
   */
  public function testRead($delegate) {
    // Create mock.
    /** @var $reader PHPUnit_Framework_MockObject_MockObject */
    $reader = $this->getMockForAbstractClass('XMLStructReader', array($delegate));

    // Mock each method to store data for validation.
    $data = array();
    $reader->expects($this->atLeastOnce())
      ->method('startElement')
      ->will($this->returnCallback(
      function ($parser, $name, array $attributes) use (&$data) {
        $data['start'] = $name;
        $data['attributes'] = $attributes;
      }));
    $reader->expects($this->atLeastOnce())
      ->method('characterData')
      ->will($this->returnCallback(
      function ($parser, $cData) use (&$data) {
        $data['content'] = trim($cData);
      }));
    $reader->expects($this->atLeastOnce())
      ->method('endElement')
      ->will($this->returnCallback(
      function ($parser, $name) use (&$data) {
        $data['end'] = $name;
      }));
    $reader->expects($this->atLeastOnce())
      ->method('getData')
      ->will($this->returnCallback(
      function () use (&$data) {
        return $data;
      }));

    // Test reading data.
    /** @var $reader XMLStructReader */
    $readData = $reader->read();
    $expectedData = array(
      'start' => 'test',
      'attributes' => array('attr' => 'value'),
      'content' => 'content',
      'end' => 'test',
    );
    $this->assertSame($expectedData, $readData, 'Data is correctly read from a reader.');
  }

  /**
   * @depends testObject
   * @dataProvider delegateProvider
   * @expectedException RuntimeException
   * @expectedExceptionMessage Data could not be read.
   */
  public function testReadInvalidParser($delegate) {
    $reader = $this->getMockReaderWithoutParser($delegate);
    $reader->read();
  }

  /**
   * @depends testObject
   * @dataProvider invalidDelegateProvider
   * @expectedException XMLStructReaderException
   */
  public function testReadInvalidXML($delegate) {
    $reader = $this->getMockReader($delegate);
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
