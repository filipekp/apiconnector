<?php
  
  namespace apiconnector;
  
  use PF\helpers\MyString;

  /**
   * Třída Response.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   20.02.2019
   */
  class Response
  {
    private $generateTime = 0;
    private $errors = [];
    private $success = FALSE;
    private $data = [];
    private $token = FALSE;
  
    public function __construct($data) {
      foreach ($data as $property => $value) {
        $classProperty = lcfirst(MyString::camelize($property));
        if (property_exists($this, $classProperty)) {
          $this->{$classProperty} = $value;
        }
      }
    }
  
    public function getGenerateTime() { return $this->generateTime; }
    public function getErrors() { return $this->errors; }
    public function getSuccess() { return $this->success; }
    public function getData() { return $this->data; }
    public function getToken() { return $this->token; }
  }