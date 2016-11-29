@extends('adminlte::layouts.app')

@section('contentheader_title')
    Просмотр Задачи К Парсингу
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-body">

                        <a href="{{ route('parsing_tasks.start') }}" class="btn btn-success">Запустить</a>
                        <a href="{{ route('parsing_tasks.stop') }}" class="btn btn-danger">Остановить</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Тип Поиска</th>
                                <th>Статус</th>
                                <th>Поисковый Запрос</th>
                            </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $data->id }}</td>
                                    <td>{{ $data->tasksType->name }}</td>
                                    <td>{{ $task_info }}</td>
                                    <td>{{ $data->task_query }}</td>

                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer text-center">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
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
