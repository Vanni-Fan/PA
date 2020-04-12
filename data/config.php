<?php
# 管理系统框架本身的配置
return [
    # 调试开关
    'debug'       => 1,

    # 是否开启 Phalcon 的跟踪调试
    'trace'       => 0,

    # 应用的基础目录
    'application' => POWER_BASE_DIR,

    # 多模块设置
    'module_path' => null, // 指向一个目录，目录下的子目录会被认为是模块，每一个模块下面应该有完整mvc（controllers：必须，models：可选，views:可选）

    # 模块的域名绑定
    /**
     * 子模块的域名配置，当你需要不同的模块分开部署的时候，会比较用于
     *
     * root_domain:根域名，用于子域名的拼接，如果没有，子域名则使用去掉第一段的 $_SERVER['SERVER_NAME']
     * domain_bind: 数组，格式如下，默认为空数组，相当于全模块开放，等于 ['*'=>'*']
     *   [
     *       域名1|子域名|* => 模块名,模块名2,模块名3,
     *       域名2|子域名|* => 模块名,模块名2,模块名3,
     *   ]
     *   比如:
     *   [
     *       'www'          => 'web,gzh_api', // 简短的www子域名配置，此配置需要有 root_domain 的配置。 web,gzh_api 表示，对外开放：网页模块、公众号API模块
     *       'www.aa.com'   => 'web',  // 设置具体的域名，并只开放 web 模块
     *       '*'            => 'web' // 不限制域名，只开放 web 模块
     *       'admin'        => '*', // 管理员域名，开放全部模块
     *       '*'            => '*', // 全部模块开放
     *   ]
     */
    //
    'root_domain'=>null,
    'domain_bind'=>null,

    # 用户的同步方法， PA管理系统中的用户修改，会调用此方法
    'user_handler'=>null, // 如果有用户的配置，在PA登录时，会回调此方法，必须是 \Power\HandlerPAUserAbs 的子类

    # 额外的路由配置，格式如下
    # 'routers'=>[
    #     '*'=>[ // 可以指定 POST, GET, DELETE, PATCH 请求方法，也可使用 * 表示通配所有方法
    #         '正则表达式'=>[ // 可以是一个具体的URL，也可以是一个正则表达式
    #             'module'     => 'web', // 默认为空
    #             'namespace'  => 'web\Controllers', // 默认 \
    #             'controller' => 'activities', // 默认 index
    #             'action'     => 'index', // 默认 index
    #             'params'     => 1, // 默认无
    #             'priority'   => 10, // 默认0，优先级，Phalcon的路由为后加入的优先，所以为了避免替换，可以设置一个比较到的优先级
    #         ]
    #     ]
    # ];
    'routers' => [],

    # PA 的基础URL配置
    'pa_url_path' => '/admin/',

    # PA 的数据库配置，如果不需要 PA 的数据库，请将 pa_db 设置成 null
    'pa_db'       => [
        'adapter' => 'sqlite',
        'dbname'  => POWER_DATA . '/powerdb.sql3.db',
    ],

#    'pa_db'       => [
#        'adapter' => 'mysql',
#        'dbname'  => 'pa',
#        'username'=> 'root',
#        'password'=> '123456',
#        'host'    => 'mysql',
#        'prefix'  => 'pa_'
#    ],

    # PA 的Cookie加密Key
    'cookie_key'    => file_get_contents(POWER_DATA .'/cookie.key'),
    'cookie_cipher' => 'aes-192-cbc',
    'cookie_name'   => 'admin_token',
    'cookie_fields' => ['user_id'=>4, 'login_time'=>4], // 必须保留 user_id, 'login_ip'=>8,只支持64为，暂时去除
    'cookie_maker'  => 'Power\\Models\\Users::makeToke',  // 需要返回对应的Token
    'cookie_parser' => 'Power\\Models\\Users::parseToken',// 解析Token

    # 错误处理函数
    'error' => [
        'handler'   => 'Power\\Controllers\\ErrorController::handlerError',
        'exception' => 'Power\\Controllers\\ErrorController::handlerException',
        'controller'=> 'Power\\Controllers\\ErrorController',
    ],

    # 插件的激活，将插件放到 pa 的 plugins 目录下，并在此配置名称
    # 比如： 'plugins'=>['GraphQL','Proxy','Tables']
    'plugins' => [],

    # 是否加载数据库的配置，如果开启的话，将加载PA数据库中，配置的路由、和其他配置
    # 数据库中的配置，在配置文件加载后加载，所以会覆盖 config.php 中的配置值
    'load_db_config' => false,

    # 数据库是否抛异常
    'exception_on_failed_save' => true,

    # 事件监听，能配置的类型在 https://docs.phalcon.io/4.0/en/events#list-of-events 里面
    # 比如:
    # 'event' => [
    #     'handler' => 'Logger::logs',
    #     'events'=>['db:afterQuery'],
    # ],
//    'event' => [],
    # 视图配置
    'view' => [
        'class' => 'Phalcon\Mvc\View',
        'disable_layout' => false,
        'disable_main_layout' => false,
    ],
];
