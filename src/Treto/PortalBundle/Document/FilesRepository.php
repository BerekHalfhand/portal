<?php
namespace Treto\PortalBundle\Document;

class FilesRepository extends SecureRepository
{
    /**
     * @param $filename
     * @param $collectionName
     * @param $unid
     * @param string $originalFilename
     * @return a|Files
     * @throws \Exception
     */
  public function findOrSave($filename, $collectionName, $unid, $originalFilename = '') {
    if(!file_exists($filename)) throw new \Exception('sourcefile `'.$filename.'` is not readable');
    $hash = Files::computeHashFromFile($filename);
      /** @var Files $record */
    $record = $this->findOneBy(array('hash'=>$hash));
    if(!$record) {
      $record = $this->createFromFile($filename, $originalFilename);
    }
    $record->addToReferences($collectionName, $unid, $originalFilename);

    return $record;
  }
  /**
    * @param
    * @return
   */ 
  public function findAsRefs($collectionName, $unid) {
    $rel = $this->getList($collectionName, $unid);
    $ret = [];
    foreach($rel as $relItem) {
      $ret [] = ['ref'=>$relItem->getHash(), 'mime'=>$relItem->getMime()];
    }
    return $ret;
  }
  /**
    * @param collectionName eg Portal, Contacts etc
    * @param unid the unid or the record to which the files are attached
    * @return string[] file names array
   */ 
  public function findAsFilenames($collectionName, $unid) {
    return $this->findAsRefs($collectionName, $unid);
  }
  /**
   * @param $collectionName
   * @param $unid
   * @return array of relevant records
   */ 
  public function findByCollectionNameAndUnid($collectionName, $unid) {
    $files = $this->getList($collectionName, $unid);
    $f = [];
    foreach ($files as $f) { 
      $res []= $f->getDocument();
    }
    return $res;
  }
  /**
    * @param
    * @return
   */ 
  public function getList($collectionName, $unid) {
    $qs = sprintf('function(){
      for(var m in this.references) {
        var cur=this.references[m];
        if(cur.collectionName == "%s" && cur.unid == "%s") return true;
      }
      return false
    }', $collectionName, $unid);
    $q = $this->dm->createQueryBuilder('TretoPortalBundle:Files');
    $files = $q->field('$where')->where($qs);
    return $files->getQuery()->execute();
  }
  /**
   * @param  existing $filename with data in it
   * @return a brandnew Files instance with the path given 
   */ 
  protected function createFromFile($filename, $originalFilename = '') {
    if(!Files::chkdir()) {
      mkdir(Files::getDirname());
      if(!Files::chkdir()) {
        throw new \Exception('Cound not create ' .  Files::getDirname());
      }
      chmod(Files::getDirname(), 0777);
    }
    if(!is_writable(Files::getDirname())) throw new \Exception('Directory is not writable ' . Files::getDirname());
    return new Files($filename, $originalFilename);
  }
  public function findByRunid($runid) {
    $qs = sprintf('function(){
      for(var m in this.references) {
        var cur=this.references[m];
        if(cur.runid == "%s") return true;
      }
      return false
    }', $runid);
    $q = $this->dm->createQueryBuilder('TretoPortalBundle:Files');
    $files = $q->field('$where')->where($qs);
    $res = $files->getQuery()->execute();
    foreach($res as $refItem) {
      return $refItem;
    }
  }
}
