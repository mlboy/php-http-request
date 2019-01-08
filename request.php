<?php

/**
 * Created by PhpStorm.
 * User: maliang
 * Date: 16/7/6
 * Time: 上午10:55
 */
namespace Http;
class request
{
    private static $instance;
    private $handle;
    private $curlOpts = array();
    private $socketTimeout = 2000;
    private $connectTimeout = 2000;
    private $verifyPeer = true;
    private $verifyHost = true;
    private $cookie = null;
    private $cookieFile = null;
    private $auth = array (
        'user' => '',
        'pass' => '',
        'method' => CURLAUTH_BASIC
    );
    private $proxy = array(
        'port' => false,
        'tunnel' => false,
        'address' => false,
        'type' => CURLPROXY_HTTP,
        'auth' => array (
            'user' => '',
            'pass' => '',
            'method' => CURLAUTH_BASIC
        )
    );
    private $reqBody = array();
    private $reqHeaders = array();

    public static function get($url, $headers = array(), $parameters = null, $username = null, $password = null) {
        return self::send('GET', $url, $parameters, $headers, $username, $password);
    }
    public static function head($url, $headers = array(), $parameters = null, $username = null, $password = null) {
        return self::send('HEAD', $url, $parameters, $headers, $username, $password);
    }
    public static function options($url, $headers = array(), $parameters = null, $username = null, $password = null) {
        return self::send('OPTIONS', $url, $parameters, $headers, $username, $password);
    }
    public static function connect($url, $headers = array(), $parameters = null, $username = null, $password = null) {
        return self::send('CONNECT', $url, $parameters, $headers, $username, $password);
    }
    public static function post($url, $headers = array(), $body = null, $username = null, $password = null) {
        return self::send('POST', $url, $body, $headers, $username, $password);
    }
    public static function delete($url, $headers = array(), $body = null, $username = null, $password = null) {
        return self::send('DELETE', $url, $body, $headers, $username, $password);
    }
    public static function put($url, $headers = array(), $body = null, $username = null, $password = null){
        return self::send('PUT', $url, $body, $headers, $username, $password);
    }
    public static function patch($url, $headers = array(), $body = null, $username = null, $password = null) {
        return self::send('PATCH', $url, $body, $headers, $username, $password);
    }
    public static function trace($url, $headers = array(), $body = null, $username = null, $password = null) {
        return self::send('TRACE', $url, $body, $headers, $username, $password);
    }
    public static function instance($bool = true){
        if (!self::$instance && $bool) {
            return self::$instance = new self;
        } else {
            return self::$instance;
        }
    }
    public static function send($method, $url, $reqBody = null, $headers = array(), $username = null, $password = null) {
        self::instance()->reqHeaders = $headers;
        self::instance()->reqBody = $reqBody;
        self::instance()->handle = curl_init();
        curl_setopt_array(self::instance()->handle, self::instance()->curlOpts);
        if ($method !== 'GET') {
            curl_setopt(self::instance()->handle, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt(self::instance()->handle, CURLOPT_POSTFIELDS, self::instance()->formatBody($reqBody));
        } else if (is_array($reqBody)) {
            if (strpos($url, '?') !== false) {
                $url .= '&';
            } else {
                $url .= '?';
            }
            $url .= http_build_query($reqBody);
        }
        curl_setopt_array(self::instance()->handle, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true, // 是否返回内容
            CURLOPT_FOLLOWLOCATION => true, // 是否会自动301,302跳转
            CURLOPT_MAXREDIRS => 10,        // 跳转请求的最大次数
            CURLOPT_HTTPHEADER => self::instance()->formatHeaders($headers),
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => self::instance()->verifyPeer,
            CURLOPT_SSL_VERIFYHOST => self::instance()->verifyHost === false ? 0 : 2,
            CURLOPT_ENCODING => '' // 编码 空 会自动发送所有
        ));
        if (self::instance()->socketTimeout !== null) {
            curl_setopt(self::instance()->handle, CURLOPT_TIMEOUT_MS, self::instance()->socketTimeout);
        }
        if (self::instance()->connectTimeout !== null) {
            curl_setopt(self::instance()->handle, CURLOPT_CONNECTTIMEOUT_MS, self::instance()->connectTimeout);
        }
        //在超时时间小于1000ms时候自动开启nosignal用来解决鸟哥说的那个bug
        if (self::instance()->connectTimeout <= 1000) {
            curl_setopt(self::instance()->handle, CURLOPT_NOSIGNAL, 1);
        }
        if (self::instance()->cookie) {
            curl_setopt(self::instance()->handle, CURLOPT_COOKIE, self::instance()->cookie);
        }
        if (self::instance()->cookieFile) {
            curl_setopt(self::instance()->handle, CURLOPT_COOKIEFILE, self::instance()->cookieFile);
            curl_setopt(self::instance()->handle, CURLOPT_COOKIEJAR, self::instance()->cookieFile);
        }
        // 基本 auth
        if (!empty($username)) {
            curl_setopt_array(self::instance()->handle, array(
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $username . ':' . $password
            ));
        }
        // 自定义 auth
        if (!empty(self::instance()->auth['user'])) {
            curl_setopt_array(self::instance()->handle, array(
                CURLOPT_HTTPAUTH    => self::instance()->auth['method'],
                CURLOPT_USERPWD     => self::instance()->auth['user'] . ':' . self::instance()->auth['pass']
            ));
        }
        //代理
        if (self::instance()->proxy['address'] !== false) {
            curl_setopt_array(self::instance()->handle, array(
                CURLOPT_PROXYTYPE       => self::instance()->proxy['type'],
                CURLOPT_PROXY           => self::instance()->proxy['address'],
                CURLOPT_PROXYPORT       => self::instance()->proxy['port'],
                CURLOPT_HTTPPROXYTUNNEL => self::instance()->proxy['tunnel'],
                CURLOPT_PROXYAUTH       => self::instance()->proxy['auth']['method'],
                CURLOPT_PROXYUSERPWD    => self::instance()->proxy['auth']['user'] . ':' . self::instance()->proxy['auth']['pass']
            ));
        }
        $startTime = microtime(true);

        $response   = curl_exec(self::instance()->handle);
        $useTime = microtime(true) - $startTime;
        $error      = curl_error(self::instance()->handle);
        $info       = curl_getinfo(self::instance()->handle);

        $header_size = $info['header_size'];
        $header      = substr($response, 0, $header_size);
        $body        = substr($response, $header_size);
        $httpCode    = $info['http_code'];
        if ($error) {
            $errno      = curl_errno(self::instance()->handle);
            //throw new EasyrestException($error,$errno);
            //可能是超时之类的可以记录日志
            $body = $error.' (NAMELOOKUP_TIME:' . $info['namelookup_time'] . ' CONNTION_TIME:' . $info['connect_time'] . ' TOTAL_TIME:'. $info['total_time'].')';
            $httpCode = - $errno;
        }
        $info['params'] = $reqBody;
        return self::instance()->response($httpCode, $body, $header,$info);
    }
    private function formatHeaders($headers) {
        $formatHeaders = array();
        $headers = array_change_key_case($headers,CASE_LOWER);
        foreach ($headers as $key => $val) {
            $formatHeaders[] = self::instance()->formatKV(trim(strtolower($key)), $val,': ');
        }
        if (!array_key_exists('user-agent', $headers)) {
            $formatHeaders[] = 'user-agent: easyrest-by-php/1.0';
        }
        if (!array_key_exists('expect', $headers)) {
            $formatHeaders[] = 'expect:';
        }
        return $formatHeaders;
    }
    private function formatBody($body,$parent = false) {
        if (is_array($body)) {

            if (
                isset(self::instance()->reqHeaders)
                && isset(self::instance()->reqHeaders['Content-Type'])
                && strtolower(self::instance()->reqHeaders['Content-Type']) == 'application/x-www-form-urlencoded'
            ) {
                return http_build_query($body);
            }
            $result = array();
            foreach ($body as $key => $value) {
                $k = ($parent)?sprintf('%s[%s]', $parent, $key):$key;
                if (!$value instanceof \CURLFile && is_array($value)) {
                    $result = array_merge($result, self::instance()->formatBody($value, $k));
                } else {
                    $result[$k] = $value;
                }
            }
            return $result;
        } else {
            return $body;
        }

    }
    private function formatKV($key,$val,$placeholder = '') {
        return $key . $placeholder . $val;
    }
    private function response($status, $body, $header,$info = null) {
        return new response($status, $body, $header,$info);
    }
    public static function addOpts($key,$val = null) {
        self::instance()->curlOpts[$key] = $val;
        return self::instance();
    }
    public static function setOpts($key,$val = null) {
        self::instance()->curlOpts[$key] = $val;
        return self::instance();
    }
    public static function setTimeout($time) {
        self::instance()->socketTimeout = $time;
        return self::instance();
    }
    public static function setConnectTimeout($time) {
        self::instance()->connectTimeout = $time;
        return self::instance();
    }
    public static function setVerify($Peer = true,$Host = true) {
        self::instance()->verifyPeer = $Peer;
        self::instance()->verifyHost = $Host;
        return self::instance();
    }
    public static function setCookie($cookie) {
        self::instance()->cookie = $cookie;
        return self::instance();
    }
    public static function cookieFile($cookieFile) {
        self::instance()->$cookieFile = $cookieFile;
        return self::instance();
    }
    public static function auth($username = '', $password = '', $method = CURLAUTH_BASIC) {
        self::instance()->auth['user'] = $username;
        self::instance()->auth['pass'] = $password;
        self::instance()->auth['method'] = $method;
        return self::instance();
    }
    public static function proxy($address, $port = 1080, $type = CURLPROXY_HTTP, $tunnel = false) {
        self::instance()->proxy['type'] = $type;
        self::instance()->proxy['port'] = $port;
        self::instance()->proxy['tunnel'] = $tunnel;
        self::instance()->proxy['address'] = $address;
    }
    public static function proxyAuth($username = '', $password = '', $method = CURLAUTH_BASIC) {
        self::instance()->proxy['auth']['user'] = $username;
        self::instance()->proxy['auth']['pass'] = $password;
        self::instance()->proxy['auth']['method'] = $method;
    }
    /**
     * Prepares a file for upload. To be used inside the parameters declaration for a request.
     * @param string $filename The file path
     * @param string $mimetype MIME type
     * @param string $postname the file name
     * @return string|\CURLFile
     */
    public static function File($filename, $mimetype = '', $postname = '')
    {
        if (class_exists('CURLFile')) {
            return new \CURLFile($filename, $mimetype, $postname);
        }
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename, $mimetype, $postname);
        }
        return sprintf('@%s;filename=%s;type=%s', $filename, $postname ?: basename($filename), $mimetype);
    }
    public static function Multipart($data, $files = false)
    {
        if (is_object($data)) {
            return get_object_vars($data);
        }
        if (!is_array($data)) {
            return array($data);
        }
        if ($files !== false) {
            foreach ($files as $name => $file) {
                $data[$name] = call_user_func(array(__CLASS__, 'File'), $file);
            }
        }
        return $data;
    }
}
class response {
    public $status = null;
    public $body = null;
    public $header = null;
    public $info = null;
    public function __construct($status = null,$body = null ,$header = null,$info = null) {
        $this->status = $status;
        $this->body = $body;
        $this->header = $header;
        $this->info = $info;
    }
    public function url() {
        if(isset($this->info['url'])) {
            return $this->info['url'];
        }
        return null;
    }
    public function params() {
        if(isset($this->info['params'])) {
            return $this->info['params'];
        }
        return null;
    }
    public function info() {
        if(isset($this->info)) {
            return $this->info;
        }
        return null;
    }
    public function status() {
        return $this->status;
    }
    public function body() {
        return $this->body;
    }
    public function header() {
        return $this->header;
    }
    public function data() {
        $json = json_decode($this->body,true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return array();
        }
        return $json;
    }
}
