<!-- Main Header -->
<header class="main-header">

    <!-- Logo -->
    <a href="{{ url('/home') }}" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini">PW</span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg">ParserWeb </span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">{{ trans('adminlte_lang::message.togglenav') }}</span>
        </a>
        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                <!-- Notifications Menu -->
                <li>
                    <!-- Menu toggle button -->
                    <a href="#">
                        <i class="fa fa-bell-o"></i>
                        <span class="label label-warning">1</span>
                    </a>
                </li>
                @if (Auth::guest())
                    <li><a href="{{ url('/login') }}">{{ trans('adminlte_lang::message.login') }}</a></li>
                @else
                    <!-- User Account Menu -->
                    <li class="user user-menu">

                        <a href="{{ url('/logout') }}" class=""
                           onclick="event.preventDefault();
                                     document.getElementById('logout-form').submit();">
                            <img src="{{ Gravatar::get($user->email) }}" class="user-image" alt="User Image"/>
                            Выход({{$user->name}})
                        </a>

                        <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
                            {{ csrf_field() }}
                            <input type="submit" value="logout" style="display: none;">
                        </form>

                    </li>
                @endif
            </ul>
        </div>
    </nav>
</header>
