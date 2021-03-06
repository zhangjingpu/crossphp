<?php
/**
 * Cross - a micro PHP 5 framework
 *
 * @link        http://www.crossphp.com
 * @license     http://www.crossphp.com/license
 * @version     1.0.5
 */
namespace Cross\MVC;

use Cross\Core\FrameBase;
use Cross\Core\Helper;
use Cross\Core\Response;
use Cross\Core\Router;
use Cross\Exception\CoreException;
use Cross\Lib\ArrayOperate\Array2XML;

/**
 * @Auth: wonli <wonli@live.com>
 * Class View
 * @package Cross\MVC
 */
class View extends FrameBase
{
    /**
     * 模板数据
     *
     * @var array
     */
    protected $data;

    /**
     * layer字面量
     *
     * @var array
     */
    protected $set;

    /**
     * url配置
     *
     * @var array
     */
    protected $url_config;

    /**
     * 资源配置
     *
     * @var array
     */
    protected $res_list;

    /**
     * 默认模板目录
     *
     * @var string
     */
    protected $tpl_dir;

    /**
     * 默认模板路径
     *
     * @var string
     */
    protected $tpl_base_path;

    /**
     * 默认连接
     *
     * @var string
     */
    protected $link_base = null;

    /**
     * 静态资源路径
     *
     * @var string
     */
    protected $static_url;

    /**
     * 模版扩展文件名
     *
     * @var string
     */
    protected $tpl_file_ext_name = '.tpl.php';

    /**
     * 路由别名配置
     *
     * @var array
     */
    protected static $router_alias = array();

    /**
     * 初始化视图
     */
    function __construct()
    {
        parent::__construct();

        $this->url_config = $this->config->get("url");
        $this->static_url = $this->config->get("sys", "static_url");

        $this->set("static_url", $this->static_url);
    }

    /**
     * 模板的绝对路径
     *
     * @param $tpl_name
     * @param bool $get_content 是否读取模板内容
     * @return string
     */
    function tpl($tpl_name, $get_content = false)
    {
        $file_path = $this->getTplPath() . $tpl_name . $this->tpl_file_ext_name;
        if (true === $get_content) {
            return file_get_contents($file_path, true);
        }

        return $file_path;
    }

    /**
     * 载入模板, 并输出$data变量中的数据
     *
     * @param $tpl_name
     * @param array|mixed $data
     */
    function renderTpl($tpl_name, $data = array())
    {
        include $this->tpl($tpl_name);
    }

    /**
     * 设置layer附加参数
     *
     * @param $name
     * @param null $value
     * @return $this
     */
    final function set($name, $value = null)
    {
        if (is_array($name)) {
            $this->set = array_merge($this->set, $name);
        } else {
            $this->set[$name] = $value;
        }

        return $this;
    }

    /**
     * 生成资源文件路径
     *
     * @param $res_url
     * @param bool $use_static_url
     * @return string
     */
    function res($res_url, $use_static_url = true)
    {
        if (defined('RES_BASE_URL')) {
            $res_base_url = RES_BASE_URL;
        } elseif ($use_static_url) {
            $res_base_url = $this->static_url;
        } else {
            $res_base_url = SITE_URL;
        }

        return rtrim($res_base_url, "/") . '/' . $res_url;
    }

    /**
     * 获取生成连接的基础路径
     *
     * @return string
     */
    function getLinkBase()
    {
        if (null === $this->link_base) {
            $this->setLinkBase($this->config->get("sys", "site_url"));
        }

        return $this->link_base;
    }

    /**
     * 设置生成的连接基础路径
     *
     * @param $link_base
     */
    function setLinkBase($link_base)
    {
        $this->link_base = $link_base;
    }

    /**
     * 模板路径
     *
     * @return string 要加载的模板路径
     */
    function getTplPath()
    {
        return rtrim($this->getTplBasePath() . $this->getTplDir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * app 默认模板路径
     *
     * @return string
     */
    function getAppDefaultTplPath()
    {
        $current_app_name = $this->config->get('sys', 'current_app_name');
        if ($current_app_name) {
            return APP_PATH_DIR . $current_app_name . DIRECTORY_SEPARATOR . 'templates';
        }

        return APP_PATH . 'templates';
    }

    /**
     * 设置模板路径
     *
     * @param $tpl_base_path
     */
    function setTplBasePath($tpl_base_path)
    {
        $this->tpl_base_path = rtrim($tpl_base_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取模板默认路径
     *
     * @return string
     */
    function getTplBasePath()
    {
        if (!$this->tpl_base_path) {
            $this->setTplBasePath($this->getAppDefaultTplPath());
        }

        return $this->tpl_base_path;
    }

    /**
     * 生成连接
     *
     * @param null $controller 控制器:方法
     * @param null $params
     * @param bool $sec
     * @return string
     */
    function link($controller = null, $params = null, $sec = false)
    {
        $url = $this->getLinkBase();
        $_url_ext = $this->url_config['ext'];
        $url_controller = '';
        if ($controller) {
            $url_controller = $this->makeController($controller);
        }

        $url_params = '';
        if ($params != null) {
            $url_params = $this->makeParams($params, $sec);
        }

        if ($_url_ext) {
            switch($this->url_config['type'])
            {
                case 2:
                    $url .= $url_controller.$_url_ext.$url_params;
                    break;
                case 1:
                case 3:
                case 4:
                    $url .= $url_controller.$url_params.$_url_ext;
                    break;
            }
        } else {
            $url .= $url_controller.$url_params;
        }

        return $url;
    }

    /**
     * 生成参数加密的连接
     *
     * @param null $_controller
     * @param null $params
     * @return string
     */
    function slink($_controller = null, $params = null)
    {
        return $this->link($_controller, $params, true);
    }

    /**
     * 生成控制器连接
     *
     * @param $controller
     * @throws \Cross\Exception\CoreException
     * @return string
     */
    private function makeController($controller)
    {
        $_link_url = '/';
        $this->getControllerAlias($controller, $_controller, $_action);

        if ($this->url_config ['rewrite']) {
            $_link_url .= $_controller;
        } else {
            $index = $this->url_config ['index'];

            switch ($this->url_config['type']) {
                case 1:
                case 3:
                    if (strcasecmp($index, 'index.php') == 0) {
                        $_dot = '?';
                    } else {
                        $_dot = $index . '?';
                    }
                    break;

                case 2:
                case 4:
                    $_dot = $index;
                    break;

                default:
                    throw new CoreException('不支持的url type');
            }

            $_link_url .= $_dot . '/' . $_controller;
        }

        if (null != $_action) {
            $_link_url .= $this->url_config['dot'] . $_action;
        }

        return $_link_url;
    }

    /**
     * 解析控制器别名
     *
     * @param string $controller
     * @param string $_controller
     * @param string $_action
     */
    private function getControllerAlias($controller, & $_controller, & $_action)
    {
        $alias_config = $this->parseControllerAlias();

        if (isset($alias_config[$controller])) {
            $_controller = $alias_config[$controller];
        } else {
            $_action = null;
            if (false !== strpos($controller, ':')) {
                list($_controller, $_action) = explode(':', $controller);
            } else {
                $_controller = $controller;
            }

            if (isset($alias_config[$_controller])) {
                $controller_action_alias_config = $alias_config[$_controller];
                if (isset($controller_action_alias_config[$_action])) {
                    $_action = $controller_action_alias_config[$_action];
                }
            }
        }
    }

    /**
     * 解析路由别名配置
     *
     * @return array
     */
    private function parseControllerAlias()
    {
        if (empty(self::$router_alias)) {
            $router = $this->config->get('router');
            if (! empty($router)) {
                foreach($router as $controller_alias => $real_controller) {
                    if (is_array($real_controller)) {
                        self::$router_alias[$controller_alias] = array_flip($real_controller);
                    } else {
                        self::$router_alias[$real_controller] = $controller_alias;
                    }
                }
            }
        }

        return self::$router_alias;
    }

    /**
     * 生成link参数
     *
     * @param $params
     * @param bool $sec
     * @return string
     */
    private function makeParams($params, $sec = false)
    {
        $_params = '';
        $_dot = $this->url_config['dot'];

        if ($params) {
            switch ($this->url_config['type']) {
                case 1:
                    if (is_array($params)) {
                        $_params = implode($this->url_config['dot'], $params);
                    } else {
                        $url_str = array();
                        parse_str($params, $url_str);
                        $_params = implode($this->url_config['dot'], $url_str);
                    }
                    break;

                case 2:
                    $_dot = '?';
                    if (is_array($params)) {
                        $_params = http_build_query($params);
                    } else {
                        $_params = $params;
                    }
                    break;

                case 3:
                case 4:
                    if (!is_array($params)) {
                        $p = array();
                        parse_str($params, $p);
                    } else {
                        $p = $params;
                    }

                    foreach ($p as $p_key => $p_val) {
                        $_params .= sprintf("%s%s%s%s", $p_key, $this->url_config['dot'], $p_val, $this->url_config['dot']);
                    }
                    $_params = rtrim($_params, $this->url_config['dot']);
                    break;
            }

            if (true === $sec) {
                $_params = $this->urlEncrypt($_params);
            }
        }

        return $_dot . $_params;
    }

    /**
     * 渲染模板
     *
     * @param null $data
     * @param null $method
     */
    function display($data = null, $method = null)
    {
        $this->data = $data;
        if ($method === null) {
            $display_type = $this->config->get("sys", "display");
            if ($display_type && $display_type != "HTML") {
                $method = strtoupper($display_type);
            } else {
                $method = $this->action;
            }
        }

        if (!$method) {
            $method = Router::$default_action;
        }

        $this->obRender($data, $method);
    }

    /**
     * 输出带layer的view
     *
     * @param $data
     * @param $method
     */
    function obRender($data, $method)
    {
        ob_start();
        $this->$method($data);
        $this->loadLayer(ob_get_clean());
    }

    /**
     * 设置模板dir
     *
     * @param $dir_name
     */
    function setTplDir($dir_name)
    {
        $this->tpl_dir = $dir_name;
    }

    /**
     * 取得模板路径前缀
     *
     * @return string
     */
    function getTplDir()
    {
        if (!$this->tpl_dir) {
            $default_tpl_dir = $this->config->get('sys', 'default_tpl_dir');
            if (!$default_tpl_dir) {
                $default_tpl_dir = 'default';
            }

            $this->setTplDir($default_tpl_dir);
        }

        if ($this->config->get('sys', 'auto_switch_tpl')) {
            if ($this->is_robot()) {
                return 'spider';
            } elseif ($this->is_mobile()) {
                return 'mobile';
            }
        }

        return $this->tpl_dir;
    }

    /**
     * 判断是否是蜘蛛
     *
     * @return bool
     */
    function is_robot()
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (!empty($agent)) {
            $spiderSite = array("TencentTraveler", "Baiduspider+", "BaiduGame", "Googlebot",
                "msnbot", "Sosospider+", "Sogou web spider", "ia_archiver", "Yahoo! Slurp",
                "YoudaoBot", "Yahoo Slurp", "MSNBot", "Java (Often spam bot)", "BaiDuSpider", "Voila",
                "Yandex bot", "BSpider", "twiceler", "Sogou Spider", "Speedy Spider", "Google AdSense",
                "Heritrix", "Python-urllib", "Alexa (IA Archiver)", "Ask", "Exabot", "Custo",
                "OutfoxBot/YodaoBot", "yacy", "SurveyBot", "legs", "lwp-trivial", "Nutch", "StackRambler",
                "The web archive (IA Archiver)", "Perl tool", "MJ12bot", "Netcraft", "MSIECrawler",
                "WGet tools", "larbin", "Fish search");

            foreach ($spiderSite as $val) {
                if (stripos($agent, $val) !== false) {
                    return $val;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * 判断是否是移动设备
     *
     * @return bool
     */
    function is_mobile()
    {
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
            return true;
        } elseif (strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML") > 0) {
            return true;
        } elseif (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|
            elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|
            phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $_SERVER['HTTP_USER_AGENT'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * 运行时加载css/js
     *
     * @param $res_url
     * @param string $location
     * @param bool $convert
     */
    function addRes($res_url, $location = "header", $convert = true)
    {
        $this->res_list [$location][] = array(
            'url' => $res_url,
            'convert' => $convert
        );
    }

    /**
     * 加载 css|js
     *
     * @param string $location
     * @return string
     */
    function loadRes($location = "header")
    {
        $result = '';
        if (empty($this->res_list) || empty($this->res_list[$location])) {
            return $result;
        }

        if (isset($this->res_list [$location]) && !empty($this->res_list [$location])) {
            $data = $this->res_list [$location];
        }

        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $r) {
                    $result .= $this->outputResLink($r['url'], $r['convert']);
                }
            } else {
                $result .= $this->outputResLink($data);
            }
        }

        return $result;
    }

    /**
     * 输出js/css连接
     *
     * @param $res_link
     * @param bool $make_link
     * @return null|string
     */
    function outputResLink($res_link, $make_link = true)
    {
        $t = Helper::getExt($res_link);
        switch (strtolower($t)) {
            case 'js' :
                $tpl = '<script type="text/javascript" src="%s"></script>';
                break;

            case 'css' :
                $tpl = '<link rel="stylesheet" type="text/css" href="%s"/>';
                break;

            default :
                $tpl = null;
        }

        if (null !== $tpl) {
            if ($make_link) {
                $res_link = $this->res($res_link);
            }

            return sprintf("{$tpl}\n", $res_link);
        }

        return null;
    }

    /**
     * 输出JSON
     *
     * @param $data
     */
    function JSON($data)
    {
        $this->set(
            array("layer" => "json")
        );

        Response::getInstance()->setContentType('json');
        echo json_encode($data);
    }

    /**
     * 输出XML
     *
     * @param $data
     * @param string $root_name
     */
    function XML($data, $root_name = 'root')
    {
        $this->set(
            array("layer" => "xml")
        );

        Response::getInstance()->setContentType('xml');
        $xml = Array2XML::createXML($root_name, $data);

        echo $xml->saveXML();
    }

    /**
     * 加载布局
     *
     * @param string $content
     * @param string $layer_ext
     * @throws CoreException
     */
    function loadLayer($content, $layer_ext = '.layer.php')
    {
        if ($this->set) {
            extract($this->set, EXTR_PREFIX_SAME, "USER_DEFINED");
        }
        $_real_path = $this->getTplPath();

        //运行时>配置>默认
        if (isset($layer)) {
            $layer_file = $_real_path . $layer . $layer_ext;
        } else {
            $layer_file = $_real_path . 'default' . $layer_ext;
        }

        if (!file_exists($layer_file)) {
            throw new CoreException($layer_file . ' layer Not found!');
        }

        include $layer_file;
    }
}


