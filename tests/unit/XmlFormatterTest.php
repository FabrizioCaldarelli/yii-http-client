<?php

namespace yii\httpclient\tests\unit;

use DOMDocument;
use DOMElement;
use yii\httpclient\XmlFormatter;
use yii\httpclient\Request;

class XmlFormatterTest extends \yii\tests\TestCase
{
    protected function setUp()
    {
        $this->mockApplication();
    }

    // Tests :

    public function testFormat()
    {
        $request = new Request();
        $data = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $request->setParams($data);

        $formatter = new XmlFormatter();
        $formatter->format($request);
        $expectedContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request><name1>value1</name1><name2>value2</name2></request>

XML;
        $this->assertEqualsWithoutLE($expectedContent, $request->getBody()->__toString());
        $this->assertEquals('application/xml; charset=UTF-8', $request->getHeaderLine('Content-Type'));
    }

    /**
     * @depends testFormat
     */
    public function testFormatArrayWithNumericKey()
    {
        $request = new Request();
        $data = [
            'group' => [
                [
                    'name1' => 'value1',
                    'name2' => 'value2',
                ],
            ],
        ];
        $request->setParams($data);

        $formatter = new XmlFormatter();
        $formatter->format($request);
        $expectedContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request><group><item><name1>value1</name1><name2>value2</name2></item></group></request>

XML;
        $this->assertEqualsWithoutLE($expectedContent, $request->getBody()->__toString());
    }

    /**
     * @depends testFormat
     */
    public function testFormatTraversable()
    {
        $request = new Request();

        $postsStack = new \SplStack();
        $post = new \stdClass();
        $post->name = 'name1';
        $postsStack->push($post);

        $request->setParams($postsStack);

        $formatter = new XmlFormatter();

        $formatter->useTraversableAsArray = true;
        $formatter->format($request);
        $expectedContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request><stdClass><name>name1</name></stdClass></request>

XML;
        $this->assertEqualsWithoutLE($expectedContent, $request->getBody()->__toString());

        $formatter->useTraversableAsArray = false;
        $formatter->format($request);
        $expectedContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request><SplStack><stdClass><name>name1</name></stdClass></SplStack></request>

XML;
        $this->assertEqualsWithoutLE($expectedContent, $request->getBody()->__toString());
    }

    /**
     * @depends testFormat
     */
    public function testFormatFromDom()
    {
        $request = new Request();
        $data = new DOMDocument('1.0', 'UTF-8');
        $root = new DOMElement('root');
        $data->appendChild($root);
        $request->setParams($data);

        $formatter = new XmlFormatter();
        $formatter->format($request);
        $expectedContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root/>

XML;
        $this->assertEqualsWithoutLE($expectedContent, $request->getBody()->__toString());
    }

    /**
     * @depends testFormat
     */
    public function testFormatFromSimpleXml()
    {
        $request = new Request();

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request><name1>value1</name1><name2>value2</name2></request>

XML;
        $simpleXmlElement = simplexml_load_string($xml);
        $request->setParams($simpleXmlElement);

        $formatter = new XmlFormatter();
        $formatter->format($request);
        $this->assertEqualsWithoutLE($xml, $request->getBody()->__toString());
    }

    /**
     * @depends testFormat
     */
    public function testFormatEmpty()
    {
        $request = new Request();

        $formatter = new XmlFormatter();
        $formatter->format($request);
        $this->assertFalse($request->hasBody());
    }
}
