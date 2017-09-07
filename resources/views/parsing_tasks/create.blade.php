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
                                    <label for="task_type" class="control-label">Тип Задачи</label>
                                    <select class="form-control task_type_select" name="task_type_id" id="task_type" >
                                        @foreach($types as $item)
                                            @if($item->id != 3)
                                                <option value="{{ $item->id }}" {{ $item->id == 1 ? "selected" : "" }}>{{ $item->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group has-feedback task_query_div">
                                    <label for="task_query" class="control-label">Поисковые Запросы</label>
                                    <textarea name="task_query" class="form-control" id="task_query" cols="30" rows="10"></textarea>
                                    <span class="add-info-text">Каждый с новой строки</span>
                                </div>
                                <div class="form-group has-feedback site_list_div">
                                    <label for="site_list" class="control-label">Список Сайтов</label>
                                    <textarea class="form-control" rows="5" name="site_list" id="site_list"></textarea>
                                </div>

                                <div class="form-group has-feedback">
                                    <label class="form-check-label">
                                        <input class="form-check-input" type="checkbox" name="send_directly">
                                        Рассылать Сразу
                                    </label>
                                </div>

                                <div class="send_directly_div">
                                    <hr>
                                    <div class="form-group has-feedback">
                                        <label for="subject" class="control-label">Тема Сообщения E-mail</label>
                                        <input type="text" class="form-control" placeholder="Тема" name="subject" id="subject"/>
                                    </div>
                                    <div class="form-group has-feedback">
                                        <label for="mails_text" class="control-label">Текст Сообщения E-mail</label>
                                        <textarea class="form-control" rows="5" name="mails_text" id="mails_text"></textarea>
                                    </div>
                                    {{--<div class="form-group has-feedback">
                                        <label for="mails_file" class="control-label">Вложения Для Сообщения E-mail</label>
                                        <input type="file" id="mails_file" name="file[]" multiple>
                                    </div>--}}

                                    <hr>
                                    <div class="form-group has-feedback">
                                        <label for="skype_text" class="control-label">Текст Сообщения Skype</label>
                                        <textarea class="form-control" rows="5" name="skype_text" id="skype_text"></textarea>
                                    </div>
                                    <hr>
                                    <div class="form-group has-feedback">
                                        <label for="vk_text" class="control-label">Текст Сообщения VK</label>
                                        <textarea class="form-control" rows="5" name="vk_text" id="vk_text"></textarea>
                                    </div>
                                    <hr>
                                    <div class="form-group has-feedback">
                                        <label for="ok_text" class="control-label">Текст Сообщения OK</label>
                                        <textarea class="form-control" rows="5" name="ok_text" id="ok_text"></textarea>
                                    </div>
                                    <hr>
                                    <div class="form-group has-feedback">
                                        <label for="fb_text" class="control-label">Текст Сообщения FB</label>
                                        <textarea class="form-control" rows="5" name="fb_text" id="fb_text"></textarea>
                                    </div>
                                    <hr>
                                    <div class="form-group has-feedback">
                                        <label for="viber_text" class="control-label">Текст Сообщения Viber</label>
                                        <textarea class="form-control" rows="5" name="viber_text" id="viber_text" maxlength="100"></textarea>
                                    </div>
                                    <div class="form-group has-feedback">
                                        <label for="whats_text" class="control-label">Текст Сообщения WhatsApp</label>
                                        <textarea class="form-control" rows="5" name="whats_text" id="whats_text" maxlength="100"></textarea>
                                    </div>
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
