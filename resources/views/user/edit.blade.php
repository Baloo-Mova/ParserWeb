@extends('adminlte::layouts.app')

@section('contentheader_title')
    Редактирование пользователя
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
                                <label for="name" class="control-label">Имя</label>
                                <input type="text" class="form-control" placeholder="Имя" name="name" value="{{ $data->name }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="email" class="control-label">Ел. почта</label>
                                <input type="email" class="form-control" placeholder="Ел. почта}" name="email" value="{{ $data->email }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="password" class="control-label">Пароль</label>
                                <input type="password" class="form-control" placeholder="Пароль" name="password" />
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection