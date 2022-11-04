<?php

namespace Trello\HttpClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use Trello\Exception\ErrorException;
use Trello\Exception\RuntimeException;
use Trello\HttpClient\Listener\AuthListener;
use Trello\HttpClient\Listener\ErrorListener;

class HttpClient implements HttpClientInterface
{
    protected $options = [
        'base_url' => 'https://api.trello.com/',
        'user_agent' => 'php-trello-api (http://github.com/cdaguerre/php-trello-api)',
        'timeout' => 50,
        'api_version' => 1,
    ];

    /**
     * @var ClientInterface
     */
    protected $client;

    protected $headers = [];

    private ?ResponseInterface $lastResponse = null;

    private HandlerStack $handlerStack;

    public function __construct(array $options = [], ClientInterface $client = null)
    {
        $this->handlerStack = HandlerStack::create();

        $this->options = array_merge($this->options, $options);
        $this->options['handler'] = $this->handlerStack;
        $client = $client ?: new GuzzleClient($this->options);
        $this->client = $client;

        $errorListener = new ErrorListener();
        $this->addMiddleware($errorListener->getErrorsHandler());
        $this->clearHeaders();
    }

    /**
     * {@inheritDoc}
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Clears used headers
     */
    public function clearHeaders()
    {
        $this->headers = [
            'Accept' => sprintf('application/vnd.orcid.%s+json', $this->options['api_version']),
            'User-Agent' => sprintf('%s', $this->options['user_agent']),
        ];
    }

    /**
     * example $handler->push(Middleware::mapRequest(function (RequestInterface $request) {
     *      // Notice that we have to return a request object
     *      return $request->withHeader('X-Foo', 'Bar');
     * }));
     */
    public function addMiddleware(callable $middleware)
    {
        $this->handlerStack->push($middleware);
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, array $parameters = [], array $headers = [])
    {
        return $this->request($path, $parameters, 'GET', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function post($path, $body = null, array $headers = [])
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        return $this->request($path, $body, 'POST', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function patch($path, $body = null, array $headers = [])
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        return $this->request($path, $body, 'PATCH', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($path, $body = null, array $headers = [])
    {
        return $this->request($path, $body, 'DELETE', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function put($path, $body, array $headers = [])
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        return $this->request($path, $body, 'PUT', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function request($path, $body = null, $httpMethod = 'GET', array $headers = [], array $options = [])
    {
        try {
            $response = $this->sendRequest($httpMethod, $path, $body, $headers, $options);
        } catch (\LogicException $e) {
            throw new ErrorException($e->getMessage(), $e->getCode(), $e);
        } catch (\RuntimeException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $this->lastResponse = $response;

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate($tokenOrLogin, $password, $method)
    {
        $this->addMiddleware(Middleware::mapRequest([
            new AuthListener($tokenOrLogin, $password, $method),
            'onRequestBeforeSend',
        ]));
    }

    public function getLastResponse(): ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * @param string $httpMethod
     * @param string $path
     */
    protected function sendRequest($httpMethod, $path, $body = null, array $headers = [], array $options = []): ResponseInterface
    {
        $path = $this->options['api_version'] . '/' . $path;

        if ($httpMethod === 'GET' && $body) {
            $path .= (false === strpos($path, '?') ? '?' : '&');
            $path .= utf8_encode(http_build_query($body, '', '&'));
        }

        $options['body'] = $body;
        $options['headers'] = array_merge($this->headers, $headers);

        return $this->client->request($httpMethod, $path, $options);
    }
}
