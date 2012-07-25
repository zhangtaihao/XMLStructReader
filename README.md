XMLStructReader: XML structured array parser
============================================

Repository: <http://github.com/zhangtaihao/XMLStructReader>

XMLStructReader is a PHP utility for reading XML into a PHP structured array.


Requirements
------------

The library uses the built-in XML Parser for reading XML. PHP requirements
include:

* PHP 5.1
* SPL extension (enabled by default)


Basic usage
-----------

To parse XML files into a PHP array, create a reader factory and use it to
create a reader given file path, stream resource, or an SplFileObject. Example:

```php
<?php
// Create factory and read XML.
$factory = new DefaultXMLStructReaderFactory();
$data = $factory->createReader('data.xml')->read();
// Output data array.
print_r($data);
```

You can use XMLStructReader to read:

```xml
<configuration>
  <database provider="pdo">
    <dsn>mysql:host=localhost;dbname=testdb</dsn>
    <username>testuser</username>
    <password>testpass</password>
  </database>
  <cache>
    <ttl>3600</ttl>
  </cache>
</configuration>
```

into the PHP array:

```
Array
(
    [configuration] => Array
        (
            [database] => Array
                (
                    [provider] => pdo
                    [dsn] => mysql:host=localhost;dbname=testdb
                    [username] => testuser
                    [password] => testpass
                )

            [cache] => Array
                (
                    [ttl] => 3600
                )

        )

)
```

### Quirks

#### Conflicting array keys

When an element contains more than one value with the same key, generally the
latter value will overwrite the former. This quirk covers both attribute and
element names. Where [text keys](#textkey-attribute) are used and values are not
[joined](#xml_struct_reader_option_text_join), this quirk will also apply to
textual values.

#### Mixed text/element content

Whenever an element contains both child elements and textual content, the text
value is ignored.

The exception to this rule is through the use of the `textKey` annotation on the
parent element to specify a key for the text value (see
[`textKey` attribute](#textkey-attribute) in Advanced usage).


Advanced usage
--------------

### Special XML markup

The XML document can contain special annotations in the document tree to mark
parts of the data structure for special meaning. All special markup provided by
default is in the namespace `http://xml.zth.me/XMLStructReader`.

#### `textKey` attribute

Specifies the array key for textual values. For example:

```xml
<root xmlns:x="http://xml.zth.me/XMLStructReader">
  <element x:textKey="special key">special value</element>
</root>
```

will be read as:

```
Array
(
    [root] => Array
        (
            [element] => Array
                (
                    [special key] => special value
                )

        )

)
```

#### `listElement` attribute

Specifies the element name to use as list item in a numeric array. For example:

```xml
<root xmlns:x="http://xml.zth.me/XMLStructReader">
  <list x:listElement="item">
    <item>a</item>
    <item>b</item>
    <item>c</item>
  </list>
</root>
```

will be read as:

```
Array
(
    [root] => Array
        (
            [list] => Array
                (
                    [0] => a
                    [1] => b
                    [2] => c
                )

        )

)
```

Specify `listElement="*"` to indicate that all child elements are list items.

#### `asKey` attribute

Specifies the key to use instead of the element name when adding to parent. For
example:

```xml
<root xmlns:x="http://xml.zth.me/XMLStructReader">
  <element x:asKey="key">value</element>
</root>
```

will be read as:

```
Array
(
    [root] => Array
        (
            [key] => value
        )

)
```

#### `include` element

Includes the XML document specified in `file` and replace the `include` element
with the loaded array. For example:

```xml
<root xmlns:x="http://xml.zth.me/XMLStructReader">
  <x:include file="other_document.xml"/>
</root>
```

will read `other_document.xml` to where the `include` element is placed under
`<root>`. If `other_document.xml` contains the following markup:

```xml
<other-structure>
  <key>value</key>
</other-structure>
```

The previous XML document will be read as:

```
Array
(
    [root] => Array
        (
            [other-structure] => Array
                (
                    [key] => value
                )

        )

)
```

Note that PHP will attempt to find the file path relative to the current working
directory. To change this behavior, specify an alternative directory for finding
included files using the option [`XML_STRUCT_READER_OPTION_INCLUDE_PATH`]
(#xml_struct_reader_option_include_path) when creating a reader.

You can also use defined constants in the file path with `${PHP_CONSTANT}` for
the constant `PHP_CONSTANT`. The constant must be defined prior to reading.

Finally, you can attach special markup to the root of the included document by
specifying special attributes on the `include` element. For example:

```xml
<x:include file="other_document.xml" x:listElement="*"/>
```

will read `other_document.xml` assuming all immediate child elements under
`<other-structure>` are list items. To force a certain context value, override
it in the sub-document. You can use the values `php:null`, `php:true`, and
`php:false` to respective indicate `NULL`, `TRUE`, and `FALSE`.

### Options

Options can be specified when creating a reader. For example:

```php
<?php
$factory = new DefaultXMLStructReaderFactory();
$options = array(XML_STRUCT_READER_OPTION_TEXT_SKIP_EMPTY => FALSE);
$data = $factory->createReader('data.xml', $options)->read();
// Output data array.
print_r($data);
```

#### `XML_STRUCT_READER_OPTION_KEY_CONFLICT`

#### `XML_STRUCT_READER_OPTION_TEXT_TRIM`

Whether to trim whitespace from ends of contiguous chunks of element text (i.e.
not interrupted by an element).

**Possible values:**

* `TRUE` *(default)*
* `FALSE`

#### `XML_STRUCT_READER_OPTION_TEXT_JOIN`

Whether to join all text chunks in an element.

**Possible values:**

* `TRUE` *(default)*
* `FALSE`

Note that `FALSE` means only the last chunk is in effect if conflicting values
are replaced.

#### `XML_STRUCT_READER_OPTION_TEXT_SKIP_EMPTY`

Whether to skip empty chunks of text within elements. The text is measured in
its trimmed form.

**Possible values:**

* `TRUE` *(default)*
* `FALSE`

#### `XML_STRUCT_READER_OPTION_INCLUDE_PATH`

Default base path for included file names.

**Possible values:**  Any valid directory path string. *Default*: `.` (current
working directory)

#### `XML_STRUCT_READER_OPTION_INCLUDE_READER_FACTORY`

Factory class to create reader for included files.

**Possible values:**  Any valid findable class name. *Default*:
`DefaultXMLStructReaderFactory`


License
-------

This library is licensed under the General Public License, version 3. For full
license details, see LICENSE.txt.
