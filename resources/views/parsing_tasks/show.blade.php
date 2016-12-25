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
                                <td class="reserve_task_id" data-task-id="{{ $data->id }}">{{ $data->id }}</td>
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
                        <a href="{{ route('parsing_tasks.reserved', ['id' => $data->id]) }}" class="btn btn-danger" {{ $data->reserved == 0 ? "disabled" : "" }}>Вернуть задачу</a>


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
                                    <span>Разослано: <span class="badge bg-warning task_result_span_sended">-</span></span>&nbsp;
                                    <hr>
                                    <a href="{{ route('parsing_tasks.getCsv', ['id' => $data->id]) }}" class="btn btn-primary" >Экспортировать в CSV</a>
                                    <hr>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered task_result_table">
                                        <thead>
                                        <tr>
                                            <th class="small__th">#</th>
                                            <th>Link</th>
                                            <th>Name</th>
                                            <th>City</th>
                                            <th>Mails</th>
                                            <th>Phones</th>
                                            <th>Skypes</th>
                                        </tr>
                                        </thead>
                                        <tbody class="task_result_tbody">
                                        {{--@forelse($search_queries as $key => $value )--}}
                                        {{--<tr>--}}
                                        {{--<td data-id="{{ $value->id }}" data-text="{{ count($search_queries) - $key }}" data-task-id="{{ $value->task_id }}">{{ count($search_queries) - $key }}</td>--}}
                                        {{--<td>{{ $value->link }}</td>--}}
                                        {{--<td>{{ $value->mails }}</td>--}}
                                        {{--<td>{{ $value->phones }}</td>--}}
                                        {{--<td>{{ $value->skypes }}</td>--}}
                                        {{--</tr>--}}
                                        {{--@empty--}}
                                        {{--<tr class="no_results_class">--}}
                                        {{--<td colspan="5" class="text-center">--}}
                                        {{--Нет результатов!--}}
                                        {{--</td>--}}
                                        {{--</tr>--}}
                                        {{--@endforelse--}}
                                        <tr class="no_results_class">
                                            <td colspan="7" class="text-center"> Ожидание результатов ...</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                {{--{{ $search_queries->links() }}--}}
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">

                                    </ul>
                                </nav>
                            </div>

                            <div id="data" class="tab-pane well fade">
                                <a href="{{ route('parsing_tasks.startDelivery', ['id' => $data->id]) }}" class="btn btn-success" {{empty($mails->subject) || empty($skype->text) || $data->need_send == 1 ? "disabled" : "" }}>Запустить рассылку</a>
                                <a href="{{ route('parsing_tasks.stopDelivery', ['id' => $data->id]) }}" class="btn btn-danger" {{$data->need_send == 0 ? "disabled" : "" }}>Остановить рассылку</a>
                                <hr>
                                <form action="{{ route('parsing_tasks.changeDeliveryInfo') }}" method="post">
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Mail subject</th>
                                            <th>Mail text</th>
                                           
                                        </tr>
                                        </thead>
                                        <tbody>
                                            {{ csrf_field() }}
                                        <td><textarea type="text" class="form-control" name="mail_subject" cols="30" rows="3" >{{ empty($mails) ? "-" : $mails->subject }}</textarea></td>
                                            <td><textarea name="mail_text" class="form-control" cols="30" rows="3">{{ empty($mails) ? "-" : $mails->text }}</textarea></td>
                                            
                                            <input type="hidden" name="delivery_id" value="{{ $data->id }}">
                                        </tbody>
                                    </table>
                                     <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Skype text</th>
                                            <th>VK text</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            {{ csrf_field() }}
                                           
                                            <td><textarea name="skype_text" class="form-control" cols="30" rows="3">{{ empty($skype) ? "-" : $skype->text }}</textarea></td>
                                            <td><textarea name="vk_text" class="form-control" cols="30" rows="3">{{ empty($vk) ? "-" : $vk->text }}</textarea></td>
                                            <input type="hidden" name="delivery_id" value="{{ $data->id }}">
                                        </tbody>
                                    </table>
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>OK text</th>
                                            <th>Twitter text</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        {{ csrf_field() }}

                                        <td><textarea name="ok_text" class="form-control" cols="30" rows="3">{{ empty($ok) ? "-" : $ok->text }}</textarea></td>
                                        <td><textarea name="tw_text" class="form-control" cols="30" rows="3" maxlength="100">{{ empty($tw) ? "-" : $tw->text }}</textarea></td>
                                        <input type="hidden" name="delivery_id" value="{{ $data->id }}">
                                        </tbody>
                                    </table>
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>FB text</th>
                                            
                                        </tr>
                                        </thead>
                                        <tbody>
                                        {{ csrf_field() }}

                                        <td><textarea name="fb_text" class="form-control" cols="30" rows="3">{{ empty($fb) ? "-" : $fb->text }}</textarea></td>
                                        
                                        <input type="hidden" name="delivery_id" value="{{ $data->id }}">
                                        </tbody>
                                    </table>
                                    <input type="submit" class="btn btn-primary" value="Изменить">
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer text-center">
                        <input type="hidden" class="last_task_id" value="0">
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script>
        $(document).ready(function () {
            window.number = 1;
            //Number(window.location.hash.replace(/\D+/g,"")) == 0 ? paginatePrint(1) : paginatePrint(Number(window.location.hash.replace(/\D+/g,"")));
            paginateConstruct(1);

            function getNewInfo() {

                var
                        lastId      = $(".last_task_id").val(),
                        taskId      = $(".reserve_task_id").data("taskId"),
                        page_number = Number(window.location.hash.replace(/\D+/g,"")) == 0 ? 1 : Number(window.location.hash.replace(/\D+/g,""));
                        window.number = $(".task_result_table td:first").data("listNumber") == null ? 0 : $(".task_result_table td:first").data("listNumber");

                window.location.hash = "page="+page_number;

                if (page_number == 0 || page_number == 1) {
                    $.ajax({
                        method  : "get",
                        url     : "{{ url('api/actualParsed') }}/" + taskId + "/" + lastId,
                        success : function (data) {
                            console.log(data);
                            if (data.success == true) {
                                $(".task_result_span_parsed").text(data.count_parsed);
                                $(".task_result_span_queue").text(data.count_queue);
                                $(".task_result_span_sended").text(data.count_sended);
                                $(".last_task_id").val(data.max_id);

                                if (Object.keys(data.result).length > 0) {
                                    $('.no_results_class').remove();
                                }

                                    paginateConstruct(1);

                            }
                        },
                        dataType: "json"
                    });

                }
            }

            setInterval(
                    getNewInfo, 3000);

            function pagination(c, m) {
                var current = c,
                        last = m,
                        delta = {{ config('config.task_result_paginate_delta') }},
                        left = current - delta,
                        right = current + delta + 1,
                        range = [],
                        rangeWithDots = [],
                        l;

                for (var i = 1; i <= last; i++) {
                    if (i == 1 || i == last || i >= left && i < right) {
                        range.push(i);
                    }
                }

                for (var j of range) {
                    if (l) {
                        if (j - l === 2) {
                            rangeWithDots.push(l + 1);
                        } else if (j - l !== 1) {
                            rangeWithDots.push('...');
                        }
                    }
                    rangeWithDots.push(j);
                    l = j;
                }

                return rangeWithDots;
            }

            function paginatePrint(l, page)
            {
                $(".pagination").html("");
                var paginate = pagination(page, l);
                //Рисуем пагинацию
                paginate.forEach(function (item, i, arr) {

                    if(item == "..."){
                        $(".pagination").append("<li class=\"disabled\"><a href=\"#\">" + item + "</a></li>");
                    }else{
                        if(item == page){
                            $(".pagination").append("<li class=\"active\"><a href=\"#\">" + item + "</a></li>");
                        }else{
                            $(".pagination").append("<li><a href=\"#\">" + item + "</a></li>");
                        }
                    }


                });
                //Рисуем пагинацию
            }

            function paginateConstruct(page)
            {
                window.location.hash = "page="+page;
                $.ajax({
                    method  : "get",
                    url     : "{{ url('api/paginateParsed') }}/" + page + "/" + $(".reserve_task_id").data("taskId"),
                    success : function (data) {

                        console.log(data);

                        var l = 0;

                        if(data.number > 10){
                            if(data.number / 10 > 1){
                                l = parseInt((data.number / 10), 10) + 1;
                            }else{
                                l = (data.number / 10).toFixed();
                            }
                        }else{
                           if(data.number / 10 < 1){
                               l = 1;
                           }else{
                               l = (data.number / 10).toFixed();
                           }
                        }

                        if(data.number > 10){
                            paginatePrint(l, page); // Рисуем пагинацию
                        }



                        if (data.success == true) {

                            $(".task_result_span_parsed").text(data.count_parsed);
                            $(".task_result_span_queue").text(data.count_queue);
                            $(".last_task_id").val(data.max_id);

                            if (Object.keys(data.result).length > 0) {
                                $('.no_results_class').remove();
                                $(".task_result_tbody").html("");
                            }

                            //if(page != 0 || page != 1){
                                var i = 1;
                                data.result.forEach(function (item, i, arr) {
                                    $(".task_result_table").append("<tr>" +
                                            "<td  data-id='" + item.id + "' data-task-id='" + item.task_id + "' data-list-number='"+ ((data.number - page * 10) + 10 - i ) +"'>" + ((data.number - page * 10) + 10 - i ) + "</td>" +
                                            "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\"  data-toggle=\"tooltip\" data-placement=\"bottom\" title=\""+ item.link+"\">" + item.link + "</div></td>" +
                                            "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\">" + item.vk_name + "</div></td>" +
                                            "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\"  data-toggle=\"tooltip\" data-placement=\"bottom\" title=\""+ item.vk_city+"\">" + item.vk_city + "</div></td>" +
                                            "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\">" + item.mails + "</div></td>" +
                                            "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\">" + item.phones + "</div></td>" +
                                            "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\">" + item.skypes + "</div></td>" +
                                            "</tr>");

                                });

                            //}
                        }


                        console.log(data);

                    },
                    dataType: "json"
                });
            }

            $("body").on("click", ".pagination a", function (e) {
                e.preventDefault();
                if($(this).text() == "..."){
                    return false;
                }
                var page = $(this).text() == 0 ? 1 : $(this).text();
                window.location.hash = "page="+page;
                paginateConstruct(parseInt(page, 10));
            });


        });
    </script>
@stop