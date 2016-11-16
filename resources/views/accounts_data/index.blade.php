@extends('adminlte::layouts.app')

@section('contentheader_title')
    База аккаунтов
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="{!! route("accounts_data.create") !!}" class="btn btn-success btn-flat add__button">Добавить</a>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Логин</th>
                                <th>Пароль</th>
                                <th>Тип</th>
                                <th>SMTP порт</th>
                                <th>SMTP адрес</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->login }}</td>
                                    <td>{{ $item->password }}</td>
                                    <td>{{ $item->type_id }}</td>
                                    <td>{{ $item->smtp_port }}</td>
                                    <td>{{ $item->smtp_address }}</td>
                                    <td>
                                        <a href="{{ route('accounts_data.edit',['id'=>$item['id']]) }}"
                                           class="btn btn-default" title="Update"><span class="glyphicon glyphicon-pencil"></span></a>
                                        <a href="{{ route('accounts_data.delete',['id'=>$item['id']]) }}"
                                           onclick="return confirm('Удалить выбраную запись?')"
                                           class="btn btn-danger" title="Delete"><span class="glyphicon glyphicon-trash"></span></a>
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
                <div class="box-footer text-center">

                </div>
            </div>
        </div>
    </div>
@endsection