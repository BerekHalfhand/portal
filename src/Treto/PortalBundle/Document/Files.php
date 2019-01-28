<?php
namespace Treto\PortalBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * класс ОДМ
 * хранящий файловые записи
 * умеющий работать с диском
 *
 * @MongoDB\Document(repositoryClass="FilesRepository") @MongoDB\HasLifecycleCallbacks
 */
class Files extends SecureDocument
{
  const STORAGE_DIR = 'upload';

  protected /*transient*/ $fs = null;
  protected /*transient*/ $data = null;

  /** @MongoDB\Id(strategy="auto") */
  protected $_id;

  /** @MongoDB\String */
  protected $mimeType;

  /** @MongoDB\String */
  protected $originalFilename;

  /** @MongoDB\String */
  protected $unid;

  /** @MongoDB\String */
  protected $created;

  /** @MongoDB\String */
  protected $modified;

  /** @MongoDB\String */
  protected $lastaccessed;

  /** @MongoDB\String */
  protected $hash;

  /** @MongoDB\Collection */
  protected $references;
  /** generated with service/document-code-generator/Files.sh
  /** --------------------- B/Getters + Setters ---------------------*/
  /** unid */
  public function setUnid($unid = null) {
    if($unid) { $this->unid = $unid; }
    else {
      $this->unid = strtoupper(uniqid(time()));
    }
  }
  /** unid */
  public function getUnid(){
    return $this->unid;
  }

  /** hash */
  protected function setHash ($hash) {
    $this->hash = $hash;
  }
  /** hash */
  public function getHash () {
    return $this->hash;
  }

  /**created */
  public function getCreated(){
    return $this->created;
  }

  /**created */
  public function setCreated($created = null){
    if(! $created) {
      $this->created = self::createIsoTimestamp();
    } else {
      $this->created = $created;
    }
  }

  /**modified */
  public function getModified(){
    return $this->modified;
  }

  /**modified */
  public function setModified($modified = null){
    if(! $modified) {
      $this->modified = self::createIsoTimestamp();
    } else {
      $this->modified = $modified;
    }
  }

  /**lastaccessed*/
  public function getLastaccessed(){
    return $this->lastaccessed;
  }

  /**lastaccessed*/
  public function setLastaccessed($lastaccessed = null) {
    if(! $lastaccessed) {
      $this->lastaccessed = self::createIsoTimestamp();
    } else {
      $this->lastaccessed = $lastaccessed;
    }
  }
  public function setMimeType ($mimeType) {
    $this->mimeType = $mimeType;
    return $this;
  }
  public function getMimeType () {
    if($this->mimeType)
      return $this->mimeType;
    return $this->mimeType = $this->getMime();
  }
  public function setOriginalFilename ($originalFilename) {
    $this->originalFilename = $originalFilename;//
    return $this;
  }
  public function getOriginalFilename () {
    return $this->originalFilename;
  }
  /** references */
  public function setReferences ($references) {
    $this->references = $references; return $this;
  }
  /** references */
  public function getReferences () {
    return $this->references;
  }
  /** --------------------- E/Getters + Setters ---------------------*/

  /** references */
  public function addToReferences ($collectionName, $unid, $originalFilename = '') {
    $this->references = is_array($this->references) ? $this->references: array();
    $added = self::createIsoTimestamp();
    $runid = uniqid(time());
    $this->references []= array(
      'collectionName'=>$collectionName,
      'unid'=>$unid,
      'runid'=>$runid,
      'added'=>$added,
      'originalFilename'=>$originalFilename);
  }
  public function getReference($clct,$unid) {
    foreach( $this->getReferences() as $ref) {
      if($ref['collectionName'] == $clct && $ref['unid'] == $unid) {
        return $ref;
      }
    }
  }
  /**
   *  @param roles see serializr
   *  @return array
   */
  public function getDocument($roles = []) {
    $ret = (new \Treto\PortalBundle\Model\DocumentSerializer($this,$roles))->toArray();
    $ret['mimeType'] = $this->getMimeType();
    return $ret;
  }

  /** --------------------- B/Filesystem API calls-------------------*/
  /**
   * @param $hash the basename for the filename
   * @param $throw need to check dir
   * @throws \Exception
   * @return /path/to/filename
   */
  public static function getFullPath($hash, $throw = false) {
    self::chkdir($throw);
    if(!$hash) return false;
    return join('/', array(self::getDirname(), $hash));
  }

  /**
   * @return the root dir path defined in app/appKernel.php
   */
  public static function getAppDir() {
    return KERNEL_DIR;
  }
  /**
   * @return true if uploads dir exists
   */
  public static function chkdir($throw = false) {
    if($throw && !is_dir(self::getDirname())) throw new \Exception('missing upload directory');
    return is_dir(self::getDirname());
  }
  /**
   * @param $src
   * @param $hash
   * @return
   */
  public function __construct($src, $originalFilename) {
    if(!file_exists($src)) throw new \Exception('source file ' . $src . ' does not exist');
    $h = self::computeHashFromFile($src);
    $fullPath = self::getFullPath($h, true);

    if(!copy($src, $fullPath)){
        throw new IOException('copying to ' . $fullPath . ' failed');
    }

    $this->setHash($h);
    $this->setOriginalFilename($originalFilename);
    $this->setMimeType($this->getMime());
  }

  /** --------------- EVENTS --------------*/
  /** @MongoDB\PrePersist */
  public function prePersist() {
    $this->setCreated();
    $this->setUnid();
    $this->setModified();
  }
  /** @MongoDB\PreUpdate */
  public function preUpdate() {
    $this->setModified();
  }
  /** @MongoDB\PostLoad */
  public function postLoad() {
    $this->SetLastaccessed();
  }
  /**
   *
   */
  public function getMime() {
    if(!$this->getHash()) return 'text/plain';
    if(!file_exists(self::getFullPath($this->getHash())))
      return 'text/plain';
    return mime_content_type(self::getFullPath($this->getHash()));
  }
  /**
   *
   */
  public function getHeader() {
    return 'Content-type: ' . $this->getMime();
  }
  /**
   */
  public function getData() {
    return $this->data ? $this->data : ($this->data = file_get_contents(self::getFullPath($this->hash)));
  }
  /**
   */
  public function getThumbnailData($sx, $sy) { //TODO: implement
    //header('Content-type: image/jpeg');    //
    //print $this->data ? $this->data : ($this->data = file_get_contents(self::getFullPath($this->hash)));
    $file = self::getFullPath($this->hash);
    list($width, $height) = getimagesize($file);
    $size = min($sy/$height, $sx/$width);
    // Setting the resize parameters
    $modwidth = $width * $size;
    $modheight = $height * $size;

    // Resizing the Image
    $tn = imagecreatetruecolor($modwidth, $modheight);
    // This sets it to a .jpg,
    // but you can change this to png or gif
    $mime = $this->getMimeType();
    switch($mime) {
      case 'image/jpeg': $image = @imagecreatefromjpeg($file); break;
      case 'image/png': $image = @imagecreatefrompng($file); break;
      case 'image/gif': $image = @imagecreatefromgif($file); break;
    }
    imagecopyresampled($tn, $image, 0, 0, 0, 0, $modwidth, $modheight, $width, $height);
    // Outputting a .jpg,
    // you can make this gif
    // or png if you want
    //notice we set the quality (third value) to 20

    ob_start(); //Stdout --> buffer

    imagejpeg($tn, null, 100);

    $ret = ob_get_contents(); //store stdout

    ob_end_clean(); //clear buffer

    imagedestroy($tn); //destroy img
    return $ret;
  }
  /**
    * @return timestamp as 20141231T181104,12+04
   */
  public function createIsoTimestamp() {
    $d = new \DateTime();
    $u = substr(explode(" ",microtime())[0],2,2);
    $z = substr($d->format('O'),0,3);
    $h = sprintf('%02d',$d->format('G'));
    return $d->format('Ymd').'T'.$h.$d->format(':i:s,').$u.$z;
  }
  /**
   * @return directory where the uploaded files should reside. May or may not exist
   */
  public static function getDirname () {
    return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'?'':'/').join('/', array(self::getAppDir(), self::STORAGE_DIR));
  }
  /**
    * @param $file data storage
    * @return string hash
   */
  public static function computeHashFromFile($file) {
    return md5_file($file);
  }

  public static function computeHash($data) {
    return md5($data);
  }

  /**
    * @return this->originalFilename
   */
  public function getLabel() {
    return $this->getOriginalFilename();
  }

  public function getThumbnailMime () {
    return 'image/jpeg';
  }

  public function removeReferenceBy($field, $value) {
    foreach($this->references as $key=>$ref) {
      if($ref[$field] == $value){
        unset($this->references[$key]);
        return $this;
      }
    }
    return $this;
  }
}
