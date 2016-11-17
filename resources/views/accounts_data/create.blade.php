@extends('adminlte::layouts.app')

@section('contentheader_title')
    Добавление новых данных об аккаунте
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-body">
                        <form action="" method="post">
                            {{csrf_field()}}
                            <div class="form-group has-feedback">
                                <input type="text" class="form-control" placeholder="Логин" name="login"/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="password" class="form-control" placeholder="Пароль" name="password"/>
                            </div>
                            <div class="form-group has-feedback">
                                <select class="form-control" name="type_id" id="">
                                    <option value=""></option>
                                    <option value="1">VK</option>
                                    <option value="2">OK</option>
                                    <option value="3">SMTP</option>
                                </select>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" class="form-control" placeholder="SMTP порт" name="smtp_port"/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" class="form-control" placeholder="SMTP Адрес" name="smtp_address"/>
                            </div>
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое добавление (VK)
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form action="{{ url('/accounts-data/vk-upload') }}" method="post">
                            {{ csrf_field() }}
                            <div class="form-group has-feedback">
                                <textarea name="text" class="form-control" cols="30" rows="8"></textarea>
                            </div>
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-primary btn-flat">Загрузить</button>
                        </form>
                        
                    </div>
                </div>

                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое добавление (OK)
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form action="{{ url('/accounts-data/ok-upload') }}" method="post">
                            {{ csrf_field() }}
                            <div class="form-group has-feedback">
                                <textarea name="text" class="form-control" cols="30" rows="8"></textarea>
                            </div>
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-primary btn-flat">Загрузить</button>
                        </form>

                    </div>
                </div>

                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое добавление (Mails)
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form action="{{ url('/accounts-data/mails-upload') }}" method="post">
                            {{ csrf_field() }}
                            <div class="form-group has-feedback">
                                <textarea name="text" class="form-control" cols="30" rows="8"></textarea>
                            </div>
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-primary btn-flat">Загрузить</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection