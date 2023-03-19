<?php

namespace Kamil\CategorySpecial\Repository;

use Doctrine\DBAL\Connection;
use PDO;

class CategorySpecialRepository
{
    private $connection;
    private $dbPrefix;

    public function __construct(Connection $connection, $dbPrefix)
    {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
    }

    public function findIdByCategory($categoryId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('`id_reviewer`')
            ->from($this->dbPrefix . 'category_special')
            ->where('`id_category` = :category_id')
        ;

        $queryBuilder->setParameter('category_id', $categoryId);

        return (int) $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
    }

    public function getCategorySpecialStatus($categoryId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('`is_special`')
            ->from($this->dbPrefix . 'category_special')
            ->where('`id_category` = :category_id')
        ;

        $queryBuilder->setParameter('category_id', $categoryId);

        return (bool) $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
    }
}
