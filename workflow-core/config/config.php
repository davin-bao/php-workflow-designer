<?php
return [
    'log_path' => __DIR__ . '/../logs/',
    'flow_file_path' => __DIR__ . '/../flows/',
    'activities_file_path' => [
        ['path' => '../../workflow-core/src/Activities', 'namespace' => 'DavinBao\WorkflowCore\Activities\\']
    ],

    'db_path' => ''
];
