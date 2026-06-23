<?php

namespace App\Security\WebserviceVus;

use Exception;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class VusUserAuthenticationService
{
    private LoggerInterface $logger;
    private HttpClientInterface $client;
    private string $wsUrl;
    private string $wsUser;
    private string $wsPass;
    private ?string $aplicacio;

    public function __construct(
        LoggerInterface     $logger,
        HttpClientInterface $client
    )
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    public function setWsUrl(string $wsUrl): self
    {
        $this->wsUrl = $wsUrl;

        return $this;
    }

    public function setWsUser(string $wsUser): self
    {
        $this->wsUser = $wsUser;

        return $this;
    }

    public function setWsPass(string $wsPass): self
    {
        $this->wsPass = $wsPass;

        return $this;
    }

    public function setAplication(?string $aplicacio): self
    {
        $this->aplicacio = $aplicacio;

        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function login(string $username, string $password): array
    {
        if (!$password || !$username) {
            throw new InvalidVusUserCredentialsException('Username and password cannot be empty.');
        }

        $body = [
            'ws_usuari' => $this->wsUser,
            'ws_clau' => $this->wsPass,
            'usuari_vus' => 'OPS$' . strtoupper($username),
            'clau_vus' => $password,
        ];
        if (!empty($this->aplicacio)) {
            $body['aplicacio'] = $this->aplicacio;
        }

        $response = $this->client->request('POST', $this->wsUrl, [
            'body' => $body,
        ]);

        $this->checkStatusCode($response);
        $this->checkContentType($response);

        $content = new SimpleXMLElement($response->getContent());

        $this->checkCodiResposta($content, $response);

        return $this->extractUserInfo($content, $response);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function checkStatusCode(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $this->logger->error('VUS invalid status code', ['vus.statusCode' => $statusCode]);

            throw new InvalidVusUserCredentialsException($response->getContent());
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function checkContentType(ResponseInterface $response): void
    {
        $contentType = $response->getHeaders()['content-type'][0] ?? null;

        if ($contentType !== 'text/xml') {
            $this->logger->error('VUS returned a non XML response', ['vus.contentType' => $contentType]);

            throw new InvalidVusUserCredentialsException($response->getContent());
        }
    }

    /**
     * @param SimpleXMLElement $content
     * @param ResponseInterface $response
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function checkCodiResposta(
        SimpleXMLElement  $content,
        ResponseInterface $response
    ): void
    {
        if (((string)$content->resposta->codi_resposta) !== "0") {
            $this->logger->error('VUS authentication error', ['vus.response' => $response->getContent()]);

            throw new InvalidVusUserCredentialsException($content);
        }
    }

    private function extractUserInfo(SimpleXMLElement $content, ResponseInterface $response): array
    {
        $usuariInfo = $content->resposta->usuari_vus ?? null;

        if ($usuariInfo === null) {
            $this->logger->error('VUS returned empty user info', ['vus.response' => $response]);

            throw new InvalidVusUserCredentialsException($content);
        }

        return json_decode(json_encode($usuariInfo), true);
    }
}
