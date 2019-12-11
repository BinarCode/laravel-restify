<?php

namespace Binaryk\LaravelRestify\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use SerializesModels;

    /**
     * @var Model
     */
    public $authenticated;

    /**
     * @var Request
     */
    public $request;

    /**
     * @param Model $authenticated
     * @param Request $request
     */
    public function __construct(Model $authenticated, Request $request)
    {
        $this->authenticated = $authenticated;
        $this->request = $request;
    }
}
