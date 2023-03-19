<?php

namespace Kamil\CategorySpecial\Domain\Reviewer\QueryResult;

class CategorySpecialSettingsForForm
{
    private $isSpecial;

    public function __construct($isSpecial)
    {
        $this->isSpecial = $isSpecial;
    }

    public function isSpecial()
    {
        return $this->isSpecial;
    }
}
