@extends('adminlte::layouts.app')

@section('contentheader_title')
    <i class='fa fa-envelope-o'></i> Редактирование  Email Шаблона
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
                                <label for="name" class="control-label">Имя устройства (зашифрованное название android устройства)</label>
                                <input type="text" class="form-control" placeholder="Имя" name="name" value="{{ $data->name }}" id="name"/>
                            </div>

                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection