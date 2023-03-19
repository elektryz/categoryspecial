<?php

namespace Kamil\CategorySpecial\Domain\Reviewer\Query;

use PrestaShop\PrestaShop\Core\Domain\Category\ValueObject\CategoryId;

class GetCategorySpecialSettingsForForm
{
    private $categoryId;

    public function __construct($categoryId)
    {
        $this->categoryId = null !== $categoryId ? new CategoryId((int) $categoryId) : null;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }
}
