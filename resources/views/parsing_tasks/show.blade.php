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
                        @if($data->active_type == 0 || $data->active_type == 2)
                            <a href="{{ route('parsing_tasks.start', ['id' => $data->id]) }}"
                               class="btn btn-success btn-flat">Запустить парсинг</a>
                        @elseif($data->active_type == 1 || $data->google_offset == -1)
                            <a href="{{ route('parsing_tasks.stop', ['id' => $data->id]) }}"
                               class="btn btn-danger btn-flat">Остановить парсинг</a>
                        @endif

                        @if($data->need_send == 0)
                            <a href="{{ route('parsing_tasks.startDelivery', ['id' => $data->id]) }}"
                               class="btn btn-success btn-flat">Запустить рассылку</a>
                        @elseif(empty($mails->subject) || empty($skype->text) || $data->need_send == 1)
                            <a href="{{ route('parsing_tasks.stopDelivery', ['id' => $data->id]) }}"
                               class="btn btn-danger btn-flat">Остановить рассылку</a>
                        @endif
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
                                    <div style="margin-top: -10px;">
                                        <a href="{{ route('parsing_tasks.getCsv', ['id' => $data->id]) }}"
                                           class="btn btn-primary btn-flat" style="margin-top: -3px;">Экспортировать в CSV</a>


                                        <form action="{{ route('parsing_tasks.getFromCsv') }}" enctype="multipart/form-data" method="post" id="targetForm" style="display: inline-block;">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="task_id" value="{{ $data->id }}">
                                            <label for="file-upload" class="custom-file-upload">
                                                Импортировать из CSV
                                            </label>
                                            <input id="file-upload" type="file" name="myfile"/>
                                        </form>
                                    </div>
                                    <div class="file_format_info">
                                        <h5><strong>Формат файла:</strong></h5>
                                        link;mails;phones;skypes;city;name <br>
                                        "=""link_val""";"=""mails_val""";"=""phones_val""";"=""skypes_val""";"=""city_val""";"=""name_val"""
                                    </div>

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
                                            <th>Soc.Network</th>
                                        </tr>
                                        </thead>
                                        <tbody class="task_result_tbody">
                                        <tr class="no_results_class">
                                            <td colspan="8" class="text-center"> Ожидание результатов ...</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">

                                    </ul>
                                </nav>
                            </div>

                            <div id="data" class="tab-pane well fade">
                                <form action="{{ route('parsing_tasks.changeDeliveryInfo') }}" enctype="multipart/form-data" method="post">
                                    {{ csrf_field() }}
                                    <input type="hidden" name="delivery_id" value="{!! $data->id !!}">
                                    <div class="row add_info_row">
                                        <div class="col-xs-12">
                                            <div class="add_info_card_wrap {!! $mails_file == "" ? "add_info_card_wrap_medium" : "add_info_card_wrap_big" !!}">
                                                <h4>Mail subject</h4>
                                                <input type="text" class="form-control" name="mail_subject" value="{!! empty($mails) ? "-" : $mails->subject !!}">
                                                <input type="hidden" name="mail_id" value="{!! empty($mails) ? "" : $mails->id !!}">

                                                <h4 style="margin-top: 10px;">Mail text</h4>
                                                <textarea name="mail_text" class="form-control" cols="30" rows="4">{!! empty($mails) ? "-" : $mails->text !!}</textarea>
                                                <input type="hidden" name="mail_id" value="{!! empty($mails) ? "" : $mails->id !!}">
                                                @if($mails_file)
                                                    <label for="mails_file" class="mails_file_label">Mail file</label>
                                                    <br>
                                                    <strong class="small">
                                                        {{ storage_path('app/').$mails_file }}
                                                    </strong>
                                                    <br>
                                                    <label for="mails_file" class="custom-file-upload" style="margin-top: 10px;">
                                                        Изменить файл
                                                    </label>
                                                    <input id="mails_file" type="file" name="mails_file"/>
                                                @else
                                                    <div class="small mails_file_path">
                                                    </div>
                                                    <label for="mails_file" class="custom-file-upload cfu2" style="margin-top: 10px;">
                                                        Добавить файл
                                                    </label>
                                                    <input id="mails_file" type="file" class="mails_file_input" name="mails_file"/>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row add_info_row">
                                        <div class="col-xs-6 pr">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>Skype text</h4>
                                                <input type="hidden" name="skype_id" value="{!! empty($skype) ? "" : $skype->id !!}">
                                                <textarea name="skype_text" class="form-control" cols="30" rows="6">{!! empty($skype) ? "-" : $skype->text !!}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-xs-6 pl">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>VK text</h4>
                                                <input type="hidden" name="vk_id" value="{!! empty($vk) ? "" : $vk->id !!}">
                                                <textarea name="vk_text" class="form-control" cols="30" rows="6">{!! empty($vk) ? "-" : $vk->text !!}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row add_info_row">
                                        <div class="col-xs-6 pr">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>OK text</h4>
                                                <input type="hidden" name="ok_id" value="{!! empty($ok) ? "" : $ok->id !!}">
                                                <textarea name="ok_text" class="form-control" cols="30" rows="6">{!! empty($ok) ? "-" : $ok->text !!}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-xs-6 pl">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>FB text</h4>
                                                <input type="hidden" name="fb_id" value="{!! empty($fb) ? "" : $fb->id !!}">
                                                <textarea name="fb_text" class="form-control" cols="30" rows="6">{!! empty($fb) ? "-" : $fb->text !!}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                        <!--<th>Twitter text</th>-->
                                        <!--<td><textarea name="tw_text" class="form-control" cols="30" rows="3" maxlength="100">{!! empty($tw) ? "-" : $tw->text !!}</textarea></td>-->

                                    <div class="row add_info_row">
                                        <div class="col-xs-6 pr">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>Viber text</h4>
                                                <input type="hidden" name="viber_id" value="{!! empty($viber) ? "" : $viber->id !!}">
                                                <textarea name="viber_text" class="form-control" cols="30" rows="6">{!! empty($viber) ? "-" : $viber->text !!}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-xs-6 pl">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>WhatsApp text</h4>
                                                <input type="hidden" name="whats_id" value="{!! empty($whats) ? "" : $whats->id !!}">
                                                <textarea name="whats_text" class="form-control" cols="30" rows="6">{!! empty($whats) ? "-" : $whats->text !!}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="submit" class="btn btn-primary btn-flat" value="Изменить">
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

@section('css')
    <style>

        input[type="file"] {
            display: none;
        }
        .custom-file-upload {
            border: 1px solid #ccc;
            display: inline-block;
            padding: 6px 12px;
            cursor: pointer;
            color: #fff;
            background-color: #337ab7;
            border-color: #2e6da4;
            white-space: nowrap;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.42857143;
            font-weight: 300;
            text-align: center;
            height: 34px;
        }

        .custom-file-upload:hover{
            background-color: #286090;
        }

        .file_format_info{
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            display: none;
        }
        .file_format_info h5{
            margin-top: 0px;
        }

        .file_actions_wrap{
            display: inline-block;
        }
        textarea {
            resize: none;
        }
        .mails_file_input{
            margin-top: 5px;
        }
        .mails_file_label{
            margin-top: 10px;
            margin-bottom: 0px;
        }
        .add_info_card_wrap_big{
            height: 340px;
        }
        .add_info_card_wrap_medium{
            height: 300px;
        }
        .add_info_card_wrap_normal{
            height: 220px;
        }
        .add_info_card_wrap{
            background-color: #fff;
            padding: 10px;
            -webkit-box-shadow: 2px 2px 10px 0px rgba(50, 50, 50, 0.4);
            -moz-box-shadow:    2px 2px 10px 0px rgba(50, 50, 50, 0.4);
            box-shadow:         2px 2px 10px 0px rgba(50, 50, 50, 0.4);
        }
        .add_info_card_wrap h4{
            margin-top: 0px;
        }
        .add_info_row{
            margin-bottom: 10px;
        }
        .pr{
            padding-right: 5px !important;
        }
        .pl{
            padding-left: 5px !important;
        }
        .cfu2{
            margin-top: 5px;
        }
        .mails_file_path{
            height: 17px;
            margin-top: 5px;
            font-weight: 700;
        }
    </style>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            window.number = 1;
            needCheck = true;

            getNewInfo();

            function getNewInfo() {
                var
                    lastId      = $(".last_task_id").val(),
                    taskId      = $(".reserve_task_id").data("taskId"),
                    page_number = Number(window.location.hash.replace(/\D+/g,"")) == 0 ? 1 : Number(window.location.hash.replace(/\D+/g,""));
                window.number = $(".task_result_table td:first").data("listNumber") == null ? 0 : $(".task_result_table td:first").data("listNumber");

                window.location.hash = "page="+page_number;


                    $.ajax({
                        method  : "get",
                        url     : "{{ url('api/actualParsed') }}/" + taskId + "/" + lastId + "/" + page_number,
                        success : function (data) {
                            if (data.success == true) {

                                window.location.hash = "page="+page_number;

                                var l = 0;

                                if(data.count_parsed > 10){
                                    if(data.count_parsed / 10 > 1){
                                        l = parseInt((data.count_parsed / 10), 10) + 1;
                                    }else{
                                        l = (data.count_parsed / 10).toFixed();
                                    }
                                }else{
                                    if(data.count_parsed / 10 < 1){
                                        l = 1;
                                    }else{
                                        l = (data.count_parsed / 10).toFixed();
                                    }
                                }

                                if(data.count_parsed > 10){
                                    paginatePrint(l, page_number); // Рисуем пагинацию
                                }

                                $(".task_result_span_parsed").text(data.count_parsed);
                                $(".task_result_span_queue").text(data.count_queue);
                                $(".task_result_span_sended").text(data.count_sended);
                                $(".last_task_id").val(data.max_id);

                                if (Object.keys(data.result).length > 0) {
                                    $('.no_results_class').remove();
                                    $(".task_result_tbody").html("");
                                }

                                var i = 1;

                                data.result.forEach(function (item, i, arr) {
                                    var socn = "";

                                    if(item.vk_id != null){
                                        socn = "ВК "+item.vk_id;
                                    }
                                    if(item.ok_user_id != null){
                                        socn = "ОК "+item.ok_user_id;
                                    }
                                    if(item.fb_id != null){
                                        socn = "ФБ "+item.fb_id;
                                    }
                                    if(item.tw_user_id != null){
                                        socn = "Твиттер "+item.tw_user_id;
                                    }
                                    if(item.ins_user_id != null){
                                        socn = "Инстаграм "+item.ins_user_id;
                                    }

                                    $(".task_result_table").append("<tr>" +
                                        "<td  data-id='" + item.id + "' data-task-id='" + item.task_id + "' data-list-number='"+ ((data.count_parsed - page_number * 10) + 10 - i ) +"'>" + ((data.count_parsed - page_number * 10) + 10 - i ) + "</td>" +
                                        "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\"  data-toggle=\"tooltip\" data-placement=\"bottom\" title=\""+ item.link+"\">" + item.link + "</div></td>" +
                                        "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\">" + item.name + "</div></td>" +
                                        "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\"  data-toggle=\"tooltip\" data-placement=\"bottom\" title=\""+ item.city+"\">" + item.city + "</div></td>" +
                                        "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\""+ item.mails+"\">" + item.mails + "</div></td>" +
                                        "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\""+ item.phones+"\">" + item.phones + "</div></td>" +
                                        "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\""+ item.skypes+"\">" + item.skypes + "</div></td>" +
                                        "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\">" + socn + "</div></td>" +
                                        "</tr>");
                                });

                            }
                        },
                        dataType: "json"
                    });

            }

            function newPage(){
                getNewInfo();
            }

            setInterval(function() {
                if(needCheck) {
                    getNewInfo();
                }
            },5000);

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

            $("body").on("click", ".pagination a", function (e) {
                e.preventDefault();
                if($(this).text() == "..."){
                    return false;
                }
                var page = $(this).text() == 0 ? 1 : $(this).text();
                window.location.hash = "page="+page;
                if(page > 1){
                    needCheck = false;
                    newPage();
                }else{
                    needCheck = true;
                    getNewInfo();
                }


            });

            $('#file-upload').change(function() {
                $('#targetForm').submit();
            });

            $(".custom-file-upload").on("mouseenter", function(){
               $(".file_format_info").css("display", "block");
            });

            $(".custom-file-upload").on("mouseleave", function(){
                $(".file_format_info").css("display", "none");
            });

            $(".mails_file_input").on("change", function(){
                var file_name = document.getElementById("mails_file").files[0].name;
                $(".mails_file_path").text(file_name);
            });

        });

    </script>
@stop