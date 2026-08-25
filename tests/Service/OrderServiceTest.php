<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\EmptyCartException;
use App\Repository\Interface\CartItemRepositoryInterface;
use App\Repository\Interface\OrderRepositoryInterface;
use App\Service\OrderService;
use App\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
class OrderServiceTest extends AbstractTestCase
{
    private OrderRepositoryInterface|MockObject $orderRepository;
    private CartItemRepositoryInterface|MockObject $cartItemRepository;
    private OrderService $orderService;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->cartItemRepository = $this->createMock(CartItemRepositoryInterface::class);
        $this->orderService = new OrderService($this->orderRepository, $this->cartItemRepository);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testCreate(): void
    {
        $user = $this->createUser();

        $cart = $user->getCartOrCreate();

        $product = $this->createProduct();

        $cartItem = CartItem::create($cart, $product, 2);

        $this->cartItemRepository->expects($this->once())
            ->method('findWithProducts')
            ->with($user)
            ->willReturn([$cartItem]);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Order $savedOrder) use ($user) {
                $this->assertSame($user, $savedOrder->getUser());
                $this->assertEquals(246, $savedOrder->getTotalPrice());
                $this->assertCount(1, $savedOrder->getOrderItems());

                $this->setEntityId($savedOrder, 99);
            });

        $this->cartItemRepository->expects($this->once())
            ->method('remove')
            ->with($cartItem);

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

        $this->cartItemRepository->expects($this->once())
            ->method('findWithProducts')
            ->with($user)
            ->willReturn([]);

        $this->cartItemRepository->expects($this->never())
            ->method('remove');

        $this->orderRepository->expects($this->never())
            ->method('save');

        $this->orderRepository->expects($this->never())
            ->method('commit');

        $this->expectException(EmptyCartException::class);
        $this->orderService->create($user);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testCreateWhenCartIsEmptyThrowsException(): void
    {
        $user = $this->createUser();
        $cart = $user->getCartOrCreate();

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

        $cart = $user->getCartOrCreate();

        $cartItem = CartItem::create($cart, $product, 2);

        $order = Order::create($user);
        $this->setEntityId($order, 10);

        $orderItem = $order->consumeCartItem($cartItem);

        $this->orderRepository->expects($this->once())
            ->method('findAllByUserIdWithProduct')
            ->with($user->getId())
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
            ->with($user->getId())
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
}
