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

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

final class QueryEncryptionError extends InvalidArgumentException implements EncryptionError
{
    public static function dueToMissingParameter(string $parameterName): self
    {
        return new self('The parameter "'.$parameterName.'" is missing or contains no value.');
    }

    public static function dueToMissingValue(string $parameterName): self
    {
        return new self('The parameter "'.$parameterName.'" contains no value.');
    }

    public static function dueToWrongValue(string $parameterName): self
    {
        return new self('The parameter "'.$parameterName.'" contains invalid value.');
    }

    public static function dueToAlreadyPresentParameter(string $parameterName): self
    {
        return new self('The parameter "'.$parameterName.'" reserved for generating signed URI is already present. Please rename your parameter.');
    }

    public static function dueToCorruptedUrl(UriInterface $uri): self
    {
        return new self('The URI "'.$uri.'" is an invalid signed UR.');
    }
}
