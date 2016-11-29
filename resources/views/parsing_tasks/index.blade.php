@extends('adminlte::layouts.app')

@section('contentheader_title')
    Задачи К Парсингу
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="{!! route("parsing_tasks.create") !!}" class="btn btn-success btn-flat pull-left add__button">Добавить</a>

                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Тип Поиска</th>
                            <th>Поисковый Запрос</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($data as $item)

                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->tasksType->name }}</td>
                                    <td>{{ $item->task_query }}</td>
                                    <td>
                                        <a href="{{ route('parsing_tasks.show', ['id' => $item->id]) }}">
                                            <i class="fa fa-eye" aria-hidden="true"></i>
                                        </a>
                                    </td>

                                </tr>

                        @empty
                            <tr>
                                <td colspan="10" class="text-center">
                                    Нет записей!
                                </td>
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