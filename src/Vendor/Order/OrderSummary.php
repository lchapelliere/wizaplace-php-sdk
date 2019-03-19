<?php
/**
 * @copyright Copyright (c) Wizacha
 * @license Proprietary
 */
declare(strict_types=1);

namespace Wizaplace\SDK\Vendor\Order;

use Wizaplace\SDK\Vendor\Order\Order as VendorOrder;

/**
 * Class OrderSummary
 * @package Wizaplace\SDK\Vendor\Order
 */
final class OrderSummary
{
    /** @var string */
    private $customerEmail;

    /** @var string */
    private $customerFirstName;

    /** @var string */
    private $customerLastName;

    /** @var int */
    private $orderId;

    /** @var OrderStatus */
    private $status;

    /** @var \DateTimeImmutable */
    private $createdAt;

    /** @var int */
    private $customerUserId;

    /** @var int */
    private $basketId;

    /** @var float */
    private $total;

    /** @var int  */
    private $companyId;

    /** @var \DateTimeImmutable */
    private $lastStatusChange;

    /**
     * @internal
     *
     * @param array $data
     *
     * @throws \Exception
     */
    public function __construct(array $data)
    {
        $this->customerEmail = $data['email'];
        $this->customerFirstName = $data['customer_firstname'];
        $this->customerLastName = $data['customer_lastname'];
        $this->orderId = $data['order_id'];
        $this->companyId = $data['company_id'];
        $this->status = new OrderStatus($data['status']);
        $this->createdAt = new \DateTimeImmutable('@'.$data['timestamp']);
        $this->customerUserId = $data['user_id'];
        $this->basketId = $data['basket_id'];
        $this->total = $data['total'];
        $this->lastStatusChange = VendorOrder::denormalizeLastStatusChange($data['last_status_change'] ?? null);
    }

    /**
     * @return string
     */
    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    /**
     * @return string
     */
    public function getCustomerFirstName(): string
    {
        return $this->customerFirstName;
    }

    /**
     * @return string
     */
    public function getCustomerLastName(): string
    {
        return $this->customerLastName;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    /**
     * @return OrderStatus
     */
    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return int
     */
    public function getCustomerUserId(): int
    {
        return $this->customerUserId;
    }

    /**
     * @return int
     */
    public function getBasketId(): int
    {
        return $this->basketId;
    }

    /**
     * @return float
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getLastStatusChange(): ?\DateTimeImmutable
    {
        return $this->lastStatusChange;
    }
}
