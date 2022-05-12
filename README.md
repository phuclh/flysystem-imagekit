# A Laravel flysystem driver for Imagekit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phuclh/flysystem-imagekit.svg?style=flat-square)](https://packagist.org/packages/phuclh/flysystem-imagekit)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/phuclh/flysystem-imagekit/run-tests?label=tests)](https://github.com/phuclh/flysystem-imagekit/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/phuclh/flysystem-imagekit/Check%20&%20fix%20styling?label=code%20style)](https://github.com/phuclh/flysystem-imagekit/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/phuclh/flysystem-imagekit.svg?style=flat-square)](https://packagist.org/packages/phuclh/flysystem-imagekit)

This package is an upgraded version of [TaffoVelikoff/imagekit-adapter](https://github.com/TaffoVelikoff/imagekit-adapter) that supports Laravel 9 and Flysystem 3.

## Installation

You can install the package via composer:

```bash
composer require phuclh/flysystem-imagekit
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="flysystem-imagekit-config"
```

## Setup

First you will need to sing up for an [ImageKit](https://imagekit.io/) account. Then go [https://imagekit.io/dashboard#developers](https://imagekit.io/dashboard#developers) to get your public key, private key and url endpoint. Add the following to your .env file:

```bash
IMAGEKIT_PUBLIC=your_public_key
IMAGEKIT_PRIVATE=your_public_key
IMAGEKIT_ENDPOINT=https://ik.imagekit.io/your_id
```

## Usage

```php
// Upload file (second argument can be an url, file or base64)
Storage::disk('imagekit')->put('filename.jpg', 'https://mysite.com/my_image.com');

// Get file
Storage::disk('imagekit')->get('filename.jpg');

// Delete file
Storage::disk('imagekit')->delete('filename.jpg');

// List all files 
Storage::disk('imagekit')->listContents('', false); // listContents($path, $deep)
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [phucle](https://github.com/phuclh)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
