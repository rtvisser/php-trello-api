<?php

namespace Trello\Exception;

class ApiLimitExceedException extends \InvalidArgumentException implements ExceptionInterface
{
    const API_TOKEN_LIMIT_EXCEEDED = 'API_TOKEN_LIMIT_EXCEEDED';
    const API_KEY_LIMIT_EXCEEDED = 'API_KEY_LIMIT_EXCEEDED';

    private ?string $limitType;

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, ?string $limitType = null)
    {
        parent::__construct($message, $code, $previous);
        $this->limitType = $limitType;
    }

    public static function createForApiTokenLimit(string $message, int $code): self
    {
        return new self($message, $code, null, self::API_TOKEN_LIMIT_EXCEEDED);
    }

    public static function createForApiKeyLimit(string $message, int $code): self
    {
        return new self($message, $code, null, self::API_KEY_LIMIT_EXCEEDED);
    }

    public function getLimitType(): ?string
    {
        return $this->limitType;
    }
}
