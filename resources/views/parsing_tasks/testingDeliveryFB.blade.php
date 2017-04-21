@extends('adminlte::layouts.app')

@section('contentheader_title')
    Добавление Тестовой Рассылки Facebook
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <form method="post">
                {{ csrf_field() }}
                <div class="col-md-8 col-md-offset-2">
                    <div class="box box-primary">
                        <div class="box-body">

                            <div class="form-group has-feedback">
                                <label for="fb_list" class="control-label">Список FB (каждый с новой строки)</label>
                                <textarea class="form-control" rows="6" name="fb_list" id="fb_list"></textarea>
                            </div>

                            <div class="form-group has-feedback">
                                <label for="fb_text" class="control-label">Текст Сообщения FB</label>
                                <textarea class="form-control" rows="6" name="fb_text" id="fb_text"></textarea>
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
