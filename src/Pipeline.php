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

use Psr\Http\Message\UriInterface;

final class Pipeline implements UriEncryptor
{
    private array $uriEncryptors;

    public function __construct(UriEncryptor ...$uriEncryptors)
    {
        $this->uriEncryptors = $uriEncryptors;
    }

    public function encrypt(UriInterface $uri): UriInterface
    {
        return array_reduce(
            $this->uriEncryptors,
            fn (UriInterface $uri, UriEncryptor $modifier): UriInterface => $modifier->encrypt($uri),
            $uri
        );
    }

    public function decrypt(UriInterface $encryptedUri): UriInterface
    {
        return array_reduce(
            array_reverse($this->uriEncryptors),
            fn (UriInterface $uri, UriEncryptor $modifier): UriInterface => $modifier->decrypt($encryptedUri),
            $encryptedUri
        );
    }
}
