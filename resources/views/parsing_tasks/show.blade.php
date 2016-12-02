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
                                <td>{{ $active_type }}</td>
                            </tr>
                            <tr>
                                <td><strong>Поисковый Запрос</strong></td>
                                <td>{{ $data->task_type_id == 1 ? $data->task_query : "-" }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <a href="{{ route('parsing_tasks.start', ['id' => $data->id]) }}" class="btn btn-success" {{ $data->active_type == 1 || $data->google_offset == -1 ? "disabled" : "" }}>Запустить</a>
                        <a href="{{ route('parsing_tasks.stop', ['id' => $data->id]) }}" class="btn btn-danger" {{ $data->active_type == 0 || $data->active_type == 2 ? "disabled" : "" }}>Остановить</a>
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
                                <li class="active">
                                    <a href="#result" data-toggle="tab">Результаты</a>
                                </li>
                                <li>
                                    <a href="#data" data-toggle="tab">Данные для рассылки</a>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content">
                            <div id="result" class="tab-pane well fade in active">
                                <div>
                                    <span>Обработаных: <span class="badge bg-success task_result_span_parsed">-</span></span>&nbsp;
                                    <span>В очереди: <span class="badge bg-info task_result_span_queue">-</span></span>&nbsp;
                                    <span>Не обработанных: <span class="badge bg-danger task_result_span_not_parsed">-</span></span>
                                    <hr>
                                </div>
                                <table class="table table-bordered task_result_table">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Link</th>
                                        <th>Mails</th>
                                        <th>Phones</th>
                                        <th>Skypes</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($search_queries as $key => $value )
                                            <div class="hidden">{{++$key}}</div>
                                            <tr>
                                                <td data-id="{{ $value->id }}" data-text="{{ count($search_queries) - $key }}" data-task-id="{{ $value->task_id }}">{{ count($search_queries) - $key }}</td>
                                                <td>{{ $value->link }}</td>
                                                <td>{{ $value->mails }}</td>
                                                <td>{{ $value->phones }}</td>
                                                <td>{{ $value->skypes }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    Нет результатов!
                                                </td>
                                            </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div id="data" class="tab-pane well fade">
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
                        </div>
                    </div>
                    <div class="box-footer text-center">
                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection

@section('js')
    <script>
        $(document).ready(function(){
            var number = 0;

            function getNewInfo(taskId, lastId, text) {

                $.ajax({
                    method: "get",
                    url: "{{ url('api/actualParsed') }}/"+taskId+"/"+lastId,
                    success: function (data) {
                        console.log(data);
                        if(data.success == true){
                            $(".task_result_span_parsed").text(data.count_parsed);
                            $(".task_result_span_queue").text(data.count_queue);
                            data.result.forEach(function(item, i, arr) {

                                number = ++text;

                                $(".task_result_table").prepend("<tr>" +
                                        "<td data-id='"+data.result[0].id+"' data-task-id='"+data.result[0].task_id+"'>"+number+"</td>" +
                                        "<td>"+item.link+"</td>" +
                                        "<td>"+item.mails+"</td>" +
                                        "<td>"+item.phones+"</td>" +
                                        "<td>"+item.skypes+"</td>" +
                                        "</tr>");
                            });

                        }
                    },
                    dataType: "json"
                });
            }

            var task_table_td = "",
                    taskId = "",
                    lastId = "",
                    text = "";

            setInterval(function() {
                task_table_td = $(".task_result_table").find("tr").find("td");
                taskId = task_table_td.data("taskId");
                lastId = task_table_td.data("id");
                text = task_table_td.data("text");
                getNewInfo(taskId, lastId, text);
            }, 10000);
        });
    </script>
@stop