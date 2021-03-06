<?php

namespace AppBundle\DataFixtures;

use AppBundle\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class TagFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $data) {
            $tag = new Tag();
            $tag->setName($data);
            $tag->setUser($this->getReference('user'));

            foreach (range(0, ContactFixtures::ROWS, rand(1, ContactFixtures::ROWS)) as $contact) {
                $tag->addContact($this->getReference(sprintf('%s.%d', ContactFixtures::PREFIX, $contact)));
            }

            $manager->persist($tag);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'jedzenie',
            'rachunki',
            'rozrywka',
            'wyjazdy',
        ];
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            ContactFixtures::class,
        ];
    }
}
