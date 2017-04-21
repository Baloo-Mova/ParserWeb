@extends('adminlte::layouts.app')

@section('contentheader_title')
    Добавление Тестовой Рассылки Viber и Whatsapp (Android)
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <form method="post">
                {{ csrf_field() }}
                <div class="col-md-8 col-md-offset-2">
                    <div class="box box-primary">
                        <div class="box-body">

                            <div class="form-group has-feedback">
                                <label for="phones_list" class="control-label">Список моб. телефонов (каждый с новой строки без "+" )</label>
                                <textarea class="form-control" rows="6" name="phones_list" id="phones_list"></textarea>
                            </div>

                            <div class="form-group has-feedback">
                                <label for="viber_text" class="control-label">Текст Сообщения Viber</label>
                                <textarea class="form-control" rows="6" name="viber_text" id="viber_text"></textarea>
                            </div>
                             <div class="form-group has-feedback">
                                <label for="whats_text" class="control-label">Текст Сообщения Whatsapp</label>
                                <textarea class="form-control" rows="6" name="whats_text" id="whats_text"></textarea>
                            </div>

                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </div>
                    </div>
                    </div>
                </form>
            </div>
        </div>

@endsection
