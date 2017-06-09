@extends('adminlte::layouts.app')

@section('contentheader_title')
    Тест максросcов
@endsection

@section('main-content')
    <form action="" method="post">
        {{ csrf_field() }}
        <div class="form-group">
            <label for="original">Текст с макроссом:</label>
            <textarea name="original" class="form-control" id="original" cols="30" rows="10" >{!! isset($old) ? $old : '' !!}</textarea>
        </div>
        <div class="form-group">
            <label for="new">Результат:</label>
            <textarea name="new" class="form-control" id="new" cols="30" rows="10" >{!! isset($new) ? $new : '' !!}</textarea>
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-default" value="Отправить">
        </div>
    </form>
@stop