@extends('adminlte::layouts.app')

@section('contentheader_title')
    <i class='fa fa-envelope-o'></i> Добавление  Email Шаблонов
@endsection

@section('main-content')

    <div class="container-fluid spark-screen">

        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <form action="" method="post">


                            <input type="hidden" id="_token" name="_token" ref="token" value="{{ csrf_token() }}">
                            <div class="form-group has-feedback">
                                <label for="name" class="control-label">Имя Шаблона</label>
                                <input type="text" class="form-control" placeholder="" name="name" id="name"
                                       ref="name"/>
                            </div>
                            <button type="submit" onClick="delDropped()" class="btn btn-primary btn-flat">Сохранить
                      </button>


                        <div class="row">
                            <div id="email_template">
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>


    </div>



@endsection
@section('js')
    <script>

        localStorage.removeItem('dropped');
        localStorage.removeItem('globalcolor');
        function delDropped() {
            localStorage.removeItem('dropped');
            localStorage.removeItem('autoEdit');
            localStorage.removeItem('globalcolor');
        }
    </script>

@stop
@section('css')


@stop
