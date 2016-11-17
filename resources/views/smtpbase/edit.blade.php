@extends('adminlte::layouts.app')

@section('contentheader_title')
    Редактирование данных об smtp
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
                                <input type="text" class="form-control" placeholder="Домен" name="domain" value="{{ $data->domain }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" class="form-control" placeholder="Smtp" name="smtp" value="{{ $data->smtp }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" class="form-control" placeholder="Порт" name="port" value="{{ $data->port }}"/>
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection