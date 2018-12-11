<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\SyliusProducerPlugin\Producer\Serializer;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\SyliusProducerPlugin\Model\CustomDataInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;

class ProductSerializer implements ProductSerializerInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serialize(ProductInterface $product): array
    {
        $translations = [];
        /** @var ProductTranslationInterface $translation */
        foreach ($product->getTranslations() as $translation) {
            $translations[] = [
                'locale' => $translation->getLocale(),
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
                'description' => $translation->getDescription(),
                'shortDescription' => $translation->getShortDescription(),
                'metaKeywords' => $translation->getMetaKeywords(),
                'metaDescription' => $translation->getMetaDescription(),
                'customData' => $this->getCustomData($translation),
            ];
        }

        $images = [];
        foreach ($product->getImages() as $image) {
            $images[] = [
                'id' => $image->getId(),
                'type' => $image->getType(),
                'path' => $image->getPath(),
                'customData' => $this->getCustomData($image),
            ];
        }

        $productTaxons = [];
        foreach ($product->getProductTaxons() as $productTaxon) {
            $taxon = $productTaxon->getTaxon();
            if (!$taxon) {
                continue;
            }

            $productTaxons[] = [
                'id' => $productTaxon->getId(),
                'taxonId' => $taxon->getId(),
                'position' => $taxon->getPosition(),
                'customData' => $this->getCustomData($productTaxon),
            ];
        }

        $attributes = [];
        foreach ($product->getAttributes() as $attribute) {
            $attributes[] = [
                'id' => $attribute->getId(),
                'code' => $attribute->getCode(),
                'type' => $attribute->getType(),
                'localeCode' => $attribute->getLocaleCode(),
                'value' => $attribute->getValue(),
                'customData' => $this->getCustomData($attribute),
            ];
        }

        return [
            'id' => $product->getId(),
            'code' => $product->getCode(),
            'enabled' => $product->isEnabled(),
            'mainTaxonId' => $product->getMainTaxon() ? $product->getMainTaxon()->getId() : null,
            'productTaxons' => $productTaxons,
            'translations' => $translations,
            'attributes' => $attributes,
            'images' => $images,
            'customData' => $this->getCustomData($product),
            'variants' => $this->getVariants($product)
        ];
    }

    private function getVariants(ProductInterface $product): array
    {
        if (!$product->hasVariants()) {
            return [];
        }

        $serializationContext = new SerializationContext();
        $serializationContext->setGroups(['Default', 'Detailed', 'CustomData']);

        return json_decode(
            $this->serializer->serialize($product->getVariants()->getValues(), 'json', $serializationContext),
            true
        );
    }

    private function getCustomData($object): array
    {
        if (!$object instanceof CustomDataInterface) {
            return [];
        }

        return $object->getCustomData();
    }
}
