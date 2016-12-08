@extends('adminlte::layouts.app')

@section('contentheader_title')
    Добавление Тестовой Рассылки Emails
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <form method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="col-md-8 col-md-offset-2">
                    <div class="box box-primary">
                        <div class="box-body">

                            <div class="form-group has-feedback">
                                <label for="mails_list" class="control-label">Список Mails (каждый с новой строки)</label>
                                <textarea class="form-control" rows="6" name="mails_list" id="mails_list"></textarea>
                            </div>

                            <div class="form-group has-feedback">
                                <label for="subject" class="control-label">Тема Сообщения E-mail</label>
                                <input type="text" class="form-control" placeholder="Тема" name="subject" id="subject"/>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="mails_text" class="control-label">Текст Сообщения E-mail</label>
                                <textarea class="form-control" rows="5" name="mails_text" id="mails_text"></textarea>
                            </div>
                            <div class="form-group has-feedback">
                                <label for="mails_file" class="control-label">Вложения Для Сообщения E-mail</label>
                                <input type="file" id="mails_file" name="file[]" multiple>
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
