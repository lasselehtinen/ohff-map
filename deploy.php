<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config
set('repository', 'git@github.com:lasselehtinen/ohff-map.git');
set('keep_releases', 1);

// Shared files/dirs
add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts
host('kartta.ohff.fi')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/ohff-map');

// Tasks
task('artisan:responsecache:clear', artisan('responsecache:clear'));
task('artisan:cache:warmup', artisan('cache:warmup'));

// Hooks
after('deploy:failed', 'deploy:unlock');
after('deploy:cleanup', 'artisan:responsecache:clear');
after('deploy:cleanup', 'artisan:cache:warmup');
