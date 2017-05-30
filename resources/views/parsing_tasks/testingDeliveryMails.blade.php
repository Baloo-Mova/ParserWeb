@extends('adminlte::layouts.app')

@section('contentheader_title')
    Добавление Тестовой Рассылки Emails
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">

        <form method="post" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="col-md-12">
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


                            <label for="task_type" class="control-label">Шаблоны</label>

                            <select class="form-control task_type_select" name="email_templates" id="email_templates" >
                                <option value="0" selected>Без шаблона</option>
                                @foreach($data as $item)

                                    <option value="{{ $item->id }}" >{{$item->name }}</option>

                                @endforeach
                            </select>

                        </div>
                        <div class="form-group has-feedback">
                            <label for="mails_text" class="control-label">Текст Сообщения E-mail</label>
                            <div class="tab-content">
                                <div class="tab-pane active" id="withouttemplate">
                                    <textarea class="form-control" rows="5" name="mails_text" id="mails_text"></textarea>
                                </div>
                                <div class="tab-pane" id="withtemplate">
                                    <div id="email_template">

                                    </div>
                                    </div>

                            </div>


                        </div>
                        <div class="form-group has-feedback">
                            <label for="mails_file" class="control-label">Вложения Для Сообщения E-mail</label>
                            <input type="file" id="mails_file" name="file[]" multiple>
                        </div>

                    </div>
                    <div class="box-footer">

                        <button type="submit" onClick="delDropped()" class="btn btn-primary btn-flat">Сохранить</button>
                    </div>
                </div>
            </div>
        </form>

    </div>

@endsection
@section('css')
    <style>
        .navbar-custom-menu .nav{
            background-color: #3c8dbc;
        }
        .navbar-custom-menu .nav:hover{
            background-color: #367FA9;
        }
    </style>
@stop
@section('js')
    <script>
        $(function(){

            $('#email_templates').change(function(){
                var curcolor = $('#email_templates :selected').val();
                if(curcolor!=0) {


                    $.ajax({
                        method  : "get",
                        url     : "{{ url('api/selectEmailTemplate') }}/" + curcolor ,
                        success : function (data) {
                           // console.log(data);
                            if (data.success == true) {
                                localStorage.setItem('dropped', data.result);
                                localStorage.setItem('globalcolor', data.globalcolor);
                               // $('#withtemplate').eq($(this).val()).tab('show');
                                //$('#').show();

                                document.getElementById("withtemplate").classList.add('active');

                                document.getElementById("withouttemplate").classList.remove('active');

                               // $(".task_result_span_parsed").text(data.count_parsed);
                               // $(".task_result_span_queue").text(data.count_queue);
                               // $(".task_result_span_sended").text(data.count_sended);
                               // $(".last_task_id").val(data.max_id);

                               // if (Object.keys(data.result).length > 0) {
                              //      $('.no_results_class').remove();
                               // }

                               // paginateConstruct(1);
                                //load();

                            }
                        },
                        dataType: "json"
                    });
                }
                else{
                    document.getElementById("withouttemplate").classList.add('active');

                    document.getElementById("withtemplate").classList.remove('active');
                }


            })

        });
        function delDropped(){
            localStorage.removeItem('dropped');
            localStorage.removeItem('autoEdit');
            localStorage.removeItem('globalcolor');
        }

    </script>


@stop