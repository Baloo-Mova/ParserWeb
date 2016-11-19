@extends('adminlte::layouts.app')

@section('contentheader_title')
    Редактирование Данных SMTP
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
                                <label for="domain" class="control-label">Домен</label>
                                <input type="text" class="form-control" placeholder="Домен" name="domain" id="domain" value="{{ $data->domain }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="smtp" class="control-label">Smtp</label>
                                <input type="text" class="form-control" placeholder="Smtp" name="smtp" id="smtp" value="{{ $data->smtp }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="port" class="control-label">Порт</label>
                                <input type="text" class="form-control" placeholder="Порт" name="port" id="port" value="{{ $data->port }}"/>
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection