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

interface QueryEncryptor extends UriEncryptor
{
    public function parameterName(): string;
}
