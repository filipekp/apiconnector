<?php
  
  namespace apiconnector;
  
  use Composer\Installer\PackageEvent;
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
    public static function postPackage(PackageEvent $event) {
//      $composer = $event->getComposer();
//      $v = $composer->getPackage()->getPrettyVersion();
//      var_dump($event->getName());
//      var_dump($event->getComposer()->getPackage()->getPrettyVersion());
//      var_dump($event->getFlags());


      file_put_contents(__DIR__ . '/msghistory.txt', $event->getName(), FILE_APPEND);
      file_put_contents(__DIR__ . '/msghistory.txt', $event->getComposer()->getPackage()->getPrettyVersion(), FILE_APPEND);
      file_put_contents(__DIR__ . '/msghistory.txt', json_encode($event->getFlags()), FILE_APPEND);
      
//      $file = __DIR__ . '/Connector.php';
//      $fc = file_get_contents($file);
//      file_put_contents($file, str_replace('___VERSION_N/A___', $v, $fc));
      
//      var_dump(' upgrade to: ' . $v);
//      file_put_contents(__DIR__ . '/version.log', $v);
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
  
    public static function postUpdate(Event $event) {
//      var_dump($event->getComposer()->getPackage()->getName());
//      var_dump($event->getComposer()->getPackage()->getPrettyVersion());
  
      file_put_contents(__DIR__ . '/msghistory.txt', $event->getComposer()->getPackage()->getName(), FILE_APPEND);
      file_put_contents(__DIR__ . '/msghistory.txt', $event->getComposer()->getPackage()->getPrettyVersion(), FILE_APPEND);
      
      // do stuff
    }
  }