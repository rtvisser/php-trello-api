<?php

namespace Trello\HttpClient\Listener;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Trello\Client;
use Trello\Exception\RuntimeException;

class AuthListener
{
    private $tokenOrLogin;
    private $password;
    private $method;

    /**
     * @param string $tokenOrLogin
     * @param string $password
     * @param null|string $method
     */
    public function __construct($tokenOrLogin, $password, $method)
    {
        $this->tokenOrLogin = $tokenOrLogin;
        $this->password = $password ?: null;
        $this->method = $method;
    }

    public function onRequestBeforeSend(RequestInterface $request)
    {
        // Skip by default
        if (null === $this->method) {
            return;
        }

        switch ($this->method) {
            case Client::AUTH_HTTP_PASSWORD:
                $request->withHeader(
                    'Authorization',
                    sprintf('Basic %s', base64_encode($this->tokenOrLogin . ':' . $this->password))
                );
                break;

            case Client::AUTH_HTTP_TOKEN:
                $request->withHeader(
                    'Authorization',
                    sprintf('token %s', $this->tokenOrLogin)
                );
                break;

            case Client::AUTH_URL_CLIENT_ID:
                $url = (string) $request->getUri();

                $parameters = [
                    'key' => $this->tokenOrLogin,
                    'token' => $this->password,
                ];

                $url .= (false === strpos($url, '?') ? '?' : '&');
                $url .= utf8_encode(http_build_query($parameters, '', '&'));

                $request->withUri(new Uri($url));
                break;

            case Client::AUTH_URL_TOKEN:
                $url = (string) $request->getUri();
                $url .= (false === strpos($url, '?') ? '?' : '&');
                $url .= utf8_encode(http_build_query(
                    ['token' => $this->tokenOrLogin, 'key' => $this->password],
                    '',
                    '&'
                ));

                $request->withUri(new Uri($url));
                break;

            default:
                throw new RuntimeException(sprintf('%s not yet implemented', $this->method));
        }
    }
}
