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

namespace Bakame\UriSigner;

use BadMethodCallException;
use League\Uri\Components\Query;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class HmacTest extends TestCase
{
    /** @test */
    public function it_can_use_hmac_algo_to_signa_the_url(): void
    {
        $strategy = Hmac::md5('secret');
        $url = Http::createFromString('https://my.app.example');
        $result = $strategy->encrypt($url);
        $resultQuery = Query::createFromUri($result);
        self::assertTrue($resultQuery->has('signature'));
        self::assertCount(1, $resultQuery);
        self::assertSame('signature', $strategy->parameterName());
    }

    /** @test */
    public function it_keeps_intact_the_url(): void
    {
        $strategy = Hmac::gost('secret');
        $url = Http::createFromString('https://my.app.example');
        $result = $strategy->encrypt($url);

        $final = $strategy->decrypt($result);

        self::assertEquals((string) $final, (string) $url);
    }

    /** @test */
    public function it_can_change_the_signature_by_algo_in_the_url(): void
    {
        $strategySha256 = Hmac::sha1('secret');
        $url = Http::createFromString('https://my.app.example');
        $result = $strategySha256->encrypt($url);
        $resultQuery = Query::createFromUri($result);
        self::assertTrue($resultQuery->has('signature'));
        self::assertCount(1, $resultQuery);
        self::assertSame('signature', $strategySha256->parameterName());

        $strategyMd5 = Hmac::md4('secret');
        $resultMd5 = $strategyMd5->encrypt($url);

        self::assertNotEquals((string) $resultMd5, (string) $result);
    }

    /** @test */
    public function it_will_throw_an_exception_for_url_with_a_signature_parameter(): void
    {
        $url = 'http://myapp.com/?foo=bar&baz=qux&signature=baz';

        $this->expectException(QueryEncryptionError::class);

        $strategySha256 = Hmac::sha256('secret');
        $url = Http::createFromString($url);

        $strategySha256->encrypt($url);
    }

    /** @test */
    public function it_will_throw_if_the_algo_is_unknown(): void
    {
        $this->expectException(QueryEncryptionError::class);

        Hmac::{'foobar'}('secret', 'signature');
    }

    /** @test */
    public function it_will_throw_if_there_is_not_enough_argument(): void
    {
        $this->expectException(BadMethodCallException::class);

        Hmac::{'sha3-224'}();
    }
}
