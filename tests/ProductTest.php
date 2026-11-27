<?php

namespace App\Tests;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductCanBeCreated(): void
    {
        $product = new Product();

        $product
            ->setName('Test Product')
            ->setDescription('Test description')
            ->setPrice(1500)
            ->setCreatedAt(new \DateTimeImmutable());

        self::assertSame('Test Product', $product->getName());
        self::assertSame('Test description', $product->getDescription());
        self::assertSame(1500, $product->getPrice());
        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $product->getCreatedAt()
        );
    }
}
