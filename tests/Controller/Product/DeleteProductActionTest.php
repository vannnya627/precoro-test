<?php

declare(strict_types=1);

namespace App\Tests\Controller\Product;

use App\Entity\Product;
use App\Tests\AbstractWebTestCase;

class DeleteProductActionTest extends AbstractWebTestCase
{
    public function testDeleteProduct(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $product = $this->createProduct($em);
        $productId = $product->id;

        $client->request(
            'DELETE',
            '/api/v1/product/' . $productId,
        );

        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('product deleted successfully', $responseContent['message']);

        $em->clear();
        $productInDb = $em->getRepository(Product::class)->find($productId);
        $this->assertNull($productInDb);
    }

    public function testDeleteProductNotFound(): void
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/v1/product/99999',
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
