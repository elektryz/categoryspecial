<?php

namespace Kamil\CategorySpecial\Domain\Reviewer\CommandHandler;

use Kamil\CategorySpecial\Domain\Reviewer\Command\UpdateCategorySpecialCommand;
use Kamil\CategorySpecial\Entity\CategorySpecial;
use Kamil\CategorySpecial\Repository\CategorySpecialRepository;

class UpdateCategorySpecialHandler extends AbstractCategorySpecialHandler
{
    private $csRepository;

    public function __construct(CategorySpecialRepository $csRepository)
    {
        $this->csRepository = $csRepository;
    }

    public function handle(UpdateCategorySpecialCommand $command)
    {
        $id = $this->csRepository->findIdByCategory($command->getCategoryId()->getValue());
        $cs = new CategorySpecial($id);

        if (0 >= $cs->id) {
            $cs = $this->createCategorySpecial($command->getCategoryId()->getValue());
        }

        $cs->is_special = $command->isSpecial();

        try {
            if (false === $cs->update()) {
                throw new \Exception(
                    sprintf('Failed to change status for reviewer with id "%s"', $cs->id)
                );
            }
        } catch (\Exception $exception) {
            throw new \Exception(
                'An unexpected error occurred when updating reviewer status'
            );
        }
    }
}
