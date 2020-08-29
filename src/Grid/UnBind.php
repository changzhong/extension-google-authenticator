<?php

namespace Dcat\Admin\Extension\GoogleAuthenticator\Grid;

use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Models\Administrator;
use Illuminate\Http\Request;

class UnBind extends RowAction
{
    public function title()
    {
        $src = ($this->row->{$this->getColumnName()}) ? 'on' : 'off';
        return '<img src="/vendors/dcat-admin-extensions/google-authenticator/images/switch_'.$src.'.png" style="width:39px;height:25px">';
    }

    public function handle(Request $request)
    {
        try {
            $class = $request->class;
            $column = $request->column;
            $id = $this->getKey();

            $model = $class::find($id);
            if(!$model->{$column}) {
                return $this->response()->error('当前用户未绑定');
            }
            $model->{$column} = '';
            $model->save();

            return $this->response()->success("解绑成功")->refresh();
        } catch (\Exception $e) {
            return $this->response()->error($e->getMessage());
        }
    }

    public function parameters()
    {
        return [
            'class' => $this->modelClass(),
            'column' => $this->getColumnName(),
        ];
    }

    public function getColumnName()
    {
        return $this->column->getName();
    }

    public function modelClass()
    {
        return get_class($this->parent->model()->eloquent()->repository()->eloquent());
    }

    public function confirm()
    {
        $row = Administrator::find($this->row->id);

        if($row['google_auth']) {
            return [
                "提示",
                '确认为该用户解绑吗？',
            ];
        }

    }
}
