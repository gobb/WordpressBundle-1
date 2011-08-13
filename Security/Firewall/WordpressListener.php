<?php
namespace Hypebeast\WordpressBundle\Security\Firewall;

use Hypebeast\WordpressBundle\Security\Authentication\Token\WordpressUserToken;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * AnonymousAuthenticationListener automatically adds a Token if none is
 * already present.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class WordpressListener implements ListenerInterface
{
    private $context;
    private $authenticationManager;
    private $logger;

    public function __construct(SecurityContextInterface $context, AuthenticationManagerInterface $authenticationManager, LoggerInterface $logger = null)
    {
        $this->context = $context;
        $this->authenticationManager = $authenticationManager;
        $this->logger  = $logger;
    }

    /**
     * Reads Wordpress user identity from cookies.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $key = 'a6a7afb1cdf10179e3603af9023eaf0d';
        $identity = $request->cookies->get("wordpress_logged_in_{$key}");
        
        // not logged in
        if($identity === null) {
            // TODO: what to do when user is not logged in
            return;
        }

        list($username, $expiration, $hmac) = explode('|', $identity);

        $token = new WordpressUserToken();
        $token->setUser($username);
        $token->setExpiration($expiration);
        $token->setHmac($hmac);

        try {
            // Authentication manager uses a list of AuthenticationProviderInterface
            // instances to authenticate a Token.
            $returnValue = $this->authenticationManager->authenticate($token);
            
            if ($returnValue instanceof TokenInterface) {
                return $this->context->setToken($returnValue);
            } else if ($returnValue instanceof Response) {
                return $event->setResponse($returnValue);
            }
        } catch (AuthenticationException $e) {
            // you might log something here
            throw $e;
        }

        if (null !== $this->logger) {
            $this->logger->info(get_class($this->authenticationManager));
            $this->logger->info(sprintf('Populated SecurityContext with an Wordpress Token'));
        }
    }
}