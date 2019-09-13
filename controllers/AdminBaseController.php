<?php
namespace Power\Controllers;
use Power\Models\Configs;
use Power\Models\Extensions;
use Power\Models\Logs;
use Power\Models\Rules;
use Power\Models\Users;
use Phalcon\Mvc\Controller;
use PA;

/**
 * Class AdminBaseController
 * 功能包括： 验证权限、填充默认菜单、设置用户信息、查询通知信息
 * @package Power\Controllers
 */
class AdminBaseController extends Controller{
    protected $layout    = '';
    protected $title     = '';
    protected $subtitle  = '';
    
    # 用户信息
    protected $userinfo  = [];
    protected $tokeninfo = [];
    
    # 模板设置
    protected $css_files = ['before'=>[],'after'=>[]];
    protected $styles    = ['before'=>[],'after'=>[]];
    protected $js_files  = ['before'=>[],'after'=>[]];
    protected $scripts   = ['before'=>[],'after'=>[]];
    protected $template_path = '';
    
    # 分页的设置
    protected $page_size    = 20;
    protected $page_items   = 5;
    protected $current_page = 1;

    # 权限的设置
    protected $rules    = [];
    protected $rule_id  = 1;
    protected $item_id  = 0;
    protected $is_admin = true;
    protected $extensions = [];
    protected $settings = [];
    
    public function getItemId(){return $this->item_id;}
    public function getRuleId(){return $this->rule_id;}
    protected function addCss($css_file,$position='after'){
        $plus = PA::$config['debug'] ? ('?'.random_int(1000000,9999999)) : '';
        $this->css_files[$position][] = '<link rel="stylesheet" href="'.$css_file.$plus.'" type="text/css" />';
    }
    protected function addStyle($style,$position='after'){
        $this->styles[$position][] = '<style type="text/css">'.$style.'</style>';
    }
    protected function addJs($js_file,$position='after'){
        $plus = PA::$config['debug'] ? ('?'.random_int(1000000,9999999)) : '';
        $this->js_files[$position][] = '<script type="text/javascript" src="'.$js_file.$plus.'"></script>';
    }
    protected function addScript($script,$position='after'){
        $this->scripts[$position][] = '<script type="text/javascript">'.$script.'</script>';
    }
    
    public function isAllowed(string $action, int $owner=null, int $operator=null):bool{ // 权限， 所有者， 操作者
        if(!$operator) $operator = $this->getUserId();
        if(!$owner) $owner = $this->getUserId();
        return Rules::isAllowed($action, $owner, $operator, $this->rules[$this->rule_id]);
    }
    
    /**
     * 如果需要自定义用户，那么请重载此方法
     * @return mixed
     */
    public function getUserId():int{
        return $this->userinfo['user_id'];
    }
    
    /**
     * 获得记录的所有者，如果item_id为空，表示首页或者列表页
     * @param int $item_id
     * @return int
     */
    public static function getItemOwner(int $item_id=null):int{
        if(!$item_id) return 1;
        return 1;
    }

    /**
     * 获得菜单的自定义角标，需要返回： [$rule_id=>'<i class="fa fa-angle-left pull-right">23</i>'...]
     */
    public function getMenuBadges(){return [];}
    
    /**
     * 获得系统通知的回调，需要返回：[['id'=>$id,'icon'=>'fa fa...', 'content'=>'内容'],...]
     * @return array
     */
    public function getNotifications(){return [];}
    
    /**
     * 获得系统消息，需要返回：[['id'=>$id,'title'=>'标题', 'content'=>'内容','time'=>'20分钟之前'],...]
     * @return array
     */
    public function getMessages(){return [];}
    
    /**
     * 获得任务列表，需要返回：[['name'=>'任务名称', 'percent'=>'完成进度，0~100的整数'],...]
     * @return array
     */
    public function getTasks(){return [];}
    
    /**
     * 获得用户信息，需要返回的基本信息：['id'=>$id, 'name'=>'名称','image'=>'头像','role'=>'角色名','login_time'=>'','login_ip'=>'']
     * @param $user
     * @return array
     */
    public function getUserInfo(){return $this->userinfo;}

    public function initialize(){
        header('x-powered-by: '.PA::$config['site.domain.logogram'].'/'.PA::$config['site.version']);
        # 判断是否有Token
        if(empty($_COOKIE[PA::$config['cookie_name']])) return header('Location: '.PA_URL_PATH.'login');

        # 解码Token
        $this->tokeninfo = PA::$config['cookie_parser']($_COOKIE[PA::$config['cookie_name']]);
        if(!$this->tokeninfo) return header('Location: '.PA_URL_PATH.'login');

        # 获取用户信息
        $this->userinfo = Users::getInfo($this->tokeninfo['user_id']);
        if(!$this->userinfo) return header('Location: '.PA_URL_PATH.'login');
    
        (new Logs())->create(
            [
                'user_id' => $this->tokeninfo['user_id'],
                'url'     => $_SERVER['REQUEST_URI'],
                'name'    => $this->getParam('Rule')['name'] .'['. $this->dispatcher->getActionName() .']',
                'request' => json_encode($_REQUEST,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'server'  => json_encode($_SERVER, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]
        );
        # 设置基本信息
        $this->rules   = $this->userinfo['rules'];
        $this->layout  = POWER_VIEW_DIR . 'layouts/index';
        $this->rule_id = $this->getParam('rule_id','int',array_key_first($this->rules));
        $this->item_id = $this->getParam('item_id','int',0);
        $this->current_page = $this->getParam('page','int',1);
        
        # 扩展权限
        list('rule'=>$this->extensions,'attribute'=>$this->settings) = Extensions::getExtensionsByUser($this->tokeninfo['user_id']);
        # 扩展属性
        if(!array_key_exists($this->rule_id, $this->rules)) throw new \Exception('Permission Denied(rule not exists).');
        
        if(!Rules::isAllowed($this->dispatcher->getActionName(), $this->getItemOwner($this->item_id), $this->userinfo["user_id"],$this->rules[$this->rule_id])){
            throw new \Exception('Permission Denied.');
        }
        
    }
    
    /**
     * 获得当前连接调用连接
     *
     * menu/123/item        (GET:新增显示, POST|PUT:新增提交)
     * menu/123/item/456    (GET:修改显示, POST|PUT:修改提交,  DELETE:删除)
     * menu/123             (GET:列表页)
     *
     * @param array  $params
     * @throws \Exception
     * @return string
     */
    public function url($action='index', $params=[]){
        $url     = PA_URL_PATH.'menu/'.($params['rule_id']??$this->rule_id);
        $item_id = $params['item_id'] ?? $this->item_id ?? 0;
        $all_actions = [
            'index'   => '/index',
            'list'    => '/items',
            'new'     => '/item/new',
            'append'  => '/item/new',
            'delete'  => "/item/$item_id/delete",
            'display' => "/item/$item_id",
            'update'  => "/item/$item_id"
        ];
        if(!array_key_exists($action, $all_actions)) throw new \Exception("事件不对，只能为：".json_encode(array_keys($all_actions)));
        $url .= $all_actions[$action];
        
//        $url_params = $this->getParam();     // Phalcon 的URL中参数
        unset($params['rule_id'], $params['item_id']);
//        foreach(array_merge($url_params, $params) as $k=>$v){ # 手动指定的参数大于URL现在的参数
        foreach($params as $k=>$v){
            $url .= '/'.$k.'/'.$v;
        }
        $url_query  = $this->request->isGet() ? $this->request->get() : []; // URL 中问号中的参数
        unset($url_query['_url']);
        $url .= $url_query ? ('?'.http_build_query($url_query)) : '';
        return $url;
    }
    
    /**
     * 根据路由规则获得URL
     * $this->routerUrl('index',  ['controller'=>'tools'],['action'=>'aes']);
     * $this->routerUrl('index', ['namespace'=>'Power\\Controllers','controller'=>'users']);
     * @param string $action
     * @param array  $router
     * @param array  $params
     * @return string
     * @throws \Exception
     */
    public function routerUrl(string $action='index', array $router=[], array $params=[]):string {
        $all = Rules::find()->toArray();
        # 模块名称，如果不提供，那么默认为当前模块
        $module_name = $this->dispatcher->getModuleName();
        if($module_name && !isset($router['module'])) $router['module'] = $module_name;
        
        # 控制器
        $controller_name = $this->dispatcher->getControllerName();
        if(!isset($router['controller'])) $router['controller'] = $controller_name;
        
        # 名字空间
        $namespace =$this->dispatcher->getNamespaceName();
        if($namespace && !isset($router['namespace'])) $router['namespace'] = $namespace;
        
        # 查找匹配到的数据
        $matched = array_filter($all, function($v) use ($router, $params){
            $_router = json_decode($v['router'],1);
            $_params = json_decode($v['params']?:'[]', 1);
            return
                $_router
                && ($_router['controller']??'index') === ($router['controller']??'index')
                && ($_router['action']??'index')     === ($router['action']??'index')
                && ($_router['namespace']??'')       === ($router['namespace']??'')
                && ($_router['module']??'')          === ($router['module']??'')
                && array_intersect_key($_params, $params) == $_params
                ;
        });
        
        if(!$matched) throw new \Exception('找不到控制器');
        $params['rule_id'] = current($matched)['rule_id'];
        return $this->url($action, $params);
    }
   
    /**
     * 获得分页字符串
     * @param int $total 总行数
     * @param int $current_page 当前页面
     * @param int $page_size 页面尺寸
     * @param int $page_items 分页字符串显示几条
     * @throws \Exception
     * @return string
     */
    public function getPaginatorString(int $total, int $current_page=1, int $page_size=20, int $page_items=5){
        $p = new \Pagination($total, $this->current_page ?? $current_page, $this->page_size ?? $page_size, $this->page_items ?? $page_items);
        $p->setStyle('front','<li><a href="{url}">&laquo;</a></li>');
        $p->setStyle('first','<li><a href="{url}">{page}</a></li>');
        $p->setStyle('item','<li><a href="{url}">{page}</a></li>');
        $p->setStyle('current','<li><a class="bg-gray disabled" href="#">{page}</a></li>');
        $p->setStyle('more','<li><a href="#">...</a></li>');
        $p->setStyle('last','<li><a href="{url}">{page}</a></li>');
        $p->setStyle('next','<li><a href="{url}">&raquo;</a></li>');
        $get = $_GET;
        unset($get['_url']);
        $p->setUrl($this->url('index', ['page'=>'{page}']));
        
        return '<div class="box-footer clearfix"><ul class="pagination pagination-sm no-margin pull-right">'.
               $p->getOutput().
               '</ul></div>';
    }
    
    /**
     * 获得URL中的参数
     * @param $key
     * @return null
     */
    public function getParam($param=null, $filters = null, $defaultValue = null){
        if($param) return $this->dispatcher->getParam($param, $filters, $defaultValue);
        else return $this->dispatcher->getParams();
    }
    
    public function getParamByKey($key=null){
        return \Utils::getDispatchParamsByKey($this->dispatcher, $key);
    }
    
    public function setLayout($layout){
        $this->layout = $layout;
    }
    
    # 获得当前权限
    public static function getRules(int $user_id):array{
        return $this->rules;
    }
    
    public function setSettings($name, $value){
        if(is_int($name)){
            $extends = Extensions::findFirst($name);
        }else{
            $extends = Extensions::findFirst(['type=?0 and rule_id=?1 and extend_name=?2','bind'=>['attribute',$this->rule_id, $name]]);
        }
        
        if(!$extends || $extends->type!=='attribute') throw new \Exception("没有找到{$name}相关属性的配置");
        if($extends->extend_value_type !== 'text' && is_string($value)) throw  new \Exception("{$name}属性的值类型应该是{$extends->extend_value_type},但是当前给到是一个字符串");
        $value = is_string($value) ? $value : json_encode($value,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $config = Configs::findFirst(['user_id=?0 and rule_id=?1','bind'=>[$this->getUserId(), $this->rule_id]]);
        if($config){
            $config->update(['value'=>$value]);
        }else{
            $config = new Configs();
            $config->create(
                [
                    'user_id'=>$this->getUserId(),
                    'rule_id'=>$this->rule_id,
                    'name'=>$name,
                    'value'=>$value,
                ]
            );
        }
        return true;
    }

    public function getSettings($name=null){
        $all_settings = array_merge($this->settings[$this->rule_id]??[], $this->settings[0]??[]);
        return $name ? ($all_settings[$name] ?? null) : $all_settings;
    }
    
    public function getExceptions($name=null){
        $all_extensions = array_merge($this->extensions[$this->rule_id]??[], $this->extensions[0]??[]);
        return $name ? ($all_extensions[$name] ?? null) : $all_extensions;
    }
    
    public function settingAction(){
        if(isset($_POST['extend'])){
            foreach($_POST['extend'] as $rule_id => $setting){
                foreach($setting as $key=>$val){
                    $data = [
                        'user_id' => $this->getUserId(),
                        'rule_id' => $rule_id,
                        'name'    => $key,
                    ];
                    $config = Configs::findFirst(['user_id=?0 and rule_id=?1 and name=?2', 'bind'=>array_values($data)]);
                    if($config) $config->update(['value'=>$val]);
                    else (new Configs())->create($data + ['value'=>$val]);
                }
            }
        }
        $this->jsonOut(['code'=>'ok']);
    }
    
    /**
     * 渲染页面
     * @param null $file 指定渲染的模板： controller/action 形式，默认为当前的 controller/action
     * @param bool $partial 是否部分渲染：默认全框架渲染，如果为真，只只渲染模板文件，不渲染layout
     */
    public function render($file=null, $partial=false){
        $this->view->js            = $this->js_files;
        $this->view->css           = $this->css_files;
        $this->view->style         = $this->styles;
        $this->view->script        = $this->scripts;
        $this->view->title         = $this->title;
        $this->view->subtitle      = $this->subtitle;
        $this->view->tokeninfo     = $this->tokeninfo;
        $this->view->c             = $this;
        $this->view->r             = $this->request;
        if(!$partial && $this->is_admin){
            $this->view->tasks         = $this->getTasks();
            $this->view->messages      = $this->getMessages();
            $this->view->userinfo      = $this->getUserInfo();
            $this->view->menuBadges    = $this->getMenuBadges();
            $this->view->notifications = $this->getNotifications();
            $this->view->settings      = $this->getSettings();
            $this->view->setting_items = \AdminHelper::getExtensionsHtml(
                Extensions::getExtensions('attribute'),
                $this->settings
            );

            # 获得当前用户权限下的菜单
            #$this->view->menu = Rules::getChildIds(); // 所有菜单
            $this->view->menu = Rules::allBySubRule(array_keys($this->rules));
    
            # 获得当前菜单的面包屑
            $current_menu_path = [];
            Rules::getParentIds($this->rule_id, $current_menu_path);
            $this->view->current_menu_path = $current_menu_path;
            array_unshift($current_menu_path,['icon'=>'','name'=>'首页','url'=>'/']);
            $this->view->breadcrumbs = $current_menu_path;
        }

        # 找到对应的 view 模板文件
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);//[1];
        $caller_file = $caller[0]['file'];
        $caller = $caller[1];
        if(!$this->template_path){
            if(strpos($caller['class'], 'Power\\Controllers') !== false){
                $dir = POWER_VIEW_DIR;
            }elseif(preg_match('#^plugins\\\\.+\\\\Controllers#', $caller['class'])){
                $dir = explode('\\Controllers\\',$caller_file)[0].'/views/templates/';
            }else{
                if(!empty(\PA::$config['modules'])){
                    $dir = VIEW_DIR . explode('\\',$caller['class'])[0] . '/views/templates/';
                }else{
                    $dir = VIEW_DIR;
                }
            }
        }else $dir = $this->template_path;
        $this->view->setViewsDir($dir);
        if(!$file){
            # 使用类名(去掉Controller后缀) + '/' + 方法名(去掉Action后缀)
            $file = substr(array_slice(explode('\\',$caller['class']),-1)[0], 0, -10).  // 调用者的类名，去掉 Controller 后缀
                    '/' .
                    substr($caller['function'],0,-6); // 调用者的方法名，去掉 Action 后缀
        }
        # 模板文件统一使用小写路径
        $this->view->template_file = $dir . strtolower($file);
        if($partial) $this->view->partial($file);
        else         $this->view->pick($this->layout);
    }
    
    public function setTemplatePath($path){
        $this->template_path = $path;
    }
    public function getRelativeTemplatePath(){
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);//[1];
        $caller_file = $caller[0]['file'];
        $caller = $caller[1];
        if(strpos($caller['class'], 'Power\\Controllers') !== false){
            $dir = POWER_VIEW_DIR;
        }elseif(preg_match('#^plugins\\\\.+\\\\Controllers#', $caller['class'])){
            $dir = explode('\\Controllers\\',$caller_file)[0].'/views/templates/';
        }else{
            if(!empty(\PA::$config['modules'])){
                $dir = VIEW_DIR . explode('\\',$caller['class'])[0] . '/views/templates/';
            }else{
                $dir = VIEW_DIR;
            }
        }
        return $dir;
    }
    public function jsonOut($data){
        $this->response->setContentType('application/json;charset=utf8');
        echo json_encode($data,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}