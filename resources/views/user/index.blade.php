@extends('adminlte::layouts.app')

@section('contentheader_title')
    Пользователи
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="{!! route("user.create") !!}" class="btn btn-success btn-flat add__button">Добавить</a>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Дата создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        @foreach($data as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->created_at }}</td>
                                <td>
                                    <a href="{{ route('user.edit',['id'=>$item['id']]) }}"
                                       class="btn btn-default" title="Update"><span class="glyphicon glyphicon-pencil"></span></a>
                                    <a href="{{ route('user.delete',['id'=>$item['id']]) }}"
                                       onclick="return confirm('Удалить выбраную запись?')"
                                       class="btn btn-danger" title="Delete"><span class="glyphicon glyphicon-trash"></span></a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div class="box-footer text-center">
                    {!! $data->links() !!}
                </div>
            </div>
        </div>
    </div>
@endsection