<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $routeParams = [];
    private array $body;
    private array $query;

    public function __construct()
    {
        $this->query = $_GET;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $this->body = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $this->body = $_POST;
        }
    }

    public function method(): string
    {
        $override = $this->body['_method'] ?? null;

        return strtoupper($override ?: ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return $uri ?: '/';
    }

    public function fullUrl(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $uri !== '' ? $uri : '/';
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        return str_starts_with($header, 'Bearer ') ? substr($header, 7) : null;
    }
}
