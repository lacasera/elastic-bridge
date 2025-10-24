<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Elastic\Transport\Transport;
use Lacasera\ElasticBridge\Connection\ElasticConnection;
use Lacasera\ElasticBridge\Exceptions\MissingEnvException;
use Lacasera\ElasticBridge\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

class ElasticConnectionTest extends TestCase
{
    private function makeConnection(): ElasticConnection
    {
        return new ElasticConnection;
    }

    #[Test]
    public function it_uses_basic_auth_when_configured(): void
    {
        config()->set('elasticbridge.auth_method', 'basic-auth');
        config()->set('elasticbridge.username', 'elastic');
        config()->set('elasticbridge.password', 'secret');

        $elasticConnection = $this->makeConnection();
        $client = $elasticConnection->getClient();
        $transport = $client->getTransport();

        // Authorization header should not be set for basic auth
        $this->assertArrayNotHasKey('Authorization', $transport->getHeaders());

        // Inspect Transport private properties for user/password
        $reflectionClass = new ReflectionClass(Transport::class);
        $reflectionProperty = $reflectionClass->getProperty('user');
        $reflectionProperty->setAccessible(true);

        $propPass = $reflectionClass->getProperty('password');
        $propPass->setAccessible(true);

        $this->assertSame('elastic', $reflectionProperty->getValue($transport));
        $this->assertSame('secret', $propPass->getValue($transport));
    }

    #[Test]
    public function it_does_not_set_basic_auth_when_password_is_missing(): void
    {
        config()->set('elasticbridge.auth_method', 'basic-auth');
        config()->set('elasticbridge.username', 'elastic');
        config()->set('elasticbridge.password', null);

        $elasticConnection = $this->makeConnection();
        $transport = $elasticConnection->getClient()->getTransport();

        // No Authorization header and no initialized user/password properties
        $this->assertArrayNotHasKey('Authorization', $transport->getHeaders());

        $reflectionClass = new ReflectionClass(Transport::class);
        $reflectionProperty = $reflectionClass->getProperty('user');
        $reflectionProperty->setAccessible(true);

        $propPass = $reflectionClass->getProperty('password');
        $propPass->setAccessible(true);

        $this->assertFalse($reflectionProperty->isInitialized($transport));
        $this->assertFalse($propPass->isInitialized($transport));
    }

    #[Test]
    public function it_uses_api_key_token_when_configured(): void
    {
        config()->set('elasticbridge.auth_method', 'api-key');
        config()->set('elasticbridge.api_key', 'abc123');
        // Ensure basic credentials don't interfere
        config()->set('elasticbridge.username', null);
        config()->set('elasticbridge.password', null);

        $transport = $this->makeConnection()->getClient()->getTransport();

        $headers = $transport->getHeaders();
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertSame('ApiKey abc123', $headers['Authorization']);
    }

    #[Test]
    public function it_requires_api_key_when_auth_method_is_api_key(): void
    {
        $this->expectException(MissingEnvException::class);
        $this->expectExceptionMessage('missing value for ELASTICSEARCH_API_KEY env');

        config()->set('elasticbridge.auth_method', 'api-key');
        config()->set('elasticbridge.api_key', null);

        // Will throw during construction
        $this->makeConnection();
    }

    #[Test]
    public function it_requires_certificate_when_verify_ssl_is_true_and_certificate_missing(): void
    {
        $this->expectException(MissingEnvException::class);
        $this->expectExceptionMessage('ELASTICSEARCH_SSL_CERT is required if verify_ssl is true');

        config()->set('elasticbridge.verify_ssl', true);
        config()->set('elasticbridge.certificate', null);

        $this->makeConnection();
    }

    #[Test]
    public function it_allows_custom_certificate_when_verify_ssl_is_true(): void
    {
        config()->set('elasticbridge.verify_ssl', true);
        config()->set('elasticbridge.certificate', '/tmp/http_ca.crt');

        $elasticConnection = $this->makeConnection();
        $this->assertNotNull($elasticConnection->getClient());
    }
}
