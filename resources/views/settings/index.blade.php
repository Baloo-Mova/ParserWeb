@extends('adminlte::layouts.app')

@section('contentheader_title')
Общие Настройки
@endsection

@section('main-content')
<div class="container-fluid spark-screen">
    <!--<div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-primary">
                <div class="box-body">
                    <form action="" method="post">
                        {{csrf_field()}}
                        <div class="form-group has-feedback">
                            <label for="best_proxies" class="control-label">Best-proxies</label>
                            <input type="text" class="form-control" placeholder="Best-proxies" name="best_proxies" id="best_proxies" value="{{ empty($data) ? "" : $data->best_proxies }}"/>
                        </div>
                        <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>-->

    <!--begin Process settings -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">
                    <label for="best_proxies" class="control-labe">Настройка потоков</label>
                    {{-- <form action="{{route('settings.config.store')}}" method="post">
                    {{csrf_field()}}

                    <div class="form-group has-feedback">
                        <label for="name" class="control-labe">Name Thread</label>
                        <input type="text" class="form-control" placeholder="" name="name_proc" id="name_proc" value="{{ empty($data) ? "" : $data->best_proxies }}"/>

                    </div>
                    <div class="form-group has-feedback">
                        <label for="conf_description" class="control-labe">Description config</label>
                        <textarea rows="12" type="text" name="description_config" class="form-control"
                                  id="description_config"></textarea>
                    </div>
                    <div class="form-group has-feedback">
                        <label for="name" class="control-labe">numprocs</label>
                        <input type="number" class="form-control" placeholder="" name="numprocs" id="numprocs" value=""/>

                    </div>
                    <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                    </form>--}}
                    
                    <div>
                        <a {{ $windows ? "disabled" : ""}} href="{{route('settings.process.start.all')}}" class="btn btn-success btn-flat" title="Startall" aria-label="View" data-pjax="0">
                            Запустить все потоки
                        </a>
                        <a {{ $windows ? "disabled" : ""}} href="{{route('settings.process.stop.all')}}" class="btn btn-danger btn-flat" title="Stopall" aria-label="View" data-pjax="0">
                            Отсановить все потоки
                        </a>
                    </div>
                    <hr/>




                    <div class="grid-view ">
                        <table class="table table-striped table-bordered">
                            <thead>

                                <tr>

                                    <th class="text-center"><a class="" href="#">№</a></th>
                                    <th class="text-center"><a class="" href="#">Имя</a></th>
                                    <th class="text-center"><a class="" href="#">Кол-во потоков</a></th>
                                    <th class="text-center"><a class="" href="#">Кол-во активных сейчас</a></th>
                                    <th class="text-center"><a class="" href="#">Старт/Стоп</a></th>
                                </tr>

                            </thead>
                            <tbody class='text-center'>
                                @foreach($configs as $conf)
                                    @if($conf->name == "TWSend" || $conf->name == "TWParse" || $conf->name == "TWGetGroups" || $conf->name == "InsParse" || $conf->name == "InsGetGroups")
                                    @else
                                        <tr>
                                            <td>{{$conf->id}}</td>
                                            <td>{{$conf->name}}</td>
                                            <td>
                                                <form action="{{route('settings.config.edit', [$conf->id])}}" method="post" class="form-inline">
                                                    {{ csrf_field() }}

                                                    <div class="form-group">
                                                       <input type="number" class="form-control" placeholder="" name="numprocs" id="numprocs" value="{{$conf->numprocs}}"/>
                                                    </div>

                                                        <button type="submit" class="btn btn-primary btn-flat"><span class="glyphicon glyphicon-floppy-disk"></span></button>

                                                </form></td>
                                            <td>{{$counters[$conf->name]}}</td>

                                            <td>
                                                <a href="{{route('settings.process.start', [$conf->id])}}" title="Start" class="btn" style="width: 0px !important;" aria-label="View" data-pjax="0" {!! $windows ? "disabled" : "" !!}>
                                                    <span class="glyphicon glyphicon-play"></span>
                                                </a>

                                                <a href="{{route('settings.process.stop', [$conf->id])}}" title="Stop" class="btn" style="width: 0px !important;" aria-label="Update" data-pjax="0" {!! $windows ? "disabled" : "" !!}>
                                                    <span class="glyphicon glyphicon-stop"></span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                    <hr/>




                </div>
            </div>
        </div>
    </div>


    <!--end Process setting -->
</div>
@endsection