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
use Psr\Http\Message\UriInterface;

/**
 * @method static Hmac md2(string $secret, string $parameterName = 'signature')
 * @method static Hmac md4(string $secret, string $parameterName = 'signature')
 * @method static Hmac md5(string $secret, string $parameterName = 'signature')
 * @method static Hmac sha1(string $secret, string $parameterName = 'signature')
 * @method static Hmac sha224(string $secret, string $parameterName = 'signature')
 * @method static Hmac sha256(string $secret, string $parameterName = 'signature')
 * @method static Hmac sha384(string $secret, string $parameterName = 'signature')
 * @method static Hmac sha512(string $secret, string $parameterName = 'signature')
 * @method static Hmac ripemd128(string $secret, string $parameterName = 'signature')
 * @method static Hmac ripemd160(string $secret, string $parameterName = 'signature')
 * @method static Hmac ripemd256(string $secret, string $parameterName = 'signature')
 * @method static Hmac ripemd320(string $secret, string $parameterName = 'signature')
 * @method static Hmac whirpool(string $secret, string $parameterName = 'signature')
 * @method static Hmac snefru(string $secret, string $parameterName = 'signature')
 * @method static Hmac snefru256(string $secret, string $parameterName = 'signature')
 * @method static Hmac gost(string $secret, string $parameterName = 'signature')
 */
final class Hmac implements QueryEncryptor
{
    private string $algorithm;

    public function __construct(
        string $algorithm,
        private string $secret,
        private string $parameterName,
    ) {
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_hmac_algos(), true)) {
            throw new QueryEncryptionError('Unknown or unsupported algorithm "'.$algorithm.'".');
        }

        $this->algorithm = $algorithm;
    }

    /**
     * @param array<string> $arguments
     */
    public static function __callStatic(string $method, array $arguments = []): self
    {
        if ([] === $arguments) {
            throw new BadMethodCallException('"'.self::class.'" magic named constructors require a secret string and an optional query parameter name.');
        }

        return new self($method, ...($arguments + [1 => 'signature']));
    }

    public function parameterName(): string
    {
        return $this->parameterName;
    }

    public function encrypt(UriInterface $uri): UriInterface
    {
        $query = Query::createFromUri($uri);
        if ($query->has($this->parameterName)) {
            throw QueryEncryptionError::dueToAlreadyPresentParameter($this->parameterName, $uri);
        }

        return $uri->withQuery(
            (string) $query->withPair($this->parameterName, $this->createSignature($uri))->toRFC3986()
        );
    }

    private function createSignature(UriInterface $uri): string
    {
        return hash_hmac($this->algorithm, (string) $uri, $this->secret);
    }

    public function decrypt(UriInterface $encryptedUri): UriInterface
    {
        $query = Query::createFromUri($encryptedUri);
        $signature = $query->get($this->parameterName);
        $unsignedUrl = $encryptedUri->withQuery((string) $query->withoutPair($this->parameterName)->toRFC3986());

        return match (true) {
            null === $signature => throw QueryEncryptionError::dueToMissingParameter($this->parameterName, $encryptedUri),
            1 !== preg_match('/^[0-9a-f]+$/', $signature) => throw QueryEncryptionError::dueToWrongValue($this->parameterName, $encryptedUri),
            !hash_equals($signature, $this->createSignature($unsignedUrl)) => throw QueryEncryptionError::dueToWrongValue($this->parameterName, $encryptedUri),
            default => $unsignedUrl,
        };
    }
}
