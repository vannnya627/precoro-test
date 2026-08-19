<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\EmptyCartException;
use App\Repository\Interface\OrderRepositoryInterface;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Service\OrderService;
use App\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
class OrderServiceTest extends AbstractTestCase
{
    private OrderRepositoryInterface|MockObject $orderRepository;
    private ProductRepositoryInterface|MockObject $productRepository;
    private OrderService $orderService;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->orderService = new OrderService($this->orderRepository, $this->productRepository);
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testCreate(): void
    {
        $user = new User();
        $this->setEntityId($user, 1);

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
            ->setQuantity(2);

        $cart->addCartItem($cartItem);

        $this->productRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1]]);

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Order $savedOrder) use ($user) {
                $this->assertSame($user, $savedOrder->getUser());
                $this->assertEquals(246, $savedOrder->getTotalPrice());
                $this->assertCount(1, $savedOrder->getOrderItems());

                $this->setEntityId($savedOrder, 99);
            });

        $this->orderRepository->expects($this->once())
            ->method('commit');

        $response = $this->orderService->create($user);

        $this->assertEquals(99, $response->id);
        $this->assertEquals(246, $response->totalPrice);
        $this->assertEquals('NEW', $response->status);
        $this->assertCount(1, $response->orderItems);

        $this->assertCount(0, $cart->getCartItems());
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function testCreateWhenThrowsEmptyCartException(): void
    {
        $user = new User();
        $this->setEntityId($user, 1);

        $this->productRepository->expects($this->never())
            ->method('findBy');

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
        $user = new User();
        $this->setEntityId($user, 1);

        $user->setCart(new Cart());

        $this->expectException(EmptyCartException::class);
        $this->orderService->create($user);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetList(): void
    {
        $user = new User();
        $this->setEntityId($user, 1);

        $product = new Product()
            ->setName('name')
            ->setDescription('description')
            ->setPrice(123);
        $this->setEntityId($product, 1);

        $order = new Order()
            ->setUser($user)
            ->setTotalPrice(246);
        $this->setEntityId($order, 10);

        $orderItem = new OrderItem()
            ->setProduct($product)
            ->setQuantity(2)
            ->setPrice(123);

        $order->addOrderItem($orderItem);

        $this->orderRepository->expects($this->once())
            ->method('findAllByUserId')
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
        $user = new User();
        $this->setEntityId($user, 1);

        $this->orderRepository->expects($this->once())
            ->method('findAllByUserId')
            ->with($user->getId())
            ->willReturn([]);

        $response = $this->orderService->getList($user);

        $this->assertEquals([], $response);
    }
}
