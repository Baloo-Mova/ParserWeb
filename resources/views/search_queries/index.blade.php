@extends('adminlte::layouts.app')

@section('contentheader_title')
    Результаты Запросов
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Дата Создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>

                    </table>
                </div>
                <div class="box-footer text-center">
                </div>
            </div>
        </div>
    </div>
@endsection