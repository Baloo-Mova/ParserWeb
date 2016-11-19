@extends('adminlte::layouts.app')

@section('contentheader_title')
    Общие Настройки
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
                                <label for="best_proxies" class="control-label">Best-proxies</label>
                                <input type="text" class="form-control" placeholder="Best-proxies" name="best_proxies" id="best_proxies" value="{{ empty($data) ? "" : $data->best_proxies }}"/>
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection