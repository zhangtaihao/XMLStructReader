<?php

require_once 'XMLStructReaderTest.inc.php';
require_once 'XMLStructReader.php';

/**
 * Stream delegate utility test.
 */
class XMLStructReader_StreamDelegateTest extends XMLStructReaderTestCase {
  protected $xmlPath;

  protected function getXMLPath() {
    if (!isset($this->xmlPath)) {
      $xml = "<root>\n" .
        "  <element>\n" .
        "    <property>value</property>\n" .
        "  </element>\n" .
        "</root>";
      $this->xmlPath = $this->createXMLPath($xml);
    }
    return $this->xmlPath;
  }

  protected function createResourceDelegate() {
    return new XMLStructReader_StreamDelegate(fopen($this->getXMLPath(), 'r'));
  }

  protected function createObjectDelegate() {
    return new XMLStructReader_StreamDelegate(new SplFileObject($this->getXMLPath()));
  }

  protected function getExpectedLines() {
    return array(
      "<root>\n",
      "  <element>\n",
      "    <property>value</property>\n",
      "  </element>\n",
      "</root>",
    );
  }

  public function testCreateResourceDelegate() {
    $delegate = $this->createResourceDelegate();
    $this->assertTrue($delegate->isResource(), 'A delegate is created for a file resource.');
  }

  public function testCreateObjectDelegate() {
    $delegate = $this->createObjectDelegate();
    $this->assertTrue($delegate->isObject(), 'A delegate is created for a file object.');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testCreateInvalidDelegate() {
    new XMLStructReader_StreamDelegate(NULL);
  }

  public function resourceDelegateProvider() {
    return array(array($this->createResourceDelegate()));
  }

  public function objectDelegateProvider() {
    return array(array($this->createResourceDelegate()));
  }

  /**
   * @depends testCreateResourceDelegate
   * @dataProvider resourceDelegateProvider
   */
  public function testResourceStreamEOF(XMLStructReader_StreamDelegate $delegate) {
    $this->assertFalse($delegate->isEOF(), 'A delegate can read a resource.');
  }

  /**
   * @depends testResourceStreamEOF
   * @dataProvider resourceDelegateProvider
   */
  public function testResourceStreamReadLine(XMLStructReader_StreamDelegate $delegate) {
    $lines = array();
    while (!$delegate->isEOF()) {
      $lines[] = $delegate->readLine();
    }
    $expectedLines = $this->getExpectedLines();
    $this->assertSame($expectedLines, $lines, 'A resource delegate correctly reads lines.');
  }

  /**
   * @depends testCreateObjectDelegate
   * @dataProvider objectDelegateProvider
   */
  public function testObjectStreamEOF(XMLStructReader_StreamDelegate $delegate) {
    $this->assertFalse($delegate->isEOF(), 'A delegate can read a file object.');
  }

  /**
   * @depends testObjectStreamEOF
   * @dataProvider objectDelegateProvider
   */
  public function testObjectStreamReadLine(XMLStructReader_StreamDelegate $delegate) {
    $lines = array();
    while (!$delegate->isEOF()) {
      $lines[] = $delegate->readLine();
    }
    $expectedLines = $this->getExpectedLines();
    $this->assertSame($expectedLines, $lines, 'A file object delegate correctly reads lines.');
  }
}
