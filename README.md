# Create signed URLs

This package can signed URLs.

```php
use Bakame\UriSigner\Hmac;
use Bakame\UriSigner\Expiration;
use Bakame\UriSigner\UriStringSigner;
use League\Uri\HttpFactory;

$strategy1 = Expiration::after(new DateInterval('PT6H'));
$urlSigner = new UriStringSigner($strategy1, new HttpFactory());
$url = $urlSigner->encrypt('https://myapp.com');

$strategy2 = Hmac::sha256('random_key');
$urlSigner2 = new UriStringSigner($strategy2, new HttpFactory());
$url = $urlSigner2->encrypt($url);


// => The generated url will expire after 6 hours amd will be signed using the sha256 algorithm and a secret key
```

This will output an URL that looks like `https://myapp.com/?expires=xxx&signature=xxxx`.

Imagine mailing this URL out to the users of your application. When a user clicks on a signed URL
your application can validate it with:

```php
$urlSigner1->validate('https://myapp.com/?expires=xxxx&signature=xxxx'); // return true
$urlSigner2->validate('https://myapp.com/?expires=xxxx&signature=xxxx'); // return true
```

## System Requirements

You need **PHP >= 8.0** but the latest stable version of PHP is recommended.

- A [PSR-17 implementing package](https://packagist.org/providers/psr/http-factory-implementation) to convert as string into a PSR-7 UriInterface object.
- [League/uri/components](https://packagist.org/packages/league/uri-components) to use the bundled UrlModifiers

## Installation

The package can installed via Composer:
```
composer require bakame-php/url-signer
```

## Usage

### Generating URLs

The signer-object can sign, unsign and validate signed URLs.
Since signing can be different depending on the business logic of your application.
The package comes bundle with different signing strategy. You can create your own
signing strategy by:

- creating a class that implemets the  `Bakame\UrlSigner\UrlEncryptor` interface.
- combining multiple strategies using the `Bakame\UrlSigner\Pipeline` class.
- using the `Bakame\UrlSigner\Expiration` class.
- using the `Bakame\UrlSigner\Hmac` class.

```php
use Bakame\UriSigner\Expiration;
use Bakame\UriSigner\Hmac;
use Bakame\UriSigner\Pipeline;
use Bakame\UriSigner\UriStringSigner;
use League\Uri\HttpFactory;

$strategy = new Pipeline(
    Expiration::after(new DateInterval('PT6H')),
    Hmac::sha256('random_key'),
);

$urlSigner = new UriStringSigner($strategy, new HttpFactory());
```

### Validating URLs

To validate a signed URL, simply call the `validate()` method. This will return a boolean.

```php
$urlSigner->validate('https://myapp.com/?expires=1439223344&signature=2d42f65bd023362c6b61f7432705d811');

// => true

$urlSigner->validate('https://myapp.com/?expires=1439223344&signature=2d42f65bd0-INVALID-23362c6b61f7432705d811');

// => false
```

## Tests

`bakame/url-signer` has:

- a [PHPUnit](https://phpunit.de) test suite
- a code analysis compliance test suite using [Psalm](https://psalm.dev).
- a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).
- a coding style compliance test suite using [PHP CS Fixer](https://cs.symfony.com).

To run the tests, run the following command from the project folder.

``` bash
$ composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email nyamsprod@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
