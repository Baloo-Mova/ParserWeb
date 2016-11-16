@extends('adminlte::layouts.app')

@section('contentheader_title')
    Редактирование данных об аккаунте
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
                                <input type="text" class="form-control" placeholder="Логин" name="login" value="{{ $data->login }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="password" class="form-control" placeholder="Пароль" name="password" value="{{ $data->password }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <select class="form-control" name="type_id" id="">
                                    <option value=""></option>
                                    <option value="1" {{ $data->type_id == 1 ? "selected" : ""}}>VK</option>
                                    <option value="2" {{ $data->type_id == 2 ? "selected" : ""}}>OK</option>
                                    <option value="3" {{ $data->type_id == 3 ? "selected" : ""}}>SMTP</option>
                                </select>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" class="form-control" placeholder="SMTP порт" name="smtp_port" value="{{ $data->smtp_port }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" class="form-control" placeholder="SMTP Адрес" name="smtp_address" value="{{ $data->smtp_address }}"/>
                            </div>
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection