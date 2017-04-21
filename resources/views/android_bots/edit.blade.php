@extends('adminlte::layouts.app')

@section('contentheader_title')
 <i class='fa fa-android'></i>   Редактирование Android Бота
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
                            <div class="form-group has-feedback">
                                <label for="phone" class="control-label">Моб. номер телефона (который был использован для входа в Viber и Whatsapp на устройстве)</label>
                                <input type="phone" class="form-control" placeholder="Телефон" name="phone" value="{{ $data->phone }}" id="phone"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="status" class="control-label">Статус(0-1 = Не работает, 2 - Работает)</label>
                                <input type="status" class="form-control" placeholder="" name="status" value="{{ $data->status }}" id="status"/>
                            </div>
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection