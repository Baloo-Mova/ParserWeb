@extends('adminlte::layouts.app')

@section('contentheader_title')
    Добавление Задачи К Парсингу
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
                                    <label for="task_query" class="control-label">Поисковый Запрос</label>
                                    <input type="text" class="form-control" placeholder="Запрос" name="task_query" id="task_query"/>
                                </div>
                                <div class="form-group has-feedback">
                                    <label for="task_type" class="control-label">Тип Задачи</label>
                                    <select class="form-control" name="task_type_id" id="task_type" >
                                        <option value="" ></option>
                                        @foreach($types as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <hr>
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

                                <hr>
                                <div class="form-group has-feedback">
                                    <label for="skype_text" class="control-label">Текст Сообщения Skype</label>
                                    <textarea class="form-control" rows="5" name="skype_text" id="skype_text"></textarea>
                                </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection