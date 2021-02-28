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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Psr15UriValidatorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UriEncryptor $uriEncryptor,
        private string $attributeName
    ) {
    }

    /**
     * @throws EncryptionError
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $signedUrl = $request->getUri();
        $unsignedUrl = $this->uriEncryptor->decrypt($signedUrl);

        return $handler->handle(
            $request
                ->withAttribute($this->attributeName, $unsignedUrl)
                ->withUri($unsignedUrl)
        );
    }
}
