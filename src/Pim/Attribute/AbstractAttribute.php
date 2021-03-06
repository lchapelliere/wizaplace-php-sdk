<?php
/**
 * @copyright Copyright (c) Wizacha
 * @license Proprietary
 */
declare(strict_types=1);

namespace Wizaplace\SDK\Pim\Attribute;

/**
 * Class AbstractAttribute
 * @package Wizaplace\SDK\Pim\Attribute
 *
 * @internal
 */
abstract class AbstractAttribute
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var AttributeType */
    private $type;

    /** @var int[] */
    private $categoriesPath;

    /**
     * @internal
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['feature_id'];
        $this->name = $data['description'];
        $this->type = new AttributeType($data['feature_type']);
        $this->categoriesPath = $data['categories_path'];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return AttributeType
     */
    public function getType(): AttributeType
    {
        return $this->type;
    }

    /**
     * @return int[]
     */
    public function getCategoriesPath(): array
    {
        return $this->categoriesPath;
    }
}
