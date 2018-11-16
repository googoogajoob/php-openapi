<?php

use cebe\openapi\Reader;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses;

/**
 * @covers \cebe\openapi\spec\Response
 * @covers \cebe\openapi\spec\Responses
 */
class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testRead()
    {
        /** @var $response Response */
        $response = Reader::readFromJson(<<<'JSON'
{
  "description": "A complex object array response",
  "content": {
    "application/json": {
      "schema": {
        "type": "array",
        "items": {
          "$ref": "#/components/schemas/VeryComplexType"
        }
      }
    }
  }
}
JSON
        , Response::class);

        $result = $response->validate();
        $this->assertEquals([], $response->getErrors());
        $this->assertTrue($result);

        $this->assertEquals('A complex object array response', $response->description);
        $this->assertArrayHasKey("application/json", $response->content);
        $this->assertInstanceOf(MediaType::class, $response->content["application/json"]);

        /** @var $response Response */
        $response = Reader::readFromJson(<<<'JSON'
{
  "content": {
    "application/json": {
      "schema": {
        "type": "array",
        "items": {
          "$ref": "#/components/schemas/VeryComplexType"
        }
      }
    }
  }
}
JSON
        , Response::class);

        $result = $response->validate();
        $this->assertEquals([
            'Response is missing required property: description',
        ], $response->getErrors());
        $this->assertFalse($result);
    }

    public function testResponses()
    {
        /** @var $responses Responses */
        $responses = Reader::readFromYaml(<<<'YAML'
'200':
  description: a pet to be returned
  content:
    application/json:
      schema:
        $ref: '#/components/schemas/Pet'
default:
  description: Unexpected error
  content:
    application/json:
      schema:
        $ref: '#/components/schemas/ErrorModel'
YAML
            , Responses::class);

        $result = $responses->validate();
        $this->assertEquals([], $responses->getErrors());
        $this->assertTrue($result);

        $this->assertTrue($responses->hasResponse(200));
        $this->assertFalse($responses->hasResponse(201));
        $this->assertTrue($responses->hasResponse('200'));
        $this->assertFalse($responses->hasResponse('201'));
        $this->assertTrue($responses->hasResponse('default'));
        $this->assertTrue(isset($responses[200]));
        $this->assertFalse(isset($responses[201]));
        $this->assertTrue(isset($responses['200']));
        $this->assertFalse(isset($responses['201']));
        $this->assertTrue(isset($responses['default']));

        $this->assertCount(2, $responses->getResponses());
        $this->assertInstanceOf(Response::class, $responses->getResponses()[200]);
        $this->assertInstanceOf(Response::class, $responses->getResponses()['200']);
        $this->assertInstanceOf(Response::class, $responses->getResponses()['default']);

        $this->assertInstanceOf(Response::class, $responses->getResponse(200));
        $this->assertInstanceOf(Response::class, $responses->getResponse('200'));
        $this->assertInstanceOf(Response::class, $responses->getResponse('default'));
        $this->assertNull($responses->getResponse('201'));
        $this->assertInstanceOf(Response::class, $responses[200]);
        $this->assertInstanceOf(Response::class, $responses['200']);
        $this->assertInstanceOf(Response::class, $responses['default']);
        $this->assertNull($responses['201']);

        $this->assertEquals('a pet to be returned', $responses->getResponse('200')->description);
        $this->assertEquals('a pet to be returned', $responses['200']->description);
    }

    public function testResponseCodes()
    {
        /** @var $responses Responses */
        $responses = Reader::readFromYaml(<<<'YAML'
'200':
  description: valid statuscode
'99':
  description: invalid statuscode
'302':
  description: valid statuscode
'401':
  description: valid statuscode
'601':
  description: invalid statuscode
'6X1':
  description: invalid statuscode
'2X1':
  description: invalid statuscode
'2XX':
  description: valid statuscode
'default':
  description: valid statuscode
'example':
  description: valid statuscode
YAML
            , Responses::class);

        $result = $responses->validate();
        $this->assertEquals([
            'Responses: 99 is not a valid HTTP status code.',
            'Responses: 601 is not a valid HTTP status code.',
            'Responses: 6X1 is not a valid HTTP status code.',
            'Responses: 2X1 is not a valid HTTP status code.',
            'Responses: example is not a valid HTTP status code.',

        ], $responses->getErrors());
        $this->assertFalse($result);

    }
}
