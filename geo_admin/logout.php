<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

logout_user();
flash('success', 'You have been logged out.');
redirect('/geo_admin/?login=1');

