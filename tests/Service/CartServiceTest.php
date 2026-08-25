<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\Request\AddItemRequestDTO;
use App\DTO\Response\CartItemResponseDTO;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\ProductNotFoundException;
use App\Repository\Interface\CartItemRepositoryInterface;
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
    private CartItemRepositoryInterface|MockObject $cartItemRepository;
    private CartService $cartService;

    protected function setUp(): void
    {
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->cartItemRepository = $this->createMock(CartItemRepositoryInterface::class);
        $this->cartService = new CartService($this->cartRepository, $this->productRepository, $this->cartItemRepository);
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

        $cart = $user->getCartOrCreate();

        $product = $this->createProduct();
        $productId = $product->getId();

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($product);

        $cartItem = CartItem::create($cart, $product, $request->quantity);

        $this->cartItemRepository->expects($this->once())
            ->method('findByCartAndProduct')
            ->with($cart, $product)
            ->willReturn($cartItem);

        $this->cartRepository->expects($this->once())
            ->method('saveAndCommit')
            ->with($cart);

        $this->cartService->addItem($request, $user);

        $this->assertEquals(2, $cartItem->getQuantity());
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
            ->method('findById')
            ->with($productId)
            ->willReturn(null);

        $this->cartItemRepository->expects($this->never())
            ->method('findByCartAndProduct');

        $this->cartRepository->expects($this->never())
            ->method('saveAndCommit');

        $this->expectException(ProductNotFoundException::class);
        $this->cartService->addItem($request, $user);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testAddItemWhenCartItemRepositoryReturnNull(): void
    {
        $request = new AddItemRequestDTO(
            productId: 1,
            quantity: 1,
        );

        $user = $this->createUser();

        $cart = $user->getCartOrCreate();

        $product = $this->createProduct();
        $productId = $product->getId();

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($product);

        $this->cartItemRepository->expects($this->once())
            ->method('findByCartAndProduct')
            ->with($cart, $product)
            ->willReturn(null);

        $this->cartRepository->expects($this->once())
            ->method('saveAndCommit')
            ->with($this->isInstanceOf(Cart::class));

        $this->cartService->addItem($request, $user);

        $this->assertCount(1, $cart->getCartItems());

        $addedItem = $cart->getCartItems()[0];

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
            ->method('findById')
            ->willReturn($product);

        $this->cartItemRepository->expects($this->once())
            ->method('findByCartAndProduct')
            ->with(
                $this->callback(function (Cart $savedCart) use ($user) {
                    return $savedCart->getUser() === $user;
                }),
                $this->identicalTo($product)
            )
            ->willReturn(null);

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

        $cart = $user->getCartOrCreate();

        $product = $this->createProduct();

        $cartItem = CartItem::create($cart, $product, 1);

        $this->cartItemRepository->expects($this->once())
            ->method('findWithProducts')
            ->with($user)
            ->willReturn([$cartItem]);

        $expectedResponse = [
            new CartItemResponseDTO(
                productId: $product->getId(),
                productName: $product->getName(),
                quantity: $cartItem->getQuantity(),
            ),
        ];

        $response = $this->cartService->getList($user);
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetListWhenCartIsEmpty(): void
    {
        $user = $this->createUser();
        $this->cartItemRepository->expects($this->once())
            ->method('findWithProducts')
            ->with($user)
            ->willReturn([]);

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
}
