<?php

declare(strict_types=1);

namespace Lacasera\ElasticBridge\Tests\Unit;

use Aws\Credentials\CredentialProvider;
use Lacasera\ElasticBridge\Connection\OpenSearchConnection;
use Lacasera\ElasticBridge\Exceptions\MissingEnvException;
use Lacasera\ElasticBridge\Tests\TestCase;
use OpenSearch\Client;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class OpenSearchConnectionTest extends TestCase
{
    #[Test]
    public function it_builds_a_client_with_basic_auth(): void
    {
        config()->set('elasticbridge.auth_method', 'basic-auth');
        config()->set('elasticbridge.username', 'admin');
        config()->set('elasticbridge.password', 'admin');

        $openSearchConnection = new OpenSearchConnection;

        $this->assertInstanceOf(Client::class, $openSearchConnection->getClient());
    }

    #[Test]
    public function it_accepts_multiple_hosts_and_uses_the_first(): void
    {
        config()->set('elasticbridge.auth_method', 'basic-auth');
        config()->set('elasticbridge.host', ['https://node-1:9200', 'https://node-2:9200']);

        $openSearchConnection = new OpenSearchConnection;

        $this->assertInstanceOf(Client::class, $openSearchConnection->getClient());
    }

    #[Test]
    public function it_builds_a_client_with_an_api_key_header(): void
    {
        config()->set('elasticbridge.auth_method', 'api-key');
        config()->set('elasticbridge.api_key', 'abc123');

        $openSearchConnection = new OpenSearchConnection;

        $this->assertInstanceOf(Client::class, $openSearchConnection->getClient());
    }

    #[Test]
    public function it_requires_an_api_key_when_auth_method_is_api_key(): void
    {
        $this->expectException(MissingEnvException::class);
        $this->expectExceptionMessage('missing value for SEARCH_API_KEY env');

        config()->set('elasticbridge.auth_method', 'api-key');
        config()->set('elasticbridge.api_key', null);

        new OpenSearchConnection;
    }

    #[Test]
    public function it_requires_a_region_for_sigv4(): void
    {
        $this->expectException(MissingEnvException::class);
        $this->expectExceptionMessage('SEARCH_AWS_REGION is required for SigV4 authentication');

        config()->set('elasticbridge.auth_method', 'sigv4');
        config()->set('elasticbridge.sig_v4.region', null);

        new OpenSearchConnection;
    }

    #[Test]
    public function it_requires_the_aws_sdk_for_sigv4(): void
    {
        if (class_exists(CredentialProvider::class)) {
            $this->markTestSkipped('aws/aws-sdk-php is installed; the missing-dependency guard cannot be exercised.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('aws/aws-sdk-php');

        config()->set('elasticbridge.auth_method', 'sigv4');
        config()->set('elasticbridge.sig_v4.region', 'us-east-1');

        new OpenSearchConnection;
    }

    #[Test]
    public function it_requires_a_certificate_when_verify_ssl_is_true(): void
    {
        $this->expectException(MissingEnvException::class);
        $this->expectExceptionMessage('SEARCH_SSL_CERT is required if verify_ssl is true');

        config()->set('elasticbridge.verify_ssl', true);
        config()->set('elasticbridge.certificate', null);

        new OpenSearchConnection;
    }
}
