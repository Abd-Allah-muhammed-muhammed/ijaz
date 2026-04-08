<?php

namespace App\UserProviders;

use App\Models\Admin;
use Illuminate\Auth\EloquentUserProvider;

class EloquentAdminProvider extends EloquentUserProvider
{
    public function retrieveById($identifier)
    {
        /**
         * @var Admin $model
         */
        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->select(['*', 'root'])
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }
}
