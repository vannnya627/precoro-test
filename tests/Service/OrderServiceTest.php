<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\EmptyCartException;
use App\Repository\Interface\CartRepositoryInterface;
use App\Repository\Interface\OrderRepositoryInterface;
use App\Service\OrderService;
use App\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
class OrderServiceTest extends AbstractTestCase
{
    private OrderRepositoryInterface|MockObject $orderRepository;
    private CartRepositoryInterface|MockObject $cartRepository;
    private OrderService $orderService;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->orderService = new OrderService($this->orderRepository, $this->cartRepository);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testCreate(): void
    {
        $user = $this->createUser();

        $cart = $this->createCart($user);
        $user->addCart($cart);

        $product = $this->createProduct();

        $cart->addItem($product, 2);

        $this->cartRepository->expects($this->once())
            ->method('findCartWithItemsAndProducts')
            ->with($user)
            ->willReturn($cart);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Order $savedOrder) use ($user) {
                $this->assertSame($user, $savedOrder->getUser());
                $this->assertEquals(246, $savedOrder->getTotalPrice());
                $this->assertCount(1, $savedOrder->getOrderItems());

                $this->setEntityId($savedOrder, 99);
            });

        $this->cartRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Cart $savedCart) use ($user) {
                $this->assertSame($user, $savedCart->user);

                $this->assertCount(0, $savedCart->cartItems);
            });

        $this->orderRepository->expects($this->once())
            ->method('commit');

        $response = $this->orderService->create($user);

        $this->assertEquals(99, $response->id);
        $this->assertEquals(246, $response->totalPrice);
        $this->assertEquals('NEW', $response->status);
        $this->assertCount(1, $response->orderItems);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testCreateWhenThrowsEmptyCartException(): void
    {
        $user = $this->createUser();

        $cart = $this->createCart($user);
        $user->addCart($cart);
        $this->cartRepository->expects($this->once())
            ->method('findCartWithItemsAndProducts')
            ->with($user)
            ->willReturn($cart);

        $this->orderRepository->expects($this->never())
            ->method('save');

        $this->cartRepository->expects($this->never())
            ->method('save');

        $this->orderRepository->expects($this->never())
            ->method('commit');

        $this->expectException(EmptyCartException::class);
        $this->orderService->create($user);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetList(): void
    {
        $user = $this->createUser();

        $product = $this->createProduct();

        $cart = $this->createCart($user);
        $user->addCart($cart);

        $order = Order::create($user);
        $this->setEntityId($order, 10);

        $cart->addItem($product, 2);

        $order->addItemsFromCart($cart->cartItems);

        $this->orderRepository->expects($this->once())
            ->method('findAllByUserIdWithProduct')
            ->with($user->id)
            ->willReturn([$order]);

        $response = $this->orderService->getList($user);

        $this->assertCount(1, $response);

        $orderDTO = $response[0];

        $this->assertEquals(10, $orderDTO->id);
        $this->assertEquals(246, $orderDTO->totalPrice);
        $this->assertEquals('NEW', $orderDTO->status);

        $this->assertCount(1, $orderDTO->orderItems);
        $this->assertEquals(1, $orderDTO->orderItems[0]->productId);
        $this->assertEquals(2, $orderDTO->orderItems[0]->quantity);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetListWhenUserHasNoOrders(): void
    {
        $user = $this->createUser();
        $this->orderRepository->expects($this->once())
            ->method('findAllByUserIdWithProduct')
            ->with($user->id)
            ->willReturn([]);

        $response = $this->orderService->getList($user);

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
        $product = Product::create('Test Product', 'Test Description', 123);
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
