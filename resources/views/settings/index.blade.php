@extends('adminlte::layouts.app')

@section('contentheader_title')
    Общие настройки
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="box box-primary">
                    <div class="box-body">
                        <form action="" method="post">
                            {{csrf_field()}}
                            <div class="form-group has-feedback">
                                <input type="text" class="form-control" placeholder="Best-proxies" name="best_proxies" value="{{ empty($data) ? "" : $data->best_proxies }}"/>
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection