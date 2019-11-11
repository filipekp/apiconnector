<?php
  
  namespace apiconnector;
  
  use Composer\Script\Event;

  /**
   * Třída VersionControll.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   11.11.2019
   */
  class VersionControll
  {
    public static function postUpdate(Event $event) {
      $composer = $event->getComposer();
      $v = $composer->getPackage()->getPrettyVersion();
      
      $file = __DIR__ . '/Connector.php';
      $fc = file_get_contents($file);
      file_put_contents($file, str_replace('___VERSION_N/A___', $v, $fc));
    }
    
//    public static function postAutoloadDump($event) {
//      $oldMessage = "";
//
//      $deletedFormat = "";
//
////read the entire string
//      $str=file_get_contents('msghistory.txt');
//
////replace something in the file string - this is a VERY simple example
//      $str=str_replace("$oldMessage", "$deletedFormat",$str);
//
////write the entire string
//      file_put_contents('msghistory.txt', $str);
//    }
  }