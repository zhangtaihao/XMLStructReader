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

  protected function tearDown() {
    @fclose($this->fileResource);
    unset($this->fileObject);
  }
}
