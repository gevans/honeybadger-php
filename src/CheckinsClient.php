<?php

namespace Honeybadger;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Honeybadger\Contracts\ApiClient;
use Honeybadger\Exceptions\ServiceException;
use Honeybadger\Exceptions\ServiceExceptionFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckinsClient extends ApiClient
{
    const BASE_URL = 'https://app.honeybadger.io/';

    /**
     * @var Checkin[][]
     */
    private $projectCheckins = [];

    /**
     * @param  string  $key
     * @return void
     */
    public function checkin(string $key): void
    {
        try {
            $response = $this->client->head(sprintf('v1/check_in/%s', $key));

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->handleServiceException((new ServiceExceptionFactory($response))->make());
            }
        } catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));
        }
    }

    /**
     * @param string $projectId
     * @return Checkin[]|null
     */
    public function listForProject(string $projectId): ?array
    {
        if (! $this->hasPersonalAuthToken()) {
            $this->handleServiceException(ServiceException::missingPersonalAuthToken());

            return [];
        }

        if (isset($this->projectCheckins[$projectId])) {
            return $this->projectCheckins[$projectId];
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins', $projectId);
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->handleServiceException((new ServiceExceptionFactory($response))->make());

                return [];
            }

            $data = json_decode($response->getBody(), true);
            $this->projectCheckins[$projectId] = array_map(function ($checkin) use ($projectId) {
                $result = new Checkin($checkin);
                $result->projectId = $projectId;

                return $result;
            }, $data['results']);

            return $this->projectCheckins[$projectId];
        } catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));

            return [];
        }
    }

    /**
     * @param string $projectId
     * @param string $checkinId
     * @return Checkin|null
     */
    public function get(string $projectId, string $checkinId): ?Checkin
    {
        if (! $this->hasPersonalAuthToken()) {
            $this->handleServiceException(ServiceException::missingPersonalAuthToken());

            return null;
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins/%s', $projectId, $checkinId);
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                $this->handleServiceException((new ServiceExceptionFactory($response))->make());

                return null;
            }

            $data = json_decode($response->getBody(), true);

            return new Checkin($data);
        } catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));

            return null;
        }
    }

    /**
     * @param Checkin $checkin
     * @return Checkin|null
     */
    public function create(Checkin $checkin): ?Checkin
    {
        if (! $this->hasPersonalAuthToken()) {
            $this->handleServiceException(ServiceException::missingPersonalAuthToken());

            return null;
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins', $checkin->projectId);
            $response = $this->client->post($url, [
                'json' => [
                    'check_in' => $checkin->toArray(),
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_CREATED) {
                $this->handleServiceException((new ServiceExceptionFactory($response))->make());

                return null;
            }

            $data = json_decode($response->getBody(), true);
            return new Checkin($data);
        } catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));

            return null;
        }
    }

    /**
     * @param Checkin $checkin
     * @return Checkin|null
     */
    public function update(Checkin $checkin): ?Checkin
    {
        if (! $this->hasPersonalAuthToken()) {
            $this->handleServiceException(ServiceException::missingPersonalAuthToken());

            return null;
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins/%s', $checkin->projectId, $checkin->id);
            $response = $this->client->put($url, [
                'json' => [
                    'check_in' => $checkin->toArray(),
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                $this->handleServiceException((new ServiceExceptionFactory($response))->make());

                return null;
            }

            return $checkin;
        } catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));

            return null;
        }
    }

    /**
     * @param string $projectId
     * @param string $checkinId
     * @return bool
     */
    public function remove(string $projectId, string $checkinId): bool {
        if (! $this->hasPersonalAuthToken()) {
            $this->handleServiceException(ServiceException::missingPersonalAuthToken());

            return false;
        }

        try {
            $url = sprintf('v2/projects/%s/check_ins/%s', $projectId, $checkinId);
            $response = $this->client->delete($url);

            if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
                throw (new ServiceExceptionFactory($response))->make();
            }
        } catch (Throwable $e) {
            $this->handleServiceException(ServiceException::generic($e));

            return false;
        }

        return true;
    }

    public function makeClient(): Client
    {
        return new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::AUTH => [
                $this->config['personal_auth_token'], ''
            ],
        ]);
    }
}
