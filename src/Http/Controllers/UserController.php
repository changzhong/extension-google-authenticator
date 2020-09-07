<?php

namespace Dcat\Admin\Extension\GoogleAuthenticator\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Auth\Permission;
use Dcat\Admin\Extension\GoogleAuthenticator\GoogleAuthenticator;
use Dcat\Admin\Extension\GoogleAuthenticator\Http\Grid\UnBind;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\IFrameGrid;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Models\Administrator as AdministratorModel;
use Dcat\Admin\Models\Repositories\Administrator;
use Dcat\Admin\Show;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\Box;
use Dcat\Admin\Widgets\Tree;
use Illuminate\Support\Facades\DB;
use Dcat\Admin\Extension\GoogleAuthenticator\Actions\Unbind as UnbindAction;

class UserController extends \Dcat\Admin\Controllers\UserController
{

    protected function grid()
    {
        return Grid::make(new Administrator('roles'), function (Grid $grid) {
            $grid->id('ID')->sortable();
            $grid->username;
            $grid->name;
            $grid->column('is_open_google_auth', '谷歌验证')->using(['否', '是'])->badge([1=>'success', 'default' => 'danger']);
            $grid->column('google_auth', '是否绑定')->action(\Dcat\Admin\Extension\GoogleAuthenticator\Grid\UnBind::class);

            if (config('admin.permission.enable')) {
                $grid->roles->pluck('name')->label('primary', 3);

                $permissionModel = config('admin.database.permissions_model');
                $roleModel = config('admin.database.roles_model');
                $nodes = (new $permissionModel())->allNodes();
                $grid->permissions
                    ->if(function () {
                        return ! empty($this->roles);
                    })
                    ->showTreeInDialog(function (Grid\Displayers\DialogTree $tree) use (&$nodes, $roleModel) {
                        $tree->nodes($nodes);

                        foreach (array_column($this->roles, 'slug') as $slug) {
                            if ($roleModel::isAdministrator($slug)) {
                                $tree->checkAll();
                            }
                        }
                    })
                    ->else()
                    ->emptyString();
            }

            $grid->created_at;
            $grid->updated_at->sortable();

            $grid->quickSearch(['id', 'name', 'username']);

            $grid->disableBatchDelete();
//            $grid->showQuickEditButton();
            $grid->disableFilterButton();
            $grid->enableDialogCreate();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                if ($actions->getKey() == AdministratorModel::DEFAULT_ID) {
                    $actions->disableDelete();
                }
            });
        });
    }


    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {

        return $content
            ->title($this->title())
            ->description($this->description()['edit'] ?? trans('admin.edit'))
            ->row(function (Row $row) use ($id) {

                $row->column(9, function (Column $column) use ($id) {
                    $column->append($this->form()->edit($id));
                });

                $row->column(3, function (Column $column) use ($id){
                    $userTable = config('admin.database.users_table');
                    $user = DB::table($userTable)->where('id', $id)->first();
                    $column->append($this->google($user));
                });

            });
    }

    public function form()
    {
        return Form::make(new Administrator('roles'), function (Form $form) {
            if($form->model()->google_auth) {
                $form->tools(function (Form\Tools $tools) {
                // 添加一个按钮, 参数可以是字符串, 匿名函数, 或者实现了Renderable或Htmlable接口的对象实例
                    $tools->append(new UnbindAction());
            });
            }

            $userTable = config('admin.database.users_table');

            $connection = config('admin.database.connection');

            $id = $form->getKey();

            $form->display('id', 'ID');

            $form->text('username', trans('admin.username'))
                ->required()
                ->creationRules(['required', "unique:{$connection}.{$userTable}"])
                ->updateRules(['required', "unique:{$connection}.{$userTable},username,$id"]);
            $form->text('name', trans('admin.name'))->required();
            $form->image('avatar', trans('admin.avatar'));

            $form->switch('is_open_google_auth', '谷歌验证')->customFormat(function ($v) {
                return $v == 1 ? 1 : 0;
            })->default(1);
//            $form->switch('is_open_google_auth')->->value(1);

            if ($id) {
                $form->password('password', trans('admin.password'))
                    ->minLength(5)
                    ->maxLength(20)
                    ->customFormat(function () {
                        return '';
                    });
            } else {
                $form->password('password', trans('admin.password'))
                    ->required()
                    ->minLength(5)
                    ->maxLength(20);
            }

            $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

            $form->ignore(['password_confirmation']);

            if (config('admin.permission.enable')) {
                $form->multipleSelect('roles', trans('admin.roles'))
                    ->options(function () {
                        $roleModel = config('admin.database.roles_model');

                        return $roleModel::all()->pluck('name', 'id');
                    })
                    ->customFormat(function ($v) {
                        return array_column($v, 'id');
                    });
            }

            $form->display('created_at', trans('admin.created_at'));
            $form->display('updated_at', trans('admin.updated_at'));

            if ($id == AdministratorModel::DEFAULT_ID) {
                $form->disableDeleteButton();
            }
        })->saving(function (Form $form) {
            if ($form->password && $form->model()->get('password') != $form->password) {
                $form->password = bcrypt($form->password);
            }

            if (! $form->password) {
                $form->deleteInput('password');
            }
        });
    }

    protected function google($user)
    {
        $secret = $user->google_auth ?? '';
        $createSecret = google_create_secret(32,$secret, $user->username);
        $box = new Box('Google 验证绑定',view(GoogleAuthenticator::NAME.'::google', ['createSecret' => $createSecret, 'id' => $user->id, 'google_auth' => $user->google_auth]) );
        $box->style('info');
        return $box->render();

    }
}
