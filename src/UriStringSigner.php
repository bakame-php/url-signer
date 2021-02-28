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

use Psr\Http\Message\UriFactoryInterface;

use Throwable;

final class UriStringSigner
{
    public function __construct(
        private UriEncryptor $urlEncryptor,
        private UriFactoryInterface $uriFactory
    ) {
    }

    /**
     * @throws EncryptionError
     */
    public function encrypt(string $url): string
    {
        return (string) $this->urlEncryptor->encrypt($this->uriFactory->createUri($url));
    }

    /**
     * @throws EncryptionError
     */
    public function decrypt(string $signedUrl): string
    {
        return (string) $this->urlEncryptor->decrypt($this->uriFactory->createUri($signedUrl));
    }

    public function validate(string $signedUrl): bool
    {
        try {
            $this->decrypt($signedUrl);
        } catch (Throwable) {
            return false;
        }

        return true;
    }
}
