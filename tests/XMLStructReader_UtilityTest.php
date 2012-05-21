<?php
/**
 * Stream delegate utility test.
 */
class XMLStructReader_StreamDelegateTest extends PHPUnit_Framework_TestCase {
  protected $fileResource;
  protected $fileObject;

  protected function setUp() {
    require_once 'XMLStructReader.php';
    $this->fileResource = fopen('tests/basic.xml', 'r');
    $this->fileObject = new SplFileObject('tests/basic.xml');
  }

  public function testCreateResourceDelegate() {
    $delegate = new XMLStructReader_StreamDelegate($this->fileResource);
    $this->assertTrue($delegate->isResource(), 'A delegate is created for a file resource.');
  }

  public function testCreateObjectDelegate() {
    $delegate = new XMLStructReader_StreamDelegate($this->fileObject);
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
