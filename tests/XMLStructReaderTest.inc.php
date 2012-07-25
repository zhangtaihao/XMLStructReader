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

  public function createXMLPath($xml) {
    return 'data://text/plain,' . $xml;
  }

  public function createXMLFile($xml) {
    return new SplFileObject($this->createXMLPath($xml));
  }

  public function createXMLDelegate($xml) {
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

  public function doTestDefaultRead($xml, $expectedValue, array $options = array()) {
    $xml = $this->prepareXMLNamespace($xml);
    $delegate = $this->createXMLDelegate($xml);
    $reader = new DefaultXMLStructReader($delegate, $options);
    $data = $reader->read();
    $this->assertSame($data, $expectedValue);
  }

  public function prepareXMLNamespace($xml) {
    return str_replace('%ns%', XMLStructReader::NS, $xml);
  }

  public function returnArguments() {
    return new XMLStructReaderTestStub_ReturnArguments();
  }

  /**
   * @param string $mockedClass
   * @param array|PHPUnit_Framework_MockObject_Stub $arguments
   * @param PHPUnit_Framework_MockObject_Stub[] $methodStubs
   * @return XMLStructReaderMockStub
   */
  public function returnMock($mockedClass, $arguments = array(), array $methodStubs = array()) {
    return new XMLStructReaderMockStub($this, $mockedClass, $arguments, $methodStubs);
  }

  public function returnMockException($mockedClass, array $arguments, array $methods, $message) {
    return new XMLStructReaderMockExceptionStub($this, $mockedClass, $arguments, $methods, $message);
  }
}

/**
 * @codeCoverageIgnore
 */
class XMLStructReaderTestStub_ReturnArguments implements PHPUnit_Framework_MockObject_Stub {
  public function invoke(PHPUnit_Framework_MockObject_Invocation $invocation) {
    return $invocation->parameters;
  }

  public function toString() {
    return 'return arguments';
  }
}

/**
 * @codeCoverageIgnore
 */
class XMLStructReaderMockStub implements PHPUnit_Framework_MockObject_Stub {
  /** @var PHPUnit_Framework_TestCase */
  protected $testCase;

  protected $mockedClass;
  protected $arguments;
  protected $methodStubs;

  /**
   * Creates the stub with class to mock and methods to throw exception message.
   *
   * @param PHPUnit_Framework_TestCase $testCase
   * @param string $mockedClass
   * @param array|PHPUnit_Framework_MockObject_Stub $arguments
   * @param PHPUnit_Framework_MockObject_Stub[] $methodStubs
   */
  public function __construct($testCase, $mockedClass, $arguments = array(), array $methodStubs = array()) {
    $this->testCase = $testCase;
    $this->mockedClass = $mockedClass;
    $this->arguments = $arguments;
    $this->methodStubs = $methodStubs;
  }

  /**
   * @return PHPUnit_Framework_MockObject_Stub[]
   */
  protected function getMethodStubs() {
    return $this->methodStubs;
  }

  protected function createMock(array $arguments) {
    /** @var $mock PHPUnit_Framework_MockObject_MockObject */
    $mock = NULL;
    $methodStubs = $this->getMethodStubs();
    $methods = array_keys($methodStubs);
    $reflector = new ReflectionClass($this->mockedClass);
    if ($reflector->isInterface()) {
      $mock = $this->testCase->getMock($this->mockedClass, array(), $arguments);
    }
    elseif ($reflector->isAbstract()) {
      $mock = $this->testCase->getMockForAbstractClass($this->mockedClass, $arguments, '', TRUE, TRUE, TRUE, $methods);
    }
    else {
      $mock = $this->testCase->getMock($this->mockedClass, $methods, $arguments);
    }

    foreach ($methodStubs as $method => $stub) {
      if ($stub instanceof Closure) {
        /** @var $stub Closure */
        $stub = $this->testCase->returnCallback($stub);
      }

      /** @var $stub PHPUnit_Framework_MockObject_Stub */
      $mock->expects($this->testCase->any())
        ->method($method)
        ->will($stub);
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
    $arguments = $this->arguments;
    if (is_object($arguments) && $arguments instanceof PHPUnit_Framework_MockObject_Stub) {
      $arguments = $arguments->invoke($invocation);
    }
    return $this->createMock($arguments);
  }

  /**
   * Returns a string representation of the object.
   *
   * @return string
   */
  public function toString() {
    return sprintf('return a mock instance of %s for: %s', $this->mockedClass, implode(', ', array_keys($this->methodStubs)));
  }
}

/**
 * @codeCoverageIgnore
 */
class XMLStructReaderMockExceptionStub extends XMLStructReaderMockStub {
  protected $message;
  protected $methods;

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
    parent::__construct($testCase, $mockedClass, $arguments);
    $this->message = $message;
    $this->methods = $methods;
  }

  /**
   * @return PHPUnit_Framework_MockObject_Stub[]
   */
  public function getMethodStubs() {
    $methodStubs = array();
    foreach ($this->methods as $method) {
      $methodStubs[$method] = $this->testCase->throwException(new XMLStructReaderException($this->message));
    }
    return $methodStubs;
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
