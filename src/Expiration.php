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

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use League\Uri\Components\Query;
use Psr\Http\Message\UriInterface;

final class Expiration implements QueryEncryptor
{
    private DateTimeInterface $expiresAt;

    private function __construct(private string $parameterName, DateTimeInterface $expiresAt)
    {
        if ($this->isPast($expiresAt)) {
            throw new QueryEncryptionError('Expiration date "'.$expiresAt->getTimestamp().'" must be in the future');
        }

        $this->expiresAt = $expiresAt;
    }

    private function isPast(DateTimeInterface|int $expires): bool
    {
        $now = new DateTimeImmutable();
        if (! $expires instanceof DateTimeInterface) {
            return $expires < $now->getTimestamp();
        }

        return $expires < $now;
    }

    public static function after(DateInterval $interval, string $parameterName = 'expires'): self
    {
        return new self($parameterName, (new DateTimeImmutable())->add($interval));
    }

    public static function at(DateTimeInterface $expiresAt, string $parameterName = 'expires'): self
    {
        return new self($parameterName, $expiresAt);
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

        /** @var string $queryString */
        $queryString = $query->withPair($this->parameterName, $this->expiresAt->getTimestamp())->toRFC3986();

        return $uri->withQuery($queryString);
    }

    public function decrypt(UriInterface $encryptedUri): UriInterface
    {
        $query = Query::createFromUri($encryptedUri);
        $expiration = $query->get($this->parameterName);

        return match (true) {
            null === $expiration => throw QueryEncryptionError::dueToMissingParameter($this->parameterName, $encryptedUri),
            false === ($timestamp = filter_var($expiration, FILTER_VALIDATE_INT)) => throw QueryEncryptionError::dueToWrongValue($this->parameterName, $encryptedUri),
            $this->isPast($timestamp) => throw new QueryEncryptionError('Corrupted Url, the expiration date "'.$timestamp.'" must be in the future.', $encryptedUri),
            default => $encryptedUri->withQuery((string) $query->withoutPair($this->parameterName)->toRFC3986()),
        };
    }
}
