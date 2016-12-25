@extends('adminlte::layouts.app')

@section('contentheader_title')
    Скайп Аккаунты
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="{!! route("skypes_accounts.create") !!}" class="btn btn-success btn-flat pull-left add__button">Добавить</a>
                    <a href="{{ route('skypes_accounts.destroy.sk') }}"
                       onclick="return confirm('Вы точно хотите удалить все записи типа Skype?')"
                       class="btn btn-danger btn-flat pull-right add__button delete__button"
                       title="Данное действие удалит записи типа Skype">Удалить все</a>
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th class="small__th">ID</th>
                            <th>Логин</th>
                            <th>Пароль</th>
                            <th class="skypes_action_td">Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->login }}</td>
                                    <td>{{ $item->password }}</td>
                                    <td>
                                        <a href="{{ route('skypes_accounts.edit',['id'=>$item['id']]) }}"
                                           class="btn btn-default" title="Update"><span class="glyphicon glyphicon-pencil"></span></a>
                                        <a href="{{ route('skypes_accounts.delete',['id'=>$item['id']]) }}"
                                           onclick="return confirm('Удалить выбраную запись?')"
                                           class="btn btn-danger" title="Delete"><span class="glyphicon glyphicon-trash"></span></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Нет Записей!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $data->links() }}
                </div>
                <div class="box-footer text-center">
                </div>
            </div>
        </div>
    </div>


@endsection