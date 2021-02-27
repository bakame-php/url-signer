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

interface UrlEncryptor
{
    /**
     * @throws EncryptionError if the UriInterface object can not be encrypted
     */
    public function encrypt(UriInterface $uri): UriInterface;

    /**
     * @throws EncryptionError if the UriInterface object can not be decrypted
     */
    public function decrypt(UriInterface $uri): UriInterface;
}
