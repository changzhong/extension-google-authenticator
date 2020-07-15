<?php

namespace Dcat\Admin\Extension\GoogleAuthenticator;

use Dcat\Admin\Extension;

class GoogleAuthenticator extends Extension
{
    const NAME = 'google-authenticator';

    protected $serviceProvider = GoogleAuthenticatorServiceProvider::class;

    protected $composer = __DIR__.'/../composer.json';

    protected $views = __DIR__.'/../resources/views';

    protected $assets = __DIR__.'/../resources/assets';

}
