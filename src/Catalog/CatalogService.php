<?php
/**
 * @copyright Copyright (c) Wizacha
 * @license Proprietary
 */
declare(strict_types = 1);

namespace Wizaplace\SDK\Catalog;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Wizaplace\SDK\AbstractService;
use Wizaplace\SDK\Exception\NotFound;
use Wizaplace\SDK\Exception\ProductNotFound;
use Wizaplace\SDK\Exception\SomeParametersAreInvalid;
use function theodorejb\polycast\to_string;

/**
 * Class CatalogService
 * @package Wizaplace\SDK\Catalog
 */
final class CatalogService extends AbstractService implements CatalogServiceInterface
{
    /**
     * @param string|null $language
     *
     * @return \Generator|Product[] a Generator of Product
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getAllProducts(string $language = null): \Generator
    {
        $page = 1;

        while (true) {
            $options = [];
            if (!is_null($language)) {
                $options[RequestOptions::HEADERS]['Accept-Language'] = $language;
            }

            $response = $this->client->get("catalog/export/{$page}", $options);

            if (!isset($response['result']) || !is_array($response['result'])) {
                throw new \Exception('Results missing in response');
            }

            foreach ($response['result'] as $item) {
                yield new Product($item, $this->client->getBaseUri());
            }

            if ($response['pagination']['page'] >= $response['pagination']['nbPages']) {
                break;
            }

            $page++;
        }
    }

    /**
     * @param string $id
     *
     * @return Product
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getProductById(string $id) : Product
    {
        $response = $this->client->get("catalog/products/{$id}");

        return new Product($response, $this->client->getBaseUri());
    }

    /**
     * @param string $id
     *
     * @return Declination
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getDeclinationById(string $id): Declination
    {
        $response = $this->client->get("catalog/declinations/{$id}");

        return new Declination($response);
    }

    /**
     * @param string $code
     *
     * @return Product[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getProductsByCode(string $code) : array
    {
        $response = $this->client->get('catalog/products', [RequestOptions::QUERY => ['code' => $code]]);

        return array_map(function ($product) {
            return new Product($product, $this->client->getBaseUri());
        }, $response);
    }

    /**
     * @param string $supplierReference
     *
     * @return Product[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getProductsBySupplierReference(string $supplierReference) : array
    {
        $response = $this->client->get('catalog/products', [RequestOptions::QUERY => ['supplierRef' => $supplierReference]]);

        return array_map(function ($product) {
            return new Product($product, $this->client->getBaseUri());
        }, $response);
    }

    /**
     * @param ProductFilter $productFilter
     *
     * @return Product[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getProductsByFilters(ProductFilter $productFilter): array
    {
        $response = $this->client->get('catalog/products', [RequestOptions::QUERY => $productFilter->getFilters()]);

        return array_map(function ($product) {
            return new Product($product, $this->client->getBaseUri());
        }, $response);
    }

    /**
     * @return CategoryTree[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getCategoryTree():array
    {
        $categoryTree = $this->client->get('catalog/categories/tree');

        return CategoryTree::buildCollection($categoryTree);
    }

    /**
     * @param int $id
     *
     * @return Category
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getCategory(int $id): Category
    {
        $category = $this->client->get("catalog/categories/{$id}");

        return new Category($category);
    }

    /**
     * @return Category[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getCategories(): array
    {
        $categories = $this->client->get('catalog/categories');

        return array_map(static function ($category) {
            return new Category($category);
        }, $categories);
    }

    /**
     * @param string         $query
     * @param array          $filters
     * @param array          $sorting
     * @param int            $resultsPerPage
     * @param int            $page
     * @param GeoFilter|null $geoFilter
     *
     * @return SearchResult
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function search(
        string $query = '',
        array $filters = [],
        array $sorting = [],
        int $resultsPerPage = 12,
        int $page = 1,
        ?GeoFilter $geoFilter = null
    ): SearchResult {
        $query = [
            'filters' => $filters,
            'sorting' => $sorting,
            'resultsPerPage' => $resultsPerPage,
            'page' => $page,
            'query' => $query,
        ];
        if ($geoFilter !== null) {
            $query['geo'] = [
                'lat' => $geoFilter->getLatitude(),
                'lng' => $geoFilter->getLongitude(),
                'radius' => $geoFilter->getRadius(),
            ];
        }

        $results = $this->client->get(
            'catalog/search/products',
            [
                RequestOptions::QUERY => $query,
            ]
        );

        return new SearchResult($results);
    }

    /**
     * @param int $id
     *
     * @return CompanyDetail
     * @throws NotFound
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getCompanyById(int $id): CompanyDetail
    {
        try {
            $response = $this->client->get("catalog/companies/{$id}");
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() === 404) {
                throw new NotFound("Company #{$id} not found.", $exception);
            }

            throw $exception;
        }

        return new CompanyDetail($response);
    }

    /**
     * @return CompanyDetail[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getCompanies(): array
    {
        $response = $this->client->get("catalog/companies");

        $companies = array_map(function ($companyData) {
            return new CompanyListItem($companyData);
        }, $response);

        return $companies;
    }

    /**
     * @return Attribute[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getAttributes(): array
    {
        $attributesData = $this->client->get("catalog/attributes");

        return array_map([$this, 'unserializeAttribute'], $attributesData);
    }

    /**
     * @param int $attributeId
     *
     * @return Attribute
     * @throws NotFound
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getAttribute(int $attributeId): Attribute
    {
        try {
            $attributeData = $this->client->get("catalog/attributes/$attributeId");
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new NotFound("Attribute #$attributeId not found", $e);
            }
            throw $e;
        }

        return $this->unserializeAttribute($attributeData);
    }

    /**
     * @param int $variantId
     *
     * @return AttributeVariant
     * @throws NotFound
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getAttributeVariant(int $variantId): AttributeVariant
    {
        try {
            $variantData = $this->client->get("catalog/attributes/variants/$variantId");
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new NotFound("Attribute Variant #$variantId not found", $e);
            }
            throw $e;
        }

        return new AttributeVariant($variantData);
    }

    /**
     * @param int $attributeId
     *
     * @return AttributeVariant[]
     * @throws NotFound
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getAttributeVariants(int $attributeId): array
    {
        try {
            $variantsData = $this->client->get("catalog/attributes/$attributeId/variants");
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new NotFound("Attribute #$attributeId not found", $e);
            }
            throw $e;
        }

        return array_map(function (array $variantData): AttributeVariant {
            return new AttributeVariant($variantData);
        }, $variantsData);
    }

    /**
     * Report a suspicious product to the marketplace administrator.
     *
     * @param ProductReport $report
     *
     * @throws SomeParametersAreInvalid
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function reportProduct(ProductReport $report): void
    {
        $report->validate();

        try {
            $this->client->post("catalog/products/{$report->getProductId()}/report", [
                RequestOptions::JSON => [
                    'productId' => $report->getProductId(),
                    'name' => $report->getReporterName(),
                    'email' => $report->getReporterEmail(),
                    'message' => $report->getMessage(),
                ],
            ]);
        } catch (ClientException $e) {
            switch ($e->getCode()) {
                case 400:
                    throw new SomeParametersAreInvalid(to_string($e->getResponse()->getBody()), 400, $e);
            }
            throw $e;
        }
    }

    /**
     * Convenience method to extract a brand from a product
     *
     * @param ProductSummary|Product $product
     *
     * @return null|ProductAttributeValue
     * @throws NotFound
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getBrand($product): ?ProductAttributeValue
    {
        if ($product instanceof ProductSummary) {
            return $this->getBrandFromProductSummary($product);
        }

        if ($product instanceof Product) {
            return $this->getBrandFromProduct($product);
        }

        throw new \TypeError('Unexpected type for $product in getBrand : '.(is_object($product) ? get_class($product) : gettype($product)));
    }

    /**
     * @param ProductSummary $product
     *
     * @return ProductAttributeValue|null
     */
    public function getBrandFromProductSummary(ProductSummary $product): ?ProductAttributeValue
    {
        foreach ($product->getAttributes() as $attribute) {
            if ($attribute->getType()->equals(AttributeType::LIST_BRAND())) {
                $values = $attribute->getValues();
                $brand = reset($values);

                return $brand;
            }
        }

        return null;
    }

    /**
     * @param Product $product
     *
     * @return ProductAttributeValue|null
     * @throws NotFound
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Wizaplace\SDK\Exception\JsonDecodingError
     */
    public function getBrandFromProduct(Product $product): ?ProductAttributeValue
    {
        foreach ($product->getAttributes() as $attribute) {
            if ($attribute->getType()->equals(AttributeType::LIST_BRAND())) {
                $values = $attribute->getValueIds();
                $variant = $this->getAttributeVariant(reset($values));

                return new ProductAttributeValue([
                    'id' => $variant->getId(),
                    'slug' => $variant->getSlug(),
                    'name' => $variant->getName(),
                    'attributeId' => $variant->getAttributeId(),
                    'image' => $variant->getImage(),
                ]);
            }
        }

        return null;
    }

    /**
     * @param array $attributeData
     *
     * @return Attribute
     */
    private function unserializeAttribute(array $attributeData): Attribute
    {
        return new Attribute(
            $attributeData['id'],
            $attributeData['name'],
            new AttributeType($attributeData['type']),
            $attributeData['position'],
            $attributeData['parentId'] ?? null
        );
    }
}
