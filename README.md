# Filament Impersonate - Authenticate as your users


[![Latest Version on Packagist](https://img.shields.io/packagist/v/xlite-dev/filament-impersonate.svg?style=flat-square)](https://packagist.org/packages/xlite-dev/filament-impersonate)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/xlited/filament-impersonate/run-tests?label=tests)](https://github.com/xlited/filament-impersonate/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/xlited/filament-impersonate/Check%20&%20fix%20styling?label=code%20style)](https://github.com/xlited/filament-impersonate/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xlite-dev/filament-impersonate.svg?style=flat-square)](https://packagist.org/packages/xlite-dev/filament-impersonate)



## Installation

You can install the package via composer:

```bash
composer require xlite-dev/filament-impersonate
```

## Usage

### 1. Add `Table` action

Open the resource where you want the impersonate action to appear. This is generally going to be your `UserResource` class.

Go down to the `table` method. Inside `actions` or  `prependActions` add `ImpersonateAction::make`  as a new action for the table. Your class should look like this:

```php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use XliteDev\FilamentImpersonate\Tables\Actions\ImpersonateAction; // <---

class UserResource extends Resource {
    // ...

    public static function table(Table $table)
    {
        return $table
            ->columns([
                // ...
            ])
            ->actions([
                ImpersonateAction::make(), // <--- 
                // ...
            ]);
    }
```

You should now see an action icon next to each user in your Filament `UserResource` list:

<img width="1157" alt="image" src="https://user-images.githubusercontent.com/4498933/199263982-132c225b-7b97-49d8-82ac-5dddf7377c95.png">


When you click on the impersonate icon you will be logged in as that user, and redirected to your main app. You will see the impersonation banner at the top of the page, with a button to leave and return to Filament:

<img width="1156" alt="image" src="https://user-images.githubusercontent.com/4498933/199263509-260eebe7-fca3-41fe-8be5-6a393715219e.png">


### 2. Add `Page` action

Open `EditUser` or `ViewUser` class, where you want the impersonate action to appear.

Go down to the `getActions` method and add `ImpersonateAction::make` as a new action for the page. Your class should look like this:

```php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use XliteDev\FilamentImpersonate\Pages\Actions\ImpersonateAction; // <---

class EditUser extends ViewRecord
{
    // ...

    protected function getActions(): array
    {
        return [
            ImpersonateAction::make()->record($this->getRecord()), // <---
            // ...
        ];
    }
```

You should now see an action icon on the `EditUser` or `ViewUser` page:

<img width="1157" alt="image" src="https://user-images.githubusercontent.com/4498933/199263660-a6e32c39-270a-4406-a289-bb3859045732.png">




## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-impersonate-config"
```

This is the contents of the published config file:

```php
return [

    // This is the guard used when logging in as the impersonated user.
    'guard' => env('FILAMENT_IMPERSONATE_GUARD', 'web'),

    // After impersonating this is where we'll redirect you to.
    'redirect_to' => env('FILAMENT_IMPERSONATE_REDIRECT', '/'),

    // We wire up a route for the "leave" button. You can change the middleware stack here if needed.
    'leave_middlewares' => [
        env('FILAMENT_IMPERSONATE_LEAVE_MIDDLEWARE', 'web'),
    ],

    'banner' => [
        // Currently supports 'dark' and 'light'.
        'style' => env('FILAMENT_IMPERSONATE_BANNER_STYLE', 'dark'),

        // Turn this off if you want `absolute` positioning, so the banner scrolls out of view
        'fixed' => env('FILAMENT_IMPERSONATE_BANNER_FIXED', true),

        // Currently supports 'top' and 'bottom'.
        'position' => env('FILAMENT_IMPERSONATE_BANNER_POSITION', 'top'),
    ],
];

```

## Authorization

By default, only Filament admins can impersonate other users. You can control this by adding a `canImpersonate` method to your `FilamentUser` class:

```php
class User implements FilamentUser {
    
    public function canImpersonate()
    {
        return true;
    }
    
}
```

You can also control which targets can *be* impersonated. Just add a `canBeImpersonated` method to the user class with whatever logic you need:

```php
class User {

    public function canBeImpersonated()
    {
        // Let's prevent impersonating other users at our own company
        return !Str::endsWith($this->email, '@mycorp.com');
    }
    
}
``` 

## Customizing the banner

You can publish the views using

```bash
php artisan vendor:publish --tag="filament-impersonate-views"
```

The blade component has a few options you can customize.

### Style

The banner is dark by default, you can set this to light:

```html
<x-filament-impersonate::banner style='light'/>
```

### Display name

The banner will show the name of the impersonated user, assuming there is a `name` attribute. You can customize this if needed:

```html
<x-filament-impersonate::banner :display='auth()->user()->email'/>
```


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ion Caliman](https://github.com/icaliman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
