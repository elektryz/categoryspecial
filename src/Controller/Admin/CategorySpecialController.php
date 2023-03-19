<?php

namespace Kamil\CategorySpecial\Controller\Admin;

use Kamil\CategorySpecial\Domain\Reviewer\Command\ToggleCategorySpecialCommand;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotCreateCategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotToggleCategorySpecialStatusException;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShop\PrestaShop\Core\Domain\Category\Exception\CategoryException;
use Module;

class CategorySpecialController extends FrameworkBundleAdminController
{
    public function toggleCategorySpecialAction($categoryId)
    {
        $mod = Module::getInstanceByName('categoryspecial');

        try {
            $this->getCommandBus()->handle(new ToggleCategorySpecialCommand((int)$categoryId));
            $response = [
                'status' => true,
                'message' => $mod->getMessage('The special category status had been updated.'),
            ];
        } catch (CategorySpecialException $e) {
            $response = [
                'status' => false,
                'message' => $this->getErrorMessageForException($e, $this->getErrorMessageMapping($mod)),
            ];
        }

        return $this->json($response);
    }

    private function getErrorMessageMapping($mod)
    {
        return [
            CategoryException::class => $mod->getMessage('Something bad happened when trying to get category id.'),
            CannotCreateCategorySpecialException::class => $mod->getMessage('Failed to create special category.'),
            CannotToggleCategorySpecialStatusException::class => $mod->getMessage('An error occurred while updating the status.'),
        ];
    }
}
