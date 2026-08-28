<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\Request\AddItemRequestDTO;
use App\DTO\Response\CartItemResponseDTO;
use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\ProductNotFoundException;
use App\Repository\Interface\CartRepositoryInterface;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Service\CartService;
use App\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
class CartServiceTest extends AbstractTestCase
{
    private CartRepositoryInterface|MockObject $cartRepository;
    private ProductRepositoryInterface|MockObject $productRepository;
    private CartService $cartService;

    protected function setUp(): void
    {
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->cartService = new CartService($this->cartRepository, $this->productRepository);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testAddItem()
    {
        $request = new AddItemRequestDTO(
            productId: 1,
            quantity: 1,
        );

        $user = $this->createUser();

        $cart = $this->createCart($user);
        $user->addCart($cart);

        $product = $this->createProduct();
        $productId = $product->getId();

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($product);

        $cart->addItem($product, 1);

        $this->cartRepository->expects($this->once())
            ->method('saveAndCommit')
            ->with($cart);

        $this->cartService->addItem($request, $user);

        $updatedItem = $cart->getCartItems()->first();

        $this->assertEquals(2, $updatedItem->getQuantity());
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testAddItemWhenThrowsExceptionProductNotFound(): void
    {
        $request = new AddItemRequestDTO(
            productId: 1,
            quantity: 1,
        );

        $user = $this->createUser();
        $productId = $request->productId;

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willThrowException(new ProductNotFoundException());

        $this->cartRepository->expects($this->never())
            ->method('saveAndCommit');

        $this->expectException(ProductNotFoundException::class);
        $this->cartService->addItem($request, $user);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testAddCompletelyNewItemToCart(): void
    {
        $request = new AddItemRequestDTO(
            productId: 1,
            quantity: 1,
        );

        $user = $this->createUser();

        $cart = $this->createCart($user);
        $user->addCart($cart);

        $product = $this->createProduct();
        $productId = $product->getId();

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($product);

        $this->cartRepository->expects($this->once())
            ->method('saveAndCommit')
            ->with($this->isInstanceOf(Cart::class));

        $this->cartService->addItem($request, $user);

        $this->assertCount(1, $cart->getCartItems());

        $addedItem = $cart->getCartItems()->first();

        $this->assertEquals(1, $addedItem->getQuantity());
        $this->assertSame($product, $addedItem->getProduct());
        $this->assertSame($cart, $addedItem->getCart());
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testAddItemWhenUserHasNoCart(): void
    {
        $request = new AddItemRequestDTO(productId: 1, quantity: 1);

        $user = $this->createUser();

        $product = $this->createProduct();

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->willReturn($product);

        $this->cartRepository->expects($this->once())
            ->method('saveAndCommit')
            ->with($this->callback(function (Cart $savedCart) use ($user) {
                return $savedCart->getUser() === $user;
            }));

        $this->cartService->addItem($request, $user);

        $this->assertNotNull($user->getCart());
        $this->assertCount(1, $user->getCart()->getCartItems());
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetList(): void
    {
        $user = $this->createUser();

        $cart = $this->createCart($user);
        $user->addCart($cart);

        $product = $this->createProduct();

        $cart->addItem($product, 1);

        $this->cartRepository->expects($this->once())
            ->method('findCartWithItemsAndProducts')
            ->with($user)
            ->willReturn($cart);

        $expectedResponse = [
            new CartItemResponseDTO(
                productId: $product->getId(),
                productName: $product->getName(),
                quantity: $cart->getCartItems()->first()->getQuantity(),
            ),
        ];

        $response = $this->cartService->getList($user);
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetListWhenCartIsNullOrEmpty(): void
    {
        $user = $this->createUser();

        $cart = $this->createCart($user);
        $user->addCart($cart);

        $this->cartRepository->expects($this->once())
            ->method('findCartWithItemsAndProducts')
            ->with($user)
            ->willReturn($cart);

        $response = $this->cartService->getList($user);

        $this->assertEquals([], $response);
    }

    /**
     * @throws \ReflectionException
     */
    private function createUser(): User
    {
        $userId = 1;
        $user = User::createCustomer('test@gmail.com', 'dsgsfggdsgds');
        $this->setEntityId($user, $userId);

        return $user;
    }

    /**
     * @throws \ReflectionException
     */
    private function createProduct(): Product
    {
        $productId = 1;
        $product = Product::create('Test Product', 'Test Description', 100);
        $this->setEntityId($product, $productId);

        return $product;
    }

    /**
     * @throws \ReflectionException
     */
    private function createCart(User $user): Cart
    {
        $cartId = 1;
        $cart = Cart::create($user);
        $this->setEntityId($cart, $cartId);

        return $cart;
    }
}
