<?php
/**
 * @copyright Copyright (c) Wizacha
 * @license Proprietary
 */
declare(strict_types = 1);

namespace Wizaplace\SDK\Catalog;

use Wizaplace\SDK\Image\Image;

/**
 * Class Declination
 * @package Wizaplace\SDK\Catalog
 */
final class Declination
{
    /** @var DeclinationId */
    private $id;

    /** @var string */
    private $code;

    /** @var string */
    private $supplierReference;

    /** @var float */
    private $price;

    /** @var float */
    private $priceWithTaxes;

    /** @var float */
    private $priceWithoutVat;

    /** @var float */
    private $vat;

    /** @var float */
    private $greenTax;

    /** @var float */
    private $originalPrice;

    /** @var float|null */
    private $crossedOutPrice;

    /** @var int */
    private $amount;

    /** @var string|null */
    private $affiliateLink;

    /** @var Image[] */
    private $images;

    /** @var bool */
    private $isBrandNew;

    /** @var DeclinationOption[] */
    private $options;

    /** @var CompanySummary */
    private $company;

    /** @var bool */
    private $isAvailable;

    /** @var bool */
    private $infiniteStock;

    /** @var array|null */
    private $shippings = null;

    /**
     * @internal
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $prices = $data['prices'] ?? [];
        $this->id = new DeclinationId($data['id']);
        $this->code = $data['code'];
        $this->supplierReference = $data['supplierReference'];
        $this->price = $data['price'];
        $this->originalPrice = $data['originalPrice'];
        $this->crossedOutPrice = $data['crossedOutPrice'];
        $this->priceWithTaxes = $prices['priceWithTaxes'] ?? $this->price;
        $this->priceWithoutVat = $prices['priceWithoutVat'] ?? $this->price;
        $this->vat = $prices['vat'] ?? 0;
        $this->greenTax = $data['greenTax'] ?? 0;
        $this->amount = $data['amount'];
        $this->affiliateLink = $data['affiliateLink'];
        $this->images = array_map(static function (array $imageData) : Image {
            return new Image($imageData);
        }, $data['images']);
        $this->isBrandNew = $data['isBrandNew'] ?? true;
        $this->options = array_map(static function (array $optionData) : DeclinationOption {
            return new DeclinationOption($optionData);
        }, $data['options']);
        $this->company = new CompanySummary($data['company']);
        $this->isAvailable = $data['isAvailable'];
        $this->infiniteStock = $data['infiniteStock'];

        if (isset($data['shippings'])) {
            $this->shippings = $data['shippings'];
        }
    }

    /**
     * @return DeclinationId
     */
    public function getId(): DeclinationId
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getSupplierReference(): string
    {
        return $this->supplierReference;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getOriginalPrice(): float
    {
        return $this->originalPrice;
    }

    /**
     * @return float|null
     */
    public function getCrossedOutPrice(): ?float
    {
        return $this->crossedOutPrice;
    }

    /**
     * @return float
     */
    public function getPriceWithTaxes(): float
    {
        return $this->priceWithTaxes;
    }

    /**
     * @return float
     */
    public function getPriceWithoutVat(): float
    {
        return $this->priceWithoutVat;
    }

    /**
     * @return float
     */
    public function getVat(): float
    {
        return $this->vat;
    }

    /**
     * @return float
     */
    public function getGreenTax(): float
    {
        return $this->greenTax;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return string|null
     */
    public function getAffiliateLink(): ?string
    {
        return $this->affiliateLink;
    }

    /**
     * Is the declination brand new (true) or second-hand (false).
     */
    public function isBrandNew(): bool
    {
        return $this->isBrandNew;
    }

    /**
     * @return DeclinationOption[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return Image[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * This function checks if the declination has the requested variantsIds and only those
     *
     * example : if the requested Ids are [1, 2] and the declination variantIds are [1, 2, 3], it won't be valid
     *
     * @param int[] $variantIds
     *
     * @return bool
     */
    public function hasVariants(array $variantIds): bool
    {
        /**
         * collecting the declination's variantIds
         */
        $declinationVariantIds = [];
        foreach ($this->options as $option) {
            $declinationVariantIds[] = $option->getVariantId();
        }

        /**
         * looks for requested variantIds among declination's variantIds and increment $foundIds when one is found
         */
        $foundIds = 0;
        foreach ($variantIds as $variantId) {
            if (in_array($variantId, $declinationVariantIds)) {
                $foundIds++;
            }
        }

        /**
         * if all variantIds have been found, then $foundIds == count($variantIds).
         * Also checking that the declination has no other variants
         * by checking if the count of $variantIds is the same than the $declinationVariantIds'
         */
        return (count($variantIds) === $foundIds && count($variantIds) === count($declinationVariantIds));
    }

    /**
     * @return CompanySummary
     */
    public function getCompany(): CompanySummary
    {
        return $this->company;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    /**
     * @return bool
     */
    public function hasInfiniteStock(): bool
    {
        return $this->infiniteStock;
    }

    /**
     * @return array|null
     */
    public function getShippings(): ?array
    {
        return $this->shippings;
    }

    /**
     * @param array|null $shippings
     */
    public function setShippings(?array $shippings): void
    {
        $this->shippings = $shippings;
    }
}
