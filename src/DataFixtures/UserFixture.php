<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);
        $user = new User();
        $user->setEmail('test@test.ru');
        $user->setApiToken('secret');
        $user->setRoles(['ROLE_API_USER']);
        $manager->persist($user);

        $manager->flush();
    }
}
