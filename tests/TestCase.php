<?php

namespace Tests;

use App\Support\RegistrationFormProtection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    public function postJson($uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        return parent::postJson($uri, $this->mergeRegistrationSecurity($uri, $data), $headers, $options);
    }

    public function postJsonWithoutRegistrationSecurity($uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        return parent::postJson($uri, $data, $headers, $options);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function registrationSecurityPayload(array $overrides = []): array
    {
        $renderedAt = isset($overrides['form_rendered_at'])
            ? (int) $overrides['form_rendered_at']
            : now()->subSeconds(5)->timestamp;

        $payload = array_merge(
            RegistrationFormProtection::issue('127.0.0.1', $renderedAt),
            ['company' => '']
        );

        return array_merge($payload, $overrides);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function mergeRegistrationSecurity($uri, array $data): array
    {
        if (!$this->isRegisterRequest($uri)) {
            return $data;
        }

        return $this->registrationSecurityPayload($data);
    }

    private function isRegisterRequest($uri): bool
    {
        $path = parse_url((string) $uri, PHP_URL_PATH);

        return $path === '/api/v1/register';
    }
}
