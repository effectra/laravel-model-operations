<?php

return [
    /**
     * Whether to use caching in fetch operations.
     */
    'use_cache'=>true,
   
    /**
     * Cache TTL settings for fetch operations in seconds.
     */
    'fetch_ttl'=>[
        'read'=>60,
        'read_one'=>60,
        'read_many'=>60,
    ],
     /**
     * Whether to use soft deletes (trash) in models.
     */
    'use_trash' => true,
    /**
     * Time-to-live for trashed models in seconds.
     */
    'trash_ttl' => 3600*24, // Time-to-live for trashed models in seconds (24 hours).
    /**
     * Whether to save old model data in cache before updates.
     */
    'save_old_data_when_update' => true,
];