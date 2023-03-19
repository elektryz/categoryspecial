<?php

namespace Kamil\CategorySpecial\Entity;

use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;

use Db;

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

    public static function isSpecial($idCategory)
    {
        return (bool)Db::getInstance()->getValue(
            'SELECT is_special FROM '._DB_PREFIX_.'category_special WHERE id_category='.(int)$idCategory
        );
    }
}
