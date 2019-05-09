<?php
/**
 * Snowdog
 *
 * @author      Paweł Pisarek <pawel.pisarek@snow.dog>.
 * @category
 * @package
 * @copyright   Copyright Snowdog (http://snow.dog)
 */

namespace Snowdog\Menu\Model\NodeType;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Profiler;
use Magento\Catalog\Model\CategoryFactory;

class Category extends AbstractNode
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('Snowdog\Menu\Model\ResourceModel\NodeType\Category');
        parent::_construct();
    }

    /**
     * Category constructor.
     *
     * @param Profiler $profiler
     * @param MetadataPool $metadataPool
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        Profiler $profiler,
        MetadataPool $metadataPool,
        CategoryFactory $categoryFactory
    ) {
        $this->metadataPool = $metadataPool;
        $this->categoryFactory = $categoryFactory;
        parent::__construct($profiler);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function fetchConfigData()
    {
        $this->profiler->start(__METHOD__);
        $metadata = $this->metadataPool->getMetadata(CategoryInterface::class);
        $identifierField = $metadata->getIdentifierField();

        $data = $this->getResource()->fetchConfigData();
        $labels = [];

        foreach ($data as $row) {
            if (isset($labels[$row['parent_id']])) {
                $label = $labels[$row['parent_id']];
            } else {
                $label = [];
            }
            $label[] = $row['name'];
            $labels[$row[$identifierField]] = $label;
        }
        
        $fieldOptions = [];
        foreach ($labels as $id => $label) {
            $fieldOptions[] = [
                'label' => $label = implode(' > ', $label),
                'value' => $id
            ];
        }

        $data = [
            'snowMenuAutoCompleteField' => [
                'type'    => 'category',
                'options' => $fieldOptions,
                'message' => __('Category not found'),
            ],
        ];

        $this->profiler->stop(__METHOD__);

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function fetchData(array $nodes, $storeId)
    {
        $this->profiler->start(__METHOD__);

        $localNodes = [];
        $categoryIds = [];
        $categories = [];

        foreach ($nodes as $node) {
            $localNodes[$node->getId()] = $node;
            $categoryId = (int)$node->getContent();
            $categoryIds[] = $categoryId;
            $categories[$categoryId] = $this->getCategory($categoryId);
        }

        $categoryUrls = $this->getResource()->fetchData($storeId, $categoryIds);

        $this->profiler->stop(__METHOD__);

        return [$localNodes, $categoryUrls, $categories];
    }

    public function getCategory($categoryId)
    {
        $category = $this->categoryFactory->create();
        $category->load($categoryId);
        return $category;
    }
}
