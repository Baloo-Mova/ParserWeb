@extends('adminlte::layouts.app')

@section('contentheader_title')
    Задачи К Парсингу
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="{!! route("skypes_accounts.create") !!}" class="btn btn-success btn-flat pull-left add__button">Добавить</a>

                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th class="small__th">ID</th>
                            <th>Логин</th>
                            <th>Пароль</th>
                        </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->login }}</td>
                                    <td>{{ $item->password }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Нет Записей!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
                <div class="box-footer text-center">
                </div>
            </div>
        </div>
    </div>


@endsection