<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Treto\PortalBundle\Document\Files;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileSystemController extends AbstractDiscussionController {
  //---------------------E/PUBLIC
  /**
   * retrieves list of attachments of a collection's record. 
   * Record must be specified with two params.
   * @param $clct collection of record
   * @param $unid unid of record
   */
  public function listAction($clct,$unid) {
    $dm  = $this->getDM('Files');
    $files = $this->getRepo('Files')->getList($clct,$unid);
    $docs = []; 
    foreach ($files as $file)  {
      $docs []= $this->packDoc($file, $clct, $unid);
    }
    return new JsonResponse($docs);
  }

  /// 
  public function displayAction($runid) {
    $dm = $this->get('doctrine.odm.mongodb.document_manager');
    $rep = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Files');
    $file = $rep->findByRunid($runid);
    if($file)
      return new JsonResponse(['success'=>true, 'doc'=>$file->getDocument()]);
    return new JsonResponse(['success'=>false, 'explain'=>'not found']);
  }
  /**
    * @param
    * @return
   */ 
    public function srcAction($hash) {
      $file = $this->getRepo('Files')->findOneBy(['hash'=>$hash]);
      if($file) {
        if ($this->canRead($file)) {
          $response = new Response($file->getData());
          $response->headers->set('Content-Type', $file->getMime());
          //file_put_contents('1.txt', $file->getMime());
          if ($file->getMime() != 'image/jpeg' &&
              $file->getMime() != 'image/png' &&
              $file->getMime() != 'image/gif')
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$file->getOriginalFilename().'"');
            
          return $response;
        }else{
          return new Response('Access denied');
        }
      }
      return new Response('document not found');
    }
  /**
    * @param file hash
    * @return thumbnail image render
   */ 
  public function thumbnailAction($hash) {
    $file = $this->getRepo('Files')->findOneBy(['hash'=>$hash]);
    if($file) {
      if ($this->canRead($file)) {
        $response = new Response($file->getThumbnailData(180,180));
        $response->headers->set('Content-Type', $file->getThumbnailMime());
        return $response;
      }else{
        return new Response('Access denied');
      }
    }
    return new Response('document not found');
  }
    /// 
    public function addRecordAction($clct, $unid, $manualy = false) {
      if(is_array($manualy) && !empty($manualy)){
        $this->setContainer($manualy['container']);
        $files = $manualy['files'];
      }
      else {
        $files = $_FILES;
      }

      $dm = $this->get('doctrine.odm.mongodb.document_manager');
      $rep = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Files');
      $docs = [];

      foreach($files as $handle => $data) {
        if ($data['name'] == "blob" || $data['name'] == "undefined"){
          $data['name'] = "";
        }

       $infos = getimagesize($data['tmp_name']);
       if (isset($infos['channels']) && $infos['channels'] == 4) { // channels 4 - CMYK profile
         $this->convertProfileToRgb($data['tmp_name']);
       }

        $file = $rep->findOrSave($data['tmp_name'], $clct, $unid, $data['name']);
        $dm->persist($file);
       $docs [] = $this->packDoc($file, $clct, $unid);
      }
      $dm->flush(null, array('safe' => true, 'fsync' => true));
      $result = ['success'=>true, 'data'=>$docs];
      return !$manualy?new JsonResponse($result):$result;
    }

    private function convertProfileToRgb($src){
        $image = new \Imagick($src);
        $profiles = $image->getImageProfiles('*', false);
        $has_icc_profile = (array_search('icc', $profiles) !== false);
        $pathIcc = $this->get('kernel')->getRootDir() . '/../web/public/icc';
        if ($has_icc_profile === false){
            $icc_cmyk = file_get_contents($pathIcc.'/CMYK/JapanColor2001Uncoated.icc');
            $image->profileImage('icc', $icc_cmyk);
        }

        $icc_rgb = file_get_contents($pathIcc.'/RGB/AdobeRGB1998.icc');
        $image->profileImage('icc', $icc_rgb);
        $image->setImageColorSpace(\Imagick::COLORSPACE_SRGB);
        $image->writeImage($src);
    }

  //---------------------E/PUBLIC
    /**
     *
     */
    protected function getThumbnailLink($file) { //TODO
      switch($file->getMime()) {
        case 'image/gif':
        case 'image/png':
        case 'image/jpeg': return $this->getImageThumbnail($file); //TODO
        default: return $this->getAttachmentLink($file, $file->getLabel());
      }
    }
    /**
     *
     */
    protected function getMediaLink($file) {
      switch($file->getMime()) {
        case 'image/gif':
        case 'image/png':
        case 'image/jpeg': return $this->getImageLink($file);
        default: return $this->getAttachmentLink($file, $file->getLabel());
      }
    }
    /**
      * @param file
      * @return url
     */ 
    protected function getImageLink($file) {
      return "{$this->generateUrl('file_src', ['hash'=>$file->getHash()])}" ;
    }
    /**
      * @param file
      * @return url
     */ 
    protected function getImageThumbnail($file) {
      return "{$this->generateUrl('thumb_src', ['hash'=>$file->getHash()])}" ; 
    }
    /**
     *
     */
    protected function getAttachmentLink($file, $label) {
      return "{$this->generateUrl('file_src', ['hash'=>$file->getHash()])}" ;
    }
    /**
     *  
     */
    protected function packDoc($file, $collectionName, $unid) {
      $doc = [];
      $doc['doc'] = $file->getDocument();
      $doc['mimeArray'] = explode('/',$file->getMime());
      $doc['link'] =  $this->getMediaLink($file);
      $doc['thumbnail'] = $this->getThumbnailLink($file);
      return $doc;
    }
    public function removeReferenceAction($hash, $runid) {
      $dm = $this->get('doctrine.odm.mongodb.document_manager');
      $rep = $this->get('doctrine_mongodb')->getRepository('TretoPortalBundle:Files');
      $file = $rep->findOneByHash($hash);
      if($file) {
        $file = $file->removeReferenceBy('unid', $runid);
        $dm->persist($file);
        $dm->flush(null, array('safe' => true, 'fsync' => true));
        return new JsonResponse(['success'=>true,'doc'=>$file->getDocument()]);
      }else {
        return new JsonResponse(['success'=>false, 'explain'=>'not found']);
      }
    }

    protected function canRead($file){
      if ($this->getUser() && $this->getUser()->hasRole("PM")) return true;
      
      $unids = ['Portal'=>[], 'Contacts'=>[]];
      foreach( $file->getReferences() as $ref) {
        if ($ref['collectionName']=='Chat') return true;
        $unids[$ref['collectionName']][] = $ref['unid'];
      }

      foreach ($unids as $key => $value) {
        $repo = $this->getRepo($key);
        $docs = $repo->findBy(['unid'=>['$in' => $value]]);
        if ($docs) {
          foreach ($docs as $doc) {
            $main = $this->getMainDocFor($doc);
            if (!$main) $main = $doc;

            if ($this->getUserPortalData() && $main->hasReadPrivilegeFor($this->getUserPortalData()->GetLogin(), true, $this->getUserPortalData()->GetRole())) {
              return true;
            }
            if ($main->GetForm() != 'Contact' && $main->GetToSite() === '1'){
              return true;
            }
          }
        } else {
          return true;
        }
      }
      return false;
    }
}

