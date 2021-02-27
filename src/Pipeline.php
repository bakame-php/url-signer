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

use Psr\Http\Message\UriInterface;

final class Pipeline implements UrlEncryptor
{
    private array $modifiers;

    public function __construct(UrlEncryptor ...$modifiers)
    {
        $this->modifiers = $modifiers;
    }

    public function encrypt(UriInterface $uri): UriInterface
    {
        return array_reduce(
            $this->modifiers,
            fn (UriInterface $uri, UrlEncryptor $modifier): UriInterface => $modifier->encrypt($uri),
            $uri
        );
    }

    public function decrypt(UriInterface $uri): UriInterface
    {
        return array_reduce(
            array_reverse($this->modifiers),
            fn (UriInterface $uri, UrlEncryptor $modifier): UriInterface => $modifier->decrypt($uri),
            $uri
        );
    }
}
