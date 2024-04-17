<?php

namespace Trello\HttpClient\Listener;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Trello\HttpClient\Message\ResponseMediator;
use Trello\Exception\ErrorException;
use Trello\Exception\RuntimeException;
use Trello\Exception\PermissionDeniedException;
use Trello\Exception\ValidationFailedException;
use Trello\Exception\ApiLimitExceedException;

class ErrorListener
{
    public function getErrorsHandler()
    {
        return function (callable $handler) {
            return function ($request, array $options) use ($handler) {
                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request) {
                        if (!$this->isClientError($response) && !$this->isServerError($response)) {
                            return $response;
                        }

                        $this->throwException($request, $response);
                    }
                );
            };
        };
    }

    private function isClientError(ResponseInterface $response)
    {
        return $response->getStatusCode() >= 400 && $response->getStatusCode() < 500;
    }

    private function isServerError(ResponseInterface $response)
    {
        return $response->getStatusCode() >= 500 && $response->getStatusCode() < 600;
    }

    /**
     * {@inheritDoc}
     */
    private function throwException(RequestInterface $request, ResponseInterface $response)
    {
        $content = ResponseMediator::getContent($response);

        switch ($response->getStatusCode()) {
            case 429:
                $message = 'Wait a second.';

                switch ($content['error'] ?? null) {
                    case ApiLimitExceedException::API_KEY_LIMIT_EXCEEDED:
                        throw ApiLimitExceedException::createForApiKeyLimit($message, 429);
                    case ApiLimitExceedException::API_TOKEN_LIMIT_EXCEEDED:
                        throw ApiLimitExceedException::createForApiTokenLimit($message, 429);
                    default:
                        throw new ApiLimitExceedException($message, 429);
                }
        }

        if (is_array($content) && isset($content['message'])) {
            if (400 == $response->getStatusCode()) {
                throw new ErrorException($content['message'], 400);
            }

            if (401 == $response->getStatusCode()) {
                throw new PermissionDeniedException($content['message'], 401);
            }

            if (422 == $response->getStatusCode() && isset($content['errors'])) {
                $errors = [];
                foreach ($content['errors'] as $error) {
                    switch ($error['code']) {
                        case 'missing':
                            $errors[] = sprintf('The %s %s does not exist, for resource "%s"', $error['field'], $error['value'], $error['resource']);
                            break;

                        case 'missing_field':
                            $errors[] = sprintf('Field "%s" is missing, for resource "%s"', $error['field'], $error['resource']);
                            break;

                        case 'invalid':
                            $errors[] = sprintf('Field "%s" is invalid, for resource "%s"', $error['field'], $error['resource']);
                            break;

                        case 'already_exists':
                            $errors[] = sprintf('Field "%s" already exists, for resource "%s"', $error['field'], $error['resource']);
                            break;

                        default:
                            $errors[] = $error['message'];
                            break;

                    }
                }

                throw new ValidationFailedException('Validation Failed: ' . implode(', ', $errors), 422);
            }
        }

        throw new RuntimeException(isset($content['message']) ? $content['message'] : $content, $response->getStatusCode());
    }
}
