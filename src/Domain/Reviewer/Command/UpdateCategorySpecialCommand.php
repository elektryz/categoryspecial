<?php

namespace Kamil\CategorySpecial\Domain\Reviewer\Command;

use PrestaShop\PrestaShop\Core\Domain\Category\ValueObject\CategoryId;

class UpdateCategorySpecialCommand
{
    private $categoryId;
    private $isSpecial;

    public function __construct($categoryId, $isSpecial)
    {
        $this->categoryId = new CategoryId($categoryId);
        $this->isSpecial = $isSpecial;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function isSpecial()
    {
        return $this->isSpecial;
    }
}
