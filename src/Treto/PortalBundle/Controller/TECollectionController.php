<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TECollectionController extends Controller
{

  public function getLastThreeAction() {
    $type = $this->param('collectionType', null);
    if (!$type || ($type !== 'publication' && $type !== 'delivery'))
      return $this->fail("Не верно указан тип запрашиваемых коллекций (параметр 'collectionType').\n".
                          "Возможные варианты:\n".
                            "'publication' - для получения опубликованных коллекций\n".
                            "'delivery' - для получения снятых с производства.");

    $url = 'https://tile.expert/last/three';
    $hash = md5('test'.date('Y.m.d'));

    $post_params = ['hash' => $hash, 'type' => $type];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_params));
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);

    $result = curl_exec($ch);
    curl_close($ch);

    return $this->success(['documents' => json_decode($result)]);
  }

  public function getCollectionsAction() {
    $type = $this->param('type', null);
    $since = $this->param('since', null);
    $until = $this->param('until', null);
    if (!$type || ($type !== 'publication' && $type !== 'delivery'))
      return $this->fail("Не верно указан тип запрашиваемых коллекций (параметр 'collectionType').\n".
                          "Возможные варианты:\n".
                            "'publication' - для получения опубликованных коллекций\n".
                            "'delivery' - для получения снятых с производства.");

    $url = 'https://tile.expert/last/period';
    $hash = md5('test'.date('Y.m.d'));
    
    if ($since !== null) $since = new \DateTime($since);
    else {
      $since = new \DateTime();
      $since->sub(new \DateInterval("P1D"));
    }
    if ($until !== null) $until = new \DateTime($until);
    else $until = new \DateTime();

    $post_params = ['hash' => $hash, 'type' => $type,
                    'start' => $since->format('Y-m-d'), 'end' => $until->format('Y-m-d'),
                    'page' => 0, 'limit' => 0];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_params));

    $result = curl_exec($ch);
    curl_close($ch);

    return $this->success(['documents' => json_decode($result)]);
  }

}