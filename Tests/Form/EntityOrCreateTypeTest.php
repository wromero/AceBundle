<?php

namespace NS\AceBundle\Tests\Form;

use \NS\AceBundle\Form\AutocompleterType;
use \NS\AceBundle\Form\EntityOrCreateType;
use \Symfony\Component\Form\PreloadedExtension;
use \Symfony\Component\Form\Test\TypeTestCase;

/**
 * Description of EntityOrCreateTypeTest
 *
 * @author gnat
 */
class EntityOrCreateTypeTest extends TypeTestCase
{
    private $entityMgr;

    public function testFormType()
    {
        $entity          = new Fixtures\Entity(1);
        $this->entityMgr->expects($this->once())
            ->method('getReference')
            ->willReturn($entity);

        $className = 'NS\AceBundle\Tests\Form\Fixtures\Entity';
        $formData = array(
            'entity' => array('finder' => 1),
        );

        $formOptions = array(
            'type'  => Fixtures\EntityType::class,
            'class' => $className,
        );

        $form = $this->factory->createBuilder()
            ->add('entity', EntityOrCreateType::class, $formOptions)
            ->getForm();

        $this->assertArrayHasKey('finder', $form['entity']);
        $this->assertArrayHasKey('createForm', $form['entity']);

        $form->submit($formData);

        $data = $form['entity']->getData();
        $this->assertInstanceOf($className, $data);
        $this->assertEquals(1, $data->getId());
    }

    public function testFormType2()
    {
        $this->entityMgr->expects($this->never())->method('getReference');

        $className = 'NS\AceBundle\Tests\Form\Fixtures\Entity';
        $formData  = array(
            'entity' => array('createForm' => array('id' => 2, 'name' => 'Other Name')),
        );

        $formProperties = array(
            'type'  => Fixtures\EntityType::class,
            'class' => $className,
        );
        
        $form = $this->factory->createBuilder()
            ->add('entity', EntityOrCreateType::class, $formProperties)
            ->getForm();

        $this->assertCount(2, $form['entity']);
        $this->assertArrayHasKey('finder', $form['entity']);
        $this->assertArrayHasKey('createForm', $form['entity']);

        $form->submit($formData);

        $data = $form['entity']->getData();
        $this->assertInstanceOf($className, $data);
        $this->assertEquals(2, $data->getId());
        $this->assertEquals('Other Name', $data->getName());
    }

    public function getExtensions()
    {
        $this->entityMgr = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getReference', 'find', 'persist', 'merge', 'clear',
                'detach', 'refresh', 'flush', 'getRepository', 'remove', 'getClassMetadata',
                'getMetadataFactory', 'initializeObject', 'contains'))
            ->getMock();

        $type = new AutocompleterType($this->entityMgr);

        return array(
            new PreloadedExtension(array($type), array()));
    }
}
