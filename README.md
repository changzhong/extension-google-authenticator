### dcat-admin 后台谷歌登录验证码


##### 1. composer 安装扩展
```
 composer require changzhong/extension-google-authenticator
```

##### 2. 在后台》开发工具》扩展中启用,点扩展上面的导入

#### 3. 更改数据库 
```
php artisan migrate --path=vendor/changzhong/extension-google-authenticator/database/migrations/2019_11_21_120702_add_google_auth_to_admin_users_table.php
```

#### 4. 更改app/Admin/Controllers/AuthController.php
```php
<?php

namespace App\Admin\Controllers;

//use Dcat\Admin\Controllers\AuthController as BaseAuthController;
use Dcat\Admin\Extension\GoogleAuthenticator\Http\Controllers\AuthController as BaseAuthController;

class AuthController extends BaseAuthController
{
}

```



