<?php

/**
 * Url.Signer (https://github.com/bakame-php/url-signer)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\UrlSigner;

use League\Uri\Components\Query;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    /**
     * @param array<UrlEncryptor> $strategies
     */
    public function pipe(array $strategies): Pipeline
    {
        return new Pipeline(...$strategies);
    }

    /** @test */
    public function it_transparently_uses_another_strategy(): void
    {
        $strategy = $this->pipe([Hmac::md5('secret')]);

        $url = Http::createFromString('https://my.app.example');
        $signedUrl = $strategy->encrypt($url);
        $resultQuery = Query::createFromUri($signedUrl);
        self::assertTrue($resultQuery->has('signature'));
        self::assertCount(1, $resultQuery);

        $unsignedUrl = $strategy->decrypt($signedUrl);

        self::assertEquals((string) $unsignedUrl, (string) $url);
    }

    /** @test */
    public function it_changes_outcome_on_strategy_order(): void
    {
        $expiresStrategy = Expiration::at(new \DateTimeImmutable('+1 HOUR'));
        $hashStrategy = Hmac::md5('secret');
        $strategy1 = $this->pipe([$hashStrategy, $expiresStrategy]);
        $strategy2 = $this->pipe([$expiresStrategy, $hashStrategy]);

        $url = Http::createFromString('https://my.app.example');

        self::assertNotEquals(
            (string) $strategy1->encrypt($url),
            (string) $strategy2->encrypt($url)
        );
    }
}
