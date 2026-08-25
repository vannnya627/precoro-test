<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\OrderItemResponseDTO;
use App\DTO\Response\OrderResponseDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Exception\EmptyCartException;
use App\Repository\Interface\CartItemRepositoryInterface;
use App\Repository\Interface\OrderRepositoryInterface;
use App\Service\Interface\OrderServiceInterface;

final readonly class OrderService implements OrderServiceInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CartItemRepositoryInterface $cartItemRepository,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function create(User $user): OrderResponseDTO
    {
        $cartItems = $this->cartItemRepository->findWithProducts($user);
        if (empty($cartItems)) {
            throw new EmptyCartException();
        }

        $order = Order::create($user);

        $orderItems = [];
        foreach ($cartItems as $cartItem) {
            $orderItem = $order->consumeCartItem($cartItem);
            $orderItems[] = $orderItem;

            $this->cartItemRepository->remove($cartItem);
        }

        $this->orderRepository->save($order);
        $this->orderRepository->commit();

        $orderItemsDTO = array_map(function (OrderItem $orderItem): OrderItemResponseDTO {
            return OrderItemResponseDTO::create($orderItem);
        }, $orderItems);

        return $this->mapToOrderDTO($order, $orderItemsDTO);
    }

    /**
     * @return list<OrderResponseDTO>
     */
    public function getList(User $user): array
    {
        $orders = $this->orderRepository->findAllByUserIdWithProduct($user->getId());

        $result = [];
        foreach ($orders as $order) {
            $orderItemsDTO = [];
            foreach ($order->getOrderItems() as $orderItem) {
                $orderItemsDTO[] = OrderItemResponseDTO::create($orderItem);
            }

            $result[] = $this->mapToOrderDTO($order, $orderItemsDTO);
        }

        return $result;
    }

    /**
     * @param list<OrderItemResponseDTO> $orderItemsDTO
     */
    private function mapToOrderDTO(Order $order, array $orderItemsDTO): OrderResponseDTO
    {
        return new OrderResponseDTO(
            id: $order->getId(),
            totalPrice: $order->getTotalPrice(),
            status: $order->getStatus()->value,
            orderItems: $orderItemsDTO,
        );
    }
}
