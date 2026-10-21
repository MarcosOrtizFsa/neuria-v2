<?php

namespace Neuria\Connector;

use Neuria\Connector\Contracts\NeuriaClient;
use RuntimeException;

final class NeuriaApiClient implements NeuriaClient
{
    public function __construct(private string $baseUrl, private string $token, private int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        if (parse_url($this->baseUrl, PHP_URL_SCHEME) !== 'https' && ! in_array($host, ['localhost', '127.0.0.1'], true)) {
            throw new RuntimeException('La API de NEURIA debe utilizar HTTPS.');
        }
    }

    public function contents(?string $cursor = null, int $limit = 50): array
    {
        return $this->get('/api/v1/contents', compact('cursor', 'limit'));
    }

    public function changes(?string $cursor = null, int $limit = 50): array
    {
        return $this->get('/api/v1/changes', compact('cursor', 'limit'));
    }

    private function get(string $path, array $query): array
    {
        $url = $this->baseUrl.$path.'?'.http_build_query(array_filter($query, fn ($value) => $value !== null));
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer '.$this->token], CURLOPT_TIMEOUT => $this->timeout, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
            $body = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            if ($body !== false && $status >= 200 && $status < 300) {
                $decoded = json_decode($body, true);
                if (! is_array($decoded) || ! isset($decoded['data'], $decoded['meta'])) {
                    throw new RuntimeException('NEURIA devolvió una respuesta JSON inesperada.');
                }

                return $decoded;
            }
            if (! in_array($status, [429, 500, 502, 503, 504], true) || $attempt === 3) {
                throw new RuntimeException("Error al consultar NEURIA (HTTP {$status}): ".($error ?: 'respuesta no disponible'));
            }
            usleep(250000 * $attempt);
        }
        throw new RuntimeException('No se pudo consultar NEURIA.');
    }
}
