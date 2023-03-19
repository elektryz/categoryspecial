<?php

namespace Kamil\CategorySpecial\Domain\Reviewer\Command;

use PrestaShop\PrestaShop\Core\Domain\Category\ValueObject\CategoryId;

class ToggleCategorySpecialCommand
{
    private $categoryId;

    public function __construct($categoryId)
    {
        $this->categoryId = new CategoryId($categoryId);
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }
}
