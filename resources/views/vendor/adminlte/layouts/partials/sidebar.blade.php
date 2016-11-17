<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

        <!-- Sidebar user panel (optional) -->
        @if (! Auth::guest())
            <div class="user-panel">
                <div class="pull-left image">
                    <img src="{{ Gravatar::get($user->email) }}" class="img-circle" alt="User Image" />
                </div>
                <div class="pull-left info">
                    <p>{{ Auth::user()->name }}</p>
                    <!-- Status -->
                    <a href="#"><i class="fa fa-circle text-success"></i> {{ trans('adminlte_lang::message.online') }}</a>
                </div>
            </div>
        @endif

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <li class="header">Меню</li>
            <!-- Optionally, you can add icons to the links -->
            <li class="{{ Request::is('user/')}}">
                <a href="{{ route('user.index') }}">
                    <i class='fa fa-link'></i>
                    <span>Пользователи</span>
                </a>
            </li>
            <li class="treeview {{ Request::is('smtpbase/*') ? 'active' : ''}}">
                <a href="#">
                    <i class="fa fa-link"></i>
                    <span>База аккаунтов</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu" style="display: none;">
                    <li class="">
                        <a href="{{ route('accounts_data.index') }}">
                            <i class='fa fa-link'></i>
                            <span>База аккаунтов</span>
                        </a>
                    </li>
                    <li class=""><a href="{{ route('smtpbase.index') }}"><i class='fa fa-link'></i> <span>База smtp</span></a></li>
                </ul>
            </li>
            <li class="{{ Request::is('settings/')}}">
                <a href="{{ route('settings.index') }}">
                    <i class='fa fa-link'></i>
                    <span>Общие настройки</span>
                </a>
            </li>
        </ul><!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>
