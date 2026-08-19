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

        $user = new User()
            ->setPassword('12345')
            ->setEmail('test@gmail.com')
            ->setRoles(['ROLE_USER']);
        $userId = 1;
        $this->setEntityId($user, $userId);

        $cart = new Cart();
        $user->setCart($cart);

        $productId = 1;
        $product = new Product()
            ->setName('name')
            ->setDescription('description')
            ->setPrice(123);
        $this->setEntityId($product, $productId);

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($product);

        $cartItem = new CartItem()
            ->setCart($cart)
            ->setProduct($product)
            ->setQuantity(1);

        $this->cartItemRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'cart' => $cart,
                'product' => $product,
            ])
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

        $user = new User()
            ->setPassword('12345')
            ->setEmail('test@gmail.com')
            ->setRoles(['ROLE_USER']);
        $userId = 1;
        $this->setEntityId($user, $userId);

        $productId = 1;

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn(null);

        $this->cartItemRepository->expects($this->never())
            ->method('findOneBy');

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

        $user = new User()
            ->setPassword('12345')
            ->setEmail('test@gmail.com')
            ->setRoles(['ROLE_USER']);
        $userId = 1;
        $this->setEntityId($user, $userId);

        $cart = new Cart();
        $user->setCart($cart);

        $productId = 1;
        $product = new Product()
            ->setName('name')
            ->setDescription('description')
            ->setPrice(123);
        $this->setEntityId($product, $productId);

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($product);

        $this->cartItemRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'cart' => $cart,
                'product' => $product,
            ])
            ->willReturn(null);

        $this->cartRepository->expects($this->once())
            ->method('saveAndCommit')
            ->with($cart);

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

        $user = new User();
        $this->setEntityId($user, 1);

        $product = new Product()->setName('name')->setPrice(123);
        $this->setEntityId($product, 1);

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->willReturn($product);

        $this->cartItemRepository->expects($this->once())
            ->method('findOneBy')
            ->with($this->callback(function ($criteria) use ($product, $user) {
                return $criteria['cart'] instanceof Cart
                    && $criteria['cart']->getUser() === $user
                    && $criteria['product'] === $product;
            }))
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
        $user = new User()
            ->setPassword('12345')
            ->setEmail('test@gmail.com')
            ->setRoles(['ROLE_USER']);
        $userId = 1;
        $this->setEntityId($user, $userId);

        $cart = new Cart();
        $user->setCart($cart);

        $productId = 1;
        $product = new Product()
            ->setName('name')
            ->setDescription('description')
            ->setPrice(123);
        $this->setEntityId($product, $productId);

        $cartItem = new CartItem()
            ->setCart($cart)
            ->setProduct($product)
            ->setQuantity(1);

        $cart->addCartItem($cartItem);

        $this->productRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$productId]])
            ->willReturn([$product]);

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

    public function testGetListWhenCartIsEmpty(): void
    {
        $user = new User();

        $this->productRepository->expects($this->never())->method('findBy');

        $response = $this->cartService->getList($user);

        $this->assertEquals([], $response);
    }
}
