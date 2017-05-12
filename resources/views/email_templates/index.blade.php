@extends('adminlte::layouts.app')

@section('contentheader_title')
 <i class='fa fa-envelope-o'></i>   Email Шаблоны
@endsection

@section('main-content')
    <div class="container-fluid spark-screen">
        <div class="row">
            <div class="box box-primary">
                <div class="box-body">
                    <a href="{!! route('email_templates.create') !!}" class="btn btn-success btn-flat pull-left add__button">Добавить</a>
                   <!-- <a href="{{ route('email_templates.destroy') }}"
                       onclick="return confirm('Вы точно хотите удалить все шаблоны?')"
                       class="btn btn-danger btn-flat pull-right add__button delete__button"
                       title="Данное действие удалит записи Шаблоны">Удалить все</a>-->
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th class="small__th">ID</th>
                            <th>Имя шаблона</th>
                            <th>Дата создания</th>

                            <th class="skypes_action_td">Действия</th>
                            
                        </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->created_at }}</td>

                                    
                                    <td>
                                        <a href="{{ route('email_templates.edit',['id'=>$item['id']]) }}"
                                           class="btn btn-default" title="Update"><span class="glyphicon glyphicon-pencil"></span></a>
                                        <a href="{{ route('email_templates.delete',['id'=>$item['id']]) }}"
                                           onclick="return confirm('Удалить выбранный шаблон?')"
                                           class="btn btn-danger" title="Delete"><span class="glyphicon glyphicon-trash"></span></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Нет шаблонов</td>
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