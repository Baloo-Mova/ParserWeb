@extends('adminlte::layouts.app')

@section('contentheader_title')
    База Аккаунтов OK
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="{!! route("accounts_data.create",["type" => 2]) !!}" class="btn btn-success btn-flat pull-left add__button">Добавить</a>
                    <a href="{{ route('accounts_data.destroy.ok') }}"
                       onclick="return confirm('Вы точно хотите удалить все записи типа OK?')"
                       class="btn btn-danger btn-flat pull-right add__button delete__button"
                       title="Данное действие удалит записи типа OK">Удалить все</a><table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Логин</th>
                                <th>Пароль</th>
                                <th>Количество запросов</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr class="{{ ($item->valid == 1) ? "bg-green" : "" }}">
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->login }}</td>
                                    <td>{{ $item->password }}</td>
                                    <td>{{ $item->count_request }}</td>
                                    <td>
                                        <a href="{{ route('accounts_data.delete',['id'=>$item['id']]) }}"
                                           onclick="return confirm('Удалить выбраную запись?')"
                                           class="btn btn-danger btn-flat" title="Delete"><span class="glyphicon glyphicon-trash"></span></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">
                                        Записи отсутствуют
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    {!! $data->links() !!}
                </div>
            </div>
        </div>
    </div>
@endsection