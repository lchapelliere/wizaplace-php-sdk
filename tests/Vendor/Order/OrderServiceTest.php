<?php
/**
 * @copyright Copyright (c) Wizacha
 * @license Proprietary
 */
declare(strict_types=1);

namespace Wizaplace\SDK\Tests\Vendor\Order;

use Wizaplace\SDK\Shipping\MondialRelayLabel;
use Wizaplace\SDK\Tests\ApiTestCase;
use Wizaplace\SDK\Order\OrderService as BaseOrderService;
use Wizaplace\SDK\Vendor\Order\CreateLabelCommand;
use Wizaplace\SDK\Vendor\Order\CreateShipmentCommand;
use Wizaplace\SDK\Vendor\Order\Order;
use Wizaplace\SDK\Vendor\Order\OrderAddress;
use Wizaplace\SDK\Vendor\Order\OrderItem;
use Wizaplace\SDK\Vendor\Order\OrderService;
use Wizaplace\SDK\Vendor\Order\OrderStatus;
use Wizaplace\SDK\Vendor\Order\OrderSummary;
use Wizaplace\SDK\Vendor\Order\OrderTax;
use Wizaplace\SDK\Vendor\Order\Shipment;
use Wizaplace\SDK\Vendor\Order\Tax;

class OrderServiceTest extends ApiTestCase
{
    public function testAcceptingAnOrder()
    {
        $orderService = $this->buildVendorOrderService();

        $orderService->acceptOrder(5);

        static::assertTrue(OrderStatus::PROCESSING_SHIPPING()->equals($orderService->getOrderById(5)->getStatus()));
    }

    public function testDecliningAnOrderWithoutReason()
    {
        $orderService = $this->buildVendorOrderService();

        $orderService->declineOrder(5);

        $order = $orderService->getOrderById(5);
        static::assertTrue(OrderStatus::VENDOR_DECLINED()->equals($order->getStatus()));
        static::assertEmpty($order->getDeclineReason());
    }

    public function testDecliningAnOrderWithReason()
    {
        $orderService = $this->buildVendorOrderService();

        $orderService->declineOrder(5, 'Product out of stock');

        $order = $orderService->getOrderById(5);
        static::assertTrue(OrderStatus::VENDOR_DECLINED()->equals($order->getStatus()));
        static::assertSame('Product out of stock', $order->getDeclineReason());
    }

    public function testSetInvoiceNumber(): void
    {
        $orderService = $this->buildVendorOrderService();

        $invoiceNumber = $orderService->getOrderById(5)->getInvoiceNumber();
        static::assertSame("", $invoiceNumber);

        $orderService->setInvoiceNumber(5, "00072");

        $invoiceNumber = $orderService->getOrderById(5)->getInvoiceNumber();

        static::assertSame("00072", $invoiceNumber);

        $this->expectException(\Throwable::class); // can't set the invoice number twice
        $orderService->setInvoiceNumber(5, "00073");
    }

    public function testListOrders(): void
    {
        $orders = $this->buildVendorOrderService()->listOrders();

        static::assertContainsOnly(OrderSummary::class, $orders);
        static::assertTrue(count($orders) >= 2);

        // To fix random sort
        usort($orders, function (OrderSummary $a, OrderSummary $b): int {
            return $a->getOrderId() <=> $b->getOrderId();
        });

        $order = array_shift($orders);
        static::assertSame(4, $order->getOrderId());
        static::assertSame(7, $order->getCustomerUserId());
        static::assertSame(3, $order->getCompanyId());
        static::assertSame('customer-1@world-company.com', $order->getCustomerEmail());
        static::assertSame('Paul', $order->getCustomerFirstName());
        static::assertSame('Martin', $order->getCustomerLastName());
        static::assertGreaterThan(1500000000, $order->getCreatedAt()->getTimestamp());
        static::assertTrue(OrderStatus::COMPLETED()->equals($order->getStatus()));
        static::assertInstanceOf(\DateTimeImmutable::class, $order->getLastStatusChange());

        $order = array_shift($orders);
        static::assertSame(5, $order->getOrderId());
        static::assertSame(7, $order->getCustomerUserId());
        static::assertSame(3, $order->getCompanyId());
        static::assertSame('customer-1@world-company.com', $order->getCustomerEmail());
        static::assertSame('Paul', $order->getCustomerFirstName());
        static::assertSame('Martin', $order->getCustomerLastName());
        static::assertGreaterThan(1500000000, $order->getCreatedAt()->getTimestamp());
        static::assertTrue(OrderStatus::STANDBY_VENDOR()->equals($order->getStatus()));
        static::assertTrue(OrderStatus::STANDBY_VENDOR()->equals($order->getStatus()));
        static::assertInstanceOf(\DateTimeImmutable::class, $order->getLastStatusChange());
    }

    public function testListOrdersWithFilter(): void
    {
        $orders = $this->buildVendorOrderService()->listOrders(OrderStatus::STANDBY_VENDOR());

        static::assertContainsOnly(OrderSummary::class, $orders);
        static::assertCount(1, $orders);

        $order = $orders[0];
        static::assertSame(5, $order->getOrderId());
        static::assertSame(7, $order->getCustomerUserId());
        static::assertSame(3, $order->getCompanyId());
        static::assertSame('customer-1@world-company.com', $order->getCustomerEmail());
        static::assertGreaterThan(1500000000, $order->getCreatedAt()->getTimestamp());
        static::assertTrue(OrderStatus::STANDBY_VENDOR()->equals($order->getStatus()));
    }

    public function testGetOrderById(): void
    {
        $order = $this->buildVendorOrderService()->getOrderById(5);

        static::assertInstanceOf(Order::class, $order);
        static::assertSame(5, $order->getOrderId());
        static::assertSame(7, $order->getCustomerUserId());
        static::assertSame(3, $order->getCompanyId());
        static::assertSame('customer-1@world-company.com', $order->getCustomerEmail());
        static::assertSame('Paul', $order->getCustomerFirstName());
        static::assertSame('Martin', $order->getCustomerLastName());
        static::assertSame(66.7, $order->getTotal());
        static::assertSame(0.0, $order->getTaxSubtotal());
        static::assertSame(1.2, $order->getDiscountAmount());
        static::assertSame(0.0, $order->getShippingCost());
        static::assertSame([], $order->getShipmentsIds());
        static::assertSame('', $order->getInvoiceNumber());
        static::assertSame('', $order->getNotes());
        static::assertTrue(OrderStatus::STANDBY_VENDOR()->equals($order->getStatus()));
        static::assertTrue($order->needsShipping());
        static::assertGreaterThan(1500000000, $order->getCreatedAt()->getTimestamp());
        static::assertInstanceOf(\DateTimeImmutable::class, $order->getLastStatusChange());

        $shippingAddress = $order->getShippingAddress();
        static::assertInstanceOf(OrderAddress::class, $shippingAddress);
        static::assertSame('40 rue Laure Diebold', $shippingAddress->getAddress());
        static::assertSame('3ème étage', $shippingAddress->getComplementaryAddress());
        static::assertSame('Lyon', $shippingAddress->getCity());
        static::assertSame('FR', $shippingAddress->getCountryCode());
        static::assertSame('Paul', $shippingAddress->getFirstName());
        static::assertSame('Martin', $shippingAddress->getLastName());
        static::assertSame('01234567890', $shippingAddress->getPhoneNumber());
        static::assertSame('69009', $shippingAddress->getZipCode());

        $billingAddress = $order->getBillingAddress();
        static::assertInstanceOf(OrderAddress::class, $billingAddress);
        static::assertSame('40 rue Laure Diebold', $billingAddress->getAddress());
        static::assertSame('3ème étage', $billingAddress->getComplementaryAddress());
        static::assertSame('Lyon', $billingAddress->getCity());
        static::assertSame('FR', $billingAddress->getCountryCode());
        static::assertSame('Paul', $billingAddress->getFirstName());
        static::assertSame('Martin', $billingAddress->getLastName());
        static::assertSame('01234567890', $billingAddress->getPhoneNumber());
        static::assertSame('69009', $billingAddress->getZipCode());

        $items = $order->getItems();
        static::assertContainsOnly(OrderItem::class, $items);
        static::assertCount(1, $items);
        /** @var OrderItem $item */
        $item = reset($items);
        static::assertSame(2085640488, $item->getItemId());
        static::assertSame(1, $item->getProductId());
        static::assertSame(0.0, $item->getDiscountAmount());
        static::assertSame('978020137962', $item->getCode());
        static::assertSame(67.9, $item->getPrice());
        static::assertSame(1, $item->getQuantity());
        static::assertSame(0, $item->getQuantityShipped());
        static::assertSame(1, $item->getQuantityToShip());
        $optionsVariantsIds = $item->getOptionsVariantsIds();
        static::assertCount(0, $optionsVariantsIds);

        $taxes = $order->getTaxes();
        static::assertContainsOnly(OrderTax::class, $taxes);
        static::assertCount(1, $taxes);
        $tax = reset($taxes);
        static::assertSame('TVA 2.1%', $tax->getDescription());
        static::assertSame(2.1, $tax->getRateValue());
        static::assertSame(1.3966, $tax->getTaxSubtotal());
        static::assertTrue($tax->doesPriceIncludesTax());
    }

    public function testGetOrderComments(): void
    {
        $order = $this->buildVendorOrderService()->getOrderById(4);

        static::assertSame('Please deliver at the front desk of my company.', $order->getComment());
        $items = $order->getItems();
        static::assertSame('Please, gift wrap this product.', reset($items)->getComment());
    }

    public function testCreateShipment()
    {
        $orderService = $this->buildVendorOrderService();

        $orderId = 5;
        $orderService->acceptOrder($orderId);

        $order = $orderService->getOrderById($orderId);

        $itemsShipped = [];
        foreach ($order->getItems() as $item) {
            $itemsShipped[$item->getItemId()] = $item->getQuantityToShip();
        }

        $shipmentId = $orderService->createShipment(
            (new CreateShipmentCommand($orderId, '0ABC0123456798'))
            ->setComment('great shipment')
            ->setLabelUrl('http://mondialrelay.com/shipment-created')
            ->setShippedQuantityByItemId($itemsShipped)
        );

        static::assertGreaterThan(0, $shipmentId);

        $order = $orderService->getOrderById($orderId);
        static::assertSame([$shipmentId], $order->getShipmentsIds());

        $shipments = $orderService->listShipments($orderId);
        static::assertContainsOnly(Shipment::class, $shipments);
        static::assertCount(1, $shipments);
        static::assertSame($shipmentId, $shipments[0]->getShipmentId());
        static::assertSame($orderId, $shipments[0]->getOrderId());
        static::assertSame('great shipment', $shipments[0]->getComment());
        static::assertSame($itemsShipped, $shipments[0]->getShippedQuantityByItemId());
        static::assertSame('TNT Express', $shipments[0]->getShippingName());
        static::assertSame('0ABC0123456798', $shipments[0]->getTrackingNumber());
        static::assertSame('http://mondialrelay.com/shipment-created', $shipments[0]->getLabelUrl());
        static::assertSame(1, $shipments[0]->getShippingId());
        static::assertGreaterThanOrEqual(1500000000, $shipments[0]->getCreatedAt()->getTimestamp());

        $shipment = $orderService->getShipmentById($shipmentId);
        static::assertInstanceOf(Shipment::class, $shipment);
        static::assertEquals($shipments[0], $shipment);
    }

    public function testListTaxes(): void
    {
        $taxes = $this->buildVendorOrderService()->listTaxes();

        static::assertContainsOnly(Tax::class, $taxes);
        static::assertNotEmpty($taxes);

        foreach ($taxes as $tax) {
            static::assertInternalType('int', $tax->getId());
            static::assertGreaterThan(0, $tax->getId());

            static::assertInternalType('string', $tax->getName());
            static::assertNotEmpty($tax->getName());
        }
    }

    public function testGenerateMondialRelayLabel(): void
    {
        $orderService = $this->buildVendorOrderService();

        $orderId = 10;
        $orderService->acceptOrder($orderId);

        $order = $orderService->getOrderById($orderId);

        $itemsShipped = [];
        foreach ($order->getItems() as $item) {
            $itemsShipped[$item->getItemId()] = $item->getQuantityToShip();
        }

        $result = $orderService->generateMondialRelayLabel(
            $orderId,
            (new CreateLabelCommand())
                ->setShippedQuantityByItemId($itemsShipped)
        );

        static::assertInstanceOf(MondialRelayLabel::class, $result);
    }

    /**
     * @dataProvider lastStatusChangeProvider
     * @param mixed $lastStatusChange The lastStatusChange variable to test
     * @param string|null $expected Expected case to assert
     */
    public function testDenormalizeLastStatusChange($lastStatusChange, ?string $expected): void
    {
        $orderData = \json_decode('{"order_id":5,"company_id":3,"user_id":7,"basket_id":"c8512874-2a4a-4ed3-8e66-708c2fa54c1a","total":66.7,"discount":1.2,"shipping_cost":0.0,"timestamp":1551876306,"status":"P","notes":"","promotions":[],"b_firstname":"Paul","b_lastname":"Martin","b_company":"","b_address":"40 rue Laure Diebold","b_address_2":"3\u00e8me \u00e9tage","b_city":"Lyon","b_country":"FR","b_zipcode":"69009","b_phone":"01234567890","s_firstname":"Paul","s_lastname":"Martin","s_company":"","s_address":"40 rue Laure Diebold","s_address_2":"3\u00e8me \u00e9tage","s_city":"Lyon","s_country":"FR","s_zipcode":"69009","s_phone":"01234567890","s_pickup_point_id":"","email":"customer-1@world-company.com","decline_reason":null,"invoice_date":"","products":{"2085640488":{"item_id":"2085640488","product_id":1,"price":67.9,"amount":1,"comment":"","extra":{"combinations":null},"discount":0.0,"green_tax":"0.00","shipped_amount":0,"shipment_amount":"1","selected_code":"978020137962","supplier_ref":"INFO-001"}},"taxes":{"2":{"rate_type":"P","rate_value":"2.100","price_includes_tax":"Y","regnumber":"445711","priority":0,"tax_subtotal":1.3966,"description":"TVA 2.1%","applies":{"P_2085640488":1.3966}}},"tax_subtotal":0.0,"need_shipping":true,"shipping":[{"shipping_id":1,"status":"A","shipping":"TNT Express","delivery_time":"24h","rates":[{"amount":0,"value":null},{"amount":1,"value":null}],"specific_rate":false,"description":"<p>Code : TNT01<\/p>\r\n<p>Type : Livraison &agrave; domicile <br \/> Mode : EXP<\/p>\r\n<p>Tel : 08 25 03 30 33<\/p>\r\n<p>Email :<\/p>\r\n<p>Adresse : 58 Avenue Leclerc <br \/> 69007Lyon<br \/>France<\/p>\r\n<p>URL tracking : http:\/\/www.tnt.fr\/suivi<\/p>\r\n<p>Service : Transport express au domicile, au travail ou en relais colis.<\/p>"}],"shipment_ids":[],"invoice_number":"","last_status_change":"2019-03-06T13:45:36+01:00","customer_firstname":"Paul","customer_lastname":"Martin","payment":{"type":"manual","processorName":null,"commitmentDate":null,"processorInformation":{}},"workflow":"workflow_order_validation_pending_vendor_validation_processing"}', true);
        $orderData['last_status_change'] = $lastStatusChange;

        switch ($expected) {
            case null:
                static::assertNull((new Order($orderData))->getLastStatusChange());
                break;

            case \Throwable::class:
                $this->expectException(\Throwable::class);
                new Order($orderData);
                break;

            default:
                static::assertInstanceOf($expected, (new Order($orderData))->getLastStatusChange());
                break;
        }
    }

    public function lastStatusChangeProvider(): array
    {
        return [
            [null, null],
            ['', null],
            [0, \Throwable::class],
            ['0', \Throwable::class],
            ['qsfsdfsdf', \Throwable::class],
            ['00-00-0000 00:00:00', \DateTimeImmutable::class],
            ['08-03-2019T12:13:15Z', \DateTimeImmutable::class],
        ];
    }

    private function buildVendorOrderService(string $email = 'vendor@world-company.com', string $password = 'password-vendor'): OrderService
    {
        $apiClient = $this->buildApiClient();
        $apiClient->authenticate($email, $password);

        return new OrderService($apiClient);
    }
}
