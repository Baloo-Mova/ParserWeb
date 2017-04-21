@extends('adminlte::layouts.app')

@section('contentheader_title')
 <i class='fa fa-android'></i>   Добавление Новых Android ботов
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
                                <label for="name" class="control-label">Имя устройства (зашифрованное название android устройства)</label>
                                <input type="text" class="form-control" placeholder="" name="name" id="name"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="phone" class="control-label">Моб. номер телефона (который был использован для входа в Viber и Whatsapp на устройстве)</label>
                                <input type="phone" class="form-control" placeholder="+16143625153" name="phone" id="phone"/>
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">


                <div class="box box-primary collapsed-box">
                    <div class="box-header">
                        Массовое Добавление (name:phone)
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form action="{{ url('/android-bots/mass-upload') }}" method="post">
                            {{ csrf_field() }}
                            <div class="form-group has-feedback">
                                <textarea name="text" class="form-control" cols="30" rows="8"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Загрузить</button>
                        </form>
                    </div>
                </div>


            </div>
        </div>
    </div>
@endsection