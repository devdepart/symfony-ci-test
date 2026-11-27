<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->entityManager
            ->createQuery('DELETE FROM App\Entity\Product p')
            ->execute();
    }

    public function testProductIndexDisplaysEmptyState(): void
    {
        $crawler = $this->client->request('GET', '/products');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '.empty-state',
            'No products have been created yet.'
        );

        self::assertCount(0, $crawler->filter('table'));
    }

    public function testProductCanBeCreated(): void
    {
        $crawler = $this->client->request('GET', '/products/new');

        self::assertResponseIsSuccessful();

        $form = $crawler
            ->filter('form[name="product"]')
            ->form();

        $form['product[name]'] = 'Test product';
        $form['product[description]'] = 'Product description';
        $form['product[price]'] = '125.50';

        $this->client->submit($form);

        self::assertResponseRedirects();

        $this->entityManager->clear();

        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => 'Test product']);

        self::assertInstanceOf(Product::class, $product);
        self::assertSame(12550, $product->getPrice());
        self::assertSame(
            'Product description',
            $product->getDescription()
        );
    }

    public function testInvalidPriceIsNotSaved(): void
    {
        $crawler = $this->client->request('GET', '/products/new');

        $form = $crawler
            ->filter('form[name="product"]')
            ->form();

        $form['product[name]'] = 'Invalid product';
        $form['product[description]'] = '';
        $form['product[price]'] = '12.999';

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);

        $count = $this->entityManager
            ->getRepository(Product::class)
            ->count([]);

        self::assertSame(0, $count);
    }

    public function testNegativePriceIsNotSaved(): void
    {
        $crawler = $this->client->request('GET', '/products/new');

        $form = $crawler
            ->filter('form[name="product"]')
            ->form();

        $form['product[name]'] = 'Negative price product';
        $form['product[description]'] = '';
        $form['product[price]'] = '-1.00';

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);

        $count = $this->entityManager
            ->getRepository(Product::class)
            ->count([]);

        self::assertSame(0, $count);
    }

    public function testProductCanBeViewed(): void
    {
        $product = $this->createProduct();

        $this->client->request(
            'GET',
            '/products/'.$product->getId()
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Product #'.$product->getId());
        self::assertSelectorTextContains('.details', '$125.50');
    }

    public function testProductCanBeEditedWithoutChangingCreatedAt(): void
    {
        $product = $this->createProduct();

        $productId = $product->getId();

        $this->entityManager->clear();

        $product = $this->entityManager
            ->getRepository(Product::class)
            ->find($productId);

        self::assertInstanceOf(Product::class, $product);

        $originalCreatedAt = $product->getCreatedAt();

        $crawler = $this->client->request(
            'GET',
            '/products/'.$productId.'/edit'
        );

        self::assertResponseIsSuccessful();

        $form = $crawler
            ->filter('form[name="product"]')
            ->form();

        $form['product[name]'] = 'Updated product';
        $form['product[description]'] = 'Updated description';
        $form['product[price]'] = '99.99';

        $this->client->submit($form);

        self::assertResponseRedirects();

        $this->entityManager->clear();

        $updatedProduct = $this->entityManager
            ->getRepository(Product::class)
            ->find($productId);

        self::assertInstanceOf(Product::class, $updatedProduct);
        self::assertSame('Updated product', $updatedProduct->getName());
        self::assertSame(9999, $updatedProduct->getPrice());
        self::assertEquals(
            $originalCreatedAt,
            $updatedProduct->getCreatedAt()
        );
    }

    public function testProductCannotBeDeletedWithoutCsrfToken(): void
    {
        $product = $this->createProduct();

        $this->client->request(
            'POST',
            '/products/'.$product->getId().'/delete'
        );

        self::assertResponseStatusCodeSame(403);

        $this->entityManager->clear();

        $storedProduct = $this->entityManager
            ->getRepository(Product::class)
            ->find($product->getId());

        self::assertInstanceOf(Product::class, $storedProduct);
    }

    public function testProductCanBeDeletedWithCsrfToken(): void
    {
        $product = $this->createProduct();

        $id = $product->getId();

        $crawler = $this->client->request(
            'GET',
            '/products/'.$id
        );

        $token = $crawler
            ->filter('form[action="/products/'.$id.'/delete"] input[name="_token"]')
            ->attr('value');

        self::assertNotNull($token);

        $this->client->request(
            'POST',
            '/products/'.$id.'/delete',
            [
                '_token' => $token,
            ]
        );

        self::assertResponseRedirects('/products');

        $this->entityManager->clear();

        $deletedProduct = $this->entityManager
            ->getRepository(Product::class)
            ->find($id);

        self::assertNull($deletedProduct);
    }

    public function testUnknownProductReturnsNotFound(): void
    {
        $this->client->request('GET', '/products/999999999');

        self::assertResponseStatusCodeSame(404);
    }

    private function createProduct(): Product
    {
        $product = new Product();

        $product
            ->setName('Test product')
            ->setDescription('Product description')
            ->setPrice(12550)
        ;

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}
