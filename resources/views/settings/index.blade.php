@extends('adminlte::layouts.app')

@section('contentheader_title')
Общие Настройки
@endsection

@section('main-content')
<div class="container-fluid spark-screen">
    <div class="row">
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
    </div>

    <!--begin Process settings -->
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-primary">
                <div class="box-body">
                    <label for="best_proxies" class="control-labe">Threads Settings</label>
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
                    <hr/>
                    @foreach($configs as $conf)
                    <div class="text-center">
                        <h3 for="name" class="control-labe">{{$conf->name}}</h3>
                    </div>
                    <form action="{{route('settings.config.edit', [$conf->id])}}" method="post">
                        {{ csrf_field() }}
                        <div class="form-group">
                             <div class="col-md-2">
                            <label for="name" class="control-labe">numprocs</label>
                             </div>
                            <div class="col-md-5">
                            <input type="number" class="form-control" placeholder="" name="numprocs" id="numprocs" value="{{$conf->numprocs}}"/>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-flat">Сохранить</button>
                        </div>


                    </form>
                    <div class="grid-view ">
                        <table class="table table-striped table-bordered">
                            <thead>

                                <tr>

                                    <th><a class="" href="#">Pid</a></th>
                                    <th><a class="" href="#">name</a></th>
                                    <th><a class="" href="#">group name</a></th>
                                    <th><a class="" href="#">status</a></th>
                                    <th><a class="" href="#">date_now</a></th>
                                    <th><a class="" href="#"></a></th>
                                </tr>

                            </thead>
                            <tbody class='text-center'>
                                @foreach($processes as $item)
                                @if($item->groupname == $conf->name)
                                <tr>
                                    <td>{{$item->pid}}</td>
                                    <td>{{$item->name}}</td>
                                    <td>{{$item->groupname}}</td>
                                    <td>{{$item->statename}}</td>
                                    <td>{{$item->created_at}}</td>

                                    <td>
                                        <a href="{{route('settings.process.start', [$item->id])}}" title="Start" aria-label="View" data-pjax="0">
                                            <span class="glyphicon glyphicon-play"></span>
                                        </a>

                                        <a href="{{route('settings.process.stop', [$item->id])}}" title="Stop" aria-label="Update" data-pjax="0">
                                            <span class="glyphicon glyphicon-stop"></span></a>



                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                    <hr/>

                    @endforeach


                </div>
            </div>
        </div>
    </div>


    <!--end Process setting -->
</div>
@endsection