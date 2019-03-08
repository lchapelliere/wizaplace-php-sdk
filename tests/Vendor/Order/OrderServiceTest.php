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

        $this->assertTrue(OrderStatus::PROCESSING_SHIPPING()->equals($orderService->getOrderById(5)->getStatus()));
    }

    public function testDecliningAnOrderWithoutReason()
    {
        $orderService = $this->buildVendorOrderService();

        $orderService->declineOrder(5);

        $order = $orderService->getOrderById(5);
        $this->assertTrue(OrderStatus::VENDOR_DECLINED()->equals($order->getStatus()));
        $this->assertEmpty($order->getDeclineReason());
    }

    public function testDecliningAnOrderWithReason()
    {
        $orderService = $this->buildVendorOrderService();

        $orderService->declineOrder(5, 'Product out of stock');

        $order = $orderService->getOrderById(5);
        $this->assertTrue(OrderStatus::VENDOR_DECLINED()->equals($order->getStatus()));
        $this->assertSame('Product out of stock', $order->getDeclineReason());
    }

    public function testSetInvoiceNumber(): void
    {
        $orderService = $this->buildVendorOrderService();

        $invoiceNumber = $orderService->getOrderById(5)->getInvoiceNumber();
        $this->assertSame("", $invoiceNumber);

        $orderService->setInvoiceNumber(5, "00072");

        $invoiceNumber = $orderService->getOrderById(5)->getInvoiceNumber();

        $this->assertSame("00072", $invoiceNumber);

        $this->expectException(\Throwable::class); // can't set the invoice number twice
        $orderService->setInvoiceNumber(5, "00073");
    }

    public function testListOrders(): void
    {
        $orders = $this->buildVendorOrderService()->listOrders();

        $this->assertContainsOnly(OrderSummary::class, $orders);
        $this->assertEquals(true, count($orders) >= 2);

        // To fix random sort
        usort($orders, function ($a, $b) {
            return $a->getOrderId() <=> $b->getOrderId();
        });

        $order = array_shift($orders);
        $this->assertSame(4, $order->getOrderId());
        $this->assertSame(7, $order->getCustomerUserId());
        $this->assertSame(3, $order->getCompanyId());
        $this->assertSame('customer-1@world-company.com', $order->getCustomerEmail());
        $this->assertSame('Paul', $order->getCustomerFirstName());
        $this->assertSame('Martin', $order->getCustomerLastName());
        $this->assertGreaterThan(1500000000, $order->getCreatedAt()->getTimestamp());
        $this->assertTrue(OrderStatus::COMPLETED()->equals($order->getStatus()));
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getLastStatusChange());

        $order = array_shift($orders);
        $this->assertSame(5, $order->getOrderId());
        $this->assertSame(7, $order->getCustomerUserId());
        $this->assertSame(3, $order->getCompanyId());
        $this->assertSame('customer-1@world-company.com', $order->getCustomerEmail());
        $this->assertSame('Paul', $order->getCustomerFirstName());
        $this->assertSame('Martin', $order->getCustomerLastName());
        $this->assertGreaterThan(1500000000, $order->getCreatedAt()->getTimestamp());
        $this->assertTrue(OrderStatus::STANDBY_VENDOR()->equals($order->getStatus()));
        $this->assertTrue(OrderStatus::STANDBY_VENDOR()->equals($order->getStatus()));
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getLastStatusChange());
    }

    public function testListOrdersWithFilter(): void
    {
        $orders = $this->buildVendorOrderService()->listOrders(OrderStatus::STANDBY_VENDOR());

        $this->assertContainsOnly(OrderSummary::class, $orders);
        $this->assertCount(1, $orders);

        $order = $orders[0];
        $this->assertSame(5, $order->getOrderId());
        $this->assertSame(7, $order->getCustomerUserId());
        $this->assertSame(3, $order->getCompanyId());
        $this->assertSame('customer-1@world-company.com', $order->getCustomerEmail());
        $this->assertGreaterThan(1500000000, $order->getCreatedAt()->getTimestamp());
        $this->assertTrue(OrderStatus::STANDBY_VENDOR()->equals($order->getStatus()));
    }

    public function testGetOrderById(): void
    {
        $order = $this->buildVendorOrderService()->getOrderById(5);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame(5, $order->getOrderId());
        $this->assertSame(7, $order->getCustomerUserId());
        $this->assertSame(3, $order->getCompanyId());
        $this->assertSame('customer-1@world-company.com', $order->getCustomerEmail());
        $this->assertSame('Paul', $order->getCustomerFirstName());
        $this->assertSame('Martin', $order->getCustomerLastName());
        $this->assertSame(66.7, $order->getTotal());
        $this->assertSame(0.0, $order->getTaxSubtotal());
        $this->assertSame(1.2, $order->getDiscountAmount());
        $this->assertSame(0.0, $order->getShippingCost());
        $this->assertSame([], $order->getShipmentsIds());
        $this->assertSame('', $order->getInvoiceNumber());
        $this->assertSame('', $order->getNotes());
        $this->assertTrue(OrderStatus::STANDBY_VENDOR()->equals($order->getStatus()));
        $this->assertTrue($order->needsShipping());
        $this->assertGreaterThan(1500000000, $order->getCreatedAt()->getTimestamp());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getLastStatusChange());

        $shippingAddress = $order->getShippingAddress();
        $this->assertInstanceOf(OrderAddress::class, $shippingAddress);
        $this->assertSame('40 rue Laure Diebold', $shippingAddress->getAddress());
        $this->assertSame('3ème étage', $shippingAddress->getComplementaryAddress());
        $this->assertSame('Lyon', $shippingAddress->getCity());
        $this->assertSame('FR', $shippingAddress->getCountryCode());
        $this->assertSame('Paul', $shippingAddress->getFirstName());
        $this->assertSame('Martin', $shippingAddress->getLastName());
        $this->assertSame('01234567890', $shippingAddress->getPhoneNumber());
        $this->assertSame('69009', $shippingAddress->getZipCode());

        $billingAddress = $order->getBillingAddress();
        $this->assertInstanceOf(OrderAddress::class, $billingAddress);
        $this->assertSame('40 rue Laure Diebold', $billingAddress->getAddress());
        $this->assertSame('3ème étage', $billingAddress->getComplementaryAddress());
        $this->assertSame('Lyon', $billingAddress->getCity());
        $this->assertSame('FR', $billingAddress->getCountryCode());
        $this->assertSame('Paul', $billingAddress->getFirstName());
        $this->assertSame('Martin', $billingAddress->getLastName());
        $this->assertSame('01234567890', $billingAddress->getPhoneNumber());
        $this->assertSame('69009', $billingAddress->getZipCode());

        $items = $order->getItems();
        $this->assertContainsOnly(OrderItem::class, $items);
        $this->assertCount(1, $items);
        /** @var OrderItem $item */
        $item = reset($items);
        $this->assertSame(2085640488, $item->getItemId());
        $this->assertSame(1, $item->getProductId());
        $this->assertSame(0.0, $item->getDiscountAmount());
        $this->assertSame('978020137962', $item->getCode());
        $this->assertSame(67.9, $item->getPrice());
        $this->assertSame(1, $item->getQuantity());
        $this->assertSame(0, $item->getQuantityShipped());
        $this->assertSame(1, $item->getQuantityToShip());
        $optionsVariantsIds = $item->getOptionsVariantsIds();
        $this->assertCount(0, $optionsVariantsIds);

        $taxes = $order->getTaxes();
        $this->assertContainsOnly(OrderTax::class, $taxes);
        $this->assertCount(1, $taxes);
        $tax = reset($taxes);
        $this->assertSame('TVA 2.1%', $tax->getDescription());
        $this->assertSame(2.1, $tax->getRateValue());
        $this->assertSame(1.3966, $tax->getTaxSubtotal());
        $this->assertTrue($tax->doesPriceIncludesTax());
    }

    public function testGetOrderComments(): void
    {
        $order = $this->buildVendorOrderService()->getOrderById(4);

        $this->assertSame('Please deliver at the front desk of my company.', $order->getComment());
        $items = $order->getItems();
        $this->assertSame('Please, gift wrap this product.', reset($items)->getComment());
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

        $this->assertGreaterThan(0, $shipmentId);

        $order = $orderService->getOrderById($orderId);
        $this->assertSame([$shipmentId], $order->getShipmentsIds());

        $shipments = $orderService->listShipments($orderId);
        $this->assertContainsOnly(Shipment::class, $shipments);
        $this->assertCount(1, $shipments);
        $this->assertSame($shipmentId, $shipments[0]->getShipmentId());
        $this->assertSame($orderId, $shipments[0]->getOrderId());
        $this->assertSame('great shipment', $shipments[0]->getComment());
        $this->assertSame($itemsShipped, $shipments[0]->getShippedQuantityByItemId());
        $this->assertSame('TNT Express', $shipments[0]->getShippingName());
        $this->assertSame('0ABC0123456798', $shipments[0]->getTrackingNumber());
        $this->assertSame('http://mondialrelay.com/shipment-created', $shipments[0]->getLabelUrl());
        $this->assertSame(1, $shipments[0]->getShippingId());
        $this->assertGreaterThanOrEqual(1500000000, $shipments[0]->getCreatedAt()->getTimestamp());

        $shipment = $orderService->getShipmentById($shipmentId);
        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertEquals($shipments[0], $shipment);
    }

    public function testListTaxes(): void
    {
        $taxes = $this->buildVendorOrderService()->listTaxes();

        $this->assertContainsOnly(Tax::class, $taxes);
        $this->assertNotEmpty($taxes);

        foreach ($taxes as $tax) {
            $this->assertInternalType('int', $tax->getId());
            $this->assertGreaterThan(0, $tax->getId());

            $this->assertInternalType('string', $tax->getName());
            $this->assertNotEmpty($tax->getName());
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

        $this->assertInstanceOf(MondialRelayLabel::class, $result);
    }

    /**
     * @dataProvider badLastStatusChangeProvider
     */
    public function testDenormalizeLastStatusChange($lastStatusChange, $expected): void
    {
        $orderData = \json_decode('{"order_id":5,"company_id":3,"user_id":7,"basket_id":"c8512874-2a4a-4ed3-8e66-708c2fa54c1a","total":66.7,"discount":1.2,"shipping_cost":0.0,"timestamp":1551876306,"status":"P","notes":"","promotions":[],"b_firstname":"Paul","b_lastname":"Martin","b_company":"","b_address":"40 rue Laure Diebold","b_address_2":"3\u00e8me \u00e9tage","b_city":"Lyon","b_country":"FR","b_zipcode":"69009","b_phone":"01234567890","s_firstname":"Paul","s_lastname":"Martin","s_company":"","s_address":"40 rue Laure Diebold","s_address_2":"3\u00e8me \u00e9tage","s_city":"Lyon","s_country":"FR","s_zipcode":"69009","s_phone":"01234567890","s_pickup_point_id":"","email":"customer-1@world-company.com","decline_reason":null,"invoice_date":"","products":{"2085640488":{"item_id":"2085640488","product_id":1,"price":67.9,"amount":1,"comment":"","extra":{"combinations":null},"discount":0.0,"green_tax":"0.00","shipped_amount":0,"shipment_amount":"1","selected_code":"978020137962","supplier_ref":"INFO-001"}},"taxes":{"2":{"rate_type":"P","rate_value":"2.100","price_includes_tax":"Y","regnumber":"445711","priority":0,"tax_subtotal":1.3966,"description":"TVA 2.1%","applies":{"P_2085640488":1.3966}}},"tax_subtotal":0.0,"need_shipping":true,"shipping":[{"shipping_id":1,"status":"A","shipping":"TNT Express","delivery_time":"24h","rates":[{"amount":0,"value":null},{"amount":1,"value":null}],"specific_rate":false,"description":"<p>Code : TNT01<\/p>\r\n<p>Type : Livraison &agrave; domicile <br \/> Mode : EXP<\/p>\r\n<p>Tel : 08 25 03 30 33<\/p>\r\n<p>Email :<\/p>\r\n<p>Adresse : 58 Avenue Leclerc <br \/> 69007Lyon<br \/>France<\/p>\r\n<p>URL tracking : http:\/\/www.tnt.fr\/suivi<\/p>\r\n<p>Service : Transport express au domicile, au travail ou en relais colis.<\/p>"}],"shipment_ids":[],"invoice_number":"","last_status_change":"2019-03-06T13:45:36+01:00","customer_firstname":"Paul","customer_lastname":"Martin","payment":{"type":"manual","processorName":null,"commitmentDate":null,"processorInformation":{}},"workflow":"workflow_order_validation_pending_vendor_validation_processing"}', true);
        $orderData['last_status_change'] = $lastStatusChange;

        if ($expected === \Throwable::class) {
            $this->expectException(\Throwable::class);
            new Order($orderData);
        } else {
            $this->assertEquals((new Order($orderData))->getLastStatusChange(), $expected);
        }
    }

    public function badLastStatusChangeProvider(): array
    {
        return [
            [null, null],
            ['', null],
            [0, \Throwable::class],
            ['0', \Throwable::class],
            ['qsfsdfsdf', \Throwable::class],
            ['0000-00-00 00:00:00', null],
            ['0000-00-00T00:00:00Z', null],
        ];
    }

    private function buildVendorOrderService(string $email = 'vendor@world-company.com', string $password = 'password-vendor'): OrderService
    {
        $apiClient = $this->buildApiClient();
        $apiClient->authenticate($email, $password);

        return new OrderService($apiClient);
    }
}
