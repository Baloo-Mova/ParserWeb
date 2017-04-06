@extends('adminlte::layouts.app')

@section('contentheader_title')
    Получить Proxy по заданым параметрам
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="box-body">
                            <div class="form-group has-feedback">
                                <label for="limit" class="control-label">Количество</label>
                                <input type="text" class="form-control" placeholder="50" value="50" name="limit" id="limit"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="type" class="control-label">Тип</label>
                                <input type="text" class="form-control" placeholder="http,https" value="http,https" name="type" id="type"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="port" class="control-label">Порты</label>
                                <input type="text" class="form-control" placeholder="80,443,1080,3128,8080" value="80,443,1080,3128,8080" name="port" id="port"/>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="radio" name="mode" value="none" checked> Без дополнительных условий
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="radio" name="mode" value="mail"> Прокси с открытым 25 SMTP портом
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="radio" name="mode" value="yandex"> Прокси для Yandex
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="radio" name="mode" value="google"> Прокси для Google
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="radio" name="mode" value="mailru"> Прокси для MailRu
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="radio" name="mode" value="twitter"> Прокси для Twitter
                                </label>
                            </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary btn-flat">Выбрать</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


@endsection