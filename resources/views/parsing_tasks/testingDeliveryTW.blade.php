@extends('adminlte::layouts.app')

@section('contentheader_title')
    Добавление Тестовой Рассылки Twitter
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
                                <label for="tw_list" class="control-label">Список Twitter (каждый с новой строки)</label>
                                <textarea class="form-control" rows="6" name="tw_list" id="tw_list"></textarea>
                            </div>

                            <div class="form-group has-feedback">
                                <label for="tw_text" class="control-label">Текст Сообщения Twitter</label>
                                <textarea class="form-control" rows="6" name="tw_text" id="tw_text" maxlength="100"></textarea>
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
