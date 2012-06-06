<?php

require_once 'XMLStructReader.php';

/**
 * @codeCoverageIgnore
 */
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

  public function returnMockException($mockedClass, array $arguments, array $methods, $message) {
    return new XMLStructReaderMockExceptionStub($this, $mockedClass, $arguments, $methods, $message);
  }
}

/**
 * @codeCoverageIgnore
 */
class XMLStructReaderMockExceptionStub implements PHPUnit_Framework_MockObject_Stub {
  /** @var PHPUnit_Framework_TestCase */
  protected $testCase;

  protected $mockedClass;
  protected $arguments;
  protected $methods;
  protected $message;

  /**
   * Creates the stub with class to mock and methods to throw exception message.
   *
   * @param PHPUnit_Framework_TestCase $testCase
   * @param string $mockedClass
   * @param array $arguments
   * @param array $methods
   * @param string $message
   */
  public function __construct($testCase, $mockedClass, array $arguments, array $methods, $message) {
    $this->testCase = $testCase;
    $this->mockedClass = $mockedClass;
    $this->arguments = $arguments;
    $this->methods = $methods;
    $this->message = $message;
  }

  protected function createMock() {
    /** @var $mock PHPUnit_Framework_MockObject_MockObject */
    $mock = NULL;
    $reflector = new ReflectionClass($this->mockedClass);
    if ($reflector->isAbstract() && !$reflector->isInterface()) {
      $mock = $this->testCase->getMockForAbstractClass($this->mockedClass, $this->arguments, '', TRUE, TRUE, TRUE, $this->methods);
    }
    else {
      $methods = $this->methods;
      if ($reflector->isInterface()) {
        foreach ($reflector->getMethods() as $method) {
          /** @var $method ReflectionMethod */
          $methods[] = $method->getName();
        }
        $methods = array_unique($methods);
      }
      $mock = $this->testCase->getMock($this->mockedClass, $methods, $this->arguments);
    }

    foreach ($this->methods as $method) {
      $mock->expects($this->testCase->any())
        ->method($method)
        ->will($this->testCase->throwException(new XMLStructReaderException($this->message)));
    }

    return $mock;
  }

  /**
   * Fakes the processing of the invocation $invocation by returning a
   * specific value.
   *
   * @param  PHPUnit_Framework_MockObject_Invocation $invocation
   *         The invocation which was mocked and matched by the current method
   *         and argument matchers.
   * @return mixed
   */
  public function invoke(PHPUnit_Framework_MockObject_Invocation $invocation) {
    return $this->createMock();
  }

  /**
   * Returns a string representation of the object.
   *
   * @return string
   */
  public function toString() {
    return sprintf('return a mock instance of %s throwing "%s" on: %s', $this->mockedClass, $this->message, implode(', ', $this->methods));
  }
}
