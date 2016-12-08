@extends('adminlte::layouts.app')

@section('contentheader_title')
    Добавление Новых Аккаунтов Skype
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
                                <label for="login" class="control-label">Логин</label>
                                <input type="text" class="form-control" placeholder="Логин" name="login" id="login"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="password" class="control-label">Пароль</label>
                                <input type="password" class="form-control" placeholder="Пароль" name="password" id="password"/>
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">


                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое Добавление (login:password)
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form action="{{ url('/skypes-accounts/mass-upload') }}" method="post">
                            {{ csrf_field() }}
                            <div class="form-group has-feedback">
                                <textarea name="text" class="form-control" cols="30" rows="8"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Загрузить</button>
                        </form>
                    </div>
                </div>

                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое Добавление Из Файла
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form action="{{ url('/skypes-accounts/mass-upload') }}" enctype="multipart/form-data"  method="post">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <label for="text_file">Загрузить Файл</label>
                                <input type="file" class="form-controll" id="text_file" name="text_file">
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Загрузить</button>
                        </form>
                    </div>
                </div>


            </div>
        </div>
    </div>
@endsection