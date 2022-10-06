<?php
  
  namespace apiconnector;
  
  use PF\helpers\MyArray;
  
  /**
   * Třída Connector slouží pro komunikaci s API e-shopů.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   20.02.2019
   */
  class Connector
  {
    /** @var null|string  */
    private $apiUrl = NULL;
    
    /** @var null|string  */
    private $apiKey = NULL;
    
    /** @var string  */
    private $token = '';
    
    /** @var string  */
    private $state = 'Not Connected';
    
    /** @var array  */
    private $lastResponse = [];
    
    /** @var int  */
    private $requestTimeout = 30;
    
    /** @var null|string */
    private $principal = NULL;
    
    /** @var null|string */
    private $requestMethod = NULL;
  
    private $tmpDir = NULL;
  
    private $lastTokenFile = '';
    private $cookieFile = '';
    private $logged = FALSE;
  
    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';
    const METHOD_DELETE = 'DELETE';
  
    private static $VERSION = '___VERSION_N/A___';
    
    public static $allowedMethods = [
      self::METHOD_GET, self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE,
    ];
  
    public static $TEST = 'a';
    
    private static $cookieIndex = '';
    
    /**
     * Connector constructor.
     *
     * @param $apiUrl
     * @param $apiKey
     * @param $tmpDir
     */
    public function __construct($apiUrl, $apiKey, $tmpDir = NULL) {
      $this->apiUrl = $apiUrl;
      $this->apiKey = $apiKey;
      
      self::$cookieIndex   = md5(json_encode([$apiUrl, $apiKey]));
      $this->tmpDir        = rtrim(((is_null($tmpDir)) ? sys_get_temp_dir() : $tmpDir), '/') . '/';
      $this->lastTokenFile = $this->tmpDir . 'lasttoken_' . self::$cookieIndex;
      $this->cookieFile    = $this->tmpDir . 'cookies_' . self::$cookieIndex;
      $this->token         = @file_get_contents($this->lastTokenFile);
  
      $v = filemtime(__DIR__);
      self::$VERSION = date('Y.md.His', $v);
    }
    
    /**
     * Zajistí login do API.
     *
     * @param $countTry
     *
     * @return bool
     */
    private function login($countTry) {
      $success  = FALSE;
      $response = (array)$this->callApi('login', ['key' => $this->apiKey], TRUE, $countTry);
      
      if ($response &&  ($status = MyArray::init($response)->item('status',  MyArray::init($response)->item('success', FALSE)))) {
        $this->state = (bool)$status;
        $this->token = $response['token'];
        file_put_contents($this->lastTokenFile, $this->token);
        $success = TRUE;
      }
      
      return $success;
    }
    
    /**
     * Zajištujě zavolání API metody.
     *
     * @param       $url
     * @param array $paramsArray
     * @param bool  $responseAsArray
     * @param int   $countTry
     *
     * @return mixed
     */
    public function callApi ($url, $paramsArray = [], $responseAsArray = TRUE, $countTry = 3) {
      /** @var string $link */
      $link = $this->apiUrl . $url . (($this->token) ? '&token=' . $this->token : '');
  
      $sendHeaders = [
        'Accept-Encoding: gzip, deflate',
        'Cache-Control: no-cache',
        'Connection: Keep-Alive',
      ];
  
      if (!is_null($this->principal)) {
        $sendHeaders[] = 'PC-Principal: ' . $this->principal;
    
        $this->principal = NULL;
      }
      
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $link);
      
      $sourceRequestMethod  = NULL;
      if ($this->requestMethod) {
        if (!in_array($this->requestMethod, self::$allowedMethods)) {
          throw new \Exception('Call method `' . $this->requestMethod . '` is not allowed!');
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->requestMethod);
        $sourceRequestMethod = $this->requestMethod;
        
        $this->requestMethod = NULL;
      }
      
      if (is_array($paramsArray)) {
        curl_setopt($ch, CURLOPT_POST, count($paramsArray));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramsArray));
      } elseif (is_string($paramsArray)) {
        $sendHeaders[] = 'Accept: application/json';
        $sendHeaders[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsArray);
      } else {
        $sendHeaders[] = 'Accept: application/json';
      }
      
      curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders);
      curl_setopt($ch, CURLOPT_ENCODING, "gzip");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
      curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
      curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($ch, CURLOPT_USERAGENT, "ApiConnector-proclient-ver. " . self::getVersion());
      curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
      curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
      
      //execute post
      $result             = curl_exec($ch);
      $this->lastResponse = ['content' => $result, 'headers' => curl_getinfo($ch)];
      
      //close connection
      curl_close($ch);
      $decodedResult = json_decode($result, $responseAsArray);
      if ($decodedResult) {
        $decodedResultArr = MyArray::init($decodedResult);
        $lastResponseArr = MyArray::init($this->lastResponse);
        
        $this->logged = !in_array((int)$lastResponseArr->item(['headers', 'http_code'], 500), [401, 403]);
        
        if ($countTry > 0 && $decodedResultArr->item('status', $decodedResultArr->item('success', FALSE)) == FALSE && !$this->logged) {
          $countTry--;
          if ($this->login($countTry)) {
            $this->logged = TRUE;
            $this->setRequestMethod($sourceRequestMethod);
            $decodedResult = $this->callApi($url, $paramsArray, $responseAsArray);
          }
        }
        
        $result = $decodedResult;
      }
      
      return $result;
    }
    
    /**
     * Zajištujě zavolání API metody a data zabalí do JSON a za gzipuje.
     *
     * @param       $url
     * @param array $paramsArray
     * @param bool  $responseAsArray
     *
     * @return mixed|string
     */
    public function callApiJson($url, $paramsArray = [], $responseAsArray = TRUE) {
      $response = $this->callApi($url, [json_encode($paramsArray)], $responseAsArray);
      
      if (is_string($response) && ($data = @json_decode($response, TRUE))) {
        $response = $data;
        unset($data);
      }
      
      return $response;
    }
    
    /**
     * @param            $url
     * @param array|NULL $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function callJson($url, $data = NULL) {
      if (!is_null($data)) {
        $data = json_encode($data);
      }
      return $this->callApi($url, $data);
    }
    
    /** @return array */
    public function getLastResponse() { return $this->lastResponse; }
    /** @return false|string */
    public function getToken() { return $this->token; }
    /** @return string */
    public function getState() { return $this->state; }
    
    private function log ($url, $data) {
      // TODO: pouzit Logger, který již máme hotový
    }
    
    /**
     * @deprecated
     * @throws \Exception
     */
    public function connect() { throw new \Exception('Method ' . __METHOD__ . ' is deprecated. Now connect auto.'); }
    
    /**
     * @deprecated
     * @throws \Exception
     */
    public function isAsync() { throw new \Exception('Method ' . __METHOD__ . ' is deprecated.'); }
    
    /**
     * @deprecated
     * @throws \Exception
     */
    public function setDev() { throw new \Exception('Method ' . __METHOD__ . ' is deprecated. For Dev create new instance of ' . __CLASS__); }
  
    /**
     * @param $requestTimeout
     *
     * @return $this
     */
    public function setRequestTimeout($requestTimeout) {
      $this->requestTimeout = $requestTimeout;
    
      return $this;
    }
  
    /**
     * @param $principal
     *
     * @return $this
     */
    public function setPrincipal($principal) {
      $this->principal = $principal;
  
      return $this;
    }
  
    /**
     * @param $method
     *
     * @return $this
     */
    public function setRequestMethod($method) {
      $this->requestMethod = $method;
      
      return $this;
    }
  
    /**
     * Vrátí aktuální verzi konektoru.
     * @return string
     */
    public static function getVersion() {
      return self::$VERSION;
    }
  }