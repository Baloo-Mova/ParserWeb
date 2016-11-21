@extends('adminlte::layouts.app')

@section('contentheader_title')
    Результаты Запросов
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <button type="button" class="btn btn-success btn-flat pull-left add__button" data-toggle="modal" data-target="#searchQueryModal">
                        Сохранить В CSV
                    </button>


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

    <!-- Modal -->
    <div class="modal fade searchQueryModal" id="searchQueryModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <form action="" method="post">
            <div class="modal-dialog" role="document">
                <div class="modal-content">

                        {{ csrf_field() }}
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel"> Создать Выборку и Сохранить В CSV</h4>
                    </div>
                    <div class="modal-body">
                            <div class="form-group has-feedback">
                                <label for="query" class="control-label">Query</label>
                                <select name="query" id="query" class="form-control">
                                    <option value=""></option>
                                    @foreach($data_for_select as $item)
                                        <option value="{{ $item->query }}">{{ $item->query }}</option>
                                    @endforeach
                                </select>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <input type="submit" class="btn btn-primary btn-flat pull-left" value="Сохранить">
                        <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection