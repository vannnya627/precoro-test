<?php

declare(strict_types=1);

namespace App\Repository;

use Throwable;

trait RepositorySupportTrait
{
    public function save(object $object): void
    {
        assert($this->getClassName() === $object::class);
        $this->getEntityManager()->persist($object);
    }

    /**
     * @throws Throwable
     */
    public function commit(): void
    {
        $this->getEntityManager()->flush();
    }

    public function remove(object $object): void
    {
        assert($this->getClassName() === $object::class);
        $this->getEntityManager()->remove($object);
    }

    /**
     * @throws Throwable
     */
    public function saveAndCommit(object $object): void
    {
        $this->save($object);
        $this->commit();
    }

    /**
     * @throws Throwable
     */
    public function removeAndCommit(object $object): void
    {
        $this->remove($object);
        $this->commit();
    }
}
