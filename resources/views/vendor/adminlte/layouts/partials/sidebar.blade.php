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
            <li class="{{ Request::path() == 'parsing-tasks' ? 'active' : ''}}">
                <a href="{{ route('parsing_tasks.index') }}">
                    <i class='fa fa-thumb-tack'></i>
                    <span>Задачи</span>
                </a>
            </li>
            <li class="{{ Request::path() == 'user' ? 'active' : ''}}">
                <a href="{{ route('user.index') }}">
                    <i class='fa fa-user'></i>
                    <span>Пользователи</span>
                </a>
            </li>
            <li class="treeview {{ Request::path() == 'accounts-data/vk'
                                || Request::path() == 'accounts-data/ok'
                                || Request::path() == 'accounts-data/emails'
                                || Request::path() == 'smtp-base'
                                ? 'active' : ''}}">
                <a href="#">
                    <i class="fa fa-tasks"></i>
                    <span>База Аккаунтов</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu" style="display: none;">
                    <li >
                        <a href="{{ route('accounts_data.vk') }}" class="{{ Request::path() == 'accounts-data/vk' ? 'link_active' : ''}}">
                            <i class='fa fa-vk'></i>
                            <span>VK Аккаунты</span>
                        </a>
                        <a href="{{ route('accounts_data.ok') }}" class="{{ Request::path() == 'accounts-data/ok' ? 'link_active' : ''}}">
                            <i class='fa fa-odnoklassniki'></i>
                            <span>OK Аккаунты</span>
                        </a>
                        <a href="{{ route('accounts_data.emails') }}" class="{{ Request::path() == 'accounts-data/emails' ? 'link_active' : ''}}">
                            <i class='fa fa-envelope-o'></i>
                            <span>Emails Аккаунты</span>
                        </a>
                    </li>
                    <li class="">
                        <a href="{{ route('smtpbase.index') }}" class="{{ Request::path() == 'smtp-base' ? 'link_active' : ''}}">
                            <i class='fa fa-envelope'></i>
                            <span>Default SMTP</span>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="{{ Request::path() == 'settings' ? 'active' : '' }}">
                <a href="{{ route('settings.index') }}">
                    <i class='fa fa-wrench'></i>
                    <span>Общие Настройки</span>
                </a>
            </li>
        </ul><!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>
