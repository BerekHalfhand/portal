<?php
namespace Treto\UserBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AjaxAuthenticationListener implements 
    AuthenticationSuccessHandlerInterface, 
    AuthenticationFailureHandlerInterface, 
    LogoutSuccessHandlerInterface,
    AccessDeniedHandlerInterface
{  
    /**
     * This is called when an interactive authentication attempt succeeds. This
     * is called by authentication listeners inheriting from
     * AbstractAuthenticationListener.
     *
     * @see \Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener
     * @param Request        $request
     * @param TokenInterface $token
     * @return Response the response to return
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        if ($request->isMethod('POST')) {
            $result = [
                "success" => true,
                "user" => $token->getUser()->getDocument()
            ];
            return new JsonResponse($result);
        }
        return new Response('use POST', 405);
    }

    /**
     * This is called when an interactive authentication attempt fails. This is
     * called by authentication listeners inheriting from
     * AbstractAuthenticationListener.
     *
     * @param Request                 $request
     * @param AuthenticationException $exception    
     * @return Response the response to return
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
      if ($request->isMethod('POST')) {
        if(isset($_SESSION['fails'])) $_SESSION['fails'] += 1;
        else $_SESSION['fails'] = 1;
        $message = $exception->getMessage();
        $result = array('success' => false, 'message' => $message ? $message : var_export($exception,true), 'showCaptcha' => ($_SESSION['fails'] >= 3 ? true : false));
        return new JsonResponse($result);
      }
      return new Response('use POST', 405);
    }
    
    public function onLogoutSuccess(Request $request) {
        return new JsonResponse(array('success' => true));
    }
    
    public function handle(Request $request, AccessDeniedException $accessDeniedException) {
        return new JsonResponse(array('success' => false, 'message' => $accessDeniedException->getMessage()));
    }
}
