<?php

namespace Bigfoot\Bundle\CoreBundle\Form\EventListener;

use Doctrine\Common\Annotations\Reader;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Translation subscriber used to automatically add the translations
 * on an entity. Uses both the entity manager to find translations,
 * and the annotationReader to get the informations about the field
 * marked as "translatable".
 *
 * @package Acme\DemoBundle\Form\EventListener
 */
class TranslationSubscriber implements EventSubscriberInterface {

    protected $localeList;
    protected $doctrineService;
    protected $annotationReader;
    protected $defaultLocale;

    public function __construct($localeList, RegistryInterface $doctrineService, Reader $annotationReader, $defaultLocale)
    {
        $this->localeList = $localeList;
        $this->doctrineService = $doctrineService;
        $this->annotationReader = $annotationReader;
        $this->defaultLocale = $defaultLocale;
    }

    public static function getSubscribedEvents()
    {
        // Tells the dispatcher that you want to listen on the form.pre_set_data
        // event and that the preSetData method should be called.
        return array(FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::SUBMIT => 'submit');
    }

    /**
     * Pre-submit form handling
     *
     * @param FormEvent $event
     * @throws \Exception in case the form the subscriber was set on isn't an entity one.
     */
    public function preSetData(FormEvent $event)
    {
        try{
            $form = $event->getForm();
            $parentForm = $form->getParent();
            $parentData = $parentForm->getData();

            // First, we get the entity class
            $entityClass = get_class($parentData);

            // Then, we get the field list from the entity class metadata using the annotation reader
            $translatableFields = $this->getTranslatableFields($entityClass);

            //Case where there is data (edit)
            if ($parentData && $parentData->getId()) {

                // We get the entity manager and its translations
                $em = $this->doctrineService->getManagerForClass($entityClass);
                $repository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                $translations = $repository->findTranslations($parentData);

                // Iterating over the enabled locales
                foreach ($this->localeList as $locale) {
                    // Case of the default locale : do not display the form
                    if ($locale != $this->defaultLocale){

                        // We first add the fields without translation
                        $this->addTranslationlessFieldsForLocale($translatableFields, $parentForm, $form, $locale);

                        // If the locale has its translation
                        if (isset ($translations[$locale])) {
                            // then we get its data per field
                            // this way if a field has a translation it will automatically be filled
                            foreach($translations[$locale] as $field => $translation) {
                                // Here we have to first retrieve the field type in the parent form
                                $fieldType = $parentForm->get($field)->getConfig()->getType()->getInnerType();
                                // and then set the form type and the data
                                $form->add(sprintf("%s-%s", $field, $locale), $fieldType, array('data' => $translation, 'required' => false));
                            }
                        }
                    }
                }

                // end of the process
                return;
            }

            // Here case where there is an entity but empty one.
            $this->addTranslationlessFields($translatableFields, $parentForm, $form);

        } catch (\Exception $e) {
            // Case of a non entity object given to the parent form.
            // Unstranslatable case, throw exception.
            $secondException = new \Exception("The object that was given to the form you wanted to translate isn't an entity one. Untranslatable in this case.", $e->getCode(), $e);
            throw $secondException;
        }
    }

    /**
     * Submit form handling
     *
     * @param FormEvent $event
     * @throws \Exception in case of the Susbcriber was set on a non entity form
     */
    public function submit(FormEvent $event)
    {
        try{
            $form = $event->getForm();
            $parentForm = $form->getParent();
            $parentData = $parentForm->getData();

            // First, we get the entity class
            $entityClass = get_class($parentData);

            // Edit case
            if ($parentData) {
                $em = $this->doctrineService->getManagerForClass($entityClass);
                $repository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');

                // Gets the translatable fields and their content
                $translatableFields = $this->getTranslatableFields($entityClass);
                $data = $event->getData();
                var_dump($data);

                foreach ($this->localeList as $locale) {
                    if ($locale != $this->defaultLocale) {
                        // Here we extract the field values from the submitted data
                        // Here type is useless, just used for getting the field
                        foreach ($translatableFields as $field => $type) {
                            // Here just translate the data
                            $repository->translate($parentData, $field, $locale,$data[$field."-".$locale] );
                        }
                    }
                }

            }
        } catch (\Exception $e) {
            // Case of a non entity object given to the parent form.
            // Unstranslatable case, throw exception.
            $secondException = new \Exception("The object that was given to the form you wanted to translate isn't an entity one. Untranslatable in this case.", $e->getCode(), $e);
            throw $secondException;
        }
    }

    private function addTranslationlessFields($translatableFields, $parentForm, $form)
    {
        foreach ($this->localeList as $locale) {
            // We  add the fields without translation
            if ($locale != $this->defaultLocale)
                $this->addTranslationlessFieldsForLocale($translatableFields, $parentForm, $form, $locale);
        }
    }

    private function addTranslationlessFieldsForLocale($translatableFields, $parentForm, $form, $locale)
    {
        foreach ($translatableFields as $field => $type) {
            $fieldType = $parentForm->get($field)->getConfig()->getType()->getInnerType();
            $form->add(sprintf("%s-%s", $field, $locale), $fieldType, array('required' => false));
        }
    }

    /**
     * Private method to get the list of the translatable fields of a given entity.
     *
     * @param $className string The class to look mapping infos for.
     * @return array an array containing the list of the translatable fields associated to their type.
     */
    private function getTranslatableFields($className)
    {
        // Here we use reflection to get the class
        $reflectionClass = new \ReflectionClass($className);
        $translatableFields = array();
        // and its entities
        $reflectionProperties = $reflectionClass->getProperties();
        foreach ($reflectionProperties as $reflectionProperty) {
            // We use the annotation reader to get the properties annotated with the "Translatable" annotation
            $propertyAnnotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, 'Gedmo\Mapping\Annotation\Translatable');
            if ($propertyAnnotation) {
                // In this case we have to get the property name and its type in the mapping annotation
                $mappingAnnotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, 'Doctrine\ORM\Mapping\Column');
                $translatableFields[$reflectionProperty->getName()] = $mappingAnnotation->type;
            }
        }
        return $translatableFields;
    }
}
