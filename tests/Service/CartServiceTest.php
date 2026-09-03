<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\Request\AddItemRequestDTO;
use App\DTO\Response\CartItemResponseDTO;
use App\Repository\Interface\CartRepositoryInterface;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Repository\Interface\UserRepositoryInterface;
use App\Service\CartService;
use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\ProductNotFoundException;
use App\Tests\AbstractTestCase;
use App\ValueObject\Email;
use App\ValueObject\Price;
use App\ValueObject\Quantity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Throwable;

#[AllowMockObjectsWithoutExpectations]
class CartServiceTest extends AbstractTestCase
{
    private CartRepositoryInterface|MockObject $cartRepository;
    private ProductRepositoryInterface|MockObject $productRepository;
    private UserRepositoryInterface|MockObject $userRepository;
    private CartService $cartService;

    protected function setUp(): void
    {
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->cartService = new CartService($this->cartRepository, $this->productRepository, $this->userRepository);
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
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
        $productId = $product->id;

        $this->userRepository->expects($this->once())
            ->method('getByEmail')
            ->with($user->email)
            ->willReturn($user);

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($product);

        $cart->addItem($product, Quantity::create(1));

        $this->cartRepository->expects($this->once())
            ->method('saveAndCommit')
            ->with($cart);

        $this->cartService->addItem($request, $user->email);

        $updatedItem = $cart->cartItems->first();

        $this->assertEquals(2, $updatedItem->quantity->value);
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testAddItemWhenThrowsExceptionProductNotFound(): void
    {
        $request = new AddItemRequestDTO(
            productId: 1,
            quantity: 1,
        );

        $user = $this->createUser();
        $productId = $request->productId;

        $this->userRepository->expects($this->once())
            ->method('getByEmail')
            ->with($user->email)
            ->willReturn($user);

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willThrowException(new ProductNotFoundException($productId));

        $this->cartRepository->expects($this->never())
            ->method('saveAndCommit');

        $this->expectException(ProductNotFoundException::class);
        $this->cartService->addItem($request, $user->email);
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
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
        $productId = $product->id;

        $this->userRepository->expects($this->once())
            ->method('getByEmail')
            ->with($user->email)
            ->willReturn($user);

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId)
            ->willReturn($product);

        $this->cartRepository->expects($this->once())
            ->method('saveAndCommit')
            ->with($this->isInstanceOf(Cart::class));

        $this->cartService->addItem($request, $user->email);

        $this->assertCount(1, $cart->cartItems);

        $addedItem = $cart->cartItems->first();

        $this->assertEquals(1, $addedItem->quantity->value);
        $this->assertSame($product, $addedItem->product);
        $this->assertSame($cart, $addedItem->cart);
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testAddItemWhenUserHasNoCart(): void
    {
        $request = new AddItemRequestDTO(productId: 1, quantity: 1);

        $user = $this->createUser();

        $product = $this->createProduct();

        $this->userRepository->expects($this->once())
            ->method('getByEmail')
            ->with($user->email)
            ->willReturn($user);

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->willReturn($product);

        $this->cartRepository->expects($this->once())
            ->method('saveAndCommit')
            ->with($this->callback(fn(Cart $savedCart) => $savedCart->user === $user));

        $this->cartService->addItem($request, $user->email);

        $this->assertNotNull($user->cart);
        $this->assertCount(1, $user->cart->cartItems);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetList(): void
    {
        $user = $this->createUser();

        $cart = $this->createCart($user);
        $user->addCart($cart);

        $product = $this->createProduct();

        $cart->addItem($product, Quantity::create(1));

        $this->userRepository->expects($this->once())
            ->method('getByEmail')
            ->with($user->email)
            ->willReturn($user);

        $this->cartRepository->expects($this->once())
            ->method('findCartWithItemsAndProducts')
            ->with($user)
            ->willReturn($cart);

        $expectedResponse = [
            new CartItemResponseDTO(
                productId: $product->id,
                productName: $product->name,
                quantity: $cart->cartItems->first()->quantity->value,
            ),
        ];

        $response = $this->cartService->getList($user->email);
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetListWhenCartIsNullOrEmpty(): void
    {
        $user = $this->createUser();

        $cart = $this->createCart($user);
        $user->addCart($cart);

        $this->userRepository->expects($this->once())
            ->method('getByEmail')
            ->with($user->email)
            ->willReturn($user);

        $this->cartRepository->expects($this->once())
            ->method('findCartWithItemsAndProducts')
            ->with($user)
            ->willReturn($cart);

        $response = $this->cartService->getList($user->email);

        $this->assertEquals([], $response);
    }

    /**
     * @throws ReflectionException
     */
    private function createUser(): User
    {
        $userId = 1;
        $user = User::createCustomer(Email::create('test@gmail.com'), 'dsgsfggdsgds');
        $this->setEntityId($user, $userId);

        return $user;
    }

    /**
     * @throws ReflectionException
     */
    private function createProduct(): Product
    {
        $productId = 1;
        $product = Product::create('Test Product', 'Test Description', Price::create(100));
        $this->setEntityId($product, $productId);

        return $product;
    }

    /**
     * @throws ReflectionException
     */
    private function createCart(User $user): Cart
    {
        $cartId = 1;
        $cart = Cart::create($user);
        $this->setEntityId($cart, $cartId);

        return $cart;
    }
}
