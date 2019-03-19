<?php
  
  namespace apiconnector;
  
  use PF\helpers\MyArray;
  use PF\helpers\MyString;
  use PF\helpers\types\JSON;

  /**
   * Třída Response.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   20.02.2019
   */
  class Response
  {
    public static $mandatoryProperties = [
      'generate_time',
      'errors',
      'success',
      'data',
    ];
    
    /** @var JSON  */
    private $json;
  
    /**
     * Response constructor.
     *
     * @param array|string $data
     */
    public function __construct($data) {
      $this->json = new JSON($data);
      foreach ($data as $key => $itemData) {
        $method = 'set' . MyString::camelize($key);
        if (method_exists($this, $method)) {
          $this->{$method}($itemData);
        }
      }
      
      $this->checkMandatory();
    }
  
    /**
     * Zkontroluje povinné položky.
     *
     * @throws \InvalidArgumentException
     */
    public function checkMandatory() {
      foreach (self::$mandatoryProperties as $property) {
        if (is_null(MyArray::init($this->json)->item($property))) {
          throw new \InvalidArgumentException("Mandatory key `{$property}` is not set.");
        }
      }
    }
  
    /**
     * @param float $generateTime
     */
    private function setGenerateTime($generateTime) {
      if (!is_float($generateTime)) { throw new \InvalidArgumentException('Property `generateTime` must be `float` type.'); }
      $this->json['generate_time'] = $generateTime;
    }
  
    /**
     * @param array $errors
     */
    private function setErrors($errors) {
      if (!is_array($errors)) { throw new \InvalidArgumentException('Property `errors` must be `array` type.'); }
      $this->json['errors'] = $errors;
    }
  
    /**
     * @param array $errors
     */
    private function setError($error) {
      if (!is_string($error)) { throw new \InvalidArgumentException('Property `error` must be `string` type.'); }
      $this->json['errors'] = $error;
    }
  
    /**
     * @param boolean $success
     */
    private function setSuccess($success) {
      if (!is_bool($success)) { throw new \InvalidArgumentException('Property `success` must be `boolean` type.'); }
      $this->json['success'] = $success;
    }
  
    /**
     * @param boolean $status
     */
    private function setStatus($status) {
      if (!is_bool($status)) { throw new \InvalidArgumentException('Property `status` must be `boolean` type.'); }
      $this->json['success'] = $status;
    }
  
    /**
     * @param array $data
     */
    private function setData($data) {
      if (!is_array($data)) { throw new \InvalidArgumentException('Property `data` must be `array` type.'); }
      $this->json['data'] = $data;
    }
  
    /**
     * @param string $token
     */
    private function setToken($token) {
      if (!is_string($token)) { throw new \InvalidArgumentException('Property `token` must be `string` type.'); }
      $this->json['token'] = $token;
    }
  
    /**
     * @return float
     */
    public function getGenerateTime() {
      return $this->json['generate_time'];
    }
  
    /**
     * @return array
     */
    public function getErrors() {
      return $this->json['errors'];
    }
  
    /**
     * @return boolean
     */
    public function getSuccess() {
      return $this->json['success'];
    }
  
    /**
     * @return array
     */
    public function getData() {
      return $this->json['data'];
    }
  
    /**
     * @return string|NULL
     */
    public function getToken() {
      return MyArray::init($this->json)->item('token', NULL);
    }
  
    /**
     * @return false|string
     */
    public function __toString() {
      $this->checkMandatory();
      
      return (string)$this->json;
    }
  }