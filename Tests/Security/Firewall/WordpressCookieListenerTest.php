<?php

namespace Hypebeast\WordpressBundle\Tests\Security\Firewall;

use Hypebeast\WordpressBundle\Security\Firewall\WordpressCookieListener;
use Hypebeast\WordpressBundle\Wordpress\ApiAbstraction;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Test class for WordpressCookieListener.
 * Generated by PHPUnit on 2011-09-28 at 18:59:27.
 * 
 * @covers Hypebeast\WordpressBundle\Security\Firewall\WordpressCookieListener
 */
class WordpressCookieListenerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var WordpressCookieListener
     */
    protected $object;
    
    /**
     *
     * @var Hypebeast\WordpressBundle\Wordpress\ApiAbstraction 
     */
    protected $wordpressApi;
    
    /**
     *
     * @var Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $securityContext;
    
    /**
     *
     * @var Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface 
     */
    protected $authenticationManager;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->wordpressApi
                = $this->getMockBuilder('Hypebeast\\WordpressBundle\\Wordpress\\ApiAbstraction')
                    ->disableOriginalConstructor()->setMethods(array('wp_login_url'))->getMock();
        
        $this->securityContext
                = $this->getMock('Symfony\\Component\\Security\\Core\\SecurityContextInterface');
        $this->authenticationManager = $this->getMock(
                'Symfony\\Component\\Security\\Core\\Authentication\\AuthenticationManagerInterface'
        );
        
        $this->httpUtils = $this->getMock('Symfony\\Component\\Security\\Http\\HttpUtils');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }
    
    public function testHandleUserIsAlreadyAuthenticated() {
        $this->securityContext->expects($this->once())->method('getToken')
                ->will($this->returnValue($this->getMock(
                    'Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface')));

        $this->authenticationManager->expects($this->never())->method('authenticate');
        $this->securityContext->expects($this->never())->method('setToken');
        
        $event = $this->getMockEvent();
        $event->expects($this->never())->method('setResponse');
        
        $this->assertNull($this->getMockListener()->handle($event));
    }

    public function testHandleSuccessfulAuthenticationRequest() {
        $token = $this->getMock(
                'Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface');
        # A WordpressCookieToken should get instantiated and authenticated
        $this->authenticationManager->expects($this->once())->method('authenticate')
                ->with($this->isInstanceOf(
                    'Hypebeast\\WordpressBundle\\Security\\Authentication\\Token\\WordpressCookieToken'
                ))->will($this->returnValue($token));
        
        # The authenticated token should get set
        $this->securityContext->expects($this->once())->method('setToken')->with($token);
        
        $this->getMockListener()->handle($this->getMockEvent());
    }
    
    public function testHandleFailedAuthentication() {
        # Let's say the provided token doesn't authenticate
        $this->authenticationManager->expects($this->once())->method('authenticate')
                ->with($this->isInstanceOf(
                    'Hypebeast\\WordpressBundle\\Security\\Authentication\\Token\\WordpressCookieToken'
                ))->will($this->throwException(new AuthenticationException('auth failed')));

        $this->securityContext->expects($this->never())->method('setToken');
        
        $event = $this->getMockEvent();
        $event->expects($this->never())->method('setResponse');

        $this->assertNull($this->getMockListener(false)->handle($event));
    }
    
    public function testHandleFailedAuthenticationWithRedirectToWordpress() {
        # Let's say the provided token doesn't authenticate
        $this->authenticationManager->expects($this->once())->method('authenticate')
                ->with($this->isInstanceOf(
                    'Hypebeast\\WordpressBundle\\Security\\Authentication\\Token\\WordpressCookieToken'
                ))->will($this->throwException(new AuthenticationException('auth failed')));
        
        # Any token should get cleared
        $this->securityContext->expects($this->once())->method('setToken')
                ->with($this->identicalTo(null));
        
        # The user should be redirected to the Wordpress login
        $this->wordpressApi->expects($this->any())->method('wp_login_url')
                ->with($requestUrl = 'mock request URL', true)
                ->will($this->returnValue($expectedRedirect = 'mock wordpress login URL'));
        
        $response = $this->getMock('Symfony\\Component\\HttpFoundation\\Response');
        $this->httpUtils->expects($this->once())->method('createRedirectResponse')
                ->with(
                    $this->isInstanceOf('Symfony\\Component\\HttpFoundation\\Request'),
                    $expectedRedirect
                )->will($this->returnValue($response));
        
        $event = $this->getMockEvent($requestUrl);
        $event->expects($this->once())->method('setResponse')->with($response);

        $this->getMockListener(true)->handle($event);
    }
    
    protected function getMockListener($redirectToWordpress=false) {
        return new WordpressCookieListener(
                $this->wordpressApi,
                $this->securityContext,
                $this->authenticationManager,
                $this->httpUtils,
                null,
                $redirectToWordpress
        );
    }
    
    protected function getMockEvent($requestUrl='mock url') {
        $request = $this->getMockBuilder('Symfony\\Component\\HttpFoundation\\Request')
                ->disableOriginalClone()->getMock();
        
        $request->expects($this->any())->method('getUri')->will($this->returnValue($requestUrl));
        
        $event = $this->getMockBuilder('Symfony\\Component\\HttpKernel\\Event\\GetResponseEvent')
                ->disableOriginalConstructor()->getMock();
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($request));
        
        return $event;
    }

}

?>
