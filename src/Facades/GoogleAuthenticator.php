<?php

namespace Dcat\Admin\Extension\GoogleAuthenticator\Facades;


use Illuminate\Support\Facades\Facade;

class GoogleAuthenticator extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'GoogleAuthenticator';
	}
}
