<?php
/**
 * Copyright (c) 2022. PublishPress, All rights reserved.
 */

namespace PublishPress\Future\Modules\Expirator\Controllers;

use PostExpirator_Display;
use PostExpirator_Facade;
use PostExpirator_Util;
use PublishPress\Future\Core\DI\Container;
use PublishPress\Future\Core\DI\ServicesAbstract;
use PublishPress\Future\Core\HookableInterface;
use PublishPress\Future\Core\HooksAbstract as CoreHooksAbstract;
use PublishPress\Future\Framework\InitializableInterface;
use PublishPress\Future\Modules\Expirator\HooksAbstract as ExpiratorHooks;
use PublishPress\Future\Modules\Expirator\Schemas\ActionArgsSchema;

defined('ABSPATH') or die('Direct access not allowed.');

class PostListController implements InitializableInterface
{
    /**
     * @var HookableInterface
     */
    private $hooks;

    /**
     * @var \Closure
     */
    private $expirablePostModelFactory;

    /**
     * @var \PublishPress\Future\Framework\WordPress\Facade\SanitizationFacade
     */
    private $sanitization;

    /**
     * @var \Closure
     */
    private $currentUserModelFactory;

    /**
     * @var \PublishPress\Future\Framework\WordPress\Facade\RequestFacade
     */
    private $request;

    /**
     * @param HookableInterface $hooksFacade
     * @param callable $expirablePostModelFactory
     * @param \PublishPress\Future\Framework\WordPress\Facade\SanitizationFacade $sanitization
     * @param \Closure $currentUserModelFactory
     * @param \PublishPress\Future\Framework\WordPress\Facade\RequestFacade $request
     */
    public function __construct(
        HookableInterface $hooksFacade,
        $expirablePostModelFactory,
        $sanitization,
        $currentUserModelFactory,
        $request
    ) {
        $this->hooks = $hooksFacade;
        $this->expirablePostModelFactory = $expirablePostModelFactory;
        $this->sanitization = $sanitization;
        $this->currentUserModelFactory = $currentUserModelFactory;
        $this->request = $request;
    }

    public function initialize()
    {
        $this->hooks->addFilter(ExpiratorHooks::FILTER_MANAGE_POSTS_COLUMNS, [$this, 'addColumns'], 10, 2);
        $this->hooks->addFilter(ExpiratorHooks::FILTER_MANAGE_PAGES_COLUMNS, [$this, 'addColumns'], 11, 1);
        $this->hooks->addFilter(ExpiratorHooks::FILTER_POSTS_JOIN, [$this, 'joinExpirationDate'], 10, 2);

        $this->hooks->addAction(ExpiratorHooks::ACTION_MANAGE_POSTS_CUSTOM_COLUMN, [$this, 'managePostsCustomColumn']);
        $this->hooks->addAction(ExpiratorHooks::ACTION_MANAGE_POSTS_CUSTOM_COLUMN, [$this, 'managePostsCustomColumn']);
        $this->hooks->addAction(ExpiratorHooks::ACTION_ADMIN_INIT, [$this, 'manageSortableColumns'], 100);
        $this->hooks->addAction(ExpiratorHooks::ACTION_POSTS_ORDER_BY, [$this, 'orderByExpirationDate'], 10, 2);
    }

    /**
     * @param array $columns
     * @return array
     */
    public function addColumns($columns, $postType = 'page')
    {
        $container = Container::getInstance();
        $settingsFacade = $container->get(ServicesAbstract::SETTINGS);

        $defaults = $settingsFacade->getPostTypeDefaults($postType);

        // If settings are not configured, show the metabox by default only for posts and pages
        if ((! isset($defaults['activeMetaBox']) && in_array($postType, array(
                    'post',
                    'page'
                ), true)) || (is_array(
                    $defaults
                ) && in_array((string)$defaults['activeMetaBox'], ['active', '1']))) {
            $columns['expirationdate'] = __('Future Action', 'post-expirator');
        }

        return $columns;
    }

    /**
     * @param string $column
     * @param int $postId
     */
    public function managePostsCustomColumn($column)
    {
        if ($column !== 'expirationdate') {
            return;
        }

        $this->renderColumn();
    }

    /**
     * @param \PublishPress\Future\Modules\Expirator\Models\ExpirablePostModel $expirablePostModel
     * @param string $column
     */
    private function renderColumn()
    {
        global $post;

        // get the attributes that quick edit functionality requires
        // and save it as a JSON encoded HTML attribute
        $container = Container::getInstance();
        $factory = $container->get(ServicesAbstract::EXPIRABLE_POST_MODEL_FACTORY);
        $postModel = $factory($post->ID);
        $settings = $container->get(ServicesAbstract::SETTINGS);

        PostExpirator_Display::getInstance()->render_template('expire-column', [
            'id' => $post->ID,
            'post_type' => $post->post_type,
            'attributes' => $postModel->getExpirationDataAsArray(),
            'column_style' => $settings->getColumnStyle(),
        ]);
    }

    public function manageSortableColumns()
    {
        $post_types = postexpirator_get_post_types();
        foreach ($post_types as $post_type) {
            add_filter('manage_edit-' . $post_type . '_sortable_columns', [$this, 'sortableColumn']);
        }
    }

    public function sortableColumn($columns)
    {
        $columns['expirationdate'] = 'expirationdate';

        return $columns;
    }

    public function orderByExpirationDate($orderby, $query)
    {
        if (! is_admin()  || ! $query->is_main_query()) {
            return $orderby;
        }

        if ('expirationdate' === $query->get('orderby')) {
            $order = strtoupper($query->get('order'));

            if (! in_array($order, [
                'ASC',
                'DESC'
            ], true)) {
                $order = 'ASC';
            }

            $orderby = ActionArgsSchema::getTableName() . '.scheduled_date ' . $order;
        }

        return $orderby;
    }

    /**
     * @param string $join
     * @param \WP_Query $query
     * @return string
     */
    public function joinExpirationDate($join, $query)
    {
        global $wpdb;

        if (! is_admin() || ! $query->is_main_query()) {
            return $join;
        }

        $actionArgsSchemaTableName = ActionArgsSchema::getTableName();

        if ('expirationdate' === $query->get('orderby')) {
            $join .= " LEFT JOIN {$actionArgsSchemaTableName} ON {$actionArgsSchemaTableName}.post_id = {$wpdb->posts}.ID AND {$actionArgsSchemaTableName}.enabled = '1'";
        }

        return $join;
    }
}
