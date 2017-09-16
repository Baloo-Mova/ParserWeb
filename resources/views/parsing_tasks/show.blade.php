@extends('adminlte::layouts.app')

@section('contentheader_title')
    Просмотр Задачи ID: {{$data->id}}
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
                                <td><strong>Поисковые Запросы (первые 10)</strong></td>
                                <td>{{ $data->name  }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        @if($data->active_type == 2)
                            <a href="{{ route('parsing_tasks.start', ['id' => $data->id]) }}"
                               class="btn btn-success btn-flat">Запустить парсинг</a>
                        @else
                            <a href="{{ route('parsing_tasks.stop', ['id' => $data->id]) }}"
                               class="btn btn-danger btn-flat">Остановить парсинг</a>
                        @endif

                        @if($data->need_send == 0)
                            <a href="{{ route('parsing_tasks.startDelivery', ['id' => $data->id]) }}"
                               class="btn btn-success btn-flat">Запустить рассылку</a>
                        @else
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
                                    <a href="#stat" data-toggle="tab">Статистика</a>
                                </li>
                                <li>
                                    <a href="#data" data-toggle="tab">Данные для рассылки</a>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content">

                            <div id="result" class="tab-pane well fade  in active">
                                <div>
                                    <span>Обработаных:
                                        <span class="badge bg-success task_result_span_parsed">0</span>
                                    </span>&nbsp;
                                    <span>В очереди:
                                        <span class="badge bg-info task_result_span_queue">0</span>
                                    </span>&nbsp;
                                    <span>Доступно к рассылке:
                                        <span class="badge bg-warning task_result_span_all_to_send">0</span>
                                    </span>
                                    <span>Разослано:
                                        <span class="badge bg-warning task_result_span_sended">0</span>
                                    </span>&nbsp;
                                    <hr>
                                    <div style="margin-top: -10px;">
                                        <a href="{{ route('parsing_tasks.getCsv', ['id' => $data->id]) }}"
                                           class="btn btn-primary btn-flat" style="margin-top: -3px;">Экспортировать в
                                            CSV</a>
                                        {{--<form action="{{ route('parsing_tasks.getFromCsv') }}"--}}
                                        {{--enctype="multipart/form-data" method="post" id="targetForm"--}}
                                        {{--style="display: inline-block;">--}}
                                        {{--{{ csrf_field() }}--}}
                                        {{--<input type="hidden" name="task_id" value="{{ $data->id }}">--}}
                                        {{--<label for="file-upload" class="custom-file-upload">--}}
                                        {{--Импортировать из CSV--}}
                                        {{--</label>--}}
                                        {{--<input id="file-upload" type="file" disabled name="myfile"/>--}}
                                        {{--</form>--}}
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
                                            <th>Contact data</th>
                                        </tr>
                                        </thead>
                                        <tbody class="task_result_tbody">
                                        <tr class="no_results_class">
                                            <td colspan="5" class="text-center"> Ожидание результатов ...</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">

                                    </ul>
                                </nav>
                            </div>


                            <div id="stat" class="tab-pane well fade">
                                <h3> Для обновления данных обновите страницу</h3>
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th colspan="3">
                                            Найдено
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>Тип контакта</th>
                                        <th>Ожидает рассылки</th>
                                        <th>Разослано</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($sendInfo as $info)
                                        <tr>
                                            <td>{{\App\Models\Contacts::$types[$info->type]}}</td>
                                            <td>{{$info->count_need_send}}</td>
                                            <td>{{$info->count_sended}}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th colspan="6">Поиск</th>
                                    </tr>
                                    <tr>
                                        <th>Task Query</th>
                                        <th>Google</th>
                                        <th>Google UA</th>
                                        <th>VK Groups</th>
                                        <th>VK News</th>
                                        <th>Ok</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($taskInfo as $info)
                                        <tr>
                                            <td>{{$info->task_query}}</td>
                                            <td>Обработано <b>{{$info->google_ru_offset}}</b> страниц</td>
                                            <td>Обработано <b>{{$info->google_ua_offset}}</b> страниц</td>
                                            <td>
                                                @if($info->vk_reserved == 1)
                                                    Обработано
                                                @else
                                                    Ожидает обработки
                                                @endif
                                            </td>
                                            <td>
                                                @if($info->vk_news_reserved == 1)
                                                    Обработано
                                                @else
                                                    Ожидает обработки
                                                @endif</td>
                                            <td>
                                                @if($info->ok_reserved == 0)
                                                    Ожидает обработки
                                                @else
                                                    Обработано страниц <b>{{ $info->ok_offset - 1 }}</b>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>


                            <div id="data" class="tab-pane well fade">
                                <form action="{{ route('parsing_tasks.changeDeliveryInfo') }}"
                                      enctype="multipart/form-data" method="post">
                                    {{ csrf_field() }}
                                    <input type="hidden" name="task_group_id" value="{{$data->id}}">

                                    <div class="row content-row">
                                        <div class="col-xs-12">
                                            <div class="add_info_card_wrap">
                                                <h4>Mail</h4>
                                                <label>Subject</label>
                                                <input class="form-control" name="data[mail][subject]"
                                                       value="{{$send['mail']['subject']}}">
                                                <label>Text</label>
                                                <textarea name="data[mail][text]" class="form-control" cols="30"
                                                          rows="4">{{$send['mail']['text']}}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row content-row">
                                        <div class="col-xs-12 ">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>VK</h4>
                                                <label>Text</label>
                                                <textarea name="data[vk][text]" class="form-control" cols="30"
                                                          rows="6">{{$send['vk']['text']}}</textarea>
                                                <label>Media</label>
                                                <input name="data[vk][media]" class="form-control"
                                                       value="{{ $send['vk']['media'] }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row content-row">
                                        <div class="col-xs-6">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>OK</h4>
                                                <textarea name="data[ok][text]" class="form-control" cols="30"
                                                          rows="6">{{$send['ok']['text']}}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>Skype</h4>
                                                <textarea name="data[skype][text]" class="form-control" cols="30"
                                                          rows="6">{{$send['skype']['text']}}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row content-row">
                                        <div class="col-xs-6 ">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>Viber</h4>
                                                <textarea name="data[viber][text]" class="form-control" cols="30"
                                                          rows="6">{{$send['viber']['text']}}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="add_info_card_wrap add_info_card_wrap_normal">
                                                <h4>WhatsApp</h4>
                                                <textarea name="data[whatsapp][text]" class="form-control" cols="30"
                                                          rows="6">{{$send['whatsapp']['text']}}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="submit" class="btn btn-primary btn-flat" value="Изменить">
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer text-center">
                        <span class="last_task_id hidden" data-value="0"></span>
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

        .content-row {
            margin-bottom: 15px;
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

        .custom-file-upload:hover {
            background-color: #286090;
        }

        .file_format_info {
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            display: none;
        }

        .file_format_info h5 {
            margin-top: 0px;
        }

        .file_actions_wrap {
            display: inline-block;
        }

        textarea {
            resize: none;
        }

        .mails_file_input {
            margin-top: 5px;
        }

        .mails_file_label {
            margin-top: 10px;
            margin-bottom: 0px;
        }

        .add_info_card_wrap {
            background-color: #fff;
            padding: 10px;
            -webkit-box-shadow: 2px 2px 10px 0px rgba(50, 50, 50, 0.4);
            -moz-box-shadow: 2px 2px 10px 0px rgba(50, 50, 50, 0.4);
            box-shadow: 2px 2px 10px 0px rgba(50, 50, 50, 0.4);
        }

        .add_info_card_wrap h4 {
            margin-top: 0px;
        }

        .add_info_row {
            margin-bottom: 10px;
        }

        .pr {
            padding-right: 5px !important;
        }

        .pl {
            padding-left: 5px !important;
        }

        .cfu2 {
            margin-top: 5px;
        }

        .mails_file_path {
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
                    lastId = $(".last_task_id").data("value"),
                    taskId = $(".reserve_task_id").data("taskId"),
                    page_number = Number(window.location.hash.replace(/\D+/g, "")) == 0 ? 1 : Number(window.location.hash.replace(/\D+/g, ""));
                window.number = $(".task_result_table td:first").data("listNumber") == null ? 0 : $(".task_result_table td:first").data("listNumber");

                window.location.hash = "page=" + page_number;


                $.ajax({
                    method: "get",
                    url: "{{ url('api/actualParsed') }}/" + taskId + "/" + lastId + "/" + page_number,
                    success: function (data) {
                        if (data.success == true) {
                            window.location.hash = "page=" + page_number;
                            var l = 0;
                            if (data.sqCountAll > 10) {
                                if (data.sqCountAll / 10 > 1) {
                                    l = parseInt((data.sqCountAll / 10), 10) + 1;
                                } else {
                                    l = (data.sqCountAll / 10).toFixed();
                                }
                            } else {
                                if (data.sqCountAll / 10 < 1) {
                                    l = 1;
                                } else {
                                    l = (data.sqCountAll / 10).toFixed();
                                }
                            }

                            if (data.sqCountAll > 10) {
                                paginatePrint(l, page_number); // Рисуем пагинацию
                            }

                            $(".task_result_span_parsed").text(data.sqCountAll);
                            $(".task_result_span_all_to_send").text(data.countAll);
                            $(".task_result_span_queue").text(data.sqCountQueue);
                            $(".task_result_span_sended").text(data.countSended);
                            $(".last_task_id").data("value", data.max_id);

                            if (data.result == null) {
                                return;
                            }

                            if (Object.keys(data.result).length > 0) {
                                $('.no_results_class').remove();
                                $(".task_result_tbody").html("");
                            }

                            var i = 1;
                            data.result.forEach(function (item, i, arr) {

                                var
                                    cdata = "",
                                    link = item.link === null ? "" : item.link,
                                    name = item.name === null ? "" : item.name,
                                    city = item.city === null ? "" : item.city,
                                    contacts_data = JSON.parse(item.contact_data);

                                if (contacts_data.phones !== undefined && contacts_data.phones.length > 0) {
                                    cdata += "phones: ";
                                    contacts_data.phones.forEach(function (item, i, arr) {
                                        if (i == (contacts_data.phones.length - 1)) {
                                            cdata += item;
                                        } else {
                                            cdata += item + ", ";
                                        }
                                    });
                                    cdata += "\n";
                                }

                                if (contacts_data.skypes !== undefined && contacts_data.skypes.length > 0) {
                                    cdata += " skypes: ";
                                    contacts_data.skypes.forEach(function (item, i, arr) {
                                        if (i == (contacts_data.skypes.length - 1)) {
                                            cdata += item;
                                        } else {
                                            cdata += item + ", ";
                                        }
                                    });
                                    cdata += "\n";
                                }

                                if (contacts_data.emails !== undefined && contacts_data.emails.length > 0) {
                                    cdata += " emails: ";
                                    contacts_data.emails.forEach(function (item, i, arr) {
                                        if (i == (contacts_data.emails.length - 1)) {
                                            cdata += item;
                                        } else {
                                            cdata += item + ", ";
                                        }
                                    });
                                    cdata += "\n";
                                }

                                cdata += contacts_data.vk_id === undefined ? "" : " VK: " + contacts_data.vk_id + "\n";
                                cdata += contacts_data.ok_id === undefined ? "" : " OK: " + contacts_data.ok_id + "\n";

                                $(".task_result_table").append("<tr>" +
                                    "<td  data-id='" + item.id + "' data-task-id='" + item.task_id + "' data-list-number='" + ((data.sqCountAll - page_number * 10) + 10 - i ) + "'>" + ((data.sqCountAll - page_number * 10) + 10 - i ) + "</td>" +
                                    "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\"  data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"" + link + "\">" + link + "</div></td>" +
                                    "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\">" + name + "</div></td>" +
                                    "<td width='250px'><div style=\"max-width:250px; height: 40px; overflow: hidden;\"  data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"" + city + "\">" + city + "</div></td>" +
                                    "<td width='250px'><div style=\"max-width:500px; height: 40px; overflow: hidden;\"  data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"" + cdata + "\">" + cdata + "</div></td>" +
                                    "</tr>");
                            });

                        }
                    },
                    dataType: "json"
                });

            }

            function newPage() {
                getNewInfo();
            }

            setInterval(function () {
                if (needCheck) {
                    getNewInfo();
                }
            }, 5000);

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

            function paginatePrint(l, page) {
                $(".pagination").html("");
                var paginate = pagination(page, l);
                //Рисуем пагинацию
                paginate.forEach(function (item, i, arr) {

                    if (item == "...") {
                        $(".pagination").append("<li class=\"disabled\"><a href=\"#\">" + item + "</a></li>");
                    } else {
                        if (item == page) {
                            $(".pagination").append("<li class=\"active\"><a href=\"#\">" + item + "</a></li>");
                        } else {
                            $(".pagination").append("<li><a href=\"#\">" + item + "</a></li>");
                        }
                    }


                });
                //Рисуем пагинацию
            }

            $("body").on("click", ".pagination a", function (e) {
                e.preventDefault();
                if ($(this).text() == "...") {
                    return false;
                }
                var page = $(this).text() == 0 ? 1 : $(this).text();
                window.location.hash = "page=" + page;
                if (page > 1) {
                    needCheck = false;
                    newPage();
                } else {
                    needCheck = true;
                    getNewInfo();
                }


            });

            $('#file-upload').change(function () {
                $('#targetForm').submit();
            });

            $(".custom-file-upload").on("mouseenter", function () {
                $(".file_format_info").css("display", "block");
            });

            $(".custom-file-upload").on("mouseleave", function () {
                $(".file_format_info").css("display", "none");
            });

            $(".mails_file_input").on("change", function () {
                var file_name = document.getElementById("mails_file").files[0].name;
                $(".mails_file_path").text(file_name);
            });

        });

    </script>
@stop