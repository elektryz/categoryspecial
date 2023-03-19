<?php

namespace Kamil\CategorySpecial\Domain\Reviewer\QueryHandler;

use Kamil\CategorySpecial\Domain\Reviewer\Query\GetCategorySpecialSettingsForForm;
use Kamil\CategorySpecial\Domain\Reviewer\QueryResult\CategorySpecialSettingsForForm;
use Kamil\CategorySpecial\Repository\CategorySpecialRepository;

class GetCategorySpecialSettingsForFormHandler
{
    private $csRepository;

    public function __construct(CategorySpecialRepository $csRepository)
    {
        $this->csRepository = $csRepository;
    }

    public function handle(GetCategorySpecialSettingsForForm $query)
    {
        if (null === $query->getCategoryId()) {
            return new CategorySpecialSettingsForForm(false);
        }

        return new CategorySpecialSettingsForForm(
            $this->csRepository->getCategorySpecialStatus($query->getCategoryId()->getValue())
        );
    }
}
