<?php
  
  namespace apiconnector;
  
  use PF\helpers\MyArray;
  use PF\helpers\MyString;
  use PF\helpers\types\JSON;
  use Traversable;

  /**
   * Třída Response.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   20.02.2019
   */
  class Response implements \IteratorAggregate, \ArrayAccess, \Countable
  {
    public static $mandatoryProperties = [
      'datetime_stamp',
      'generate_time',
      'success',
//      'errors',
//      'data',
    ];
    
    /** @var JSON  */
    private $json;
    private $arrayData = [];
  
    /**
     * Response constructor.
     *
     * @param array|string $data
     */
    public function __construct($data) {
      $this->json = new JSON($data);
      foreach ($this->json as $key => $itemData) {
        $method = 'set' . MyString::camelize($key);
        if (method_exists($this, $method)) {
          $this->{$method}($itemData);
        }
      }
      
      $this->checkMandatory();
      
      if ($this->getErrors()) {
        $this->arrayData = $this->getData();
      }
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
      if (!is_numeric($generateTime)) { throw new \InvalidArgumentException('Property `generate_time` must be `float`|`int` type.'); }
      $this->json['generate_time'] = $generateTime;
    }
    
    private function setDatetimeStamp($datetimeStamp) {
      if (!($datetimeStamp = \DateTime::createFromFormat('Y-m-d H:i:s', $datetimeStamp))) { throw new \InvalidArgumentException('Property `datetime_stamp` must be in format `Y-m-d H:i:s`.'); }
      $this->json['datetime_stamp'] = $datetimeStamp;
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
      $this->json['status'] = $success;
    }
  
    /**
     * @param boolean $status
     */
    private function setStatus($status) {
      if (!is_bool($status)) { throw new \InvalidArgumentException('Property `status` must be `boolean` type.'); }
      $this->json['status'] = $status;
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
     * @return float
     */
    public function getDatetimeStamp() {
      return $this->json['datetime_stamp'];
    }
  
    /**
     * @return array
     */
    public function getErrors() {
      return MyArray::init($this->json)->item('errors', []);
    }
  
    /**
     * @return boolean
     */
    public function getStatus() {
      return $this->json['status'];
    }
  
    /**
     * @return array
     */
    public function getData() {
      return MyArray::init($this->json)->item('data', []);
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
      
      return (string)$this->json;;
    }
  
    public function getIterator() {
      return new \ArrayIterator($this->arrayData);
    }
    
    public function offsetExists($offset) {
      return array_key_exists($offset, $this->arrayData);
    }
    
    public function offsetGet($offset) {
      return $this->arrayData[$offset];
    }
    
    public function offsetSet($offset, $value) {
      $this->arrayData[$offset] = $value;
    }
    
    public function offsetUnset($offset) {
      unset($this->arrayData[$offset]);
    }
    
    public function serialize() {
      return $this->__toString();
    }
  
    /**
     * @param string $serialized json
     *
     * @return Response
     */
    public static function unserialize($serialized) {
      return new self($serialized);
    }
    
    public function count($mode = COUNT_NORMAL){
      return count($this->arrayData, $mode);
    }
  }