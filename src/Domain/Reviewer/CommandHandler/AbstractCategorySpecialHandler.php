<?php

namespace Kamil\CategorySpecial\Domain\Reviewer\CommandHandler;

use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotCreateCategorySpecialException;
use Kamil\CategorySpecial\Entity\CategorySpecial;

class AbstractCategorySpecialHandler
{
    protected function createCategorySpecial($categoryId)
    {
        try {
            $cs = new CategorySpecial();
            $cs->id_category = $categoryId;
            $cs->is_special = 0;

            if (false === $cs->save()) {
                throw new CannotCreateCategorySpecialException(
                    sprintf(
                        'An error occurred when creating special category category id "%s"',
                        $categoryId
                    )
                );
            }
        } catch (PrestaShopException $exception) {
            throw new CannotCreateCategorySpecialException(
                sprintf(
                    'An unexpected error occurred when creating special category with id "%s"',
                    $categoryId
                ),
                0,
                $exception
            );
        }

        return $cs;
    }
}
