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
    /** @var string Verze */
    const VERSION = '1.0.0';
    
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
    
    private static $cookieIndex = '';
    
    private $tmpDir = NULL;
  
    private $lastTokenFile = '';
    private $logged = FALSE;
  
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
  
      self::$cookieIndex = md5(var_export($apiUrl, TRUE) . '_' . var_export($apiKey, TRUE));
      $this->tmpDir        = rtrim(((is_null($tmpDir)) ? sys_get_temp_dir() : $tmpDir), '/') . '/';
      $this->lastTokenFile = $this->tmpDir . 'lasttoken_' . self::$cookieIndex;
      $this->token         = @file_get_contents($this->lastTokenFile);
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
      $response = $this->callApi('login', ['key' => $this->apiKey], TRUE, $countTry);
  
      if ($response && array_key_exists('token', $response) && $response['token']) {
        $this->state = (bool)$response['token'];
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
  
      $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Expect:',
          'Accept-Encoding: gzip, deflate'
        ]);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "ApiConnector-proclient-ver." . self::VERSION);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->tmpDir . 'curl-cookies' . self::$cookieIndex . '.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->tmpDir . 'curl-cookies' . self::$cookieIndex . '.txt');
        curl_setopt($ch, CURLOPT_POST, count($paramsArray));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramsArray));
        
        //execute post
        $result             = curl_exec($ch);
        $this->lastResponse = ['content' => $result, 'headers' => curl_getinfo($ch)];
  
        //close connection
        curl_close($ch);
        $decodedResult = json_decode($result, $responseAsArray);
        if ($decodedResult) {
          $decodedResultArr = MyArray::init($decodedResult);
          if ($countTry > 0 && $decodedResultArr->item('status', $decodedResultArr->item('success', FALSE)) == FALSE && !$this->logged) {
            $countTry--;
            if ($this->login($countTry)) {
              $this->logged = TRUE;
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
  
      if (($data = @json_decode($response, TRUE))) {
        $response = $data;
        unset($data);
      }
      
      return $response;
    }

    /** @return array */
    public function getLastResponse() { return $this->lastResponse; }
    /** @param $requestTimeout */
    public function setRequestTimeout($requestTimeout) { $this->requestTimeout = $requestTimeout; }
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
  }