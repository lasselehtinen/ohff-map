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
host('production')
    ->set('hostname', 'kartta.ohff.fi')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/ohff-map')
    ->set('branch', 'master');

host('beta')
    ->set('hostname', 'kartta.ohff.fi')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/ohff-map-beta')
    ->set('branch', 'staging')
    ->set('domain', 'beta.kartta.ohff.fi')
    ->set('public_path', 'public')
    ->set('php_version', '8.4')
    ->set('db_type', 'mysql')
    ->set('db_name', 'ohff_beta')
    ->set('db_user', 'ohff_beta');

// Tasks
task('artisan:responsecache:clear', artisan('responsecache:clear'));
task('artisan:cache:warmup', artisan('cache:warmup'));

// Hooks
after('deploy:failed', 'deploy:unlock');
after('deploy:cleanup', 'artisan:responsecache:clear');
after('deploy:cleanup', 'artisan:cache:warmup');
