<?php

namespace PublishPressFuture\Modules\Expirator\ExpirationActions;

use PublishPressFuture\Modules\Expirator\ExpirationActionsAbstract;
use PublishPressFuture\Modules\Expirator\Interfaces\ExpirationActionInterface;
use PublishPressFuture\Modules\Expirator\Models\ExpirablePostModel;

class PostStatusToTrash implements ExpirationActionInterface
{
    const SERVICE_NAME = 'expiration.actions.post_status_to_trash';

    /**
     * @var ExpirablePostModel
     */
    private $postModel;

    /**
     * @var array
     */
    private $log = [];

    /**
     * @var string
     */
    private $oldPostStatus;

    /**
     * @param ExpirablePostModel $postModel
     */
    public function __construct($postModel)
    {
        $this->postModel = $postModel;
    }

    public function __toString()
    {
        return ExpirationActionsAbstract::POST_STATUS_TO_TRASH;
    }

    /**
     * @inheritDoc
     */
    public function getNotificationText()
    {
        if (empty($this->log) || ! $this->log['success']) {
            return __('Status didn\'t change.', 'post-expirator');
        }

        return sprintf(
            __('Status has been successfully changed from "%s" to "%s".', 'post-expirator'),
            $this->oldPostStatus,
            'trash'
        );
    }

    /**
     * @inheritDoc
     * @throws \PublishPressFuture\Framework\WordPress\Exceptions\NonexistentPostException
     */
    public function execute()
    {
        $this->oldPostStatus = $this->postModel->getPostStatus();

        $result = $this->postModel->setPostStatus('trash');

        $this->log['success'] = $result;

        return $result;
    }

    public static function getLabel(): string
    {
        return __('Change status to trash', 'post-expirator');
    }
}
