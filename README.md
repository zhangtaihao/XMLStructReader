# XMLStructReader: XML structured array parser

XMLStructReader is a PHP utility for reading XML into a PHP structured array.

## Requirements

The library uses the built-in XML Parser for reading XML. PHP requirements
include:

* PHP 5.1
* SPL extension (enabled by default)

## Basic usage

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
  <database>
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

## Special XML markup

The XML document can contain special annotations in the document tree to mark
parts of the data structure for special meaning. All special markup provided by
default is in the namespace `http://xml.zth.me/XMLStructReader`.

### `textKey` attribute

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

### `listElement` attribute

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

## License

This library is licensed under the General Public License, version 3. For full
license details, see LICENSE.txt.
