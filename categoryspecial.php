<?php

use Kamil\CategorySpecial\Domain\Reviewer\Command\UpdateCategorySpecialCommand;
use Kamil\CategorySpecial\Domain\Reviewer\Query\GetCategorySpecialSettingsForForm;
// Kamil\CategorySpecial\Domain\Reviewer\QueryResult\CategorySpecialSettingsForForm;
//use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
//use PrestaShop\PrestaShop\Core\Search\Filters\CustomerFilters;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotCreateCategorySpecialException;
use Kamil\CategorySpecial\Domain\Reviewer\Exception\CannotToggleCategorySpecialStatusException;

class Categoryspecial extends Module
{
    public function __construct()
    {
        $this->name = 'categoryspecial';
        $this->version = '1.0.0';
        $this->author = 'Kamil';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->trans(
            'Category special',
            [],
            'Modules.Categoryspecial.Admin'
        );

        $this->description =
            $this->trans(
                'Category special',
                [],
                'Modules.Categoryspecial.Admin'
            );

        $this->ps_versions_compliancy = [
            'min' => '1.7.6.0',
            'max' => '8.99.99',
        ];
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionCategoryGridDefinitionModifier') &&
            $this->registerHook('actionCategoryGridQueryBuilderModifier') &&
            $this->registerHook('actionCategoryFormBuilderModifier') &&
            $this->registerHook('actionAfterCreateCategoryFormHandler') &&
            $this->registerHook('actionAfterUpdateCategoryFormHandler') &&
            $this->installTables();
    }

    public function hookActionCategoryGridDefinitionModifier(array $params)
    {
        $definition = $params['definition'];

        $definition
            ->getColumns()
            ->addAfter(
                'active',
                (new ToggleColumn('is_special'))
                    ->setName($this->trans('Is special category?', [], 'Modules.Categoryspecial.Admin'))
                    ->setOptions([
                        'field' => 'is_special',
                        'primary_field' => 'id_category',
                        'route' => 'categoryspecial_toggle_category_special',
                        'route_param_name' => 'categoryId',
                    ])
            );

        $definition->getFilters()->add(
            (new Filter('is_special', YesAndNoChoiceType::class))
                ->setAssociatedColumn('is_special')
        );
    }

    public function hookActionCategoryGridQueryBuilderModifier(array $params)
    {
        $searchQueryBuilder = $params['search_query_builder'];
        $searchCriteria = $params['search_criteria'];

        $searchQueryBuilder->addSelect(
            'IF(css.`is_special` IS NULL,0,css.`is_special`) AS `is_special`'
        );

        $searchQueryBuilder->leftJoin(
            'c',
            '`' . pSQL(_DB_PREFIX_) . 'category_special`',
            'css',
            'css.`id_category` = c.`id_category`'
        );

        if ('is_special' === $searchCriteria->getOrderBy()) {
            $searchQueryBuilder->orderBy('css.`is_special`', $searchCriteria->getOrderWay());
        }

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('is_special' === $filterName) {
                $searchQueryBuilder->andWhere('css.`is_special` = :is_special');
                $searchQueryBuilder->setParameter('is_special', $filterValue);

                if (!$filterValue) {
                    $searchQueryBuilder->orWhere('css.`is_special` IS NULL');
                }
            }
        }
    }

    public function hookActionCategoryFormBuilderModifier(array $params)
    {
        $formBuilder = $params['form_builder'];
        $formBuilder->add('is_special', SwitchType::class, [
            'label' => $this->trans('Is special category?', [], 'Modules.Categoryspecial.Admin'),
            'required' => false,
        ]);

        $queryBus = $this->get('prestashop.core.query_bus');

        $settings = $queryBus->handle(new GetCategorySpecialSettingsForForm($params['id']));

        $params['data']['is_special'] = $settings->isSpecial();

        $formBuilder->setData($params['data']);
    }

    public function hookActionAfterUpdateCategoryFormHandler(array $params)
    {
        $this->updateCategorySpecialStatus($params);
    }

    public function hookActionAfterCreateCategoryFormHandler(array $params)
    {
        $this->updateCategorySpecialStatus($params);
    }

    private function updateCategorySpecialStatus(array $params)
    {
        $categoryId = $params['id'];
        $categoryData = $params['form_data'];
        $isSpecial = (bool)$categoryData['is_special'];

        $commandBus = $this->get('prestashop.core.command_bus');

        try {
            $commandBus->handle(new UpdateCategorySpecialCommand(
                $categoryId,
                $isSpecial
            ));
        } catch (CategorySpecialException $exception) {
            $this->handleException($exception);
        }
    }

    private function installTables()
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS `' . pSQL(_DB_PREFIX_) . 'category_special` (
                `id_reviewer` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_category` INT(10) UNSIGNED NOT NULL,
                `is_special` TINYINT(1) NOT NULL,
                PRIMARY KEY (`id_reviewer`)
            ) ENGINE=' . pSQL(_MYSQL_ENGINE_) . ' COLLATE=utf8_unicode_ci;
        ';

        return Db::getInstance()->execute($sql);
    }

    private function handleException(CategorySpecialException $exception)
    {
        $exceptionDictionary = [
            CannotCreateCategorySpecialException::class => $this->trans(
                'Failed to create a record for category',
                [],
                'Modules.Categoryspecial.Admin'
            ),
            CannotToggleCategorySpecialStatusException::class => $this->trans(
                'Failed to toggle is special category status',
                [],
                'Modules.Categoryspecial.Admin'
            ),
        ];

        $exceptionType = get_class($exception);

        if (isset($exceptionDictionary[$exceptionType])) {
            $message = $exceptionDictionary[$exceptionType];
        } else {
            $message = $this->trans(
                'An unexpected error occurred. [%type% code %code%]',
                [
                    '%type%' => $exceptionType,
                    '%code%' => $exception->getCode(),
                ],
                'Admin.Notifications.Error'
            );
        }

        throw new \PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException($message);
    }
}
