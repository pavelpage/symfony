<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getManager()
    {
        return $this->getEntityManager();
    }

    // /**
    //  * @return Image[] Returns an array of Image objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Image
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @param $imageItem Image
     * @param $width
     * @param $height
     */
    public function addResize($imageItem, $width, $height)
    {
        $resizes = $imageItem->getResizes();
        $resizes[] = ['width' => $width, 'height' => $height];

        $this->resizes = $resizes;
        $imageItem->setResizes($resizes);

        $this->getEntityManager()->flush();
    }

    public function deleteResize($width, $height)
    {
        $resizes = collect($this->resizes);

        $resizes = array_filter($resizes,function($item) use ($width, $height){
            return !($item['width'] == $width && $item['height'] == $height);
        });

        $this->resizes = $resizes->values()->toArray();
        $this->save();
    }
}
