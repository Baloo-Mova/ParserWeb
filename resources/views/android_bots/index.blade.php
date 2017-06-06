@extends('adminlte::layouts.app')

@section('contentheader_title')
 <i class='fa fa-android'></i>   Android Боты
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="{!! route('android_bots.create') !!}" class="btn btn-success btn-flat pull-left add__button">Добавить</a>
                    <a href="{{ route('android_bots.destroy') }}"
                       onclick="return confirm('Вы точно хотите удалить все записи типа Skype?')"
                       class="btn btn-danger btn-flat pull-right add__button delete__button"
                       title="Данное действие удалит записи типа Skype">Удалить все</a>
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th class="small__th">ID</th>
                            <th>Имя устройства(ключ)</th>
                            <th>Моб. номер телефона (для Viber, Whatapp)</th>
                            <th class="small__th">Статус</th>
                            <th class="skypes_action_td">Действия</th>
                            
                        </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->phone }}</td>
                                    <td>{{ $item->status == 2 ? "Работает" : "Не работает" }}</td>
                                    
                                    <td>
                                        <a href="{{ route('android_bots.edit',['id'=>$item['id']]) }}"
                                           class="btn btn-default" title="Update"><span class="glyphicon glyphicon-pencil"></span></a>
                                        <a href="{{ route('android_bots.delete',['id'=>$item['id']]) }}"
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