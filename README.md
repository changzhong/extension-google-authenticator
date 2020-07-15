### dcat-admin 后台谷歌登录验证码


##### 1. composer 安装扩展
```
 composer request changzhong/extension-google-authenticator
```

##### 2. 在后台》开发工具》扩展中启用,登录页面有个js动画，如果需要可以点扩展上面的导入

#### 3. 更改app/Admin/Controllers/AuthController.php
```php
<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Controllers\AuthController as BaseAuthController;
//use Dcat\Admin\Extension\GoogleAuthenticator\Http\Controllers\AuthController as BaseAuthController;

class AuthController extends BaseAuthController
{
}

```



