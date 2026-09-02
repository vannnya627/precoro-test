<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\OrderItemResponseDTO;
use App\DTO\Response\OrderResponseDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Exception\EmptyCartException;
use App\Repository\Interface\CartRepositoryInterface;
use App\Repository\Interface\OrderRepositoryInterface;
use App\Repository\Interface\UserRepositoryInterface;
use Throwable;

final readonly class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CartRepositoryInterface $cartRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @throws Throwable
     */
    public function create(string $email): OrderResponseDTO
    {
        $user = $this->userRepository->getByEmail($email);
        $cart = $this->cartRepository->findCartWithItemsAndProducts($user);

        if (null === $cart || $cart->cartItems->isEmpty()) {
            throw new EmptyCartException($cart?->id);
        }
        $order = Order::create($user);
        $order->addItemsFromCart($cart->cartItems);

        $cart->clear();

        $this->orderRepository->save($order);
        $this->cartRepository->save($cart);

        $this->orderRepository->commit();

        $orderItemsDTO = array_values(
            $order->orderItems->map(fn(OrderItem $orderItem): OrderItemResponseDTO => OrderItemResponseDTO::create($orderItem))->toArray(),
        );

        return $this->mapToOrderDTO($order, $orderItemsDTO);
    }

    /**
     * @return list<OrderResponseDTO>
     */
    public function getList(string $email): array
    {
        $user = $this->userRepository->getByEmail($email);
        $orders = $this->orderRepository->findAllByUserIdWithProduct($user->id);

        $result = [];
        foreach ($orders as $order) {
            $orderItemsDTO = [];
            foreach ($order->orderItems as $orderItem) {
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
            id: $order->id,
            totalPrice: $order->totalPrice,
            status: $order->status->value,
            orderItems: $orderItemsDTO,
        );
    }
}
