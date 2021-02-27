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

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use League\Uri\HttpFactory;
use PHPUnit\Framework\TestCase;

final class SpatieTest extends TestCase
{
    private UrlSigner $urlSigner;

    public function setUp(): void
    {
        $secret = 'random_monkey';
        $strategy = Spatie::fromExpiration(new DateTimeImmutable('+20 years'), $secret);
        $this->urlSigner = new UrlSigner($strategy, new HttpFactory());
    }

    /** @test */
    public function it_will_throw_an_exception_for_an_empty_signatureKey(): void
    {
        $this->expectException(QueryEncryptionError::class);

        Spatie::fromExpiration(
            new DateTimeImmutable('+20 years', new DateTimeZone('Africa/Libreville')),
            '      '
        );
    }

    /** @test */
    public function it_returns_false_when_validating_a_forged_url(): void
    {
        $signedUrl = 'http://myapp.com/somewhereelse/?expires=4594900544&signature=41d5c3a92c6ef94e73cb70c7dcda0859';

        self::assertFalse($this->urlSigner->validate($signedUrl));
    }

    /** @test */
    public function it_returns_false_when_validating_an_expired_url(): void
    {
        $signedUrl = 'http://myapp.com/?expires=1123690544&signature=93e02326d7572632dd6edfa2665f2743';

        self::assertFalse($this->urlSigner->validate($signedUrl));
    }

    /** @test */
    public function it_returns_false_when_validating_a_forged_invalid_expires_parameter(): void
    {
        $signedUrl = 'http://myapp.com/?expires=foobar&signature=93e02326d7572632dd6edfa2665f2743';

        self::assertFalse($this->urlSigner->validate($signedUrl));
    }

    /** @test */
    public function it_returns_false_when_validating_a_forged_invalid_signature_parameter(): void
    {
        $expiration = time() + 86400;

        $signedUrl = 'http://myapp.com/?expires='.$expiration.'&signature=foobar';

        self::assertFalse($this->urlSigner->validate($signedUrl));
    }

    /** @test */
    public function it_returns_true_when_validating_an_non_expired_url(): void
    {
        $url = 'http://myapp.com';
        $signedUrl = $this->urlSigner->encrypt($url);

        self::assertTrue($this->urlSigner->validate($signedUrl));
    }

    /**
     * @return string[][]
     */
    public function unsignedUrlProvider(): array
    {
        return [
            ['http://myapp.com/?expires=4594900544'],
            ['http://myapp.com/?signature=41d5c3a92c6ef94e73cb70c7dcda0859'],
        ];
    }

    /**
     * @test
     * @dataProvider unsignedUrlProvider
     */
    public function it_returns_false_when_validating_an_unsigned_url(string $unsignedUrl): void
    {
        self::assertFalse($this->urlSigner->validate($unsignedUrl));
    }

    /**
     * @return (DateTime|DateTimeImmutable|int)[][]
     */
    public function pastExpirationProvider(): array
    {
        return [
            [new DateTime('-20 years')],
            [new DateTimeImmutable('-10 years')],
            [-10],
        ];
    }

    /**
     * @test
     *
     * @dataProvider pastExpirationProvider
     * @param DateTimeInterface|int $pastExpiration
     */
    public function it_doesnt_allow_expirations_in_the_past(DateTimeInterface|int $pastExpiration): void
    {
        $this->expectException(QueryEncryptionError::class);

        Spatie::fromExpiration($pastExpiration, 'random_monkey');
    }

    /** @test */
    public function it_keeps_the_urls_query_parameters_intact(): void
    {
        $url = 'http://myapp.com/?foo=bar&baz=qux';
        $signedUrl = $this->urlSigner->encrypt($url);

        self::assertSame($url, $this->urlSigner->decrypt($signedUrl));
    }

    /** @test */
    public function it_will_throw_an_exception_for_url_with_an_expires_parameter(): void
    {
        $url = 'http://myapp.com/?foo=bar&baz=qux&expires=baz';

        $this->expectException(QueryEncryptionError::class);

        $this->urlSigner->encrypt($url);
    }

    /** @test */
    public function it_will_throw_an_exception_for_url_with_a_signature_parameter(): void
    {
        $url = 'http://myapp.com/?foo=bar&baz=qux&signature=baz';

        $this->expectException(QueryEncryptionError::class);

        $this->urlSigner->encrypt($url);
    }
}
