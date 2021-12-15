<?php

namespace Honeybadger;

use Honeybadger\Concerns\FiltersData;
use Symfony\Component\HttpFoundation\Request as FoundationRequest;

class Request
{
    use FiltersData;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  array  $options
     */
    public function __construct(FoundationRequest $request = null)
    {
        $this->request = $request ?? FoundationRequest::createFromGlobals();

        $this->keysToFilter = [
            'password',
            'password_confirmation',
        ];
    }

    /**
     * @return string
     */
    public function url(): string
    {
        $url = $this->httpRequest()
            ? $this->request->getUri()
            : '';

        if (! $url) {
            return $url;
        }

        // Manually filter out sensitive data from URL query string
        $queryString = parse_url($url, PHP_URL_QUERY) ?? '';
        $filteredQueryParams = array_map(function ($keyAndValue) {
            $parts = explode('=', $keyAndValue);
            if (isset($parts[1]) && $parts[1] !== ''
                && in_array($parts[0], $this->keysToFilter)) {
                return "{$parts[0]}=[FILTERED]";
            }

            return $keyAndValue;
        }, explode('&', $queryString));

        return str_replace($queryString, implode('&', $filteredQueryParams), $url);
    }

    /**
     * @return array
     */
    public function params(): array
    {
        if (! $this->httpRequest()) {
            return [];
        }

        return [
            'method' => $this->request->getMethod(),
            'query' => $this->filter($this->request->query->all()),
            'data' => $this->filter($this->data()),
        ];
    }

    /**
     * @return array
     */
    public function session(): array
    {
        return $this->request->hasSession() && $this->request->getSession()
            ? $this->filter($this->request->getSession()->all())
            : [];
    }

    /**
     * @return bool
     */
    private function httpRequest(): bool
    {
        return isset($_SERVER['REQUEST_METHOD']);
    }

    /**
     * @return array
     */
    private function data(): array
    {
        if ($this->request->getContentType() === 'json') {
            return json_decode($this->request->getContent(), true) ?: [];
        }

        if ($this->request->getContentType() === 'form') {
            return $this->request->request->all();
        }

        return [];
    }
}
