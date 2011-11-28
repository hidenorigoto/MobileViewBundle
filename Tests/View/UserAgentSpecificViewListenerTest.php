<?php
namespace Xnni\Bundle\MobileViewBundle\Tests\View;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Xnni\Bundle\MobileViewBundle\View\UserAgentSpecificViewListener;

require_once 'Phake.php';
\Phake::setClient(\Phake::CLIENT_PHPUNIT);

class UserAgentSpecificViewListenerTest extends WebTestCase
{
    /**
     *
     */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
    }

    /**
     * @test
     */
    public function testOnKernelRequest()
    {
        $listener = new UserAgentSpecificViewListener(static::$kernel->getContainer());
        $event = \Phake::mock('Symfony\Component\HttpKernel\Event\GetResponseEvent');
        $dua   = \Phake::mock('Dua\UserAgentDetector');
        static::$kernel->getContainer()->set('dua', $dua);
        $request = \Phake::partialMock('Symfony\Component\HttpFoundation\Request');
        \Phake::when($event)->getRequest()->thenReturn($request);
        \Phake::when($dua)->detect(\Phake::anyParameters())->thenReturn('detected_user_agent');

        // test target
        $listener->onKernelRequest($event);

        $this->assertEquals('detected_user_agent', $request->attributes->get('mobileview.detected_ua'));
        \Phake::verify($dua)->detect(\Phake::anyParameters());
    }

    /**
     * @test
     */
    public function testOnKernelController()
    {
        $listener = new UserAgentSpecificViewListener(static::$kernel->getContainer());
        $event = \Phake::mock('Symfony\Component\HttpKernel\Event\FilterControllerEvent');
        $request = \Phake::partialMock('Symfony\Component\HttpFoundation\Request');
        \Phake::when($event)->getRequest()->thenReturn($request);
        \Phake::when($event)->getController()->thenReturn(array(new \stdClass(),'controllermethod'));

        // test target
        $listener->onKernelController($event);

        $this->assertEquals('stdClass', $request->attributes->get('mobileview.controllerClassName'));
        $this->assertEquals('controllermethod', $request->attributes->get('mobileview.controllerMethodName'));
    }

    /**
     * @test
     */
    public function testOnKernelView()
    {
        $listener = \Phake::partialMock('Xnni\Bundle\MobileViewBundle\View\UserAgentSpecificViewListener', static::$kernel->getContainer());
        $event = \Phake::mock('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent');
        $request = \Phake::partialMock('Symfony\Component\HttpFoundation\Request');
        \Phake::when($event)->getRequest()->thenReturn($request);
        \Phake::when($event)->getControllerResult()->thenReturn(array('_view'=>'viewname'));
        \Phake::when($listener)->guessTemplateName(\Phake::anyParameters())->thenReturn('guessed_templatename');

        $templatingMock = \Phake::mock('Symfony\Component\Templating\EngineInterface');
        \Phake::when($templatingMock)->render(\Phake::anyParameters())->thenReturn('test');
        static::$kernel->getContainer()->set('templating', $templatingMock);

        // test target
        $listener->onKernelView($event);

        \Phake::verify($templatingMock)->render('guessed_templatename', array());
    }


    /**
     * @test
     * @dataProvider guessTemplateNameProvider
     */
    public function testGuessTemplateName($viewname, $ua, $controllerName, $methodName, $expected)
    {
        echo $ua;
        $listener = \Phake::partialMock('Xnni\Bundle\MobileViewBundle\View\UserAgentSpecificViewListener', static::$kernel->getContainer());
        $bundleMock = \Phake::mock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        \Phake::when($bundleMock)->getName()->thenReturn('bundlename');
        \Phake::when($listener)->getBundleForClass(\Phake::anyParameters())->thenReturn($bundleMock);
        $request = \Phake::partialMock('Symfony\Component\HttpFoundation\Request');
        \Phake::when($request)->getRequestFormat()->thenReturn('format');

        // test target (protected)
        $class = new \ReflectionClass('Xnni\Bundle\MobileViewBundle\View\UserAgentSpecificViewListener');
        $method = $class->getMethod('guessTemplateName');
        $method->setAccessible(true);
        $templateName = $method->invokeArgs($listener, array($viewname, $ua, $controllerName, $methodName, $request));

        $this->assertEquals($expected, $templateName);
    }

    public function guessTemplateNameProvider()
    {
        return array(
            array('viewname', 'nonmobile', '', '', 'bundlename:viewname.format.twig'),
            array('viewname', 'iphone', '', '', 'bundlename:viewname_s.format.twig'),
            array('viewname', 'docomo', '', '', 'bundlename:viewname_k.format.twig'),
            array('', 'nonmobile', 'AcmeTestBundle\Controller\TestController', 'indexAction', 'bundlename:Test:index.format.twig'),
            array('', 'iphone', 'AcmeTestBundle\Controller\TestController', 'indexAction', 'bundlename:Test:index_s.format.twig'),
        );
    }


    /**
     * @test
     */
    public function testGetBundleForClass()
    {
        $listener = \Phake::partialMock('Xnni\Bundle\MobileViewBundle\View\UserAgentSpecificViewListener', static::$kernel->getContainer());
        $bundle1 = \Phake::mock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        \Phake::when($bundle1)->getNamespace()->thenReturn('namespace1');
        $bundle2 = \Phake::mock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        \Phake::when($bundle2)->getNamespace()->thenReturn('Vendor\Bundle\TestBundle');
        $kernelMock = \Phake::mock('Symfony\Component\HttpKernel\KernelInterface');
        \Phake::when($kernelMock)->getBundles()->thenReturn(array(
            $bundle1,
            $bundle2,
        ));
        static::$kernel->getContainer()->set('kernel', $kernelMock);

        // test target (protected)
        $class = new \ReflectionClass('Xnni\Bundle\MobileViewBundle\View\UserAgentSpecificViewListener');
        $method = $class->getMethod('getBundleForClass');
        $method->setAccessible(true);
        $retBundle = $method->invokeArgs($listener, array('Vendor\Bundle\TestBundle\Controller\TestController'));

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Bundle\BundleInterface', $retBundle);
        $this->assertEquals($bundle2, $retBundle);
    }
}
