<?php

namespace App\Http\Requests;

class UpdatePassThroughPackageRequest extends StorePassThroughPackageRequest
{
    protected $errorBag = 'passThroughPackageUpdate';
}
