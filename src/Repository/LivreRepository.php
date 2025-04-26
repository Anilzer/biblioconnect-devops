<?php

namespace App\Repository;

use App\Entity\Livre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Livre>
 *
 * @method Livre|null find($id, $lockMode = null, $lockVersion = null)
 * @method Livre|null findOneBy(array $criteria, array $orderBy = null)
 * @method Livre[]    findAll()
 * @method Livre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LivreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livre::class);
    }

    // Méthode pour rechercher les livres par titre, auteur ou catégorie
    public function searchByQuery($query)
    {
        if (!$query) {
            return [];
        }

        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.category', 'c')
            ->leftJoin('l.auteur', 'a');

        $queryParts = explode(' ', $query);
        $prenom = $queryParts[0] ?? '';
        $nom = $queryParts[1] ?? '';

        $orX = $qb->expr()->orX();

        // Recherche complète sur titre
        $orX->add($qb->expr()->like('l.title', ':fullQuery'));
        // Recherche complète sur la catégorie
        $orX->add($qb->expr()->like('c.name', ':fullQuery'));

        // Si on a à la fois un prénom et un nom, on cherche sur les deux
        if (!empty($prenom) && !empty($nom)) {
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->like('a.firstname', ':prenom'),
                    $qb->expr()->like('a.name', ':nom')
                )
            );
            $qb->setParameter('prenom', '%' . $prenom . '%');
            $qb->setParameter('nom', '%' . $nom . '%');
        } else {
            // Sinon on cherche large (comme avant)
            $orX->add($qb->expr()->like('a.firstname', ':fullQuery'));
            $orX->add($qb->expr()->like('a.name', ':fullQuery'));
        }

        $qb->andWhere($orX)
            ->setParameter('fullQuery', '%' . $query . '%');

        return $qb->getQuery()->getResult();

//    /**
//     * @return Livre[] Returns an array of Livre objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Livre
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
}
