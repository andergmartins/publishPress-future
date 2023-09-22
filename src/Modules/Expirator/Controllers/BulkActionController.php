<?php
/**
 * Copyright (c) 2022. PublishPress, All rights reserved.
 */

namespace PublishPress\Future\Modules\Expirator\Controllers;

use PublishPress\Future\Core\HookableInterface;
use PublishPress\Future\Framework\InitializableInterface;
use PublishPress\Future\Modules\Expirator\HooksAbstract;
use PublishPress\Future\Modules\Expirator\Models\PostTypes;
use PublishPress\Pimple\Psr11\Container;

defined('ABSPATH') or die('Direct access not allowed.');

class BulkActionController implements InitializableInterface
{
    public const BULK_ACTION_SYNC = 'sync_scheduler_with_post_meta';

    public const NOTICE_SUCCESS = 'publishpress-future-posts-synced';

    public const NOTICE_NO_POSTS_SELECTED = 'publishpress-future-no-posts-selected';

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
     * @var NoticeFacade
     */
    private $notices;

    /**
     * @param HookableInterface $hooksFacade
     * @param callable $expirablePostModelFactory
     * @param \PublishPress\Future\Framework\WordPress\Facade\SanitizationFacade $sanitization
     * @param \Closure $currentUserModelFactory
     * @param \PublishPress\Future\Framework\WordPress\Facade\RequestFacade $request
     * @param \PublishPress\Future\Framework\WordPress\Facade\NoticeFacade $notices
     */
    public function __construct(
        HookableInterface $hooksFacade,
        $expirablePostModelFactory,
        $sanitization,
        $currentUserModelFactory,
        $request,
        $notices
    ) {
        $this->hooks = $hooksFacade;
        $this->expirablePostModelFactory = $expirablePostModelFactory;
        $this->sanitization = $sanitization;
        $this->currentUserModelFactory = $currentUserModelFactory;
        $this->request = $request;
        $this->notices = $notices;
    }

    public function initialize()
    {
        $this->addHooks();
        $this->registerNotices();
    }

    private function addHooks()
    {
        $this->hooks->addAction(
            HooksAbstract::ACTION_SYNC_SCHEDULER_WITH_POST_META,
            [$this, 'syncSchedulerWithPostMeta']
        );

        $container = \PublishPress\Future\Core\DI\Container::getInstance();
        $postTypes = new PostTypes($container);
        $activatedPostTypes = $postTypes->getActivatedPostTypes();

        foreach ($activatedPostTypes as $postType) {
            $this->hooks->addAction(
                'bulk_actions-edit-' . $postType,
                [$this, 'filterBulkActions']
            );
        }
    }

    private function registerNotices()
    {
        $this->notices->registerErrorNotice(
            self::NOTICE_NO_POSTS_SELECTED,
            __('No posts selected. Unable to sync Future Actions.', 'post-expirator')
        );

        $this->notices->registerSuccessNotice(
            self::NOTICE_SUCCESS,
            __('Future Actions successfully synced with Post Metadata.', 'post-expirator')
        );
    }

    public function filterBulkActions($actions)
    {
        $actions[self::BULK_ACTION_SYNC] = __('Update Future Actions from Post Metadata', 'post-expirator');

        return $actions;
    }

    private function getSelectedPostsFromRequest(): array
    {
        return array_filter(
            $_REQUEST['post'] ?? [],
            'intval'
        );
    }

    public function syncSchedulerWithPostMeta()
    {
        if ( ! isset($_REQUEST['action']) || $_REQUEST['action'] !== self::BULK_ACTION_SYNC) {
            return;
        }

        $postIds = $this->getSelectedPostsFromRequest();

        if (empty($postIds)) {
            $this->notices->redirectShowingNotice(self::NOTICE_NO_POSTS_SELECTED);
        }

        $expirablePostModelFactory = $this->expirablePostModelFactory;
        foreach ($postIds as $postId) {
            $postModel = $expirablePostModelFactory($postId);

            $postModel->syncScheduleWithPostMeta();
        }

        $this->notices->redirectShowingNotice(self::NOTICE_SUCCESS);
    }
}
