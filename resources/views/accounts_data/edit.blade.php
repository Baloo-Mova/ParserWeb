@extends('adminlte::layouts.app')

@section('contentheader_title')
    Редактирование Данных Об Аккаунте
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
                                <label for="login" class="control-label">Логин</label>
                                <input type="text" class="form-control" placeholder="Логин" name="login" id="login" value="{{ $data->login }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="password" class="control-label">Пароль</label>
                                <input type="password" class="form-control" placeholder="Пароль" name="password" id="password" value="{{ $data->password }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="type_id" class="control-label">Тип Аккаунта</label>
                                <select class="form-control" name="type_id" id="type_id">
                                    <option value=""></option>
                                    <option value="1" {{ $data->type_id == 1 ? "selected" : ""}}>VK</option>
                                    <option value="2" {{ $data->type_id == 2 ? "selected" : ""}}>OK</option>
                                    <option value="3" {{ $data->type_id == 3 ? "selected" : ""}}>SMTP</option>
                                    <option value="4" {{ $data->type_id == 4 ? "selected" : ""}}>Twitter</option>
                                    <option value="5" {{ $data->type_id == 5 ? "selected" : ""}}>Instagram</option>
                                </select>
                            </div>
                            @if($data->type_id == 3)
                            <div class="form-group has-feedback">
                                <label for="smtp_port" class="control-label">SMTP Порт</label>
                                <input type="text" class="form-control" placeholder="SMTP порт" name="smtp_port" id="smtp_port" value="{{ $data->smtp_port }}"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="smtp_address" class="control-label">SMTP Адрес</label>
                                <input type="text" class="form-control" placeholder="SMTP Адрес" name="smtp_address" id="smtp_address" value="{{ $data->smtp_address }}"/>
                            </div>
                            @endif
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection