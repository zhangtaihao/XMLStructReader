<?php

require_once 'XMLStructReaderTest.inc.php';

/**
 * Tests for x:include element.
 */
class XMLStructReaderIncludeTest extends XMLStructReaderTestCase {
  /**
   * @dataProvider includeDataProvider
   */
  public function testInclude($xml, $expectedValue) {
    if (!defined('TEST_ROOT')) {
      define('TEST_ROOT', __DIR__);
    }
    $this->doTestDefaultRead($xml, $expectedValue);
  }

  public function includeDataProvider() {
    return array(
      array(
        '<root xmlns:x="%ns%">
          <x:include file="${TEST_ROOT}/include.xml"/>
        </root>',
        array('root' => array(
          'included' => 'value',
        )),
      ),
      array(
        '<root xmlns:x="%ns%">
          <x:include>
            <file>${TEST_ROOT}/include.xml</file>
          </x:include>
        </root>',
        array('root' => array(
          'included' => 'value',
        )),
      ),
      array(
        '<root xmlns:x="%ns%">
          <x:include>
            <file>${INVALID}/include.xml</file>
          </x:include>
        </root>',
        NULL,
      ),
      array(
        '<root xmlns:x="%ns%">
          <x:include file="notfound"/>
        </root>',
        NULL,
      ),
      // Test character data for completeness.
      array(
        '<root xmlns:x="%ns%">
          <x:include file="notfound">ignored</x:include>
        </root>',
        NULL,
      ),
      // Test included context.
      array(
        '<root xmlns:x="%ns%">
          <x:include file="${TEST_ROOT}/include.xml" x:listElement="included"/>
        </root>',
        array('root' => array(
          0 => 'value',
        )),
      ),
    );
  }

  public function testOptions() {
    $xml = $this->prepareXMLNamespace('<root xmlns:x="%ns%">
      <x:include file="include.xml"/>
    </root>');
    $reader = new DefaultXMLStructReader($this->createXMLDelegate($xml), array(
      XML_STRUCT_READER_OPTION_INCLUDE_PATH => __DIR__,
    ));
    $data = $reader->read();
    $this->assertSame(array(
      'root' => array(
        'included' => 'value',
      ),
    ), $data);
  }
}
