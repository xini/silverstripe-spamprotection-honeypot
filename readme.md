# Silverstripe Honeypot SpamProtection Module

[![Version](https://img.shields.io/packagist/v/innoweb/silverstripe-spamprotection-honeypot.svg?color=darkorange&style=flat-square)](https://packagist.org/packages/innoweb/silverstripe-spamprotection-honeypot)
[![Silverstripe](https://img.shields.io/static/v1?label=Silverstripe&message=^4.10&color=blue&style=flat-square)](composer.json)
[![Downloads](https://img.shields.io/packagist/dt/innoweb/silverstripe-spamprotection-honeypot?color=lightseagreen&style=flat-square)](https://packagist.org/packages/innoweb/silverstripe-spamprotection-honeypot)
[![Issues](https://img.shields.io/github/issues-raw/xini/silverstripe-spamprotection-honeypot?color=darkred&style=flat-square)](https://github.com/xini/silverstripe-spamprotection-honeypot/issues)
[![License](https://img.shields.io/packagist/l/innoweb/silverstripe-spamprotection-honeypot.svg?style=flat-square)](license.md)

## Overview

Provides invisible honeypot spam protection for Silverstripe CMS.

Creates form fields hidden from users that invalidate submission if the contained data has been tampered with. Also invalidates submissions that respond too quickly.

## Requirements

- Silverstripe Framework 4.10+
- Silverstripe [SpamProtection](https://github.com/silverstripe/silverstripe-spamprotection) 3+.

## Installation

Run the following to add this module as a requirement and install it via composer.

```bash
$ composer require innoweb/silverstripe-spamprotection-honeypot
```
Then run dev/build.

## Usage

Create a configuration file `spamprotection.yml` in `app/_config` with the following configuration:

```yaml
---
Name: app-spamprotection
---
SilverStripe\SpamProtection\Extension\FormSpamProtectionExtension:
  default_spam_protector: Innoweb\SpamProtectionHoneypot\SpamProtector\HoneypotSpamProtector
```

We also recommend changing the default field name from `Captcha` to something less obvious:

```yaml
SilverStripe\SpamProtection\Extension\FormSpamProtectionExtension:
  field_name: 'AdditionalInformation'
```

Then enable spam protection on your form by calling `Form::enableSpamProtection()`.

```php
public function ExampleForm()
{
    $form = new ExampleForm($this, 'Example');

    $form->enableSpamProtection();

    return $form;
}
```

You can change the amount time that is checked to see if the response is made too quickly with the following configuration.

```yaml
Innoweb\SpamProtectionHoneypot\FormField\HoneypotField:
  time_limit: 12
```

This example changes the time to 12 seconds. The default is set to 8 seconds.

You can also change the default text used in the value field, overriding the translation using your lang file (e.g. `app/lang/en.yml`):

```yaml
en:
    Innoweb\SpamProtectionHoneypot\FormField\HoneypotField:
        DefaultValue: 'Some text that should not be touched.'
```

This defaults to `'Please leave this as is.'`.

## Contributing

Please see [contributing](contributing.md) for details.

## Credits

Thanks to [studiobonito/silverstripe-spamprotection-honeypot](https://github.com/studiobonito/silverstripe-spamprotection-honeypot) and [symbiote-library/silverstripe-spamprotection-honeypot](https://github.com/symbiote-library/silverstripe-spamprotection-honeypot) for the inspirations.

## License

BSD 3-Clause License, see [License](license.md)
