@extends('adminlte::layouts.app')

@section('contentheader_title')
    База smtp
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="{!! route("smtpbase.create") !!}" class="btn btn-success btn-flat pull-left add__button">Добавить</a>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Домен</th>
                                <th>Smtp</th>
                                <th>Порт</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->domain }}</td>
                                    <td>{{ $item->smtp }}</td>
                                    <td>{{ $item->port }}</td>
                                    <td>
                                        <a href="{{ route('smtpbase.edit',['id'=>$item['id']]) }}"
                                           class="btn btn-default" title="Update"><span class="glyphicon glyphicon-pencil"></span></a>

                                        <a href="{{ route('smtpbase.delete',['id'=>$item['id']]) }}"
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
                <div class="box-footer">
                    {!! $data->links() !!}
                </div>
            </div>
        </div>
    </div>
@endsection