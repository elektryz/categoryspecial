<?php

namespace Kamil\CategorySpecial\Entity;

use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;

class CategorySpecial extends ObjectModel
{
    public $id_category;
    public $is_special;

    public static $definition = [
        'table' => 'category_special',
        'primary' => 'id_reviewer',
        'fields' => [
            'id_category' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'is_special' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];
}
