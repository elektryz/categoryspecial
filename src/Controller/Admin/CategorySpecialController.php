<?php

namespace Kamil\CategorySpecial\Controller\Admin;

use Kamil\CategorySpecial\Domain\Reviewer\Command\ToggleCategorySpecialCommand;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotCreateCategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotToggleCategorySpecialStatusException;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShop\PrestaShop\Core\Domain\Category\Exception\CategoryException;
use Configuration;

class CategorySpecialController extends FrameworkBundleAdminController
{
    public function toggleCategorySpecialAction($categoryId)
    {
        try {
            $this->getCommandBus()->handle(new ToggleCategorySpecialCommand((int) $categoryId));

            $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
        } catch (CategorySpecialException $e) {
            $this->addFlash('error', $this->getErrorMessageForException($e, $this->getErrorMessageMapping()));
        }

        return $this->redirectToRoute('admin_categories_index');
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
