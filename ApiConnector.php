<?php
  
  namespace apiconnector;
  
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
    
    /** @var string  */
    const _LOF_FILE = '/log*';
    
    /** @var int  */
    const _LOG_FOR_SECONDS = 7200;
    
    const _NUMBER_OF_THREADS = 8;
    
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
    
    private static $cookieIndex = 0;
    
    public function __construct($apiUrl, $apiKey) {
      $this->apiUrl = $apiUrl;
      $this->apiKey = $apiKey;
  
      $this->token = @file_get_contents(__DIR__ . '/lasttoken');
      self::$cookieIndex++;
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
  
      if ($response && array_key_exists('success', $response) && $response['success']) {
        $this->state = $response['success'];
        $this->token = $response['token'];
        file_put_contents(__DIR__ . '/lasttoken', $this->token);
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']);
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "ApiConnector-proclient-ver." . self::VERSION);
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/curl-cookies' . self::$cookieIndex . '.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/curl-cookies' . self::$cookieIndex . '.txt');
        curl_setopt($ch, CURLOPT_POST, count($paramsArray));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paramsArray));
        
        //execute post
        $result             = curl_exec($ch);
        $this->lastResponse = ['content' => $result, 'headers' => curl_getinfo($ch)];
      
      //close connection
      curl_close($ch);
      $decodedResult = json_decode($result, $responseAsArray);
      if ($decodedResult['status'] == FALSE) {
        $countTry--;
        if ($this->login($countTry)) {
          $decodedResult = $this->callApi($url, $paramsArray, $responseAsArray);
        }
      }

      return $decodedResult;
    }

    public function callApiJson($url, $paramsArray = [], $responseAsArray = TRUE, $countTry = 3) {
      return $this->callApi($url, ['data' => json_encode($paramsArray)], $responseAsArray, $countTry);
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