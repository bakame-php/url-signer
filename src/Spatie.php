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

use DateInterval;
use DateTimeInterface;
use League\Uri\Components\Query;
use Psr\Http\Message\UriInterface;

final class Spatie implements UrlEncryptor
{
    private string $secret;

    public function __construct(
        private QueryEncryptor $queryEncryptor,
        private string $parameterName,
        string $secret
    ) {
        if ('' === trim($secret)) {
            throw QueryEncryptionError::dueToMissingValue('signature');
        }

        $this->secret = $secret;
    }

    public static function fromExpiration(DateTimeInterface|int $expires, string $secret): self
    {
        if ($expires instanceof DateTimeInterface) {
            return new self(Expiration::at($expires), 'signature', $secret);
        }

        $interval = DateInterval::createFromDateString($expires.' DAYS');

        return new self(Expiration::after($interval), 'signature', $secret);
    }

    public function encrypt(UriInterface $uri): UriInterface
    {
        $uri = $this->queryEncryptor->encrypt($uri);
        $query = Query::createFromUri($uri);
        if ($query->has($this->parameterName)) {
            throw QueryEncryptionError::dueToAlreadyPresentParameter($this->parameterName);
        }

        /** @var string $queryEncryptedValue */
        $queryEncryptedValue = $query->get($this->queryEncryptor->parameterName());

        return $uri->withQuery(
            (string) $query
            ->withPair($this->parameterName, $this->createSignature($uri, $queryEncryptedValue))
            ->toRFC3986()
        );
    }

    private function createSignature(UriInterface $uri, string|null $queryEncryptedValue): string
    {
        return md5("{$uri}::{$queryEncryptedValue}::{$this->secret}");
    }

    public function decrypt(UriInterface $uri): UriInterface
    {
        $query = Query::createFromUri($uri);
        $signature = $query->get($this->parameterName);
        $queryEncryptedValue = $query->get($this->queryEncryptor->parameterName());
        $unsignedUrl = $uri->withQuery((string) $query->withoutPair($this->parameterName)->toRFC3986());

        return match (true) {
            null === $signature => throw QueryEncryptionError::dueToMissingParameter($this->parameterName),
            1 !== preg_match('/^[0-9a-f]+$/', $signature) => throw QueryEncryptionError::dueToWrongValue($this->parameterName),
            !hash_equals($signature, $this->createSignature($unsignedUrl, $queryEncryptedValue)) => throw QueryEncryptionError::dueToCorruptedUrl($uri),
            default => $this->queryEncryptor->decrypt($unsignedUrl)
        };
    }
}
