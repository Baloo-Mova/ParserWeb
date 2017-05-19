<head>
    <meta charset="UTF-8">
    <title>@yield('htmlheader_title', 'ParserWeb') </title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ asset('/css/all.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('/css/style.css') }}" rel="stylesheet" type="text/css" />
    <script src="{{ asset('/js/jquery-2.2.4.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('/js/common.js') }}" type="text/javascript"></script>
    <?php
    if(Request::path() == 'email-templates/create' || strpos(Request::path(),"email-templates/edit")!==false ||Request::path() == 'parsing-tasks/testing-delivery-mails' ){
        echo ('<link href="'. asset('vue/css/app.css') .'" rel="stylesheet" type="text/css" />'.
   '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css">');
    }

    ?>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <script>
        window.Laravel = <?php echo json_encode([
                'csrfToken' => csrf_token(),
        ]); ?>
    </script>
</head>
