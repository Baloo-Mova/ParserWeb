@extends('adminlte::layouts.app')

@section('contentheader_title')
    Добавление Новых Данных Об Аккаунте
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
                            <div class="form-group has-feedback">
                                <input type="hidden" name="type_id" id="type_id" value="{{ $type }}">
                            </div>
                            @if($type == 3)
                            <div class="form-group has-feedback">
                                <label for="smtp_port" class="control-label">SMTP Порт</label>
                                <input type="text" class="form-control" placeholder="SMTP порт" name="smtp_port" id="smtp_port"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="smtp_address" class="control-label">SMTP Адрес</label>
                                <input type="text" class="form-control" placeholder="SMTP Адрес" name="smtp_address" id="smtp_address"/>
                            </div>
                            @endif
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                @if($type == 1)
                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое Добавление (VK)
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
                        Массовое Добавление Из Файла(VK)
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form action="{{ url('/accounts-data/vk-upload') }}" enctype="multipart/form-data"  method="post">
                            {{ csrf_field() }}
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <div class="form-group">
                                <label for="text_file">Загрузить Файл</label>
                                <input type="file" class="form-controll" id="text_file" name="text_file">
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Загрузить</button>
                        </form>
                    </div>
                </div>
                @endif

                @if($type == 2)
                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое Добавление (OK)
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
                        Массовое Добавление Из Файла(OK)
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form action="{{ url('/accounts-data/ok-upload') }}" enctype="multipart/form-data"  method="post">
                            {{ csrf_field() }}
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <div class="form-group">
                                <label for="text_file">Загрузить Файл</label>
                                <input type="file" class="form-controll" id="text_file" name="text_file">
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Загрузить</button>
                        </form>
                    </div>
                </div>
                @endif

                @if($type == 3)
                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое Добавление (Mails)
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


                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое Добавление Из Файла(Mails)
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form action="{{ url('/accounts-data/mails-upload') }}" enctype="multipart/form-data"  method="post">
                            {{ csrf_field() }}
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <div class="form-group">
                                <label for="text_file">Загрузить Файл</label>
                                <input type="file" class="form-controll" id="text_file" name="text_file">
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Загрузить</button>
                        </form>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
@endsection