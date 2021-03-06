<?php

namespace NS\AceBundle\Tests\Form;

use \NS\AceBundle\Form\AutocompleterType;
use \NS\AceBundle\Tests\BaseFormTestType;
use \NS\AceBundle\Tests\Form\Fixtures\Entity;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;

/**
 * Description of AutocompleterTypeTest
 *
 * @author gnat
 */
class AutocompleterTypeTest extends BaseFormTestType
{
    public function resetFactory($entityMgr,$router = null)
    {
        $type = new AutocompleterType($entityMgr,$router);
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions(array(new PreloadedExtension(array($type),array())))
            ->getFormFactory();
    }

    public function testFormTypeEntityToJson()
    {
        $entity    = new Entity(1);
        $className = get_class($entity);
        $formData  = array('auto' => '1');
        $routeName = 'routename';
        $router    = $this->getRouter();
        $router->expects($this->once())
            ->method('generate')
            ->with($routeName)
            ->willReturn('/route/name');

        $entityMgr = $this->getEntityManager();
        $entityMgr->expects($this->once())
            ->method('getReference')
            ->with($className, 1)
            ->willReturn($entity);

        $this->resetFactory($entityMgr,$router);

        $form = $this->factory
            ->createBuilder()
            ->add('auto', AutocompleterType::class, array(
                'route'  => $routeName,
                'class'  => $className,
                'method' => 'GET'))
            ->getForm();

        $view = $form->createView();
        $form->submit($formData);
        $data = $form['auto']->getData();

        $this->assertEquals($data, $entity);
        $this->assertInstanceOf($className, $data);
        $this->assertArrayHasKey('auto', $view);
        $this->assertArrayHasKey('attr', $view['auto']->vars);
        $this->assertArrayHasKey('data-autocompleteUrl', $view['auto']->vars['attr']);
        $this->assertEquals($view['auto']->vars['attr']['data-autocompleteUrl'], '/route/name');
        $this->assertContains('GET', $view['auto']->vars['attr']['data-options']);
    }

    public function testFormTypeCollectionToJson()
    {
        $entities = array(new Entity(1), new Entity(2));

        $className = get_class($entities[0]);
        $formData  = array('auto' => '1,2');
        $routeName = 'routename';
        $router    = $this->getRouter();

        $entityMgr = $this->getEntityManager();
        $entityMgr->expects($this->at(0))
            ->method('getReference')
            ->with($className, 1)
            ->willReturn($entities[0]);
        $entityMgr->expects($this->at(1))
            ->method('getReference')
            ->with($className, 2)
            ->willReturn($entities[1]);

        $this->resetFactory($entityMgr,$router);

        $form = $this->factory
            ->createBuilder()
            ->add('auto', AutocompleterType::class, array(
                'autocompleteUrl' => $routeName,
                'class'           => $className,
                'multiple'        => true))
            ->getForm();

        $view = $form->createView();
        $form->submit($formData);
        $data = $form['auto']->getData();

        $this->assertEquals($data, $entities);
        $this->assertArrayHasKey('auto', $view);
        $this->assertArrayHasKey('attr', $view['auto']->vars);
        $this->assertArrayHasKey('data-autocompleteUrl', $view['auto']->vars['attr']);
        $this->assertEquals($view['auto']->vars['attr']['data-autocompleteUrl'], $routeName);
    }

    public function testFormWithDataCustomProperty()
    {
        $entity     = new Entity(1);
        $className  = get_class($entity);
        $properties = array(
            'property' => 'someProperty',
            'class'    => $className,
        );

        $router    = $this->getRouter();
        $entityMgr = $this->getEntityManager();
        $this->resetFactory($entityMgr,$router);

        $form      = $this->factory
            ->createBuilder()
            ->add('auto', AutocompleterType::class, $properties)
            ->getForm();

        $viewData = $form->getViewData();
        $this->assertNull($viewData);
        $form->setData(array('auto' => $entity));
        $view     = $form->createView();
        $this->assertEquals('[{"id":1,"name":"It Matters"}]', $view['auto']->vars['value']);
    }

    public function testFormWithDataToString()
    {
        $entity    = new Entity(1);
        $className = get_class($entity);

        $router    = $this->getRouter();
        $entityMgr = $this->getEntityManager();
        $this->resetFactory($entityMgr,$router);

        $form = $this->factory
            ->createBuilder()
            ->add('auto', AutocompleterType::class, array('class' => $className,))
            ->getForm();

        $viewData = $form->getViewData();
        $this->assertNull($viewData);
        $form->setData(array('auto' => $entity));
        $view     = $form->createView();
        $this->assertEquals('[{"id":1,"name":"Does Not Matter"}]', $view['auto']->vars['value']);
    }

    public function testFormWithNullData()
    {
        $entity    = new Entity(1);
        $className = get_class($entity);
        $entityMgr = $this->getEntityManager();

        $entityMgr->expects($this->never())
            ->method('getReference');

        $this->resetFactory($entityMgr);

        $form = $this->factory
            ->createBuilder()
            ->add('auto', AutocompleterType::class, array('class' => $className,))
            ->getForm();

        $form->submit(array());
        $data = $form['auto']->getData();
        $this->assertNull($data);
    }

    private function getRouter()
    {
        return $this->getMockBuilder('\Symfony\Component\Routing\RouterInterface')
                ->disableOriginalConstructor()
                ->setMethods(array('generate', 'match', 'getRouteCollection', 'setContext', 'getContext'))
                ->getMock()
        ;
    }

    private function getEntityManager()
    {
        return $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
                ->disableOriginalConstructor()
                ->setMethods(array('getReference', 'find', 'persist', 'merge', 'clear',
                    'detach', 'refresh', 'flush', 'getRepository', 'remove', 'getClassMetadata',
                    'getMetadataFactory', 'initializeObject', 'contains'))
                ->getMock();
    }
}
