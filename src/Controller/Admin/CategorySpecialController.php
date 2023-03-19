<?php

namespace Kamil\CategorySpecial\Controller\Admin;

use Kamil\CategorySpecial\Domain\Reviewer\Command\ToggleCategorySpecialCommand;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotCreateCategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotToggleCategorySpecialStatusException;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShop\PrestaShop\Core\Domain\Category\Exception\CategoryException;

class CategorySpecialController extends FrameworkBundleAdminController
{
    public function toggleCategorySpecialAction($categoryId)
    {
        try {
            $this->getCommandBus()->handle(new ToggleCategorySpecialCommand((int)$categoryId));

            $response = [
                'status' => true,
                'message' => $this->trans('The special category status had been updated.',
                    'Modules.Categoryspecial.Categoryspecialcontroller'),
            ];
        } catch (CategorySpecialException $e) {
            $response = [
                'status' => false,
                'message' => $this->getErrorMessageForException($e, $this->getErrorMessageMapping()),
            ];
        }

        return $this->json($response);
    }

    private function getErrorMessageMapping()
    {
        return [
            CategoryException::class => $this->trans(
                'Something bad happened when trying to get category id',
                'Modules.Categoryspecial.Categoryspecialcontroller'
            ),
            CannotCreateCategorySpecialException::class => $this->trans(
                'Failed to create category special',
                'Modules.Categoryspecial.Categoryspecialcontroller'
            ),
            CannotToggleCategorySpecialStatusException::class => $this->trans(
                'An error occurred while updating the status.',
                'Modules.Categoryspecial.Categoryspecialcontroller'
            ),
        ];
    }
}
