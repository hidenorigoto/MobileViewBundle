<?php
namespace Xnni\Bundle\MobileViewBundle\View;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/*
 * @author hidenorigoto <hidenorigoto@gmail.com>
 *
 * Original code are written by Fabien Potencier for SensioFrameworkExtraBundle https://github.com/sensio/SensioFrameworkExtraBundle
 *
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * The UserAgentSpecificViewListener class handles View layer which automatically change the template file for the requested user agent
 *
 * @author     hidenorigoto <hidenorigoto@gmail.com>
 */
class UserAgentSpecificViewListener
{
    /**
     * @var Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var mixed
     */
    protected $uaMap = array(
        'nonmobile' => '',
        'docomo'    => 'k',
        'softbank'  => 'k',
        'ezweb'     => 'k',
        'willcom'   => 'k',
        'iphone'    => 's',
    );

    /**
     * Constructor.
     *
     * @param ContainerInterface $container The service container instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Sets uaMap
     *
     * @param mixed $uaMap
     */
    public function setUaMap($uaMap)
    {
        $this->uaMap = $uaMap;
    }


    /**
     * kernel.request event handler
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $ua = $this->container->get('dua')
            ->detect($request);

        $request->attributes->set('mobileview.detected_ua', $ua);
    }

    /**
     * kernel.contoller event handler
     *
     * Store the contoller information in the request for later use
     *
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // Now we handle the class/method style controllers
        // (Closure is not supported)
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set('mobileview.controllerClassName', get_class($controller[0]));
        $request->attributes->set('mobileview.controllerMethodName', $controller[1]);
    }

    /**
     * kernel.view event handler
     *
     * Renders the template and initializes a new response object with the
     * rendered template content.
     *
     * @param GetResponseForControllerResultEvent $event A GetResponseForControllerResultEvent instance
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request    = $event->getRequest();
        $parameters = $event->getControllerResult();
        $viewName   = null;

        if (is_string($parameters)) {
            $viewName = $parameters;
        } else if (is_array($parameters) && isset($parameters['_view'])) {
            $viewName = $parameters['_view'];
            unset($parameters['_view']);
        }

        $templateName = $this->guessTemplateName(
            $viewName,
            $request->attributes->get('mobileview.detected_ua'),
            $request->attributes->get('mobileview.controllerClassName'),
            $request->attributes->get('mobileview.controllerMethodName'),
            $request
        );

        if (!is_array($parameters)) {
            $parameters = array();
        }

        $event->setResponse(new Response($this->container->get('templating')->render($templateName, $parameters)));
    }

    /**
     * Guesses and returns the template name to render based on the controller
     * and action names.
     *
     * @param string $viewName
     * @param string $ua
     * @param array $controller An array storing the controller object and action method
     * @param Request $request A Request instance
     * @throws \InvalidArgumentException
     */
    protected function guessTemplateName($viewName, $ua, $controllerClassName, $controllerMethodName, Request $request)
    {
        if (!$viewName) {
            if (!preg_match('/Controller\\\(.*)Controller$/', $controllerClassName, $match)) {
                throw new \InvalidArgumentException(sprintf('The "%s" class does not look like a controller class (it does not end with Controller)',
                    get_class($controller[0])));
            }

            // concatinate and remove 'Action' suffix
            $viewName = $match[1].':'.substr($controllerMethodName, 0, -6);
        }

        // converts $ua using mapping array
        $mappedUa = $this->mapUa($ua);

        if ($mappedUa) {
            $viewName .= '_'.$mappedUa;
        }

        $bundle = $this->getBundleForClass($controllerClassName);

        return $bundle->getName().':'.$viewName.'.'.$request->getRequestFormat().'.twig';
    }

    /**
     * Returns the Bundle instance in which the given class name is located.
     *
     * @param string $class A fully qualified controller class name
     * @return Bundle $bundle A Bundle instance
     * @throws \InvalidArgumentException
     */
    protected function getBundleForClass($class)
    {
        $namespace = strtr(dirname(strtr($class, '\\', '/')), '/', '\\');
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            if (0 === strpos($namespace, $bundle->getNamespace())) {
                return $bundle;
            }
        }

        throw new \InvalidArgumentException(sprintf('The "%s" class does not belong to a registered bundle.', $class));
    }

    /**
     * Converts ua using mapping
     */
    protected function mapUa($ua)
    {
        return 's';
        if (!$this->uaMap) return $ua;
        if (isset($this->uaMap[$ua])) return $this->uaMap[$ua];

        return $ua;
    }
}

