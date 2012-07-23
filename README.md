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

## License

This library is licensed under the General Public License, version 3. For full
license details, see LICENSE.txt.
