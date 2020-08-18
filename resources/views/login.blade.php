<style>
    .login-box {
        margin-top: -10rem;
        padding: 5px;
        z-index: 10;
    }

    .login-card-body {
        padding: 1.5rem 1.8rem 1.6rem;
    }

    .card, .card-body {
        border-radius: .45rem;
        background: rgba(255, 255, 255, 0.1);
    }

    .login-btn {
        padding-left: 2rem !important;;
        padding-right: 1.5rem !important;
    }

    .content {
        overflow-x: hidden;
    }

    .form-group .control-label {
        text-align: left;
    }

    .login-page {
        position: relative;
    }

    canvas {
        position: absolute;
        top: 0;
        left: 0;
    }

</style>

<div class="login-page bg-40">
    <div class="login-box">
        <div class="login-logo mb-2 text-primary text-bold text-lg">
            <span>{{ config('admin.name') }}</span>
        </div>
        <div class="card">
            <div class="card-body login-card-body shadow-100">

                <form id="login-form" method="POST" action="{{ admin_url('auth/login') }}">

                    <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                    <input type="hidden" name="type" value="login" id="loginType">
                    <input type="hidden" name="secret" value="" id="secret">

                    <div class="form-box">
                        <p class="login-box-msg mt-1 mb-1">{{ __('admin.welcome_back') }}</p>
                        <fieldset class="form-label-group form-group position-relative has-icon-left">
                            <input
                                type="text"
                                class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                                name="username"
                                placeholder="{{ trans('admin.username') }}"
                                value="{{ old('username') }}"
                                required
                                autofocus
                            >

                            <div class="form-control-position">
                                <i class="feather icon-user"></i>
                            </div>

                            <label for="email">{{ trans('admin.username') }}</label>

                            <div class="help-block with-errors"></div>
                            @if($errors->has('username'))
                                <span class="invalid-feedback text-danger" role="alert">
                                    @foreach($errors->get('username') as $message)
                                        <span class="control-label" for="inputError">
                                            <i class="feather icon-x-circle"></i> {{$message}}
                                        </span>
                                        <br>
                                    @endforeach
                                </span>
                            @endif
                        </fieldset>

                        <fieldset class="form-label-group form-group position-relative has-icon-left">
                            <input
                                minlength="5"
                                maxlength="20"
                                id="password"
                                type="password"
                                class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                name="password"
                                placeholder="{{ trans('admin.password') }}"
                                required
                                autocomplete="current-password"
                            >

                            <div class="form-control-position">
                                <i class="feather icon-lock"></i>
                            </div>
                            <label for="password">{{ trans('admin.password') }}</label>

                            <div class="help-block with-errors"></div>
                            @if($errors->has('password'))
                                <span class="invalid-feedback text-danger" role="alert">
                                    @foreach($errors->get('password') as $message)
                                        <span class="control-label" for="inputError">
                                            <i class="feather icon-x-circle"></i> {{$message}}
                                        </span>
                                        <br>
                                    @endforeach
                                </span>
                            @endif

                        </fieldset>

                        <fieldset class="form-label-group form-group position-relative has-icon-left">
                            <input
                                minlength="6"
                                maxlength="6"
                                id="onecode"
                                class="form-control {{ $errors->has('onecode') ? 'is-invalid' : '' }}"
                                name="onecode"
                                placeholder="Google 动态验证码 未设置可不填"
                            >

                            <div class="form-control-position">
                                <i class="feather icon-lock"></i>
                            </div>
                            <label for="onecode">{{ trans('admin.password') }}</label>

                            <div class="help-block with-errors"></div>
                            @if($errors->has('onecode'))
                                <span class="invalid-feedback text-danger" role="alert">
                                    @foreach($errors->get('onecode') as $message)
                                        <span class="control-label" for="inputError">
                                            <i class="feather icon-x-circle"></i> {{$message}}
                                        </span>
                                        <br>
                                    @endforeach
                                </span>
                            @endif

                        </fieldset>


                        @if(config('admin.auth.remember'))
                            <div class="form-group d-flex justify-content-between align-items-center">
                                <div class="text-left">
                                    <fieldset class="checkbox">
                                        <div class="vs-checkbox-con vs-checkbox-primary">
                                            <input id="remember" name="remember" value="1"
                                                   type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                                            <span class="vs-checkbox">
                                                <span class="vs-checkbox--check">
                                                    <i class="vs-icon feather icon-check"></i>
                                                </span>
                                            </span>
                                            <span> {{ trans('admin.remember_me') }}</span>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        @endif
                        <button type="submit" class="btn btn-primary float-right login-btn">

                            {{ __('admin.login') }}
                            &nbsp;
                            <i class="feather icon-arrow-right"></i>
                        </button>
                    </div>

                    <div class="code-box hidden">
                        <p class="login-box-msg mt-1 mb-1">绑定谷歌帐号</p>
                        <div class="qrcode-container text-center">
                            <div id="qrcode"></div>
                            <p class="text-danger">请扫码后把验证码填写到下面输入框</p>
                        </div>

                        <fieldset class="form-label-group form-group position-relative has-icon-left">
                            <input
                                minlength="6"
                                maxlength="6"
                                id="code"
                                class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}"
                                name="code"
                                placeholder="Google 动态验证码"
                            >

                            <div class="form-control-position">
                                <i class="feather icon-lock"></i>
                            </div>
                            <label for="onecode">{{ trans('admin.password') }}</label>

                            <div class="help-block with-errors"></div>
                            @if($errors->has('onecode'))
                                <span class="invalid-feedback text-danger" role="alert">
                                    @foreach($errors->get('onecode') as $message)
                                        <span class="control-label" for="inputError">
                                            <i class="feather icon-x-circle"></i> {{$message}}
                                        </span>
                                        <br>
                                    @endforeach
                                </span>
                            @endif

                        </fieldset>


                        <button type="submit" class="btn btn-primary float-right bind-btn">

                            {{ __('绑定帐号并登录') }}
                            &nbsp;
                            <i class="feather icon-lock"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    Dcat.ready(function () {
        // ajax表单提交
        $('#login-form').form({
            validate: true,
            success: function (data) {
                if (data.code && data.code == 201) {
                    $('.form-box').addClass('hidden');
                    $('.code-box').removeClass('hidden');
                    $('#qrcode').html(data.qrcode);
                    $('#loginType').val('bind');
                    $('#secret').val(data.secret);
                    return false;
                }
                if (!data.status) {
                    Dcat.error(data.message);
                    return false;
                }

                Dcat.success(data.message);
                location.href = data.redirect;
                return false;
            }
        });


    });

</script>

