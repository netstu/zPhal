<?php

namespace ZPhal\Models\Services\Service;

use ZPhal\Models\Services\AbstractService;

/**
 * Taxonomy服务类
 * Class TaxonomyService
 * @package ZPhal\Models\Services\Service
 */
class TaxonomyService extends AbstractService
{
    /**
     * @var mixed|\Phalcon\DiInterface
     */
    private static $modelsManager;

    /**
     * PostService constructor.
     * @param null $di
     */
    public function __construct($di = null)
    {
        parent::__construct($di);
        self::$modelsManager = $this->di->get('modelsManager') ?: container('modelsManager');
    }

    /**
     * 根据类型获取分类列表
     * @param $type
     * @return mixed
     */
    public function getTaxonomyListByType($type)
    {
        return self::$modelsManager->executeQuery(
            "SELECT tt.term_taxonomy_id, tt.term_id, tt.description, tt.parent, tt.count, t.name, t.slug
                  FROM ZPhal\Models\TermTaxonomy AS tt
                  LEFT JOIN ZPhal\Models\Terms AS t ON t.term_id=tt.term_id
                  WHERE tt.taxonomy = :taxonomy:
                  ORDER BY t.term_id ASC",
            [
                "taxonomy" => $type,
            ]
        )->toArray();
    }

    /**
     * 获取post的分类或标签
     * @param $objectId
     * @param string $type category|tag 默认全部
     * @return array
     * TODO 有bug 会查询到link的数据(因为没有根据term_taxonomy_id而是根据objectid来查)
     */
    public function getPostTaxonomy($objectId, $type='')
    {
        $builder = self::$modelsManager->createBuilder()
            ->columns("tr.term_taxonomy_id, tt.taxonomy")
            ->from(['tr' => 'ZPhal\Models\TermRelationships'])
            ->leftJoin('ZPhal\Models\TermTaxonomy', 'tt.term_taxonomy_id = tr.term_taxonomy_id', "tt")
            ->where("tr.object_id = :id:", ["id" => $objectId]);

        if ($type != ''){
            $builder->andWhere("tt.taxonomy = :taxonomy:", ["taxonomy" => $type]);
        }

        $taxonomy = $builder
            ->getQuery()
            ->execute()
            ->toArray();

        $output = [];
        foreach ($taxonomy as $item){
            $output[$item['taxonomy']][] = $item['term_taxonomy_id'];
        }

        return $output;
    }
}