<?php

namespace Kamil\CategorySpecial\Domain\Reviewer\CommandHandler;

use Kamil\CategorySpecial\Domain\Reviewer\Command\ToggleCategorySpecialCommand;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotToggleCategorySpecialStatusException;
use Kamil\CategorySpecial\Entity\CategorySpecial;
use Kamil\CategorySpecial\Repository\CategorySpecialRepository;
use PrestaShopException;

class ToggleCategorySpecialHandler extends AbstractCategorySpecialHandler
{
    private $csRepository;

    public function __construct(CategorySpecialRepository $csRepository)
    {
        $this->csRepository = $csRepository;
    }

    public function handle(ToggleCategorySpecialCommand $command)
    {
        $id = $this->csRepository->findIdByCategory($command->getCategoryId()->getValue());

        $cs = new CategorySpecial($id);

        if (0 >= $cs->id) {
            $cs = $this->createCategorySpecial($command->getCategoryId()->getValue());
        }

        $cs->is_special = (bool) !$cs->is_special;

        try {
            if (false === $cs->update()) {
                throw new CannotToggleCategorySpecialStatusException(
                    sprintf('Failed to change status for reviewer with id "%s"', $cs->id)
                );
            }
        } catch (PrestaShopException $exception) {
            throw new CannotToggleCategorySpecialStatusException(
                'An unexpected error occurred when updating reviewer status'
            );
        }
    }
}
