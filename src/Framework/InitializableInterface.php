<?php

/**
 * Copyright (c) 2022. PublishPress, All rights reserved.
 */

namespace PublishPress\Future\Framework;

defined('ABSPATH') or die('Direct access not allowed.');

interface InitializableInterface
{
    /**
     * @return void
     */
    public function initialize();
}
