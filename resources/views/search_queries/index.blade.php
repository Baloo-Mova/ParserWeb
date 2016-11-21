@extends('adminlte::layouts.app')

@section('contentheader_title')
    Результаты Запросов
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="#" class="btn btn-success btn-flat pull-left add__button">Сохранить В CSV</a>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ник и ФИО</th>
                                <th>Ссылка</th>
                                <th>Пол</th>
                                <th>Email</th>
                                <th>Страна</th>
                                <th>Город</th>
                                <th>Телефоны</th>
                                <th>Скайп</th>
                                <th>Поисковой Запрос</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($data as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->FIO }}</td>
                                <td>{{ $item->link }}</td>
                                <td>{{ $item->sex }}</td>
                                <td>{{ $item->mails }}</td>
                                <td>{{ $item->country }}</td>
                                <td>{{ $item->city }}</td>
                                <td>{{ $item->phones }}</td>
                                <td>{{ $item->skypes }}</td>
                                <td>{{ $item->query }}</td>
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
                    {!! $data->links() !!}
                </div>
                <div class="box-footer text-center">
                </div>
            </div>
        </div>
    </div>
@endsection