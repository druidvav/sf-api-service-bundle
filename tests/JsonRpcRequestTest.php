<?php

declare(strict_types=1);

use Druidvav\ApiServiceBundle\JsonRpcRequest;
use Druidvav\ApiServiceBundle\JsonRpcResponse;
use Symfony\Component\HttpFoundation\Request;

$autoload = $argv[1] ?? dirname(__DIR__).'/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Pass the path to a Composer autoload.php file as the first argument.\n");
    exit(1);
}
require $autoload;

// Composer registers its loader with prepend=true; put this checkout first.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Druidvav\\ApiServiceBundle\\';
    if (0 !== strpos($class, $prefix)) {
        return;
    }

    $file = dirname(__DIR__).'/src/Druidvav/ApiServiceBundle/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_file($file)) {
        require $file;
    }
}, true, true);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function httpRequest(string $body): Request
{
    return Request::create('/api', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
}

function expectError(JsonRpcRequest $request, string $body, int $code): void
{
    try {
        $request->parseRequest(httpRequest($body));
    } catch (\Druidvav\ApiServiceBundle\Exception\JsonRpcExceptionInterface $error) {
        check($code === $error->getCode(), "Expected error $code for $body; got {$error->getCode()}");
        return;
    }

    throw new RuntimeException("Expected error $code for $body");
}

$request = new JsonRpcRequest();
$request->parseRequest(httpRequest('{"jsonrpc":"2.0","id":0,"method":"item.get","params":{}}'));
check(0 === $request->getId(), 'Integer zero ID must be accepted');
check('item.get' === $request->getMethod(), 'Method should be retained');
check(true === $request->isAssociative(), 'Empty object params are named');
check($request->getRawParams() instanceof stdClass, 'Raw empty object should remain an object');
check([] === $request->getParams(), 'Compatibility params should be an array');

$request->parseRequest(httpRequest('{"jsonrpc":"2.0","id":"0","method":"item.get","params":[{"nested":{}},[]]}'));
check('0' === $request->getId(), 'String zero ID must be accepted');
check(false === $request->isAssociative(), 'Array params are positional');
check(is_array($request->getRawParams()), 'Raw positional params should remain an array');
check($request->getRawParams()[0]->nested instanceof stdClass, 'Nested empty object must be preserved');
check([] === $request->getRawParams()[1], 'Nested empty array must be preserved');
check([] === $request->getParam(0)['nested'], 'Compatibility parameter access should return arrays');

$request->parseRequest(httpRequest('{"jsonrpc":"2.0","id":7,"method":"item.get","params":{"0":"first","1":"second"}}'));
check(true === $request->isAssociative(), 'Numeric object keys must still be named params');
check('first' === $request->getParam(0), 'Compatibility access must support numeric object keys');

$request->parseRequest(httpRequest('{"jsonrpc":"2.0","id":8,"method":"item.get","params":[]}'));
check(false === $request->isAssociative(), 'Empty array params are positional');

foreach (['{', '', '{"jsonrpc":'] as $body) {
    expectError($request, $body, -32700);
}
foreach (['null', '[]', '42', '"hello"', '{}', '{"jsonrpc":"1.0","id":1,"method":"m","params":{}}', '{"jsonrpc":2,"id":1,"method":"m","params":{}}', '{"jsonrpc":"2.0","method":"m","params":{}}', '{"jsonrpc":"2.0","id":null,"method":"m","params":{}}', '{"jsonrpc":"2.0","id":true,"method":"m","params":{}}', '{"jsonrpc":"2.0","id":1.5,"method":"m","params":{}}', '{"jsonrpc":"2.0","id":1,"method":1,"params":{}}', '{"jsonrpc":"2.0","id":1,"method":" ","params":{}}', '{"jsonrpc":"2.0","id":1,"method":"m"}'] as $body) {
    expectError($request, $body, -32600);
}
foreach (['null', 'true', '1', '"value"'] as $params) {
    expectError($request, '{"jsonrpc":"2.0","id":1,"method":"m","params":'.$params.'}', -32602);
}

foreach ([0, 1] as $id) {
    expectError($request, '{"jsonrpc":"2.0","id":'.$id.',"method":"audit.log","params":null}', -32602);
    check($id === $request->getId(), 'Valid ID must be retained for invalid-params response');
    check('audit.log' === $request->getMethod(), 'Valid method must be retained for audit context');
    check([] === $request->getParams() && null === $request->getRawParams(), 'Invalid params must not remain available');

    $response = new JsonRpcResponse($request);
    $response->setError('Invalid params', -32602);
    $responseBody = json_decode($response->getHttpResponse()->getContent(), true);
    check($id === $responseBody['id'], 'Default response must correlate invalid params with request ID');
    check(-32602 === $responseBody['error']['code'], 'Default response must preserve invalid-params code');
}

expectError($request, '{"jsonrpc":"2.0","id":null,"method":"audit.log","params":{}}', -32600);
check(null === $request->getId() && null === $request->getMethod(), 'Invalid ID must not become response context');

$request->parseRequest(httpRequest('{"jsonrpc":"2.0","id":9,"method":"before","params":{"x":1}}'));
$request->addObject(new stdClass());
expectError($request, '{', -32700);
check(null === $request->getId() && null === $request->getMethod(), 'Failed parse must clear ID and method');
check([] === $request->getParams() && null === $request->getRawParams(), 'Failed parse must clear params');
check(null === $request->isAssociative(), 'Failed parse must clear parameter kind');
check(null === $request->getObject(stdClass::class), 'Failed parse must clear attached objects');

echo "JsonRpcRequest regression checks passed.\n";
