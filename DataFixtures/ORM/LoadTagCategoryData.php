<?php

namespace Bigfoot\Bundle\CoreBundle\DataFixtures\ORM;

use Bigfoot\Bundle\CoreBundle\Entity\TagCategory;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadTagCategoryData
 * @package Bigfoot\Bundle\CoreBundle\DataFixtures\ORM
 */
class LoadTagCategoryData implements FixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $defaultCategory = new TagCategory();
        $defaultCategory->setName('Default category');
        $defaultCategory->setSlug('default');

        $manager->persist($defaultCategory);

        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 50;
    }
}