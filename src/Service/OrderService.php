<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\OrderItemResponseDTO;
use App\DTO\Response\OrderResponseDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Exception\EmptyCartException;
use App\Repository\Interface\OrderRepositoryInterface;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Service\Interface\OrderServiceInterface;

final readonly class OrderService implements OrderServiceInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function create(User $user): OrderResponseDTO
    {
        $cart = $user->getCart();

        if (null === $cart || $cart->getCartItems()->isEmpty()) {
            throw new EmptyCartException();
        }

        $order = new Order()
        ->setUser($user);

        $productIds = [];
        foreach ($cart->getCartItems() as $item) {
            $productIds[] = $item->getProduct()->getId();
        }
        $this->productRepository->findBy(['id' => $productIds]);

        $totalPrice = 0;
        $orderItems = [];
        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new OrderItem()
                ->setProduct($cartItem->getProduct())
                ->setQuantity($cartItem->getQuantity())
                ->setPrice($cartItem->getProduct()->getPrice());

            $totalPrice += $orderItem->getPrice() * $orderItem->getQuantity();

            $order->addOrderItem($orderItem);
            $orderItems[] = $orderItem;
        }

        $order->setTotalPrice($totalPrice);

        $cart->getCartItems()->clear();

        $this->orderRepository->save($order);
        $this->orderRepository->commit();

        $orderItemsDTO = array_map(function (OrderItem $orderItem): OrderItemResponseDTO {
            return OrderItemResponseDTO::create($orderItem);
        }, $orderItems);

        return $this->mapToOrderResponseDTO($order, $orderItemsDTO);
    }

    /**
     * @return list<OrderResponseDTO>
     */
    public function getList(User $user): array
    {
        $orders = $this->orderRepository->findAllByUserId($user->getId());

        $result = [];
        foreach ($orders as $order) {
            $orderItemsDTO = [];
            foreach ($order->getOrderItems() as $orderItem) {
                $orderItemsDTO[] = OrderItemResponseDTO::create($orderItem);
            }

            $result[] = $this->mapToOrderResponseDTO($order, $orderItemsDTO);
        }

        return $result;
    }

    /**
     * @param list<OrderItemResponseDTO> $orderItemsDTO
     */
    private function mapToOrderResponseDTO(Order $order, array $orderItemsDTO): OrderResponseDTO
    {
        return new OrderResponseDTO(
            id: $order->getId(),
            totalPrice: $order->getTotalPrice(),
            status: $order->getStatus()->value,
            orderItems: $orderItemsDTO,
        );
    }
}
