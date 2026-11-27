<?php

namespace App\Tests\Repository;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->entityManager->getConnection()->beginTransaction();
    }

    public function testProductCanBePersistedAndRetrieved(): void
    {
        $product = new Product();

        $product
            ->setName('Integration Test Product')
            ->setDescription('Testing PostgreSQL')
            ->setPrice(2500)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $id = $product->getId();

        self::assertNotNull($id);

        $this->entityManager->clear();

        $repository = $this->entityManager
            ->getRepository(Product::class);

        $savedProduct = $repository->find($id);

        self::assertInstanceOf(Product::class, $savedProduct);
        self::assertSame('Integration Test Product', $savedProduct->getName());
        self::assertSame(2500, $savedProduct->getPrice());
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $connection = $this->entityManager->getConnection();

            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
        }

        parent::tearDown();
    }
}
