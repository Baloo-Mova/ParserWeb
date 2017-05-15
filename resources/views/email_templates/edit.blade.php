@extends('adminlte::layouts.app')

@section('contentheader_title')
    <i class='fa fa-envelope-o'></i> Редактирование  Email Шаблона
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="box box-primary">
                    <div class="box-body">
                        <form action="" method="post">
                            <input type="hidden" id="_token" name="_token" ref="token"  value="{{ csrf_token() }}" >
                            <div class="form-group has-feedback">
                                <label for="name" class="control-label">Имя Шаблона</label>
                                <input type="text" class="form-control" placeholder="Имя" name="name"
                                       value="{{ $data->name }}" id="name"/>
                            </div>

                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>

                            <div id="email_template">

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>

       localStorage.setItem('dropped', '{!! $data->body !!}');

    </script>

@stop
