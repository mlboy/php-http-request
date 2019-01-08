# php-http-request
php http request for curl

## Http request doc

### COMMON OPTION

* Http/request::addOpts($key,$val = null);
* Http/request::setOpts($key,$val = null);
set a curl opts

* Http/request::setTimeout($timeMS);
  set curl timeout ms

* Http/request::setConnectTimeout($timeMS);
  set curl connect timeout ms
  
* Http/request::setVerify($Peer = true,$Host = true);
    set ssh config verifyPeer and verifyHost

* Http/request::setCookie($cookie);
   set cookie jar with string
   
* Http/request::cookieFile($cookieFile);
  set cookie jar with cookie file

* Http/request::auth($username = '', $password = '', $method = CURLAUTH_BASIC);
  set auth (basic)

* Http/request::proxy($address, $port = 1080, $type = CURLPROXY_HTTP, $tunnel = false);
  set proxy for client
 
* Http/request::proxyAuth($username = '', $password = '', $method = CURLAUTH_BASIC);
  set proxy auth for client

### GET METHOD

$response = Http/request::get($url, $headers = array(),$params=null,$parameters = null, $username = null, $password = null);

### POST METHOD

$response = Http/request::post($url, $headers = array(),$body=null,$parameters = null, $username = null, $password = null);

### PUT METHOD

$response = Http/request::put($url, $headers = array(),$body=null,$parameters = null, $username = null, $password = null);

### PATCH METHOD

$response = Http/request::patch($url, $headers = array(),$body=null,$parameters = null, $username = null, $password = null);

### DELETE METHOD

$response = Http/request::delete($url, $headers = array(), $params = null, $username = null, $password = null)

### OPTIOMS METHOD

$response = Http/request::options($url,$header=[],$params=null,$parameters = null, $username = null, $password = null);

### CONNECT METHOD

$response = Http/request::connect($url,$header=[],$params=null,$parameters = null, $username = null, $password = null);

### TRACE METHOD

$response = Http/request::trace($url,$header=[],$params=null,$parameters = null, $username = null, $password = null);

### Upload Field

* Http/request::File($filename, $mimetype = '', $postname = '');

Prepares a file for upload. To be used inside the parameters declaration for a request

     @param string $filename The file path
     @param string $mimetype MIME type
     @param string $postname the file name
     @return string|\CURLFile

### Multipart Field
  
* Http/request::Multipart($data, $files = false);
  

