<?php

require_once 'XMLStructReader.php';

/**
 * Stream delegate utility test.
 */
class XMLStructReader_StreamDelegateTest extends PHPUnit_Framework_TestCase {
  protected $fileResource;
  protected $fileObject;

  protected function setUp() {
    $this->fileResource = fopen('tests/basic.xml', 'r');
    $this->fileObject = new SplFileObject('tests/basic.xml');
  }

  protected function createResourceDelegate() {
    return new XMLStructReader_StreamDelegate($this->fileResource);
  }

  protected function createObjectDelegate() {
    return new XMLStructReader_StreamDelegate($this->fileObject);
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

  /**
   * @depends testCreateResourceDelegate
   */
  public function testResourceStreamEOF() {
    $delegate = $this->createResourceDelegate();
    $this->assertFalse($delegate->isEOF(), 'A delegate can read a resource.');
  }

  /**
   * @depends testResourceStreamEOF
   */
  public function testResourceStreamReadLine() {
    $delegate = $this->createResourceDelegate();
    $lines = array();
    while (!$delegate->isEOF()) {
      $lines[] = $delegate->readLine();
    }
    $expectedLines = $this->getExpectedLines();
    $this->assertSame($expectedLines, $lines, 'A resource delegate correctly reads lines.');
  }

  /**
   * @depends testCreateObjectDelegate
   */
  public function testObjectStreamEOF() {
    $delegate = $this->createObjectDelegate();
    $this->assertFalse($delegate->isEOF(), 'A delegate can read a file object.');
  }

  /**
   * @depends testObjectStreamEOF
   */
  public function testObjectStreamReadLine() {
    $delegate = $this->createObjectDelegate();
    $lines = array();
    while (!$delegate->isEOF()) {
      $lines[] = $delegate->readLine();
    }
    $expectedLines = $this->getExpectedLines();
    $this->assertSame($expectedLines, $lines, 'A file object delegate correctly reads lines.');
  }

  protected function tearDown() {
    @fclose($this->fileResource);
    unset($this->fileObject);
  }
}
