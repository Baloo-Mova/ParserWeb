@extends('adminlte::layouts.app')

@section('contentheader_title')
    Просмотр Задачи К Парсингу
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title">Подробности</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered">
                            <tbody>
                            <tr>
                                <td><strong>ID</strong></td>
                                <td>{{ $data->id }}</td>
                            </tr>
                            <tr>
                                <td><strong>Тип Поиска</strong></td>
                                <td>{{ $data->tasksType->name }}</td>
                            <tr>
                                <td><strong>Статус</strong></td>
                                <td>{{ $task_info }}</td>
                            </tr>
                            <tr>
                                <td><strong>Поисковый Запрос</strong></td>
                                <td>{{ $data->task_query }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <a href="{{ route('parsing_tasks.start') }}" class="btn btn-success" {{ $task_info == "RUNNING" ? "disabled" : "" }}>Запустить</a>
                        <a href="{{ route('parsing_tasks.stop') }}" class="btn btn-danger" {{ $task_info == "STOPPED" ? "disabled" : "" }}>Остановить</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title">Дополнительная Информация</h3>
                    </div>
                    <div class="box-body">
                        <div class="select-tabs">
                            <ul class="nav nav-tabs text-center" id="myTab">
                                <li class="active"><a href="#data" data-toggle="tab">Данные для рассылки</a></li>
                                <li><a href="#result" data-toggle="tab">Результаты</a></li>
                            </ul>
                        </div>
                        <div class="tab-content">
                            <div id="data" class="tab-pane well fade in active">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Mail subject</th>
                                            <th>Mail text</th>
                                            <th>Skype text</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <td> {{ empty($mails) ? "-" : $mails->subject }}</td>
                                        <td> {{ empty($mails) ? "-" : $mails->text }}</td>
                                        <td> {{ empty($skype) ? "-" : $skype->text }}</td>
                                    </tbody>
                                </table>
                            </div>

                            <div id="result" class="tab-pane well fade">
                                2
                            </div>
                        </div>
                    </div>
                    <div class="box-footer text-center">
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
