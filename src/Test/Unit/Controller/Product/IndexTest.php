<?php
/**
 * @author Feedoptimise
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Test\Unit\Controller\Product;

use Feedoptimise\CatalogExport\Controller\Product\Index;
use Feedoptimise\CatalogExport\Controller\Stores\Index as StoresIndex;
use Feedoptimise\CatalogExport\Helper\Currency;
use Feedoptimise\CatalogExport\Helper\Settings;
use Feedoptimise\CatalogExport\Model\Source\SourceFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\Tree;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    public function testImageFallsBackToUrlStringWhenPrimaryImageIsMissing()
    {
        $productMock = $this->createStub(Product::class);
        $productMock->method('getData')->willReturnCallback(
            function ($key = '') {
                return $key === '' || $key === null ? ['image' => 'no_selection'] : 'no_selection';
            }
        );
        $productMock->method('getAttributes')->willReturn([]);
        $productMock->method('getImage')->willReturn('no_selection');
        $productMock->method('getMediaGalleryImages')->willReturn(
            [
                $this->createMediaItem(
                    [
                        'url' => 'https://example.com/media/catalog/product/m/i/image.jpg',
                        'media_type' => 'image',
                    ]
                ),
            ]
        );

        $storeController = $this->createStub(StoresIndex::class);
        $storeController->baseImageUrl = 'https://example.com/media/catalog/product';

        $request = $this->createStub(RequestInterface::class);
        $request->method('getParams')->willReturn([]);

        $controller = $this->createController($storeController, $request);

        $result = $controller->getProductDataForTest($productMock);

        $this->assertIsString($result['image']);
        $this->assertSame('https://example.com/media/catalog/product/m/i/image.jpg', $result['image']);
    }

    private function createMediaItem(array $data): DataObject
    {
        $item = $this->createStub(DataObject::class);
        $item->method('getData')->willReturnCallback(
            function ($key = '') use ($data) {
                return $key === '' || $key === null ? $data : ($data[$key] ?? null);
            }
        );

        return $item;
    }

    private function createController(StoresIndex $storeController, RequestInterface $request): Index
    {
        return new class(
            $this->createStub(Context::class),
            $request,
            $this->createStub(JsonFactory::class),
            $this->createStub(StoreManagerInterface::class),
            $this->createStub(Settings::class),
            $this->createStub(Currency::class),
            $storeController,
            $this->createStub(CategoryRepository::class),
            $this->createStub(Tree::class),
            $this->createStub(ProductRepository::class),
            $this->createStub(CollectionFactory::class),
            $this->createStub(Status::class),
            $this->createStub(Visibility::class),
            $this->createStub(SourceFactory::class)
        ) extends Index {
            public function getProductDataForTest($product)
            {
                return $this->getProductData($product);
            }
        };
    }
}
